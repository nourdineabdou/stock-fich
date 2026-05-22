<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; }
    .header { background: #0f172a; color: #fff; padding: 18px 24px; display: flex; justify-content: space-between; align-items: flex-start; }
    .company-block { max-width: 62%; }
    .company-name { font-size: 18px; font-weight: bold; color: #38bdf8; }
    .company-line { font-size: 10px; color: #cbd5e1; margin-top: 3px; }
    .logo { height: 55px; margin-bottom: 8px; }
    .doc-title { font-size: 23px; font-weight: bold; text-align: right; letter-spacing: 1px; }
    .doc-sub { font-size: 10px; color: #cbd5e1; text-align: right; margin-top: 3px; }
    .body { padding: 20px 24px; }
    .info-grid { display: flex; gap: 16px; margin-bottom: 16px; }
    .info-box { flex: 1; border: 1px solid #e2e8f0; border-radius: 6px; padding: 10px; }
    .info-box h4 { font-size: 9px; text-transform: uppercase; color: #64748b; margin-bottom: 6px; letter-spacing: 0.8px; }
    .info-box p { font-size: 11px; margin-bottom: 2px; }
    table { width: 100%; border-collapse: collapse; margin-top: 12px; }
    thead tr { background: #0f172a; color: #fff; }
    th { padding: 8px 9px; text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; }
    td { padding: 8px 9px; border-bottom: 1px solid #e2e8f0; font-size: 11px; }
    tbody tr:nth-child(even) { background: #f8fafc; }
    .text-right { text-align: right; }
    .totals { margin-top: 14px; display: flex; justify-content: flex-end; }
    .totals-box { width: 320px; border: 1px solid #e2e8f0; border-radius: 6px; overflow: hidden; }
    .totals-box td { padding: 8px 10px; }
    .totals-box tr:last-child td { background: #0f172a; color: #fff; font-weight: bold; }
    .notes { margin-top: 18px; border: 1px solid #e2e8f0; border-radius: 6px; background: #f8fafc; padding: 10px; }
    .notes-title { font-size: 9px; color: #64748b; text-transform: uppercase; margin-bottom: 5px; }
    .footer { margin-top: 28px; text-align: center; font-size: 9px; color: #94a3b8; }
</style>
</head>
<body>
<div class="header">
    <div class="company-block">
        @if(!empty($logoPath))
            <img src="{{ $logoPath }}" alt="Logo" class="logo">
        @endif
        <div class="company-name">{{ $settings['company_name'] ?? 'FreezePro' }}</div>
        <div class="company-line">{{ $settings['company_address'] ?? '' }}</div>
        <div class="company-line">{{ $settings['company_phone'] ?? '' }} {{ !empty($settings['company_email']) ? ' - '.$settings['company_email'] : '' }}</div>
        <div class="company-line">NIF: {{ $settings['company_nif'] ?? '-' }} | ICE: {{ $settings['company_ice'] ?? '-' }}</div>
    </div>
    <div>
        <div class="doc-title">FACTURE TRAITEMENT</div>
        <div class="doc-sub">Reference: {{ $processing->reference }}</div>
        <div class="doc-sub">Date: {{ $processing->start_date?->format('d/m/Y') ?? now()->format('d/m/Y') }}</div>
        <div class="doc-sub">Client: {{ $processing->client->name ?? '-' }}</div>
    </div>
</div>

<div class="body">
    <div class="info-grid">
        <div class="info-box">
            <h4>Client</h4>
            <p style="font-weight:bold;">{{ $processing->client->name ?? '-' }}</p>
            <p>{{ $processing->client->address ?? '' }}</p>
            <p>{{ $processing->client->phone ?? '' }}</p>
            <p>{{ $processing->client->email ?? '' }}</p>
        </div>
        <div class="info-box">
            <h4>Traitement</h4>
            <p><strong>Arrivage:</strong> {{ $processing->arrival->reference ?? '-' }}</p>
            <p><strong>Mode:</strong> {{ ucfirst($processing->billing_method) }}</p>
            <p><strong>Poids stockable total:</strong> {{ number_format($totalWeight, 2) }} kg</p>
            <p><strong>Statut:</strong> {{ ucfirst($processing->status) }}</p>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Type de poisson</th>
                <th class="text-right">Quantite (kg)</th>
                <th class="text-right">Prix / kg</th>
                <th class="text-right">Montant</th>
            </tr>
        </thead>
        <tbody>
            @forelse($lines as $line)
            <tr>
                <td>{{ $line['fish'] }}</td>
                <td class="text-right">{{ number_format($line['quantity_kg'], 2) }}</td>
                <td class="text-right">{{ number_format($line['unit_price'], 2) }} {{ $settings['currency'] ?? 'MRU' }}</td>
                <td class="text-right">{{ number_format($line['line_total'], 2) }} {{ $settings['currency'] ?? 'MRU' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="4" class="text-right">Aucune ligne de tri enregistree.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div class="totals">
        <table class="totals-box">
            <tr>
                <td>Quantite totale</td>
                <td class="text-right">{{ number_format($totalWeight, 2) }} kg</td>
            </tr>
            <tr>
                <td>Total a payer</td>
                <td class="text-right">{{ number_format($subtotal, 2) }} {{ $settings['currency'] ?? 'MRU' }}</td>
            </tr>
        </table>
    </div>

    @if(!empty($processing->notes))
    <div class="notes">
        <div class="notes-title">Notes</div>
        <div>{{ $processing->notes }}</div>
    </div>
    @endif

    <div style="margin-top: 28px; display:flex; justify-content:space-between;">
        <div style="width:45%; text-align:center; border-top:1px solid #cbd5e1; padding-top:8px; font-size:10px; color:#64748b;">Signature client</div>
        <div style="width:45%; text-align:center; border-top:1px solid #cbd5e1; padding-top:8px; font-size:10px; color:#64748b;">Cachet et signature societe</div>
    </div>
</div>

<div class="footer">
    Document genere le {{ now()->format('d/m/Y H:i') }}
</div>
</body>
</html>

