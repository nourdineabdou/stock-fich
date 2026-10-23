<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
@php
    $cur = $settings['currency'] ?? 'MRU';
    $money = fn ($v) => number_format((float) $v, 2, ',', ' ');
    $first = $payments->first();
    $total = $payments->sum('amount');
@endphp
<style>
    @page { margin: 0; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #1e293b; }
    table { border-collapse: collapse; width: 100%; }
    .band td { background: #064e3b; color: #fff; padding: 16px 26px; vertical-align: top; }
    .company { font-size: 15px; font-weight: bold; color: #6ee7b7; }
    .muted-light { font-size: 8.5px; color: #d1fae5; line-height: 1.5; }
    .title { font-size: 17px; font-weight: bold; letter-spacing: 1px; text-align: right; }
    .sub { font-size: 9px; color: #d1fae5; text-align: right; line-height: 1.6; }
    .wrap { padding: 16px 26px; }
    .label { font-size: 7.5px; text-transform: uppercase; letter-spacing: 1px; color: #64748b; margin-bottom: 3px; }
    .amount { border: 2px solid #10b981; background: #ecfdf5; text-align: center; padding: 10px; }
    .amount .v { font-size: 22px; font-weight: bold; color: #047857; }
    .lines th { background: #f1f5f9; font-size: 8px; text-transform: uppercase; color: #475569; padding: 6px 8px; text-align: left; }
    .lines td { padding: 6px 8px; border-bottom: 1px solid #e2e8f0; }
    .r { text-align: right; }
    .lines th.r { text-align: right; }
    .sign td { width: 50%; padding-top: 30px; }
    .sign div { border-top: 1px solid #94a3b8; margin: 0 16px; padding-top: 4px; text-align: center; font-size: 8.5px; color: #64748b; }
</style>
</head>
<body>
<table class="band">
    <tr>
        <td style="width:55%;">
            <div class="company">{{ $settings['company_name'] }}</div>
            <div class="muted-light">{{ $settings['company_address'] }}<br>{{ $settings['company_phone'] }}</div>
        </td>
        <td>
            <div class="title">REÇU DE PAIEMENT</div>
            <div class="sub">N° <b style="color:#fff;">{{ $receipt }}</b><br>Date : {{ $first->paid_at->format('d/m/Y') }}</div>
        </td>
    </tr>
</table>

<div class="wrap">
    <table>
        <tr>
            <td style="width:58%; vertical-align:top;">
                <div class="label">Reçu de</div>
                <div style="font-size:13px; font-weight:bold;">{{ $client->name }}</div>
                @if($client->phone)<div>Tél. : {{ $client->phone }}</div>@endif
                <div style="margin-top:6px;">
                    Mode : <b>{{ $first->method_label }}</b>@if($first->reference) · Réf. {{ $first->reference }}@endif
                </div>
                <div>Objet : {{ $invoiceType === 'storage' ? 'Frais de froid / stockage' : 'Frais de traitement' }}</div>
            </td>
            <td style="vertical-align:top;">
                <div class="amount">
                    <div class="label" style="margin:0;">Montant reçu</div>
                    <div class="v">{{ $money($total) }} {{ $cur }}</div>
                </div>
            </td>
        </tr>
    </table>

    <table class="lines" style="margin-top:12px;">
        <thead>
            <tr>
                <th>Facture</th>
                <th class="r">Montant facture</th>
                <th class="r">Payé ce jour</th>
                <th class="r">Reste sur la facture</th>
            </tr>
        </thead>
        <tbody>
            @foreach($payments as $p)
            <tr>
                <td><b>{{ $p->invoice->invoice_number }}</b> <span style="color:#64748b;">du {{ $p->invoice->invoice_date->format('d/m/Y') }}</span></td>
                <td class="r">{{ $money($p->invoice->total) }}</td>
                <td class="r"><b>{{ $money($p->amount) }}</b></td>
                <td class="r">{{ $money($p->invoice->balance) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <table style="margin-top:8px;">
        <tr>
            <td style="color:#64748b;">Encaissé par : {{ $first->receiver->name ?? '—' }}</td>
            <td class="r"><b>Reste dû par le client ({{ $invoiceType === 'storage' ? 'froid' : 'traitement' }}) : {{ $money($remaining) }} {{ $cur }}</b></td>
        </tr>
    </table>

    <table class="sign">
        <tr>
            <td><div>Signature du client</div></td>
            <td><div>Cachet et signature du comptable</div></td>
        </tr>
    </table>
</div>
</body>
</html>
