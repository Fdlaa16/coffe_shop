<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: #0f0f23;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .qr-card {
            width: 400px;
            background: linear-gradient(145deg, #1a1a2e, #16213e);
            border-radius: 24px;
            padding: 0;
            box-shadow:
                0 25px 50px -12px rgba(0, 0, 0, 0.6),
                0 0 0 1px rgba(255, 255, 255, 0.05),
                inset 0 1px 0 rgba(255, 255, 255, 0.1);
            overflow: hidden;
            position: relative;
        }

        .qr-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, #667eea, #764ba2, #f093fb, #f5576c, #4facfe, #00f2fe);
            background-size: 300% 100%;
            animation: gradientShift 6s ease infinite;
        }

        @keyframes gradientShift {

            0%,
            100% {
                background-position: 0% 50%;
            }

            50% {
                background-position: 100% 50%;
            }
        }

        .header {
            text-align: center;
            padding: 40px 30px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            position: relative;
            overflow: hidden;
        }

        .header::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            opacity: 0.3;
        }

        .logo {
            width: 64px;
            height: 64px;
            border-radius: 16px;
            border: 3px solid rgba(255, 255, 255, 0.2);
            margin: 0 auto 16px;
            object-fit: cover;
            position: relative;
            z-index: 2;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
        }

        .brand-name {
            color: white;
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 4px;
            position: relative;
            z-index: 2;
            letter-spacing: -0.025em;
        }

        .subtitle {
            color: rgba(255, 255, 255, 0.8);
            font-size: 13px;
            font-weight: 500;
            position: relative;
            z-index: 2;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .content {
            padding: 30px;
        }

        .table-badge {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 16px 24px;
            border-radius: 16px;
            text-align: center;
            margin-bottom: 30px;
            box-shadow: 0 8px 24px rgba(102, 126, 234, 0.3);
        }

        .table-number {
            font-size: 32px;
            font-weight: 900;
            margin-bottom: 4px;
            letter-spacing: -0.02em;
        }

        .table-label {
            font-size: 12px;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 0.1em;
            font-weight: 600;
        }

        .qr-container {
            text-align: center;
            margin-bottom: 24px;
        }

        .qr-wrapper {
            display: inline-block;
            padding: 16px;
            background: white;
            border-radius: 20px;
            box-shadow:
                0 20px 40px rgba(0, 0, 0, 0.1),
                0 0 0 1px rgba(0, 0, 0, 0.05);
            margin-bottom: 16px;
        }

        .qr-code {
            width: 200px;
            height: 200px;
            border-radius: 8px;
            display: block;
        }

        .scan-text {
            color: #e2e8f0;
            font-size: 16px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .scan-subtitle {
            color: #94a3b8;
            font-size: 13px;
            font-weight: 400;
            line-height: 1.4;
        }

        .url-display {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            padding: 12px 16px;
            margin: 20px 0;
            font-family: 'SF Mono', Monaco, 'Cascadia Code', 'Roboto Mono', Consolas, 'Courier New', monospace;
            font-size: 11px;
            color: #cbd5e1;
            word-break: break-all;
            line-height: 1.4;
        }

        .instructions {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            border-radius: 16px;
            padding: 20px;
            margin: 20px 0;
        }

        .instructions-title {
            color: #10b981;
            font-size: 14px;
            font-weight: 700;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
        }

        .instructions-title::before {
            content: "📱";
            margin-right: 8px;
            font-size: 16px;
        }

        .instructions-list {
            color: #a7f3d0;
            font-size: 12px;
            line-height: 1.5;
        }

        .instructions-list ol {
            margin: 0;
            padding-left: 16px;
        }

        .instructions-list li {
            margin-bottom: 4px;
        }

        .footer {
            background: rgba(0, 0, 0, 0.2);
            color: #64748b;
            padding: 20px 30px;
            text-align: center;
            font-size: 11px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }

        .footer-line {
            margin-bottom: 3px;
        }

        .footer-line:last-child {
            margin-bottom: 0;
        }

        .generated-time {
            color: #94a3b8;
            font-weight: 500;
        }

        /* Print Styles */
        @media print {
            body {
                background: white;
                padding: 0;
            }

            .qr-card {
                width: 100%;
                max-width: 400px;
                box-shadow: none;
                border: 1px solid #e5e7eb;
            }
        }

        /* Alternative Minimal Style */
        .minimal-mode {
            background: white;
            color: #1f2937;
        }

        .minimal-mode .qr-card {
            background: white;
            border: 1px solid #e5e7eb;
        }

        .minimal-mode .header {
            background: #f8fafc;
            color: #1f2937;
        }

        .minimal-mode .brand-name {
            color: #1f2937;
        }

        .minimal-mode .subtitle {
            color: #6b7280;
        }

        .minimal-mode .scan-text {
            color: #374151;
        }

        .minimal-mode .scan-subtitle {
            color: #6b7280;
        }
    </style>
</head>

<body>
    <div class="qr-card">
        <!-- Header -->
        <div class="header">
            @if ($logo)
                <img src="{{ $logo }}" alt="Logo" class="logo">
            @endif
            <div class="brand-name">Papa Sans Restaurant</div>
            <div class="subtitle">QR Menu Access</div>
        </div>

        <!-- Content -->
        <div class="content">
            <!-- Table Badge -->
            <div class="table-badge">
                <div class="table-number">{{ $table->table_number ?? 'N/A' }}</div>
                <div class="table-label">Table Number</div>
            </div>

            <!-- QR Code -->
            <div class="qr-container">
                <div class="qr-wrapper">
                    @if ($qr_code)
                        <img src="{{ $qr_code }}" alt="QR Code" class="qr-code">
                    @else
                        <div
                            style="width: 200px; height: 200px; background: #f3f4f6; border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #9ca3af; font-size: 14px;">
                            QR Code Unavailable
                        </div>
                    @endif
                </div>

                <div class="scan-text">Scan to Order</div>
                <div class="scan-subtitle">Point your camera at the QR code above</div>
            </div>

            <!-- URL Display -->
            <div class="url-display">{{ $qr_data }}</div>

            <!-- Instructions -->
            <div class="instructions">
                <div class="instructions-title">How to Use</div>
                <div class="instructions-list">
                    <ol>
                        <li>Open camera app on your phone</li>
                        <li>Point camera at QR code</li>
                        <li>Tap the notification that appears</li>
                        <li>Browse menu and place your order</li>
                        <li>Enjoy your meal!</li>
                    </ol>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="footer">
            <div class="footer-line"><strong>Papa Sans Restaurant</strong></div>
            <div class="footer-line generated-time">Generated: {{ $generated_at }}</div>
            <div class="footer-line">Scan • Order • Enjoy</div>
        </div>
    </div>
</body>

</html>
