<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; }
    .header { margin-bottom: 14px; }
    .title { font-size: 20px; font-weight: bold; }
    .muted { color: #64748b; font-size: 10px; }
    .cards { margin: 10px 0 14px; }
    .cards td { border: 1px solid #cbd5e1; padding: 8px; width: 25%; }
    table { width: 100%; border-collapse: collapse; margin-top: 8px; }
    th, td { border: 1px solid #cbd5e1; padding: 6px 8px; }
    th { background: #f8fafc; text-align: left; font-size: 10px; }
    .text-right { text-align: right; }
    .section { margin-top: 16px; }
</style>
</head>
<body>
    <div class="header">
        <div class="title">Suivi Stock Client</div>
        <div class="muted">Client: {{ $client->name }}</div>
        <div class="muted">{{ $settings['company_name'] ?? 'FreezePro' }} · {{ $settings['company_phone'] ?? '' }}</div>
        <div class="muted">Généré le {{ now()->format('d/m/Y H:i') }}</div>
    </div>

    <table class="cards">
        <tr>
            <td><strong>Lots</strong><br>{{ $summary['total_lots'] }}</td>
            <td><strong>Cartons entrés</strong><br>{{ number_format($summary['total_cartons']) }}</td>
            <td><strong>Cartons retirés</strong><br>{{ number_format($summary['delivered_cartons']) }}</td>
            <td><strong>Disponible</strong><br>{{ number_format($summary['available_cartons']) }} ({{ number_format($summary['available_weight'], 2) }} kg)</td>
        </tr>
    </table>

    <div class="section">
        <strong>Détail des lots</strong>
        <table>
            <thead>
                <tr>
                    <th>Lot</th>
                    <th>Poisson</th>
                    <th>Type carton</th>
                    <th class="text-right">Entré</th>
                    <th class="text-right">Disponible</th>
                    <th class="text-right">Poids dispo (kg)</th>
                </tr>
            </thead>
            <tbody>
                @forelse($batches as $batch)
                <tr>
                    <td>{{ $batch->lot_number }}</td>
                    <td>{{ $batch->fishType->name ?? '-' }}</td>
                    <td>{{ $batch->cartonType->name ?? '-' }}</td>
                    <td class="text-right">{{ $batch->cartons_count }}</td>
                    <td class="text-right">{{ $batch->available_cartons }}</td>
                    <td class="text-right">{{ number_format($batch->available_weight ?? 0, 2) }}</td>
                </tr>
                @empty
                <tr><td colspan="6">Aucun lot</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="section">
        <strong>Retraits clients</strong>
        <table>
            <thead>
                <tr>
                    <th>Référence</th>
                    <th>Date</th>
                    <th class="text-right">Cartons</th>
                    <th>Statut</th>
                </tr>
            </thead>
            <tbody>
                @forelse($deliveries as $delivery)
                <tr>
                    <td>{{ $delivery->reference }}</td>
                    <td>{{ $delivery->delivery_date?->format('d/m/Y') ?? '-' }}</td>
                    <td class="text-right">{{ $delivery->items->sum('cartons_count') }}</td>
                    <td>{{ $delivery->status }}</td>
                </tr>
                @empty
                <tr><td colspan="4">Aucun retrait</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
</body>
</html>
