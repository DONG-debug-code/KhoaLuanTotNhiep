<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ưu đãi khách hàng thân thiết</title>
</head>
<body style="margin: 0; padding: 24px; background-color: #f4f4f4; font-family: Arial, sans-serif; color: #222;">
    <main style="max-width: 560px; margin: 0 auto; padding: 32px; background-color: #fff; border-radius: 8px;">
        <h1 style="margin-top: 0; color: #111;">Cảm ơn bạn đã đồng hành cùng Porsche Workshop</h1>
        <p>Xin chào {{ $ho_ten }},</p>
        <p>Tổng giá trị giao dịch của bạn đã đạt từ {{ number_format($nguong, 0, ',', '.') }}₫. Chúng tôi xin gửi tặng các voucher sau:</p>
        <ul style="padding-left: 20px;">
            @foreach ($vouchers as $voucher)
                <li style="margin-bottom: 12px;">
                    <strong>{{ $voucher->code }}</strong> — giảm {{ $voucher->gia_tri }}%
                    @if ($voucher->gia_san_pham_toi_da)
                        cho sản phẩm có giá dưới {{ number_format($voucher->gia_san_pham_toi_da, 0, ',', '.') }}₫
                    @endif
                </li>
            @endforeach
        </ul>
        <p>Voucher đã được lưu trong mục Voucher ở hồ sơ cá nhân của bạn và có thể chọn khi thanh toán.</p>
        <p>Trân trọng,<br>Porsche Workshop Portal</p>
    </main>
</body>
</html>
