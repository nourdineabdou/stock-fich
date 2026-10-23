<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
@php
    $cur = $settings['currency'] ?? 'MRU';
    $kg = fn ($v) => number_format((float) $v, 2, ',', ' ');
    $money = fn ($v) => number_format((float) $v, 2, ',', ' ');
@endphp
<style>
    @page { margin: 0; size: A4 portrait; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10.5px; color: #1e293b; padding: 0 0 70px 0; }
    table { border-collapse: collapse; width: 100%; }
    .band { background: #0f2a43; color: #fff; }
    .band td { padding: 22px 32px; vertical-align: top; }
    .company { font-size: 17px; font-weight: bold; color: #7dd3fc; }
    .muted-light { font-size: 9px; color: #cbd5e1; line-height: 1.5; }
    .title { font-size: 18px; font-weight: bold; letter-spacing: 1px; text-align: right; white-space: nowrap; }
    .title-sub { font-size: 9.5px; color: #cbd5e1; text-align: right; line-height: 1.6; }
    .accent { height: 4px; background: #0ea5e9; }
    .wrap { padding: 20px 32px 0 32px; }
    .label { font-size: 8px; text-transform: uppercase; letter-spacing: 1px; color: #64748b; margin-bottom: 5px; }
    .box { border: 1px solid #e2e8f0; padding: 11px 13px; vertical-align: top; }
    .strong { font-weight: bold; font-size: 12px; }
    .kpi td { border: 1px solid #e2e8f0; padding: 9px 10px; text-align: center; width: 25%; }
    .kpi .v { font-size: 14px; font-weight: bold; margin-top: 3px; }
    .bar td { height: 9px; padding: 0; }
    .lines th { background: #0f2a43; color: #fff; font-size: 8.5px; text-transform: uppercase; letter-spacing: .6px; padding: 8px 9px; text-align: left; }
    .lines td { padding: 8px 9px; border-bottom: 1px solid #e2e8f0; }
    .lines tr.alt td { background: #f8fafc; }
    .r, .lines th.r { text-align: right; }
    .lines th.c { text-align: center; }
    .c { text-align: center; }
    .totals td { padding: 8px 12px; border-bottom: 1px solid #e2e8f0; }
    .totals tr.grand td { background: #0f2a43; color: #fff; font-weight: bold; font-size: 13px; border: none; }
    .notes { border-left: 3px solid #0ea5e9; background: #f0f9ff; padding: 9px 12px; }
    .sign td { width: 50%; padding-top: 50px; }
    .sign div { border-top: 1px solid #94a3b8; margin: 0 20px; padding-top: 6px; text-align: center; font-size: 9px; color: #64748b; }
    .footer { position: fixed; bottom: 0; left: 0; right: 0; height: 40px; border-top: 1px solid #e2e8f0; padding: 10px 32px; font-size: 8.5px; color: #94a3b8; }
</style>
</head>
<body>

<table class="band">
    <tr>
        <td style="width:52%;">
            @if(!empty($logoPath))
                <img src="{{ $logoPath }}" alt="Logo" style="height:48px; margin-bottom:8px;">
            @endif
            <div class="company">{{ $settings['company_name'] ?? 'FreezePro' }}</div>
            <div class="muted-light">
                @if(!empty($settings['company_address'])){{ $settings['company_address'] }}<br>@endif
                {{ $settings['company_phone'] ?? '' }}{{ !empty($settings['company_email']) ? ' · '.$settings['company_email'] : '' }}<br>
                @if(!empty($settings['company_nif']))NIF : {{ $settings['company_nif'] }}@endif
                @if(!empty($settings['company_ice'])) · ICE : {{ $settings['company_ice'] }}@endif
            </div>
        </td>
        <td>
            <div class="title">FICHE DE TRAITEMENT</div>
            <div class="title-sub">
                Réf. <b style="color:#fff;">{{ $processing->reference }}</b><br>
                @if($invoice)Facture n° <b style="color:#fff;">{{ $invoice->invoice_number }}</b><br>@endif
                Date : {{ ($processing->end_date ?? $processing->start_date)?->format('d/m/Y') ?? now()->format('d/m/Y') }}
            </div>
        </td>
    </tr>
</table>
<div class="accent"></div>

<div class="wrap">
    {{-- Client / arrivage --}}
    <table>
        <tr>
            <td class="box" style="width:50%;">
                <div class="label">Client</div>
                <div class="strong">{{ $processing->client->name ?? '-' }}</div>
                @if($processing->client?->address)<div>{{ $processing->client->address }}</div>@endif
                @if($processing->client?->phone)<div>Tél. : {{ $processing->client->phone }}</div>@endif
                @if($processing->client?->ice_number)<div>ICE : {{ $processing->client->ice_number }}</div>@endif
            </td>
            <td style="width:12px;"></td>
            <td class="box">
                <div class="label">Traitement</div>
                <div>Arrivage : <b>{{ $processing->arrival->reference ?? '—' }}</b></div>
                <div>Début : {{ $processing->start_date?->format('d/m/Y') ?? '—' }}@if($processing->end_date) · Fin : {{ $processing->end_date->format('d/m/Y') }}@endif</div>
                <div>Facturation : par kg de poisson trié</div>
            </td>
        </tr>
    </table>

    {{-- Reception et filtrage --}}
    @if($reception['received'] > 0)
    <div class="label" style="margin-top:18px;">Réception et filtrage</div>
    <table class="kpi">
        <tr>
            <td><div class="label" style="margin:0;">Poids reçu</div><div class="v">{{ $kg($reception['received']) }} kg</div></td>
            <td><div class="label" style="margin:0;">Non utilisable</div><div class="v" style="color:#dc2626;">{{ $kg($reception['waste']) }} kg</div></td>
            <td><div class="label" style="margin:0;">{{ $reception['returned'] > 0 ? 'Retour client' : 'Utilisable' }}</div><div class="v">{{ $kg($reception['returned'] > 0 ? $reception['returned'] : $reception['usable']) }} kg</div></td>
            <td><div class="label" style="margin:0;">Poisson trié</div><div class="v" style="color:#059669;">{{ $kg($totalWeight) }} kg</div></td>
        </tr>
    </table>
    @php
        $pUsable = round(($reception['pct'])($totalWeight), 1);
        $pWaste = round(($reception['pct'])($reception['waste']), 1);
        $pReturn = round(($reception['pct'])($reception['returned']), 1);
        $pRest = max(0, 100 - $pUsable - $pWaste - $pReturn);
    @endphp
    <table class="bar" style="margin-top:6px;">
        <tr>
            @if($pUsable > 0)<td style="width:{{ $pUsable }}%; background:#10b981;"></td>@endif
            @if($pWaste > 0)<td style="width:{{ $pWaste }}%; background:#f87171;"></td>@endif
            @if($pReturn > 0)<td style="width:{{ $pReturn }}%; background:#fbbf24;"></td>@endif
            @if($pRest > 0.1)<td style="width:{{ $pRest }}%; background:#e2e8f0;"></td>@endif
        </tr>
    </table>
    <div style="font-size:8.5px; color:#64748b; margin-top:4px;">
        Trié {{ number_format($pUsable, 1, ',', ' ') }} % · Non utilisable {{ number_format($pWaste, 1, ',', ' ') }} %
        @if($pReturn > 0) · Retour {{ number_format($pReturn, 1, ',', ' ') }} %@endif
    </div>
    @endif

    {{-- Detail par type --}}
    <div class="label" style="margin-top:18px;">Détail du tri par type de poisson</div>
    <table class="lines">
        <thead>
            <tr>
                <th style="width:6%;" class="c">N°</th>
                <th>Type de poisson</th>
                <th class="r" style="width:22%;">Quantité (kg)</th>
                <th class="r" style="width:14%;">Part</th>
            </tr>
        </thead>
        <tbody>
            @forelse($lines as $i => $line)
            <tr class="{{ $i % 2 ? 'alt' : '' }}">
                <td class="c" style="color:#94a3b8;">{{ $i + 1 }}</td>
                <td><b>{{ $line['fish'] }}</b></td>
                <td class="r"><b>{{ $kg($line['quantity_kg']) }}</b></td>
                <td class="r" style="color:#64748b;">{{ number_format($line['share'], 1, ',', ' ') }} %</td>
            </tr>
            @empty
            <tr><td colspan="4" class="c" style="color:#94a3b8; padding:16px;">Aucune ligne de tri enregistrée.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- Totaux --}}
    <table style="margin-top:14px;">
        <tr>
            <td style="width:48%; vertical-align:top;">
                @if(!empty($processing->notes))
                <div class="notes">
                    <div class="label">Notes</div>
                    {{ $processing->notes }}
                </div>
                @endif
            </td>
            <td style="width:4%;"></td>
            <td style="vertical-align:top;">
                <table class="totals" style="border:1px solid #e2e8f0;">
                    <tr><td>Total poisson trié</td><td class="r"><b>{{ $kg($totalWeight) }} kg</b></td></tr>
                    <tr><td>Frais de traitement</td><td class="r">{{ $money($unitPrice) }} {{ $cur }} / kg</td></tr>
                    <tr><td>Total traitement{{ $tax > 0 ? ' HT' : '' }}<br><span style="font-size:8.5px; color:#64748b;">{{ $kg($totalWeight) }} kg × {{ $money($unitPrice) }} {{ $cur }}</span></td><td class="r"><b>{{ $money($subtotal) }} {{ $cur }}</b></td></tr>
                    @if($tax > 0)
                    <tr><td>TVA ({{ rtrim(rtrim(number_format($taxRate, 2, ',', ''), '0'), ',') }} %)</td><td class="r">{{ $money($tax) }} {{ $cur }}</td></tr>
                    @endif
                    <tr class="grand"><td>TOTAL À PAYER</td><td class="r">{{ $money($grandTotal) }} {{ $cur }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="sign">
        <tr>
            <td><div>Signature du client</div></td>
            <td><div>Cachet et signature</div></td>
        </tr>
    </table>
</div>

<div class="footer">
    <table>
        <tr>
            <td>Fiche de traitement {{ $processing->reference }} — document indépendant des frais de stockage / réfrigération.</td>
            <td class="r">Édité le {{ now()->format('d/m/Y à H:i') }}</td>
        </tr>
    </table>
</div>
</body>
</html>
