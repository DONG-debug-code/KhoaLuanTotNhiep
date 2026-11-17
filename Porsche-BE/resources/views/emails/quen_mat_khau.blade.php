<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Khôi phục mật khẩu</title>
</head>
<body style="margin: 0; padding: 24px; background-color: #f4f4f4; font-family: Arial, sans-serif; color: #222;">
    <main style="max-width: 560px; margin: 0 auto; padding: 32px; background-color: #fff; border-radius: 8px;">
        <h1 style="margin-top: 0; color: #111;">Khôi phục mật khẩu Porsche Workshop</h1>
        <p>Yêu cầu đặt lại mật khẩu cho tài khoản {{ $email }} đã được gửi.</p>
        <p>Nhập mã OTP dưới đây trên trang đặt lại mật khẩu. Mã có hiệu lực trong {{ $phut }} phút:</p>
        <p style="margin: 28px 0; text-align: center; font-size: 32px; font-weight: bold; letter-spacing: 8px; color: #b00020;">
            {{ $otp }}
        </p>
        <p>Không chia sẻ mã này với bất kỳ ai.</p>
        <p>Nếu bạn không yêu cầu đặt lại mật khẩu, vui lòng bỏ qua email này.</p>
        <p>Trân trọng,<br>Porsche Workshop Portal</p>
    </main>
</body>
</html>
