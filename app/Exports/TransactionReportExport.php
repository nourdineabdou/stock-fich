<?php

namespace App\Exports;

use App\Models\StorageEntry;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class TransactionReportExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    private Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function array(): array
    {
        $rows = StorageEntry::query()
            ->with(['stockBatch.client', 'stockBatch.fishType', 'stockBatch.cartonType', 'stockBatch.packagingOrder', 'coldRoom'])
            ->when($this->request->client_id, function ($q) {
                $q->whereHas('stockBatch', fn ($sq) => $sq->where('client_id', $this->request->client_id));
            })
            ->when($this->request->movement_type, function ($q) {
                if ($this->request->movement_type === 'processing_in') {
                    $q->where('type', 'in')
                        ->whereHas('stockBatch', fn ($sq) => $sq->whereNotNull('packaging_order_id'));
                    return;
                }

                $q->where('type', $this->request->movement_type);
            })
            ->when($this->request->start_date, fn ($q) => $q->whereDate('entry_date', '>=', $this->request->start_date))
            ->when($this->request->end_date, fn ($q) => $q->whereDate('entry_date', '<=', $this->request->end_date))
            ->orderByDesc('entry_date')
            ->get();

        $data = [];
        foreach ($rows as $tx) {
            $origin = 'Entree directe';
            if ($tx->type === 'transfer') {
                $origin = 'Transfert interne';
            } elseif (optional($tx->stockBatch)->packaging_order_id) {
                $origin = 'Depuis traitement';
            }

            $movementType = match ($tx->type) {
                'in' => optional($tx->stockBatch)->packaging_order_id ? 'Entree apres traitement' : 'Entree',
                'out' => 'Sortie',
                'transfer' => 'Transfert',
                default => (string) $tx->type,
            };

            $data[] = [
                $tx->entry_date?->format('d/m/Y H:i') ?? '-',
                optional($tx->stockBatch->client)->name ?? '-',
                $movementType,
                $origin,
                $tx->reference ?? '-',
                optional($tx->stockBatch->fishType)->name ?? '-',
                optional($tx->stockBatch->cartonType)->name ?? '-',
                optional($tx->coldRoom)->name ?? '-',
                (int) $tx->cartons_count,
                number_format((float) $tx->weight_kg, 2),
                $tx->notes ?? '-',
            ];
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            'Date',
            'Client',
            'Type mouvement',
            'Origine',
            'Reference',
            'Poisson',
            'Type carton',
            'Chambre',
            'Cartons',
            'Poids (kg)',
            'Notes',
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '0F766E']],
                'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 18,
            'B' => 20,
            'C' => 16,
            'D' => 20,
            'E' => 18,
            'F' => 14,
            'G' => 14,
            'H' => 14,
            'I' => 10,
            'J' => 12,
            'K' => 30,
        ];
    }
}
