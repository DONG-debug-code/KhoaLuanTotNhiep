<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Xác nhận thanh toán</title>
</head>
<body style="margin: 0; padding: 24px; background-color: #f4f4f4; font-family: Arial, sans-serif; color: #222;">
    <main style="max-width: 560px; margin: 0 auto; padding: 32px; background-color: #fff; border-radius: 8px;">
        <h1 style="margin-top: 0; color: #111;">Thanh toán thành công</h1>
        <p>Xin chào {{ $customerName }},</p>
        <p>Hệ thống đã ghi nhận thanh toán cho đơn hàng <strong>#{{ $orderId }}</strong>.</p>
        <p>Số tiền: <strong>{{ number_format($amount, 0, ',', '.') }}₫</strong></p>
        <p>Mã giao dịch: <strong>{{ $paymentIntentId }}</strong></p>
        <p>Chúng tôi sẽ tiếp tục xử lý đơn hàng và cập nhật tình trạng giao nhận cho bạn.</p>
        <p>Trân trọng,<br>Porsche Workshop Portal</p>
    </main>
</body>
</html>
