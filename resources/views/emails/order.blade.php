<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>Order Berhasil - PAPASANS Coffee Shop</title>
</head>

<body style="margin: 0; padding: 0; background-color: #f5f5f5; font-family: Arial, sans-serif; color: #333;">
    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%"
        style="max-width: 600px; margin: auto;">
        <tr>
            <td align="center" style="padding: 30px;">
                <!-- CARD -->
                <table width="100%" cellpadding="0" cellspacing="0"
                    style="background-color: #ffffff; border-radius: 12px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1); overflow: hidden;">
                    <tr>
                        <td align="center" style="padding: 30px 20px 10px;">
                            <img src="{{ $message->embed(public_path('images/logo/papasans.png')) }}"
                                alt="Logo PAPASANS" style="width: 100px;">
                            <hr
                                style="border: none; height: 1px; background-color: #ddd; width: 80%; margin: 15px auto;">
                        </td>
                    </tr>

                    <tr>
                        <td style="padding: 0 20px 20px 20px; color: #333;">
                            <h2 style="color: #6C63FF; text-align: center; margin: 0 0 20px 0;">
                                🎉 Pesanan Kamu Berhasil Dilakukan!
                            </h2>

                            <p>Halo <strong>{{ $customer->name }}</strong>,</p>

                            <p>Terima kasih telah melakukan pemesanan di
                                <strong>PAPASANS Coffee Shop</strong>.
                            </p>

                            <!-- Detail Pesanan -->
                            <div style="background-color: #f8f9fa; border-radius: 8px; padding: 20px; margin: 20px 0;">
                                <h3 style="color: #333; margin-top: 0;">Detail Pesanan:</h3>
                                <table style="width: 100%; border-collapse: collapse;">
                                    <tr>
                                        <td style="padding: 8px 0; border-bottom: 1px solid #eee;">
                                            <strong>Tanggal Pesanan:</strong>
                                        </td>
                                        <td style="padding: 8px 0; border-bottom: 1px solid #eee;">
                                            {{ \Carbon\Carbon::parse($order->created_at)->translatedFormat('d F Y') }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 8px 0; border-bottom: 1px solid #eee;">
                                            <strong>Status:</strong>
                                        </td>
                                        <td style="padding: 8px 0; border-bottom: 1px solid #eee;">
                                            Lunas
                                        </td>
                                    </tr>
                                    <tr>
                                        <td style="padding: 8px 0;"><strong>Total Pembayaran:</strong></td>
                                        <td style="padding: 8px 0;">
                                            Rp {{ number_format($order->total_net ?? 0, 0, ',', '.') }}
                                        </td>
                                    </tr>
                                </table>
                            </div>

                            <!-- Ringkasan Item Pesanan -->
                            <div style="margin: 20px 0;">
                                <h3 style="color: #333;">Ringkasan Item:</h3>
                                <table style="width: 100%; border-collapse: collapse; font-size: 14px;">
                                    <thead>
                                        <tr style="background-color: #f0f0f0;">
                                            <th align="left" style="padding: 8px;">Menu</th>
                                            <th align="center" style="padding: 8px;">Qty</th>
                                            <th align="right" style="padding: 8px;">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($order->orderItems as $item)
                                            <tr>
                                                <td style="padding: 6px;">{{ $item->menu_name }}</td>
                                                <td align="center" style="padding: 6px;">{{ $item->qty }}</td>
                                                <td align="right" style="padding: 6px;">
                                                    Rp {{ number_format($item->total_price, 0, ',', '.') }}
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <p style="color: #555; font-size: 14px;">Jika ada pertanyaan, silakan hubungi tim
                                kami melalui email ini atau nomor telepon di website resmi kami.</p>

                            <p style="margin-top: 30px;">
                                Salam hangat,<br>
                                <strong>PAPASANS Coffee Shop</strong>
                            </p>

                            <hr style="border: none; height: 1px; background-color: #eee; margin: 30px 0;">

                            <p style="font-size: 12px; color: #999; text-align: center;">
                                Email ini dikirim secara otomatis, mohon tidak membalas email ini.<br>
                                ©{{ date('Y') }} PAPASANS Coffee Shop Management System. All Rights Reserved
                            </p>
                        </td>
                    </tr>
                </table>
                <!-- END CARD -->
            </td>
        </tr>
    </table>
</body>

</html>
