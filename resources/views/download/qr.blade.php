<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            text-align: center;
            padding: 20px;
        }

        .header {
            margin-bottom: 20px;
        }

        .logo {
            max-width: 120px;
            margin-bottom: 10px;
        }

        .qr {
            margin: 20px auto;
            display: inline-block;
            border: 5px solid #000;
            padding: 10px;
            border-radius: 10px;
        }

        .info {
            margin-top: 15px;
            font-size: 16px;
        }

        .table-number {
            font-weight: bold;
            font-size: 18px;
            margin-top: 5px;
        }

        .footer {
            margin-top: 40px;
            font-size: 12px;
            color: #777;
        }
    </style>
</head>

<body>
    <div class="header">
        @if ($logo)
            <img src="{{ $logo }}" alt="Logo" class="logo">
        @endif
        <h2>QR Code Pemesanan</h2>
        <div class="table-number">Meja {{ $table->table_number }}</div>
    </div>

    <div class="qr">
        <img src="{{ $qr_code }}" alt="QR Code">
    </div>

    <div class="info">
        <p>Scan QR Code ini untuk melakukan pemesanan.</p>
    </div>

    <div class="footer">
        <p>Generated at: {{ $generated_at }}</p>
    </div>
</body>

</html>
