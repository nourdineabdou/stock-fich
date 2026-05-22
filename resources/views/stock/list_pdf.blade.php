<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; }
        .header { margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
        .company-name { font-size: 14px; font-weight: bold; }
        .company-info { font-size: 9px; color: #666; margin-top: 5px; }
        .title { font-size: 16px; font-weight: bold; margin: 10px 0; }
        .filters { font-size: 9px; color: #666; margin-bottom: 10px; line-height: 1.4; }
        table { width: 100%; border-collapse: collapse; }
        th { background-color: #4F46E5; color: white; padding: 8px; text-align: left; font-weight: bold; }
        td { padding: 6px; border-bottom: 1px solid #ddd; }
        tr:nth-child(even) { background-color: #f9f9f9; }
        .total-row { font-weight: bold; background-color: #e8e8e8; }
        .text-right { text-align: right; }
        .badge { display: inline-block; padding: 2px 6px; border-radius: 4px; font-size: 9px; }
        .badge-direct { background-color: #e5e7eb; color: #374151; }
        .badge-processing { background-color: #f3e8ff; color: #581c87; }
        .badge-empty { background-color: #fee2e2; color: #991b1b; }
        .badge-available { background-color: #d1fae5; color: #065f46; }
        .badge-partial { background-color: #fed7aa; color: #92400e; }
        .badge-depleted { background-color: #fecaca; color: #991b1b; }
    </style>
</head>
<body>
    <div class="header">
        <div class="company-name">{{ $settings['company_name'] ?? 'FreezePro' }}</div>
        <div class="company-info">
            @if($settings['company_address']){{ $settings['company_address'] }}<br>@endif
            @if($settings['company_phone']){{ $settings['company_phone'] }}<br>@endif
            Édition: {{ now()->format('d/m/Y H:i') }}
        </div>
    </div>

    <div class="title">Liste Complète du Stock</div>

    <div class="filters">
        <strong>Filtres appliqués:</strong><br>
        @if($request->client_id)
            • Client: {{ optional($clients->find($request->client_id))->name }}<br>
        @endif
        @if($request->fish_type_id)
            • Poisson: {{ optional($fishTypes->find($request->fish_type_id))->name }}<br>
        @endif
        @if($request->cold_room_id)
            • Chambre: {{ optional($coldRooms->find($request->cold_room_id))->name }}<br>
        @endif
        @if($request->status)
            • Statut: {{ $request->status }}<br>
        @endif
        @if($request->origin)
            • Origine: 
            @if($request->origin === 'direct')
                Entrée directe
            @elseif($request->origin === 'processing')
                Depuis traitement
            @elseif($request->origin === 'empty')
                Sortie complète
            @endif
            <br>
        @endif
        @if($request->search)
            • Recherche: {{ $request->search }}<br>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                <th>N° Lot</th>
                <th>Client</th>
                <th>Poisson</th>
                <th>Carton</th>
                <th>Chambre</th>
                <th>Origine</th>
                <th class="text-right">Cartons Dispo</th>
                <th class="text-right">Poids (kg)</th>
                <th>Statut</th>
            </tr>
        </thead>
        <tbody>
            @forelse($batches as $batch)
            <tr>
                <td><strong>{{ $batch->lot_number }}</strong></td>
                <td>{{ $batch->client->name ?? '-' }}</td>
                <td>{{ $batch->fishType->name ?? '-' }}</td>
                <td>{{ $batch->cartonType->name ?? '-' }}</td>
                <td>{{ $batch->coldRoom->name ?? '-' }}</td>
                <td>
                    @if($batch->available_cartons == 0 && $batch->cartons_count > 0)
                        <span class="badge badge-empty">Sortie complète</span>
                    @elseif($batch->packaging_order_id)
                        <span class="badge badge-processing">Depuis traitement</span>
                    @else
                        <span class="badge badge-direct">Entrée directe</span>
                    @endif
                </td>
                <td class="text-right"><strong>{{ $batch->available_cartons }}</strong></td>
                <td class="text-right">{{ number_format($batch->available_cartons * ($batch->weight_per_carton ?? 0), 2) }}</td>
                <td>
                    <span class="badge {{ match($batch->status) {
                        'available' => 'badge-available',
                        'partial' => 'badge-partial',
                        'depleted' => 'badge-depleted',
                        default => 'badge-available'
                    } }}">
                        {{ match($batch->status) {
                            'available' => 'Disponible',
                            'partial' => 'Partiel',
                            'depleted' => 'Épuisé',
                            default => $batch->status
                        } }}
                    </span>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="9" style="text-align: center; padding: 20px;">Aucun lot correspondant aux critères</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd; font-size: 10px;">
        <table style="width: auto;">
            <tr class="total-row">
                <td style="width: 150px;">Total Lots</td>
                <td class="text-right" style="width: 100px;">{{ $batches->count() }}</td>
            </tr>
            <tr class="total-row">
                <td>Total Cartons Disponibles</td>
                <td class="text-right">{{ number_format($batches->sum('available_cartons')) }}</td>
            </tr>
            <tr class="total-row">
                <td>Total Poids (kg)</td>
                <td class="text-right">{{ number_format($batches->sum(fn($b) => $b->available_cartons * ($b->weight_per_carton ?? 0)), 2) }}</td>
            </tr>
        </table>
    </div>
</body>
</html>
