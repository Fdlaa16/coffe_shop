<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Struk Invoice - {{ $invoice->invoice_number }}</title>

    <style>
        /* Set page size & remove margins untuk engine PDF */
        @page {
            size: 80mm 200mm;
            /* 80mm x 200mm */
            margin: 0;
        }

        html,
        body {
            display: flex;
            justify-content: center;
        }

        * {
            box-sizing: border-box;
        }

        .receipt {
            width: 72mm;
            /* sedikit lebih kecil dari 80mm biar ada spasi kiri-kanan */
            padding: 4mm;
            margin: 0 auto;
            /* ini yang bikin struk ketengah */
            background: #fff;
        }

        .header {
            text-align: center;
            margin-bottom: 3mm;
        }

        .header,
        .footer {
            text-align: center;
        }

        .logo {
            display: block;
            margin: 0 auto 1mm;
            max-width: 18mm;
            height: auto;
        }

        .store-name {
            font-size: 4.5mm;
            font-weight: 700;
            line-height: 1;
        }

        .store-location {
            font-size: 3.2mm;
            line-height: 1;
            margin-top: 1mm;
        }

        .bill-type {
            font-size: 3.8mm;
            font-weight: 700;
            margin-top: 1mm;
        }

        .customer-info,
        .order-info {
            font-size: 3.2mm;
            line-height: 1.05;
            margin-bottom: 2mm;
        }

        .divider {
            border-top: 0.5px dashed #000;
            margin: 2mm 0;
        }

        .service-type {
            font-size: 3.4mm;
            margin: 1mm 0;
        }

        .items {
            margin-top: 2mm;
            font-size: 3.4mm;
        }

        .item {
            margin-bottom: 1mm;
        }

        .item-name {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            font-size: 3.6mm;
        }

        .item-details {
            font-size: 3.0mm;
            color: #333;
            margin-top: 0.2mm;
        }

        .subtotal-section {
            margin-top: 2mm;
            font-size: 3.2mm;
            margin-bottom: 2mm;
        }

        .subtotal-row {
            display: flex;
            justify-content: space-between;
            margin: 0.6mm 0;
        }

        .total-section {
            margin-top: 1mm;
            padding-top: 1mm;
            border-top: 0.5px dashed #000;
        }

        .total-row {
            display: flex;
            justify-content: space-between;
            font-size: 3.8mm;
            font-weight: 700;
        }

        .footer {
            text-align: center;
            margin-top: 3mm;
            font-size: 2.8mm;
            color: #444;
        }

        /* kecilkan jarak default agar muat panjang */
        p,
        div {
            margin: 0;
            padding: 0;
        }
    </style>
</head>

<body>
    <div class="receipt">
        <div class="header">
            @if (isset($logo))
                <img src="{{ $logo }}" class="logo" alt="Logo">
            @endif
            <div class="store-name">Papasans Coffee Shop</div>
            <div class="store-location">Gg. Mangga 1, RT.1/RW.7, Cukang Galih, Kec. Curug, Kabupaten Tangerang, Banten
                15810</div>
        </div>

        <div class="divider"></div>

        @if ($invoice->customer)
            <div class="customer-info">
                <div>Customer: {{ $invoice->customer->name ?? 'Guest' }}</div>
                @if ($invoice->customer->phone)
                    <div>Phone: {{ $invoice->customer->phone }}</div>
                @endif
            </div>
        @endif

        <div class="order-info">
            <div>Invoice: {{ $invoice->invoice_number }}</div>
            <div>Tanggal: {{ \Carbon\Carbon::parse($invoice->invoice_date)->format('d/m/Y H:i') }}</div>
            <div>Status: {{ ucfirst($invoice->status) }}</div>
        </div>

        <div class="divider"></div>

        <div class="items">
            @forelse($invoice->invoiceItems as $item)
                <div class="item">
                    <div class="item-name">
                        <span>{{ $item->menu_name }}</span>
                        <span>Rp{{ number_format($item->total_price, 0, ',', '.') }}</span>
                    </div>
                    <div class="item-details">
                        {{ $item->qty }} x Rp{{ number_format($item->unit_price, 0, ',', '.') }}
                        @if ($item->size)
                            - {{ $item->size }}
                        @endif
                    </div>
                    @if ($item->notes)
                        <div class="item-details">Note: {{ $item->notes }}</div>
                    @endif
                </div>
            @empty
                <div class="item">
                    <div class="item-name">
                        <span>Tidak ada item</span>
                        <span>Rp0</span>
                    </div>
                </div>
            @endforelse
        </div>

        <div class="divider"></div>

        <div class="subtotal-section">
            <div class="subtotal-row">
                <span>Subtotal</span>
                <span>Rp{{ number_format($invoice->subtotal, 0, ',', '.') }}</span>
            </div>
            <div class="subtotal-row">
                <span>VAT, 10%</span>
                <span>Rp{{ number_format($invoice->tax, 0, ',', '.') }}</span>
            </div>
        </div>

        <div class="total-section">
            <div class="total-row">
                <span>Jumlah yang harus dibayar</span>
                <span>Rp{{ number_format($invoice->total_net ?? $total, 0, ',', '.') }}</span>
            </div>
        </div>

        <div class="footer">
            <div>Terima kasih atas pembelian Anda!</div>
            <div style="margin-top:2mm; font-size:2.6mm;">{{ $generated_at ?? now()->format('d/m/Y H:i:s') }}</div>
            @if ($invoice->expired_date)
                <div style="font-size:2.6mm;">Exp:
                    {{ \Carbon\Carbon::parse($invoice->expired_date)->format('d/m/Y H:i') }}</div>
            @endif
        </div>
    </div>
</body>

</html>
