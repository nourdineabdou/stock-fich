<?php

namespace App\Exports;

use App\Models\Client;
use App\Models\StorageEntry;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ClientStatementExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    private $client;
    private $startDate;
    private $endDate;

    public function __construct(Client $client, $startDate = null, $endDate = null)
    {
        $this->client = $client;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function array(): array
    {
        $query = StorageEntry::query()
            ->with(['stockBatch.fishType', 'stockBatch.cartonType', 'stockBatch.packagingOrder', 'coldRoom'])
            ->whereHas('stockBatch', fn ($q) => $q->where('client_id', $this->client->id))
            ->orderByDesc('entry_date');

        if ($this->startDate) {
            $query->whereDate('entry_date', '>=', $this->startDate);
        }
        if ($this->endDate) {
            $query->whereDate('entry_date', '<=', $this->endDate);
        }

        $transactions = $query->get();

        $data = [];
        foreach ($transactions as $tx) {
            $origin = 'Entrée directe';
            if ($tx->stockBatch->packaging_order_id) {
                $origin = 'Depuis traitement';
            } elseif ($tx->type === 'transfer') {
                $origin = 'Transfert';
            }

            $data[] = [
                $tx->entry_date?->format('d/m/Y H:i') ?? '-',
                $tx->type === 'in' ? 'Entrée' : ($tx->type === 'out' ? 'Sortie' : 'Transfert'),
                $origin,
                $tx->reference ?? '-',
                $tx->stockBatch->fishType->name ?? '-',
                $tx->stockBatch->cartonType->name ?? '-',
                $tx->coldRoom->name ?? '-',
                $tx->type === 'out' ? '-' . $tx->cartons_count : $tx->cartons_count,
                number_format($tx->weight_kg, 2),
                $tx->notes ?? '-',
            ];
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            'Date',
            'Type Mouvement',
            'Origine',
            'Référence',
            'Poisson',
            'Type Carton',
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
                'fill' => ['fillType' => 'solid', 'startColor' => ['rgb' => '4F46E5']],
                'alignment' => ['horizontal' => 'center', 'vertical' => 'center'],
            ],
        ];
    }

    public function columnWidths(): array
    {
        return [
            'A' => 18,
            'B' => 15,
            'C' => 18,
            'D' => 18,
            'E' => 15,
            'F' => 15,
            'G' => 15,
            'H' => 12,
            'I' => 12,
            'J' => 25,
        ];
    }
}
