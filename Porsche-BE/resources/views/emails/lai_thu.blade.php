<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $tieuDe }}</title>
</head>
<body style="margin: 0; padding: 24px; background-color: #f4f4f4; font-family: Arial, sans-serif; color: #222;">
    <main style="max-width: 560px; margin: 0 auto; padding: 32px; background-color: #fff; border-radius: 8px;">
        <h1 style="margin-top: 0; color: #111; font-size: 22px;">{{ $tieuDe }}</h1>
        <p>Xin chào {{ $laiThu->ho_ten }},</p>
        @if ($loiNhan !== '')
            <p>{{ $loiNhan }}</p>
        @else
            <p>Cảm ơn bạn đã đăng ký lái thử và nhận tư vấn mua xe. Thông tin lịch lái thử của bạn như sau:</p>
        @endif
        <table style="width: 100%; border-collapse: collapse; margin: 20px 0;">
            <tr><td style="padding: 8px 0; color: #777; width: 36%;">Mã đăng ký</td><td style="padding: 8px 0; font-weight: bold;">{{ $laiThu->ma_lai_thu }}</td></tr>
            @if (!empty($laiThu->dong_xe))
                <tr><td style="padding: 8px 0; color: #777;">Dòng xe</td><td style="padding: 8px 0;">{{ $laiThu->dong_xe }}</td></tr>
            @endif
            <tr><td style="padding: 8px 0; color: #777;">Ngày</td><td style="padding: 8px 0; font-weight: bold;">{{ \Carbon\Carbon::parse($laiThu->ngay_hen)->format('d/m/Y') }}</td></tr>
            <tr><td style="padding: 8px 0; color: #777;">Giờ</td><td style="padding: 8px 0; font-weight: bold;">{{ $laiThu->gio_hen }}</td></tr>
            <tr><td style="padding: 8px 0; color: #777;">Địa điểm</td><td style="padding: 8px 0;">{{ $laiThu->dia_diem }}</td></tr>
            <tr><td style="padding: 8px 0; color: #777;">Hotline</td><td style="padding: 8px 0;">{{ config('shop.hotline') }}</td></tr>
        </table>
        <p>Vui lòng mang theo giấy phép lái xe và căn cước công dân khi đến lái thử.</p>
        <p>Nếu cần đổi giờ hoặc có câu hỏi, bạn chỉ cần trả lời trực tiếp email này, nhân viên tư vấn sẽ phản hồi cho bạn.</p>
        <p>Trân trọng,<br>Porsche Workshop Portal</p>
    </main>
</body>
</html>
