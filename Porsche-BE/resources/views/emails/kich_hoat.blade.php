<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kích hoạt tài khoản</title>
</head>
<body style="margin: 0; padding: 24px; background-color: #f4f4f4; font-family: Arial, sans-serif; color: #222;">
    <main style="max-width: 560px; margin: 0 auto; padding: 32px; background-color: #fff; border-radius: 8px;">
        <h1 style="margin-top: 0; color: #111;">Kích hoạt tài khoản Porsche Workshop Portal</h1>
        <p>Xin chào {{ $ho_ten }},</p>
        <p>Vui lòng nhập mã dưới đây trong ứng dụng để kích hoạt tài khoản của bạn:</p>
        <p style="margin: 28px 0; text-align: center; font-size: 32px; font-weight: bold; letter-spacing: 8px; color: #b00020;">
            {{ $ma }}
        </p>
        @if ($vouchers->isNotEmpty())
            <h2 style="margin-top: 32px; color: #111;">Voucher dành cho bạn</h2>
            <p>Sau khi kích hoạt tài khoản, bạn có thể sử dụng các mã dưới đây khi đặt hàng:</p>
            <ul style="padding-left: 20px;">
                @foreach ($vouchers as $voucher)
                    <li style="margin-bottom: 12px;">
                        <strong>{{ $voucher->code }}</strong> —
                        Giảm
                        @if ($voucher->loai === 'phan_tram')
                            {{ $voucher->gia_tri }}%
                            @if ($voucher->giam_toi_da)
                                (tối đa {{ number_format($voucher->giam_toi_da, 0, ',', '.') }}₫)
                            @endif
                        @else
                            {{ number_format($voucher->gia_tri, 0, ',', '.') }}₫
                        @endif
                        @if ((float) $voucher->gia_tri_don_hang_toi_thieu > 0)
                            cho đơn từ {{ number_format($voucher->gia_tri_don_hang_toi_thieu, 0, ',', '.') }}₫
                        @endif
                    </li>
                @endforeach
            </ul>
            <p>Bạn cũng có thể xem lại các voucher này trong mục Voucher ở hồ sơ cá nhân.</p>
        @endif
        <p>Nếu bạn không thực hiện đăng ký này, vui lòng bỏ qua email.</p>
        <p>Trân trọng,<br>Porsche Workshop Portal</p>
    </main>
</body>
</html>
