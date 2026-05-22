<?php

namespace App\Exports;

use App\Models\ClientStockBatch;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Illuminate\Http\Request;

class StockListExport implements FromArray, WithHeadings, WithStyles, WithColumnWidths
{
    private $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function array(): array
    {
        $batches = ClientStockBatch::with(['client', 'fishType', 'cartonType', 'coldRoom', 'packagingOrder'])
            ->when($this->request->client_id, fn($q) => $q->where('client_id', $this->request->client_id))
            ->when($this->request->fish_type_id, fn($q) => $q->where('fish_type_id', $this->request->fish_type_id))
            ->when($this->request->cold_room_id, fn($q) => $q->where('cold_room_id', $this->request->cold_room_id))
            ->when($this->request->status, fn($q) => $q->where('status', $this->request->status))
            ->when($this->request->search, fn($q) => $q->where('lot_number', 'like', "%{$this->request->search}%"))
            ->when($this->request->origin, function($q) {
                if ($this->request->origin === 'direct') {
                    $q->whereNull('packaging_order_id');
                } elseif ($this->request->origin === 'processing') {
                    $q->whereNotNull('packaging_order_id');
                } elseif ($this->request->origin === 'empty') {
                    $q->where('available_cartons', 0)->where('cartons_count', '>', 0);
                }
            })
            ->latest('entry_date')
            ->get();

        $data = [];
        foreach ($batches as $batch) {
            $origin = 'Entrée directe';
            if ($batch->available_cartons == 0 && $batch->cartons_count > 0) {
                $origin = 'Sortie complète';
            } elseif ($batch->packaging_order_id) {
                $origin = 'Depuis traitement';
            }

            $data[] = [
                $batch->lot_number,
                $batch->client->name ?? '-',
                $batch->fishType->name ?? '-',
                $batch->cartonType->name ?? '-',
                $batch->coldRoom->name ?? '-',
                $origin,
                $batch->available_cartons,
                number_format($batch->available_cartons * ($batch->weight_per_carton ?? 0), 2),
                match($batch->status) {
                    'available' => 'Disponible',
                    'partial' => 'Partiel',
                    'depleted' => 'Épuisé',
                    default => $batch->status
                },
            ];
        }

        return $data;
    }

    public function headings(): array
    {
        return [
            'N° Lot',
            'Client',
            'Poisson',
            'Type Carton',
            'Chambre Froide',
            'Origine',
            'Cartons Dispo',
            'Poids (kg)',
            'Statut',
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
            'B' => 20,
            'C' => 15,
            'D' => 15,
            'E' => 15,
            'F' => 18,
            'G' => 14,
            'H' => 12,
            'I' => 12,
        ];
    }
}
