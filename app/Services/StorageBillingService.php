<?php

namespace App\Services;

use App\Models\Client;
use App\Models\CompanySetting;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PricingRule;
use App\Models\StorageEntry;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class StorageBillingService
{
    public function generateForPeriod(
        Client $client,
        Carbon $start,
        Carbon $end,
        string $chargeUnit,
        float $unitPrice,
        float $taxRate,
        int $dueDays,
        string $frequencyTag = 'manual'
    ): ?Invoice {
        $start = $start->copy()->startOfDay();
        $end = $end->copy()->endOfDay();

        if ($unitPrice <= 0) {
            return null;
        }

        $quantity = $this->quantityForPeriod($client, $start, $end, $chargeUnit);

        if ($quantity <= 0) {
            return null;
        }

        $tag = sprintf(
            'AUTO-STORAGE|client:%d|from:%s|to:%s|unit:%s|freq:%s',
            $client->id,
            $start->toDateString(),
            $end->toDateString(),
            $chargeUnit,
            $frequencyTag
        );

        $existing = Invoice::where('client_id', $client->id)
            ->where('invoice_type', 'storage')
            ->where('notes', 'like', '%' . $tag . '%')
            ->first();

        if ($existing) {
            return $existing;
        }

        $lineTotal = $quantity * $unitPrice;
        $tax = $lineTotal * ($taxRate / 100);
        $total = $lineTotal + $tax;

        $unitLabel = $chargeUnit === 'kg' ? 'kg' : 'carton(s)';
        $description = sprintf(
            'Stockage chambre froide %s du %s au %s (%s)',
            $client->name,
            $start->format('d/m/Y'),
            $end->format('d/m/Y'),
            $unitLabel
        );

        return DB::transaction(function () use ($client, $start, $dueDays, $taxRate, $description, $tag, $lineTotal, $tax, $total, $quantity, $unitPrice) {
            $invoice = Invoice::create([
                'client_id' => $client->id,
                'processing_order_id' => null,
                'invoice_type' => 'storage',
                'invoice_number' => $this->nextInvoiceNumber(),
                'invoice_date' => $start->toDateString(),
                'due_date' => $start->copy()->addDays(max(0, $dueDays))->toDateString(),
                'tax_rate' => $taxRate,
                'notes' => $description . ' | ' . $tag,
                'subtotal' => $lineTotal,
                'tax' => $tax,
                'total' => $total,
                'status' => 'draft',
            ]);

            InvoiceItem::create([
                'invoice_id' => $invoice->id,
                'service_type' => 'storage',
                'reference_type' => 'client',
                'reference_id' => $client->id,
                'description' => $description,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'total' => $lineTotal,
            ]);

            return $invoice;
        });
    }

    /**
     * Quantite facturable sur une periode : stock present au debut de la periode
     * + entrees pendant la periode (toute marchandise presente pendant la periode
     * est facturee pour la periode entiere, les sorties comptent pour la suivante).
     */
    public function quantityForPeriod(Client $client, Carbon $start, Carbon $end, string $chargeUnit): float
    {
        $column = $chargeUnit === 'kg' ? 'weight_kg' : 'cartons_count';
        $base = StorageEntry::query()
            ->whereIn('type', ['in', 'out'])
            ->whereHas('stockBatch', fn ($q) => $q->where('client_id', $client->id));

        $balanceAtStart = (float) (clone $base)
            ->where('entry_date', '<', $start->copy()->startOfDay())
            ->selectRaw("SUM(CASE WHEN type = 'in' THEN {$column} ELSE -{$column} END) as balance")
            ->value('balance');

        $entriesDuring = (float) (clone $base)
            ->where('type', 'in')
            ->whereBetween('entry_date', [$start->copy()->startOfDay(), $end->copy()->endOfDay()])
            ->sum($column);

        return max(0, $balanceAtStart) + $entriesDuring;
    }

    /**
     * Frais de froid cumules semaine par semaine pour un client.
     * Semaine facturee : prix de la facture. Semaine non facturee : prix actuel de la regle.
     */
    public function weeklyBreakdown(Client $client, ?PricingRule $rule, ?Carbon $until = null): array
    {
        $chargeUnit = $rule->charge_unit ?? 'kg';
        $column = $chargeUnit === 'kg' ? 'weight_kg' : 'cartons_count';
        $currentPrice = (float) ($rule->price ?? 0);
        $weekStartDay = (int) ($rule->week_start_day ?? Carbon::MONDAY);

        $movements = StorageEntry::query()
            ->whereIn('type', ['in', 'out'])
            ->whereHas('stockBatch', fn ($q) => $q->where('client_id', $client->id))
            ->orderBy('entry_date')
            ->get(['type', 'cartons_count', 'weight_kg', 'entry_date']);

        $empty = [
            'rows' => [],
            'unit' => $chargeUnit,
            'current_price' => $currentPrice,
            'total' => 0.0,
            'invoiced' => 0.0,
            'unbilled' => 0.0,
            'paid' => 0.0,
            'balance' => 0.0,
            'billable_weeks' => 0,
        ];

        if ($movements->isEmpty()) {
            return $empty;
        }

        $storageInvoices = Invoice::with('items')
            ->where('client_id', $client->id)
            ->where('invoice_type', 'storage')
            ->where('status', '!=', 'cancelled')
            ->get();

        // Factures multi-semaines : une ligne par semaine (reference_id = date de debut Ymd)
        $invoicedWeeks = [];
        foreach ($storageInvoices as $invoice) {
            foreach ($invoice->items->where('reference_type', 'storage_week') as $item) {
                $date = Carbon::createFromFormat('Ymd', (string) $item->reference_id)->toDateString();
                $invoicedWeeks[$date] = ['invoice' => $invoice, 'unit_price' => (float) $item->unit_price];
            }
        }

        // Factures d'une periode (ancien format) : plage from/to dans les notes
        $invoicedRanges = $storageInvoices
            ->map(function (Invoice $invoice) {
                if (! preg_match('/from:(\d{4}-\d{2}-\d{2})\|to:(\d{4}-\d{2}-\d{2})/', (string) $invoice->notes, $m)) {
                    return null;
                }

                return [
                    'from' => $m[1],
                    'to' => $m[2],
                    'invoice' => $invoice,
                    'unit_price' => (float) ($invoice->items->first()->unit_price ?? 0),
                ];
            })
            ->filter()
            ->values();

        $until = ($until ?? now())->copy()->endOfDay();
        $weekStart = $movements->first()->entry_date->copy()->startOfWeek($weekStartDay)->startOfDay();
        $balance = 0.0;
        $cursor = 0;
        $count = $movements->count();
        $rows = [];
        $cumulative = 0.0;

        while ($weekStart->lte($until)) {
            $weekEnd = $weekStart->copy()->addDays(6)->endOfDay();
            $entries = 0.0;
            $exits = 0.0;

            while ($cursor < $count && $movements[$cursor]->entry_date->lte($weekEnd)) {
                $value = (float) $movements[$cursor]->{$column};
                if ($movements[$cursor]->type === 'in') {
                    $entries += $value;
                } else {
                    $exits += $value;
                }
                $cursor++;
            }

            $opening = max(0, $balance);
            $quantity = $opening + $entries;
            $balance = $balance + $entries - $exits;

            if ($quantity > 0) {
                $weekDate = $weekStart->toDateString();
                $match = $invoicedWeeks[$weekDate]
                    ?? $invoicedRanges->first(fn ($r) => $weekDate >= $r['from'] && $weekDate <= $r['to']);
                $price = $match ? $match['unit_price'] : $currentPrice;
                $amount = $quantity * $price;
                $cumulative += $amount;

                $rows[] = [
                    'week_start' => $weekStart->copy(),
                    'week_end' => $weekEnd->copy(),
                    'opening' => $opening,
                    'entries' => $entries,
                    'exits' => $exits,
                    'closing' => max(0, $balance),
                    'quantity' => $quantity,
                    'price' => $price,
                    'amount' => $amount,
                    'cumulative' => $cumulative,
                    'invoice' => $match['invoice'] ?? null,
                    'is_current' => now()->between($weekStart, $weekEnd),
                ];
            }

            $weekStart = $weekStart->copy()->addWeek();
        }

        $rows = collect($rows);
        $unbilledRows = $rows->reject(fn ($r) => $r['invoice']);
        $invoiced = (float) $storageInvoices->sum('total');
        $paid = (float) $storageInvoices->sum('paid_amount');

        return array_merge($empty, [
            'rows' => $rows->reverse()->values()->all(),
            'total' => (float) $rows->sum('amount'),
            'invoiced' => $invoiced,
            'paid' => $paid,
            'balance' => max(0, $invoiced - $paid),
            'unbilled' => (float) $unbilledRows->sum('amount'),
            'billable_weeks' => $unbilledRows->reject(fn ($r) => $r['is_current'])->count(),
        ]);
    }

    /**
     * Facture froid variable : une ligne par semaine terminee non encore facturee,
     * la quantite de chaque ligne suit le stock reel de la semaine.
     */
    public function generateUnbilledWeeks(Client $client, PricingRule $rule, float $taxRate, int $dueDays): ?Invoice
    {
        $weeks = collect($this->weeklyBreakdown($client, $rule)['rows'])
            ->reject(fn ($r) => $r['invoice'] || $r['is_current'] || $r['amount'] <= 0)
            ->sortBy(fn ($r) => $r['week_start']->timestamp)
            ->values();

        if ($weeks->isEmpty()) {
            return null;
        }

        $unitLabel = ($rule->charge_unit ?? 'kg') === 'kg' ? 'kg' : 'carton(s)';
        $from = $weeks->first()['week_start'];
        $to = $weeks->last()['week_end'];
        $subtotal = round($weeks->sum('amount'), 2);
        $tax = round($subtotal * $taxRate / 100, 2);

        return DB::transaction(function () use ($client, $weeks, $unitLabel, $from, $to, $subtotal, $tax, $taxRate, $dueDays) {
            $invoice = Invoice::create([
                'client_id' => $client->id,
                'invoice_type' => 'storage',
                'invoice_number' => $this->nextInvoiceNumber(),
                'invoice_date' => now()->toDateString(),
                'due_date' => now()->addDays(max(0, $dueDays))->toDateString(),
                'tax_rate' => $taxRate,
                'notes' => sprintf('Frais de froid %s du %s au %s (%d semaine(s))', $client->name, $from->format('d/m/Y'), $to->format('d/m/Y'), $weeks->count()),
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $subtotal + $tax,
                'status' => 'draft',
            ]);

            foreach ($weeks as $week) {
                InvoiceItem::create([
                    'invoice_id' => $invoice->id,
                    'service_type' => 'storage',
                    'reference_type' => 'storage_week',
                    'reference_id' => (int) $week['week_start']->format('Ymd'),
                    'description' => sprintf('Froid semaine du %s au %s — %s %s en stock', $week['week_start']->format('d/m'), $week['week_end']->format('d/m/Y'), rtrim(rtrim(number_format($week['quantity'], 2, ',', ' '), '0'), ','), $unitLabel),
                    'quantity' => $week['quantity'],
                    'unit_price' => $week['price'],
                    'total' => round($week['amount'], 2),
                ]);
            }

            return $invoice;
        });
    }

    public function generateAutomatic(Carbon $today): array
    {
        $generated = 0;
        $skipped = 0;

        $rules = PricingRule::query()
            ->with('client')
            ->where('service_type', 'storage')
            ->where('active', true)
            ->where('auto_generate', true)
            ->whereNotNull('client_id')
            ->get();

        foreach ($rules as $rule) {
            if (! $rule->client || ! $rule->client->active) {
                $skipped++;
                continue;
            }

            [$start, $end] = $this->resolvePeriod($today, $rule);
            if (! $start || ! $end) {
                $skipped++;
                continue;
            }

            $invoice = $this->generateForPeriod(
                $rule->client,
                $start,
                $end,
                $rule->charge_unit ?? 'kg',
                (float) $rule->price,
                CompanySetting::taxRate(),
                (int) ($rule->due_days ?? 7),
                (string) ($rule->billing_frequency ?? 'week')
            );

            if ($invoice) {
                $generated++;
            } else {
                $skipped++;
            }
        }

        return ['generated' => $generated, 'skipped' => $skipped, 'rules' => $rules->count()];
    }

    private function resolvePeriod(Carbon $today, PricingRule $rule): array
    {
        $frequency = $rule->billing_frequency ?? 'week';

        if ($frequency === 'day') {
            $start = $today->copy()->subDay()->startOfDay();
            $end = $today->copy()->subDay()->endOfDay();
            return [$start, $end];
        }

        if ($frequency === 'week') {
            $weekStart = (int) ($rule->week_start_day ?? 1);
            if ((int) $today->dayOfWeek !== $weekStart) {
                return [null, null];
            }

            $start = $today->copy()->subWeek()->startOfDay();
            $end = $today->copy()->subDay()->endOfDay();
            return [$start, $end];
        }

        $monthDay = max(1, min(28, (int) ($rule->month_day ?? 1)));
        if ((int) $today->day !== $monthDay) {
            return [null, null];
        }

        $start = $today->copy()->subMonthNoOverflow()->day($monthDay)->startOfDay();
        $end = $today->copy()->subDay()->endOfDay();

        return [$start, $end];
    }

    private function nextInvoiceNumber(): string
    {
        $prefix = CompanySetting::get('invoice_prefix', 'FAC');
        $num = str_pad(Invoice::count() + 1, 5, '0', STR_PAD_LEFT);

        return $prefix . '-' . now()->format('Y') . '-' . $num;
    }
}
