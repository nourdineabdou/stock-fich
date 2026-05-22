<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<style>
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; }
    .header { background: #111827; color: #fff; padding: 16px 20px; display: flex; justify-content: space-between; align-items: flex-start; }
    .title { font-size: 22px; font-weight: bold; }
    .subtitle { font-size: 10px; color: #cbd5e1; margin-top: 4px; }
    .logo { height: 52px; margin-bottom: 6px; }
    .company { font-size: 10px; color: #d1d5db; margin-top: 2px; }
    .container { padding: 18px 20px; }
    .block { border: 1px solid #e5e7eb; border-radius: 8px; padding: 10px; margin-bottom: 12px; }
    .label { font-size: 9px; text-transform: uppercase; color: #6b7280; margin-bottom: 4px; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    thead tr { background: #111827; color: #fff; }
    th { padding: 8px; text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: 0.4px; }
    td { padding: 8px; border-bottom: 1px solid #e5e7eb; font-size: 11px; }
    tbody tr:nth-child(even) { background: #f9fafb; }
    .text-right { text-align: right; }
    .footer { margin-top: 24px; font-size: 9px; color: #6b7280; text-align: center; }
</style>
</head>
<body>
<div class="header">
    <div>
        @if(!empty($logoPath))
            <img src="{{ $logoPath }}" alt="Logo" class="logo">
        @endif
        <div class="company" style="font-size:12px;font-weight:bold;color:#e5e7eb;">{{ $settings['company_name'] ?? 'FreezePro Maroc' }}</div>
        <div class="company">{{ $settings['company_address'] ?? '' }}</div>
        <div class="company">{{ $settings['company_phone'] ?? '' }} {{ !empty($settings['company_email']) ? ' - '.$settings['company_email'] : '' }}</div>
        <div class="company">NIF: {{ $settings['company_nif'] ?? '-' }} | ICE: {{ $settings['company_ice'] ?? '-' }}</div>
    </div>
    <div style="text-align:right;">
        <div class="title">BON DE CHARGEMENT</div>
        <div class="subtitle">Traitement: {{ $processing->reference }}</div>
        <div class="subtitle">Date: {{ $processing->start_date?->format('d/m/Y') ?? now()->format('d/m/Y') }}</div>
    </div>
</div>

<div class="container">
    <div class="block">
        <div class="label">Client</div>
        <div>{{ $processing->client->name ?? '-' }}</div>
    </div>

        <div class="block">
            <div class="label">Instruction de chargement</div>
            <div>Chaque ligne indique le tunnel, le chariot, le type de poisson et la quantite a charger. Cette fiche est destinee a l'equipe terrain.</div>
        </div>

    <table>
        <thead>
            <tr>
                <th>Tunnel</th>
                <th>Chariot</th>
                <th>Poisson</th>
                <th class="text-right">Quantite (kg)</th>
            </tr>
        </thead>
        <tbody>
            @php
                $groupedRows = $rows->groupBy(fn ($row) => $row['tunnel_name'] ?? 'Affecter');
            @endphp
            @foreach($groupedRows as $tunnelName => $tunnelRows)
                @foreach($tunnelRows->values() as $index => $row)
                <tr>
                    @if($index === 0)
                    <td rowspan="{{ $tunnelRows->count() }}" style="font-weight:bold;vertical-align:top;">{{ $tunnelName }}</td>
                    @endif
                    <td>{{ $row['cart_code'] }}</td>
                    <td>{{ $row['fish'] }}</td>
                    <td class="text-right">{{ number_format($row['quantity_kg'], 2) }}</td>
                </tr>
                @endforeach
            @endforeach
            <tr>
                <td style="font-weight:bold;">Total</td>
                <td></td>
                <td></td>
                <td class="text-right" style="font-weight:bold;">{{ number_format($totalWeight, 2) }} kg</td>
            </tr>
        </tbody>
    </table>

    <div style="margin-top:26px;display:flex;justify-content:space-between;gap:20px;">
        <div style="flex:1;text-align:center;border-top:1px solid #cbd5e1;padding-top:8px;color:#64748b;font-size:10px;">Signature Responsable Production</div>
        <div style="flex:1;text-align:center;border-top:1px solid #cbd5e1;padding-top:8px;color:#64748b;font-size:10px;">Signature Chef d'equipe</div>
    </div>

    <div class="footer">
        Document genere le {{ now()->format('d/m/Y H:i') }}
    </div>
</div>
</body>
</html>
