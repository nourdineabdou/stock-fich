<?php

namespace App\Services;

use App\Models\Client;
use App\Models\ClientStockBatch;
use App\Models\CompanySetting;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\PricingRule;
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

        $batches = ClientStockBatch::where('client_id', $client->id)
            ->whereDate('entry_date', '<=', $end->toDateString())
            ->where('status', 'available')
            ->get();

        if ($batches->isEmpty() || $unitPrice <= 0) {
            return null;
        }

        $quantity = $chargeUnit === 'kg'
            ? (float) $batches->sum('available_weight')
            : (float) $batches->sum('available_cartons');

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
                (float) CompanySetting::get('tax_rate', 20),
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
