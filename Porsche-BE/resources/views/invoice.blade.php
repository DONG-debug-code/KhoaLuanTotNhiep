<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="utf-8">
<style>
    @page { margin: 36px 40px; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #17171A; }
    table { width: 100%; border-collapse: collapse; }
    .brand { font-size: 10px; letter-spacing: 2px; color: #777a80; text-transform: uppercase; }
    .code { font-family: DejaVu Sans Mono, monospace; font-size: 11px; color: #5f6369; }
    .badge { display: inline-block; border: 1px solid #dfe3ea; background: #eef1f6; border-radius: 10px; padding: 2px 8px; font-size: 10px; }
    h1 { font-size: 18px; margin: 8px 0 0; }
    .head { border-bottom: 1px solid #e3e3e0; padding-bottom: 12px; margin-bottom: 14px; }
    .info td { width: 50%; padding: 5px 0; vertical-align: top; }
    .lbl { font-size: 9px; color: #777a80; }
    .val { font-size: 12px; }
    h3 { font-size: 12px; margin: 16px 0 6px; }
    .items { border: 1px solid #e3e3e0; }
    .items td { padding: 8px 12px; border-bottom: 1px solid #ececea; vertical-align: top; }
    .items tr.last td { border-bottom: 0; }
    .sub { font-size: 9px; color: #85888d; }
    .r { text-align: right; }
    .sum { background: #f1f1ee; padding: 10px 14px; width: 48%; margin-left: 52%; margin-top: 14px; }
    .sum td { padding: 2px 0; color: #5f6369; }
    .grand td { border-top: 1px solid #d6d6d2; padding-top: 8px; color: #17171A; font-size: 12px; }
    .grand .amt { color: #AC0021; font-weight: bold; font-size: 15px; }
    .pay { border: 1px solid #e3e3e0; padding: 8px 14px; margin-top: 14px; }
    .pay td { width: 50%; padding: 4px 0; vertical-align: top; }
    .note { background: #f1f1ee; padding: 9px 14px; margin-top: 12px; color: #5f6369; font-size: 10px; }
    .sign td { text-align: center; padding-top: 22px; height: 80px; width: 50%; }
</style>
</head>
<body>
@php
    $money = fn ($v) => number_format((float) $v, 0, ',', '.') . ' ₫';
    $t = $order['thoi_gian'];
    $s = $order['tong_ket'];
    $first = $order['san_pham'][0]['ten'] ?? null;
@endphp

<div class="head">
    <table>
        <tr>
            <td>
                @if(function_exists('imagecreatefrompng'))<img src="{{ public_path('images/porsche-logo.png') }}" style="height:34px; vertical-align:middle;" alt="">@endif
                <span class="brand" style="vertical-align:middle; margin-left:6px; font-size:13px; font-weight:bold; color:#17171A;">Porsche</span>
            </td>
            <td class="r"><span class="lbl">In lúc {{ now()->setTimezone('Asia/Ho_Chi_Minh')->format('H:i d/m/Y') }}</span></td>
        </tr>
    </table>
    <div style="margin-top:8px;">
        <span class="code">{{ $order['ma_don_hang'] }}</span>
        @if($order['ma_hoa_don']) &nbsp;<span class="badge">{{ $order['ma_hoa_don'] }}</span> @endif
        &nbsp;<span class="badge">{{ $order['trang_thai_text'] }}</span>
    </div>
    <h1>{{ $first ? 'Mua ' . $first : ($order['ma_hoa_don'] ? 'Hóa đơn bán hàng' : 'Phiếu đơn hàng') }}</h1>
</div>

<table class="info">
<tr>
    <td><div class="lbl">Ngày đặt hàng</div><div class="val">{{ $t['dat_hang'] ?? '—' }}</div></td>
    <td><div class="lbl">Bên mua</div><div class="val">{{ $order['ben_mua']['ho_ten'] }}</div></td>
</tr>
<tr>
    <td><div class="lbl">Nhận hàng</div><div class="val">{{ $order['nhan_hang']['hinh_thuc_text'] }}</div></td>
    <td><div class="lbl">Nhân viên phụ trách</div><div class="val">{{ $order['nhan_vien_xac_nhan']['ho_ten'] ?? '—' }}</div></td>
</tr>
<tr>
    <td><div class="lbl">Điện thoại</div><div class="val">{{ $order['ben_mua']['so_dien_thoai'] ?: '—' }}</div></td>
    <td><div class="lbl">Địa chỉ</div><div class="val">{{ $order['ben_mua']['dia_chi'] ?: '—' }}</div></td>
</tr>
</table>

<h3>Hạng mục</h3>
<table class="items">
@foreach($order['san_pham'] as $i => $line)
<tr class="{{ $loop->last ? 'last' : '' }}">
    <td>
        {{ $line['ten'] }}
        <div class="sub">{{ $line['so_luong'] }} × {{ $money($line['don_gia']) }}@if($line['gia_goc'] > $line['don_gia']) &nbsp;(giá gốc {{ $money($line['gia_goc']) }})@endif</div>
    </td>
    <td class="r" style="white-space:nowrap;"><b>{{ $money($line['thanh_tien']) }}</b></td>
</tr>
@endforeach
</table>

<table class="sum">
<tr><td>Tạm tính</td><td class="r">{{ $money($s['tam_tinh']) }}</td></tr>
@if($s['chiet_khau_sale'] > 0)
<tr><td>Chiết khấu khuyến mãi</td><td class="r">- {{ $money($s['chiet_khau_sale']) }}</td></tr>
@endif
@if($s['giam_gia'] > 0)
<tr><td>Giảm giá{{ $s['ma_giam_gia'] ? ' (' . $s['ma_giam_gia'] . ')' : '' }}</td><td class="r">- {{ $money($s['giam_gia']) }}</td></tr>
@endif
<tr><td>Phí vận chuyển</td><td class="r">{{ $money($s['phi_van_chuyen']) }}</td></tr>
<tr class="grand"><td>Tổng thanh toán</td><td class="r amt">{{ $money($s['thanh_tien']) }}</td></tr>
</table>

<table class="pay">
<tr>
    <td><div class="lbl">Phương thức thanh toán</div><div class="val">{{ $order['thanh_toan']['phuong_thuc_text'] }}</div></td>
    <td><div class="lbl">Thời điểm thanh toán</div><div class="val">{{ $t['thanh_toan'] ?? 'Chưa thanh toán' }}</div></td>
</tr>
@if($order['thanh_toan']['ma_giao_dich'] || $order['nhan_hang']['lich_lap_dat'])
<tr>
    <td>@if($order['thanh_toan']['ma_giao_dich'])<div class="lbl">Mã giao dịch</div><div class="val">{{ $order['thanh_toan']['ma_giao_dich'] }} ({{ $order['thanh_toan']['ngan_hang'] }})</div>@endif</td>
    <td>@if($order['nhan_hang']['lich_lap_dat'])<div class="lbl">Lịch lắp đặt</div><div class="val">{{ $order['nhan_hang']['lich_lap_dat']['gio'] }} {{ $order['nhan_hang']['lich_lap_dat']['ngay'] }}</div>@endif</td>
</tr>
@endif
</table>

<div class="note">
    {{ $order['ma_hoa_don'] ? 'Đã xuất hóa đơn ' . $order['ma_hoa_don'] . '.' : 'Hóa đơn sẽ được xuất sau khi đơn hàng hoàn tất.' }}
    @if($order['nhan_hang']['ghi_chu']) Ghi chú: {{ $order['nhan_hang']['ghi_chu'] }} @endif
</div>

<table class="sign">
<tr><td>Người mua hàng<br><i>(Ký, ghi rõ họ tên)</i></td><td>Người bán hàng<br><i>(Ký, ghi rõ họ tên)</i></td></tr>
</table>
</body>
</html>
