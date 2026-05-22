<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
    .page { padding: 22px 26px; }
    .header { background: linear-gradient(90deg, #0f172a 0%, #1e293b 100%); color: #fff; padding: 16px 18px; border-radius: 10px; }
    .company { font-size: 18px; font-weight: 700; color: #67e8f9; }
    .muted { color: #cbd5e1; font-size: 10px; }
    .title { margin-top: 12px; font-size: 20px; font-weight: 800; letter-spacing: 0.4px; }
    .badge { display: inline-block; padding: 4px 10px; border-radius: 999px; background: #cffafe; color: #155e75; font-size: 10px; font-weight: 700; }
    .grid { width: 100%; margin-top: 16px; border-collapse: separate; border-spacing: 10px; }
    .box { border: 1px solid #e2e8f0; border-radius: 10px; padding: 12px; vertical-align: top; width: 50%; }
    .box h4 { font-size: 9px; text-transform: uppercase; letter-spacing: 0.7px; color: #64748b; margin-bottom: 8px; }
    .box p { margin-bottom: 4px; }
    .strong { font-weight: 700; }
    .stats { margin-top: 14px; width: 100%; border-collapse: collapse; }
    .stats th { background: #f1f5f9; color: #475569; text-transform: uppercase; font-size: 9px; padding: 8px; border: 1px solid #e2e8f0; text-align: left; }
    .stats td { padding: 9px; border: 1px solid #e2e8f0; }
    .value { font-size: 16px; font-weight: 800; color: #0f766e; }
    .signatures { margin-top: 30px; width: 100%; }
    .sign-cell { width: 48%; display: inline-block; text-align: center; }
    .line { margin-top: 45px; border-top: 1px solid #94a3b8; padding-top: 7px; font-size: 10px; color: #64748b; }
    .footer { margin-top: 18px; padding-top: 10px; border-top: 2px solid #e2e8f0; text-align: center; color: #64748b; font-size: 9px; }
</style>
</head>
<body>
<div class="page">
    <div class="header">
        <div style="display: flex; justify-content: space-between; align-items: flex-start;">
            <div>
                <div class="company">{{ $settings['company_name'] ?? 'FreezePro' }}</div>
                <div class="muted" style="margin-top: 3px;">{{ $settings['company_address'] ?? '' }}</div>
                <div class="muted">{{ $settings['company_phone'] ?? '' }}</div>
                @if(!empty($settings['company_ice']))
                <div class="muted">ICE: {{ $settings['company_ice'] }}</div>
                @endif
            </div>
            <div style="text-align: right;">
                <span class="badge">RECU CLIENT - ENTREE STOCK</span>
                <div class="title">{{ $clientStockBatch->entry_reference ?? $clientStockBatch->lot_number }}</div>
                <div class="muted">Genere le {{ now()->format('d/m/Y H:i') }}</div>
            </div>
        </div>
    </div>

    <table class="grid">
        <tr>
            <td class="box">
                <h4>Informations Client</h4>
                <p class="strong" style="font-size: 14px;">{{ $clientStockBatch->client->name ?? '-' }}</p>
                <p>{{ $clientStockBatch->client->phone ?? '-' }}</p>
                <p>{{ $clientStockBatch->client->address ?? '-' }}</p>
            </td>
            <td class="box">
                <h4>Reference Entree</h4>
                <p><span class="strong">Reference entree:</span> {{ $clientStockBatch->entry_reference ?? $clientStockBatch->lot_number }}</p>
                <p><span class="strong">Date d'entree:</span> {{ optional($clientStockBatch->entry_date)->format('d/m/Y H:i') ?? '-' }}</p>
                <p><span class="strong">Date congelation:</span> {{ optional($clientStockBatch->frozen_at)->format('d/m/Y H:i') ?? '-' }}</p>
                <p><span class="strong">Chambre froide:</span> {{ $clientStockBatch->coldRoom->name ?? 'Non assignee' }}</p>
                <p><span class="strong">Statut:</span> {{ ucfirst($clientStockBatch->status ?? '-') }}</p>
            </td>
        </tr>
    </table>

    <table class="stats">
        <thead>
            <tr>
                <th>Lot</th>
                <th>Type Poisson</th>
                <th>Type Carton</th>
                <th>Cartons Entres</th>
                <th>Poids / Carton</th>
                <th>Poids Total Entree</th>
            </tr>
        </thead>
        <tbody>
            @foreach($entryBatches as $batch)
            <tr>
                <td style="font-family: monospace;">{{ $batch->lot_number }}</td>
                <td>{{ $batch->fishType->name ?? '-' }}</td>
                <td>{{ $batch->cartonType->name ?? '-' }}</td>
                <td class="value">{{ number_format((float) $batch->cartons_count, 0, ',', ' ') }}</td>
                <td>{{ number_format((float) $batch->weight_per_carton, 2, ',', ' ') }} kg</td>
                <td class="value">{{ number_format((float) $batch->total_weight, 2, ',', ' ') }} kg</td>
            </tr>
            @endforeach
            <tr>
                <td colspan="3" class="strong">Total</td>
                <td class="value">{{ number_format((float) $entryBatches->sum('cartons_count'), 0, ',', ' ') }}</td>
                <td></td>
                <td class="value">{{ number_format((float) $entryBatches->sum('total_weight'), 2, ',', ' ') }} kg</td>
            </tr>
        </tbody>
    </table>

    @if($clientStockBatch->notes)
    <div style="margin-top: 12px; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px;">
        <div style="font-size: 9px; text-transform: uppercase; color: #64748b; margin-bottom: 5px;">Notes</div>
        <div>{{ $clientStockBatch->notes }}</div>
    </div>
    @endif

    <div class="signatures">
        <div class="sign-cell">
            <div class="line">Signature Client</div>
        </div>
        <div class="sign-cell" style="float: right;">
            <div class="line">Cachet & Signature Societe</div>
        </div>
    </div>

    <div class="footer">
        Ce recu confirme l'entree en stock des lots ci-dessus pour conservation en chambre froide.
    </div>
</div>
</body>
</html>
