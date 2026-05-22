<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; }
    .header { background: #0f172a; color: white; padding: 20px 25px; display: flex; justify-content: space-between; align-items: flex-start; }
    .company-name { font-size: 20px; font-weight: bold; color: #38bdf8; }
    .doc-title { font-size: 26px; font-weight: bold; text-align: right; letter-spacing: 2px; }
    .doc-sub { font-size: 10px; color: #94a3b8; text-align: right; margin-top: 4px; }
    .body { padding: 20px 25px; }
    .info-grid { display: flex; gap: 20px; margin: 20px 0; }
    .info-box { flex: 1; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px; }
    .info-box h4 { font-size: 9px; text-transform: uppercase; color: #64748b; margin-bottom: 8px; letter-spacing: 1px; border-bottom: 1px solid #f1f5f9; padding-bottom: 4px; }
    .info-box p { font-size: 11px; color: #1e293b; margin-bottom: 3px; }
    table { width: 100%; border-collapse: collapse; margin: 15px 0; }
    thead tr { background: #0f172a; color: white; }
    th { padding: 9px 10px; text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; }
    tbody tr:nth-child(even) { background: #f8fafc; }
    td { padding: 8px 10px; border-bottom: 1px solid #e2e8f0; font-size: 11px; }
    .text-right { text-align: right; }
    .totals { display: flex; justify-content: flex-end; margin-top: 10px; }
    .totals-table { width: 300px; border: 1px solid #e2e8f0; border-radius: 6px; overflow: hidden; }
    .totals-table tr td { padding: 7px 12px; border-bottom: 1px solid #f1f5f9; }
    .totals-table tr:last-child td { background: #0f172a; color: white; font-weight: bold; font-size: 13px; border-bottom: none; }
    .footer { margin-top: 40px; border-top: 2px solid #e2e8f0; padding-top: 15px; text-align: center; color: #94a3b8; font-size: 9px; }
    .status-badge { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 9px; font-weight: bold; }
    .status-paid { background: #d1fae5; color: #065f46; }
    .status-sent { background: #dbeafe; color: #1e40af; }
    .status-draft { background: #f1f5f9; color: #475569; }
</style>
</head>
<body>
<div class="header">
    <div>
        <div class="company-name">{{ $settings['company_name'] ?? 'FreezePro' }}</div>
        <div style="color:#94a3b8;font-size:10px;margin-top:5px;">{{ $settings['company_address'] ?? '' }}</div>
        <div style="color:#94a3b8;font-size:10px;">{{ $settings['company_phone'] ?? '' }}</div>
        <div style="color:#94a3b8;font-size:10px;">{{ $settings['company_email'] ?? '' }}</div>
        @if(isset($settings['company_ice']))<div style="color:#94a3b8;font-size:10px;">ICE: {{ $settings['company_ice'] }}</div>@endif
    </div>
    <div>
        <div class="doc-title">FACTURE</div>
        <div class="doc-sub">N° {{ $invoice->invoice_number }}</div>
        <div class="doc-sub">Émise le: {{ $invoice->invoice_date->format('d/m/Y') }}</div>
        @if($invoice->due_date)<div class="doc-sub">Échéance: {{ $invoice->due_date->format('d/m/Y') }}</div>@endif
        <div style="text-align:right;margin-top:6px;">
            <span class="status-badge {{ match($invoice->status) { 'paid'=>'status-paid','sent'=>'status-sent',default=>'status-draft' } }}">
                {{ match($invoice->status) { 'paid'=>'PAYÉE','sent'=>'ENVOYÉE','draft'=>'BROUILLON',default=>strtoupper($invoice->status) } }}
            </span>
        </div>
    </div>
</div>
<div class="body">
    <div class="info-grid">
        <div class="info-box">
            <h4>Facturé à</h4>
            <p style="font-size:13px;font-weight:bold;">{{ $invoice->client->name ?? '-' }}</p>
            <p>{{ $invoice->client->address ?? '' }}</p>
            <p>{{ $invoice->client->phone ?? '' }}</p>
            <p>{{ $invoice->client->email ?? '' }}</p>
            @if($invoice->client->ice ?? false)<p>ICE: {{ $invoice->client->ice }}</p>@endif
        </div>
        <div class="info-box">
            <h4>Détails</h4>
            <p><strong>N° Facture:</strong> {{ $invoice->invoice_number }}</p>
            <p><strong>Date:</strong> {{ $invoice->invoice_date->format('d/m/Y') }}</p>
            @if($invoice->due_date)<p><strong>Échéance:</strong> {{ $invoice->due_date->format('d/m/Y') }}</p>@endif
            <p><strong>TVA:</strong> {{ $invoice->tax_rate }}%</p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:50%">Description</th>
                <th class="text-right">Quantité</th>
                <th class="text-right">Prix unitaire</th>
                <th class="text-right">Total HT</th>
            </tr>
        </thead>
        <tbody>
            @php $ht = 0; @endphp
            @foreach($invoice->items as $item)
            @php $lineTotal = $item->quantity * $item->unit_price; $ht += $lineTotal; @endphp
            <tr>
                <td>{{ $item->description }}</td>
                <td class="text-right">{{ $item->quantity }}</td>
                <td class="text-right">{{ number_format($item->unit_price, 2) }} MRU</td>
                <td class="text-right">{{ number_format($lineTotal, 2) }} MRU</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    @php $tax = $ht * ($invoice->tax_rate / 100); $ttc = $ht + $tax; @endphp
    <div class="totals">
        <table class="totals-table">
            <tr>
                <td>Sous-total HT</td>
                <td class="text-right">{{ number_format($ht, 2) }} MRU</td>
            </tr>
            <tr>
                <td>TVA ({{ $invoice->tax_rate }}%)</td>
                <td class="text-right">{{ number_format($tax, 2) }} MRU</td>
            </tr>
            <tr>
                <td>TOTAL TTC</td>
                <td class="text-right">{{ number_format($invoice->total_amount ?: $ttc, 2) }} MRU</td>
            </tr>
        </table>
    </div>

    @if($invoice->notes)
    <div style="margin-top:20px;padding:12px;background:#f8fafc;border-radius:6px;border:1px solid #e2e8f0;">
        <p style="font-size:9px;text-transform:uppercase;color:#64748b;margin-bottom:4px;">Notes</p>
        <p>{{ $invoice->notes }}</p>
    </div>
    @endif

    <div style="margin-top:40px;display:flex;justify-content:space-between;">
        <div style="text-align:center;width:45%;">
            <div style="border-top:1px solid #cbd5e1;padding-top:8px;color:#64748b;font-size:10px;">Signature Client</div>
        </div>
        <div style="text-align:center;width:45%;">
            <div style="border-top:1px solid #cbd5e1;padding-top:8px;color:#64748b;font-size:10px;">Cachet & Signature Société</div>
        </div>
    </div>
</div>
<div class="footer">
    {{ $settings['company_name'] ?? 'FreezePro' }} · {{ $settings['company_address'] ?? '' }} · {{ $settings['company_phone'] ?? '' }}<br>
    Document généré le {{ now()->format('d/m/Y à H:i') }}
</div>
</body>
</html>

