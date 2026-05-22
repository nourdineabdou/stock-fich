<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Bon de Cartonnage - {{ $order->reference }}</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #0f172a; margin: 24px; }
        h1 { margin: 0 0 4px; font-size: 20px; }
        .muted { color: #475569; font-size: 12px; }
        .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; }
        .badge { display: inline-block; padding: 3px 8px; border-radius: 999px; background: #e2e8f0; font-size: 11px; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #cbd5e1; padding: 8px; font-size: 12px; }
        th { background: #f8fafc; text-align: left; }
        .right { text-align: right; }
        .totals { margin-top: 14px; width: 280px; margin-left: auto; }
        .totals td { border: none; padding: 4px 0; }
        .signatures { margin-top: 40px; display: flex; justify-content: space-between; gap: 24px; }
        .sig { width: 48%; }
        .line { margin-top: 45px; border-top: 1px solid #64748b; }
        @media print { body { margin: 12mm; } }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1>Bon de Cartonnage</h1>
            <div class="muted">Reference: {{ $order->reference }}</div>
            <div class="muted">Date: {{ $order->date?->format('d/m/Y') ?? $order->created_at?->format('d/m/Y') ?? '-' }}</div>
        </div>
        <div>
            <span class="badge">{{ strtoupper($order->status ?? 'PENDING') }}</span>
        </div>
    </div>

    <div class="muted">Client: {{ $order->processingOrder->client->name ?? '-' }}</div>
    <div class="muted">Ordre traitement: {{ $order->processingOrder->reference ?? '-' }}</div>

    <table>
        <thead>
            <tr>
                <th>Type poisson</th>
                <th>Type carton</th>
                <th class="right">Nb cartons</th>
                <th class="right">Poids/carton (kg)</th>
                <th class="right">Total (kg)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($order->items as $item)
            <tr>
                <td>{{ $item->fishType->name ?? '-' }}</td>
                <td>{{ $item->cartonType->name ?? '-' }}</td>
                <td class="right">{{ number_format($item->cartons_count, 0) }}</td>
                <td class="right">{{ number_format($item->weight_per_carton, 2) }}</td>
                <td class="right">{{ number_format($item->cartons_count * $item->weight_per_carton, 2) }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="right">Aucune ligne de cartonnage</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td>Total cartons:</td>
            <td class="right"><strong>{{ number_format($order->items->sum('cartons_count'), 0) }}</strong></td>
        </tr>
        <tr>
            <td>Total poids:</td>
            <td class="right"><strong>{{ number_format($order->items->sum(fn($i) => $i->cartons_count * $i->weight_per_carton), 2) }} kg</strong></td>
        </tr>
    </table>

    <div class="signatures">
        <div class="sig">
            <div class="muted">Responsable production</div>
            <div class="line"></div>
        </div>
        <div class="sig">
            <div class="muted">Equipe manoeuvre</div>
            <div class="line"></div>
        </div>
    </div>

    <script>
        window.addEventListener('load', function () {
            window.print();
        });
    </script>
</body>
</html>
