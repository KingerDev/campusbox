<!DOCTYPE html>
<html lang="sk">
<head>
    <meta charset="utf-8">
    <title>Výpis {{ $monthLabel }} · {{ $business->name }}</title>
    <style>
        body { font-family: Archivo, system-ui, sans-serif; color: #2A1D16; margin: 40px; font-size: 14px; }
        h1 { font-size: 28px; margin: 0 0 4px; letter-spacing: -.02em; }
        .muted { color: #77604F; }
        .kpi { display: flex; gap: 32px; margin: 24px 0; padding: 16px 0; border-top: 2px solid #2A1D16; border-bottom: 2px solid #2A1D16; }
        .kpi b { display: block; font-size: 22px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 8px 6px; border-bottom: 1px solid #EAD6BE; }
        th { font-size: 12px; text-transform: uppercase; letter-spacing: .05em; color: #77604F; }
        td.num, th.num { text-align: right; }
        @media print { .noprint { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
    <button class="noprint" onclick="window.print()" style="float:right;padding:10px 16px;border-radius:20px;border:0;background:#DB3B22;color:#fff;font-weight:700;cursor:pointer">Uložiť ako PDF</button>
    <h1>Výpis za {{ $monthLabel }}</h1>
    <div class="muted">{{ $business->name }} · IČO {{ $business->ico }} · {{ $business->address }}</div>
    <div class="kpi">
        <div>Tržby<b>{{ number_format($stats['sales'], 2, ',', ' ') }} €</b></div>
        <div>Provízia CampusBox<b>−{{ number_format($stats['commission'], 2, ',', ' ') }} €</b></div>
        <div>Na výplatu<b>{{ number_format($stats['net'], 2, ',', ' ') }} €</b></div>
        <div>Zachránené jedlá<b>{{ $stats['rescued'] }}</b></div>
    </div>
    <table>
        <thead><tr><th>Dátum</th><th>Kód</th><th>Obsah</th><th class="num">Suma</th></tr></thead>
        <tbody>
        @forelse ($orders as $order)
            <tr>
                <td>{{ $order->pickup_date->format('j. n. Y') }}</td>
                <td>{{ $order->code }}</td>
                <td>{{ $order->itemsLabel() }}</td>
                <td class="num">{{ number_format((float) $order->total, 2, ',', ' ') }} €</td>
            </tr>
        @empty
            <tr><td colspan="4" class="muted">V tomto mesiaci nie sú žiadne vydané objednávky.</td></tr>
        @endforelse
        </tbody>
    </table>
</body>
</html>
