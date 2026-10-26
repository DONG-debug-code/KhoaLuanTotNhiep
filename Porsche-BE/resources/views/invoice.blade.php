<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8">
<style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
    h1 { font-size: 20px; margin: 0; text-align: center; letter-spacing: 1px; }
    .sub { text-align: center; color: #555; margin-bottom: 14px; }
    table { width: 100%; border-collapse: collapse; }
    .box td { vertical-align: top; width: 50%; padding: 8px; border: 1px solid #bbb; }
    .box h3 { margin: 0 0 4px; font-size: 12px; text-transform: uppercase; }
    .items th, .items td { border: 1px solid #bbb; padding: 5px; }
    .items th { background: #eee; }
    .r { text-align: right; }
    .c { text-align: center; }
    .info td { padding: 2px 4px; }
    .info td:first-child { width: 38%; color: #555; }
    .total td { padding: 3px 5px; }
    .grand td { font-weight: bold; font-size: 13px; border-top: 2px solid #111; }
    .sign td { text-align: center; padding-top: 24px; height: 90px; }
    .gap { height: 10px; }
</style>
</head>
<body>
@php
    $money = fn ($v) => number_format((float) $v, 0, ',', '.') . ' ₫';
    $t = $order['thoi_gian'];
    $s = $order['tong_ket'];
@endphp
<h1>{{ $order['ma_hoa_don'] ? 'HÓA ĐƠN BÁN HÀNG' : 'PHIẾU ĐƠN HÀNG' }}</h1>
<div class="sub">
    Mã đơn: <b>{{ $order['ma_don_hang'] }}</b>
    @if($order['ma_hoa_don']) &nbsp;|&nbsp; Số hóa đơn: <b>{{ $order['ma_hoa_don'] }}</b> @endif
    &nbsp;|&nbsp; Trạng thái: {{ $order['trang_thai_text'] }}
</div>

<table class="box">
<tr>
    <td>
        <h3>Bên bán</h3>
        <b>{{ $order['ben_ban']['ten'] }}</b><br>
        Địa chỉ: {{ $order['ben_ban']['dia_chi'] }}<br>
        @if($order['ben_ban']['ma_so_thue']) MST: {{ $order['ben_ban']['ma_so_thue'] }}<br> @endif
        @if($order['ben_ban']['so_dien_thoai']) SĐT: {{ $order['ben_ban']['so_dien_thoai'] }}<br> @endif
        Email: {{ $order['ben_ban']['email'] }}
    </td>
    <td>
        <h3>Bên mua</h3>
        <b>{{ $order['ben_mua']['ho_ten'] }}</b><br>
        Email: {{ $order['ben_mua']['email'] }}<br>
        SĐT: {{ $order['ben_mua']['so_dien_thoai'] }}<br>
        Địa chỉ: {{ $order['ben_mua']['dia_chi'] }}
    </td>
</tr>
</table>
<div class="gap"></div>

<table class="box">
<tr>
    <td>
        <h3>Thời gian</h3>
        <table class="info">
            <tr><td>Đặt hàng</td><td>{{ $t['dat_hang'] ?? '—' }}</td></tr>
            <tr><td>Xác nhận đơn</td><td>{{ $t['xac_nhan'] ?? '—' }}</td></tr>
            <tr><td>Thanh toán</td><td>{{ $t['thanh_toan'] ?? 'Chưa thanh toán' }}</td></tr>
            <tr><td>Hoàn thành</td><td>{{ $t['hoan_thanh'] ?? '—' }}</td></tr>
            <tr><td>Nhân viên xác nhận</td><td>{{ $order['nhan_vien_xac_nhan']['ho_ten'] ?? '—' }}</td></tr>
        </table>
    </td>
    <td>
        <h3>Thanh toán &amp; nhận hàng</h3>
        <table class="info">
            <tr><td>Phương thức TT</td><td>{{ $order['thanh_toan']['phuong_thuc_text'] }}</td></tr>
            <tr><td>Tình trạng TT</td><td>{{ $order['thanh_toan']['trang_thai'] }}</td></tr>
            @if($order['thanh_toan']['ma_giao_dich'])
            <tr><td>Mã giao dịch</td><td>{{ $order['thanh_toan']['ma_giao_dich'] }} ({{ $order['thanh_toan']['ngan_hang'] }})</td></tr>
            @endif
            <tr><td>Hình thức nhận</td><td>{{ $order['nhan_hang']['hinh_thuc_text'] }}</td></tr>
            @if($order['nhan_hang']['lich_lap_dat'])
            <tr><td>Lịch lắp đặt</td><td>{{ $order['nhan_hang']['lich_lap_dat']['ma_dat_lich'] }} – {{ $order['nhan_hang']['lich_lap_dat']['gio'] }} {{ $order['nhan_hang']['lich_lap_dat']['ngay'] }}</td></tr>
            @endif
        </table>
    </td>
</tr>
</table>
<div class="gap"></div>

<table class="items">
<thead>
<tr><th class="c">STT</th><th>Sản phẩm</th><th class="c">SL</th><th class="r">Đơn giá gốc</th><th class="r">Đơn giá bán</th><th class="r">Thành tiền</th></tr>
</thead>
<tbody>
@foreach($order['san_pham'] as $i => $line)
<tr>
    <td class="c">{{ $i + 1 }}</td>
    <td>{{ $line['ten'] }}</td>
    <td class="c">{{ $line['so_luong'] }}</td>
    <td class="r">{{ $money($line['gia_goc']) }}</td>
    <td class="r">{{ $money($line['don_gia']) }}</td>
    <td class="r">{{ $money($line['thanh_tien']) }}</td>
</tr>
@endforeach
</tbody>
</table>
<div class="gap"></div>

<table style="width:55%; margin-left:45%;" class="total">
<tr><td>Tạm tính</td><td class="r">{{ $money($s['tam_tinh']) }}</td></tr>
@if($s['chiet_khau_sale'] > 0)
<tr><td>Chiết khấu khuyến mãi (đã trừ trong đơn giá)</td><td class="r">{{ $money($s['chiet_khau_sale']) }}</td></tr>
@endif
<tr><td>Giảm giá{{ $s['ma_giam_gia'] ? ' (mã ' . $s['ma_giam_gia'] . ')' : '' }}</td><td class="r">- {{ $money($s['giam_gia']) }}</td></tr>
<tr><td>Phí vận chuyển</td><td class="r">{{ $money($s['phi_van_chuyen']) }}</td></tr>
<tr class="grand"><td>Tổng thanh toán</td><td class="r">{{ $money($s['thanh_tien']) }}</td></tr>
</table>

@if($order['nhan_hang']['ghi_chu'])
<p>Ghi chú: {{ $order['nhan_hang']['ghi_chu'] }}</p>
@endif

<table class="sign">
<tr><td>Người mua hàng<br><i>(Ký, ghi rõ họ tên)</i></td><td>Người bán hàng<br><i>(Ký, ghi rõ họ tên)</i></td></tr>
</table>
<p class="c" style="color:#777; font-size:9px;">In lúc {{ now()->setTimezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y') }}</p>
</body>
</html>
