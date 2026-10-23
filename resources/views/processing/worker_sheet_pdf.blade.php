<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
@php
    $kg = fn ($v) => number_format((float) $v, 2, ',', ' ');
@endphp
<style>
    @page { margin: 0; size: A4 portrait; }
    * { margin: 0; padding: 0; box-sizing: border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; padding: 34px 40px; }
    table { border-collapse: collapse; width: 100%; }
    h1 { font-size: 18px; letter-spacing: 1px; }
    .sub { font-size: 11px; color: #4b5563; margin-top: 3px; }
    .warn { margin-top: 10px; border: 1.5px solid #dc2626; color: #b91c1c; padding: 6px 10px; font-size: 10px; font-weight: bold; }
    .sheet { margin-top: 14px; }
    .sheet th { background: #111827; color: #fff; font-size: 10px; text-transform: uppercase; padding: 8px 10px; text-align: left; border: 1px solid #111827; }
    .sheet td { padding: 9px 10px; border: 1px solid #9ca3af; }
    .cart { font-size: 15px; font-weight: bold; text-align: center; vertical-align: middle; background: #f3f4f6; }
    .r { text-align: right; }
    .sheet th.r { text-align: right; }
    .c, .sheet th.c { text-align: center; }
    .subtotal td { background: #f9fafb; font-size: 10px; color: #4b5563; }
    .total td { background: #111827; color: #fff; font-weight: bold; font-size: 13px; }
    .box { display: inline-block; width: 14px; height: 14px; border: 1.5px solid #111827; }
</style>
</head>
<body>

<table>
    <tr>
        <td>
            <h1>BON DE CHARGEMENT</h1>
            <div class="sub">{{ $processing->reference }} — {{ $processing->client->name ?? '-' }}</div>
        </td>
        <td class="r" style="vertical-align:top; font-size:11px; color:#4b5563;">{{ now()->format('d/m/Y') }}</td>
    </tr>
</table>

@php $overflowTotal = $rows->sum(fn ($r) => $r['overflow'] ?? 0); @endphp
@unless($distributionValidated)
<div class="warn">Répartition non validée — document provisoire.</div>
@endunless
@if($overflowTotal > 0)
<div class="warn">{{ $kg($overflowTotal) }} kg dépassent la capacité des chariots — ajouter un chariot.</div>
@endif

<table class="sheet">
    <thead>
        <tr>
            <th style="width:22%;" class="c">Chariot</th>
            <th>Poisson</th>
            <th class="r" style="width:24%;">Quantité</th>
            <th class="c" style="width:12%;">Fait</th>
        </tr>
    </thead>
    <tbody>
        @foreach($carts as $cart)
            @foreach($cart['lines'] as $i => $line)
            <tr>
                @if($i === 0)
                <td class="cart" rowspan="{{ $cart['lines']->count() + ($cart['lines']->count() > 1 ? 1 : 0) }}">{{ $cart['code'] }}</td>
                @endif
                <td><b>{{ $line['fish'] }}</b></td>
                <td class="r"><b>{{ $kg($line['quantity_kg']) }} kg</b></td>
                <td class="c"><span class="box"></span></td>
            </tr>
            @endforeach
            @if($cart['lines']->count() > 1)
            <tr class="subtotal">
                <td>Total chariot</td>
                <td class="r"><b>{{ $kg($cart['total']) }} kg</b></td>
                <td></td>
            </tr>
            @endif
        @endforeach
        <tr class="total">
            <td class="c">{{ $carts->count() }} chariot(s)</td>
            <td>TOTAL</td>
            <td class="r">{{ $kg($totalWeight) }} kg</td>
            <td></td>
        </tr>
    </tbody>
</table>

</body>
</html>
