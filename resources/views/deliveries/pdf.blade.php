<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; }
    .header { background: #0f172a; color: white; padding: 20px 25px; display: flex; justify-content: space-between; align-items: center; }
    .company-name { font-size: 18px; font-weight: bold; color: #38bdf8; }
    .doc-title { font-size: 22px; font-weight: bold; text-align: right; }
    .doc-ref { font-size: 11px; color: #94a3b8; text-align: right; }
    .body { padding: 20px 25px; }
    .info-grid { display: flex; gap: 20px; margin: 20px 0; }
    .info-box { flex: 1; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px; }
    .info-box h4 { font-size: 9px; text-transform: uppercase; color: #64748b; margin-bottom: 8px; letter-spacing: 1px; }
    .info-box p { font-size: 11px; color: #1e293b; margin-bottom: 2px; }
    table { width: 100%; border-collapse: collapse; margin: 15px 0; }
    thead tr { background: #0f172a; color: white; }
    th { padding: 8px 10px; text-align: left; font-size: 10px; text-transform: uppercase; }
    tbody tr:nth-child(even) { background: #f8fafc; }
    td { padding: 8px 10px; border-bottom: 1px solid #e2e8f0; }
    .text-right { text-align: right; }
    tfoot tr { background: #f1f5f9; font-weight: bold; }
    .footer { margin-top: 40px; border-top: 2px solid #e2e8f0; padding-top: 15px; text-align: center; color: #94a3b8; font-size: 9px; }
    .badge { display: inline-block; padding: 2px 8px; border-radius: 20px; font-size: 9px; }
    .badge-delivered { background: #d1fae5; color: #065f46; }
    .badge-pending { background: #fef3c7; color: #92400e; }
</style>
</head>
<body>
<div class="header">
    <div>
        <div class="company-name">{{ $settings['company_name'] ?? 'FreezePro' }}</div>
        <div style="color:#94a3b8;font-size:10px;margin-top:4px;">{{ $settings['company_address'] ?? '' }}</div>
        <div style="color:#94a3b8;font-size:10px;">{{ $settings['company_phone'] ?? '' }}</div>
    </div>
    <div>
        <div class="doc-title">BON DE LIVRAISON</div>
        <div class="doc-ref">{{ $delivery->reference }}</div>
        <div class="doc-ref">{{ $delivery->delivery_date->format('d/m/Y') }}</div>
    </div>
</div>
<div class="body">
    <div class="info-grid">
        <div class="info-box">
            <h4>Client</h4>
            <p style="font-weight:bold;font-size:13px;">{{ $delivery->client->name ?? '-' }}</p>
            <p>{{ $delivery->client->address ?? '' }}</p>
            <p>{{ $delivery->client->phone ?? '' }}</p>
            <p>{{ $delivery->client->email ?? '' }}</p>
        </div>
        <div class="info-box">
            <h4>Livraison</h4>
            <p><strong>Date:</strong> {{ $delivery->delivery_date->format('d/m/Y') }}</p>
            <p><strong>Statut:</strong> {{ match($delivery->status) { 'delivered'=>'Livré','pending'=>'En attente',default=>$delivery->status } }}</p>
            <p><strong>Réceptionnaire:</strong> {{ $delivery->receiver_name ?? '-' }}</p>
            <p><strong>CIN/NIF:</strong> {{ $delivery->receiver_id_number ?? '-' }}</p>
            <p><strong>Téléphone:</strong> {{ $delivery->receiver_phone ?? '-' }}</p>
            @if($delivery->notes)
            <p><strong>Notes:</strong> {{ $delivery->notes }}</p>
            @endif
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>N° Lot</th>
                <th>Poisson</th>
                <th>Type Carton</th>
                <th class="text-right">Quantité</th>
                <th class="text-right">Poids/Carton</th>
                <th class="text-right">Total Poids</th>
                <th class="text-right">Prix/Carton</th>
                <th class="text-right">Montant</th>
            </tr>
        </thead>
        <tbody>
            @foreach($delivery->items as $item)
            <tr>
                <td style="font-family:monospace;">{{ $item->stockBatch->lot_number ?? '-' }}</td>
                <td>{{ $item->stockBatch->fishType->name ?? '-' }}</td>
                <td>{{ $item->stockBatch->cartonType->name ?? '-' }}</td>
                <td class="text-right">{{ $item->cartons_count }}</td>
                <td class="text-right">{{ number_format($item->stockBatch->weight_per_carton ?? 0, 3) }} kg</td>
                <td class="text-right">{{ number_format($item->cartons_count * ($item->stockBatch->weight_per_carton ?? 0), 2) }} kg</td>
                <td class="text-right">{{ $item->price_per_carton ? number_format($item->price_per_carton, 2).' MRU' : '-' }}</td>
                <td class="text-right">{{ $item->price_per_carton ? number_format($item->cartons_count * $item->price_per_carton, 2).' MRU' : '-' }}</td>
            </tr>
            @endforeach
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3">Total</td>
                <td class="text-right">{{ $delivery->items->sum('cartons_count') }}</td>
                <td></td>
                <td class="text-right">{{ number_format($delivery->items->sum(fn($i) => $i->cartons_count * ($i->stockBatch->weight_per_carton ?? 0)), 2) }} kg</td>
                <td></td>
                <td class="text-right">{{ number_format($delivery->items->sum(fn($i) => $i->cartons_count * ($i->price_per_carton ?? 0)), 2) }} MRU</td>
            </tr>
        </tfoot>
    </table>

    <div style="margin-top:26px;border:1px solid #e2e8f0;padding:10px 12px;border-radius:6px;">
        <p style="font-size:10px;color:#64748b;">Accuse de retrait client</p>
        <p style="font-size:11px;margin-top:4px;"><strong>Nom:</strong> {{ $delivery->receiver_name ?? '-' }} · <strong>CIN/NIF:</strong> {{ $delivery->receiver_id_number ?? '-' }} · <strong>Tel:</strong> {{ $delivery->receiver_phone ?? '-' }}</p>
        <p style="font-size:10px;color:#64748b;margin-top:4px;">Le client confirme la reception des cartons mentionnes ci-dessus.</p>
    </div>

    <div style="margin-top:20px;display:flex;justify-content:space-between;">
        <div style="text-align:center;width:45%;">
            <div style="border-top:1px solid #cbd5e1;padding-top:8px;color:#64748b;font-size:10px;">Signature Client</div>
        </div>
        <div style="text-align:center;width:45%;">
            <div style="border-top:1px solid #cbd5e1;padding-top:8px;color:#64748b;font-size:10px;">Cachet & Signature</div>
        </div>
    </div>
</div>
<div class="footer">
    {{ $settings['company_name'] ?? 'FreezePro' }} · {{ $settings['company_address'] ?? '' }} · {{ $settings['company_phone'] ?? '' }}<br>
    Document généré le {{ now()->format('d/m/Y à H:i') }}
</div>
</body>
</html>

