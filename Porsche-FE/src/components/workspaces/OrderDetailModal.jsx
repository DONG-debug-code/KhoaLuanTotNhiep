import { useEffect, useState } from "react";
import { Calendar, CarFront, Info, MapPin, Receipt, UserRound, X } from "lucide-react";
import { downloadOrderInvoicePdf, getOrderDetail } from "../../api/WorkspaceApi";

const money = (value) => `${new Intl.NumberFormat("vi-VN").format(Number(value) || 0)} ₫`;

function Info2({ icon: Icon, label, value }) {
  return (
    <div className="flex items-start gap-2.5">
      {Icon ? <Icon className="mt-0.5 h-3.5 w-3.5 shrink-0 text-[#777a80]" /> : null}
      <div className="min-w-0">
        <p className="text-[11px] text-[#777a80]">{label}</p>
        <p className="text-[13px] font-medium text-[#17171A]">{value || "—"}</p>
      </div>
    </div>
  );
}

function Row({ label, value, strong }) {
  return (
    <div className={`flex justify-between gap-4 py-0.5 text-[13px] ${strong ? "font-semibold text-[#17171A]" : "text-[#5f6369]"}`}>
      <span>{label}</span>
      <span>{value}</span>
    </div>
  );
}

export default function OrderDetailModal({ orderId, role = "khach-hang", onClose }) {
  const [order, setOrder] = useState(null);
  const [error, setError] = useState("");
  const [printing, setPrinting] = useState(false);

  useEffect(() => {
    let active = true;
    getOrderDetail(orderId, role)
      .then((data) => { if (active) setOrder(data); })
      .catch((err) => { if (active) setError(err.response?.data?.message || "Không thể tải chi tiết đơn hàng."); });
    return () => { active = false; };
  }, [orderId, role]);

  const print = async () => {
    setPrinting(true);
    try {
      await downloadOrderInvoicePdf(orderId, order?.ma_hoa_don || order?.ma_don_hang, role);
    } catch {
      setError("Không thể tạo file PDF hóa đơn.");
    } finally {
      setPrinting(false);
    }
  };

  const time = order?.thoi_gian;
  const sum = order?.tong_ket;
  const paid = order?.thanh_toan?.trang_thai === "Đã thanh toán" || Boolean(time?.thanh_toan);
  const first = order?.san_pham?.[0]?.ten;

  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4" onClick={onClose}>
      <div className="flex max-h-[90vh] w-full max-w-2xl flex-col rounded-2xl bg-[#fbfbfa] shadow-xl" onClick={(event) => event.stopPropagation()}>
        <div className="flex items-start justify-between gap-3 border-b border-[#ededeb] px-6 py-4">
          <div className="min-w-0">
            <div className="flex flex-wrap items-center gap-2">
              <span className="font-mono text-[11px] font-semibold text-[#5f6369]">{order?.ma_don_hang || "..."}</span>
              {order?.ma_hoa_don ? <span className="rounded-full border border-[#e1e1de] bg-white px-2 py-0.5 text-[10px] font-semibold text-[#5f6369]">{order.ma_hoa_don}</span> : null}
              {order ? (
                <span className="inline-flex items-center gap-1.5 rounded-full border border-[#dfe3ea] bg-[#eef1f6] px-2.5 py-0.5 text-[11px] font-medium text-[#17171A]">
                  <span className={`h-1.5 w-1.5 rounded-full ${paid ? "bg-[#17171A]" : "bg-[#AC0021]"}`} />{order.trang_thai_text}
                </span>
              ) : null}
            </div>
            <h2 className="!mb-0 mt-2 text-lg font-bold leading-tight text-[#17171A]">{first ? `Mua ${first}` : "Chi tiết đơn hàng"}</h2>
          </div>
          <button type="button" onClick={onClose} aria-label="Đóng" className="rounded-lg p-1.5 text-[#777a80] hover:bg-[#f0f0ee]"><X className="h-4 w-4" /></button>
        </div>

        <div className="flex-1 space-y-4 overflow-y-auto px-6 py-4">
          {error ? <p className="rounded-lg bg-[#fae9eb] p-3 text-[13px] text-[#AC0021]">{error}</p> : null}
          {!order && !error ? <p className="p-6 text-center text-[13px] text-[#777a80]">Đang tải...</p> : null}

          {order ? (
            <>
              <div className="grid gap-x-6 gap-y-3 sm:grid-cols-2">
                <Info2 icon={Calendar} label="Ngày đặt hàng" value={time.dat_hang} />
                <Info2 icon={UserRound} label="Bên mua" value={order.ben_mua.ho_ten} />
                <Info2 icon={MapPin} label="Nhận hàng" value={order.nhan_hang.hinh_thuc_text} />
                <Info2 icon={CarFront} label="Nhân viên phụ trách" value={order.nhan_vien_xac_nhan?.ho_ten} />
                <Info2 icon={Receipt} label="Điện thoại" value={order.ben_mua.so_dien_thoai} />
                <Info2 icon={MapPin} label="Địa chỉ" value={order.ben_mua.dia_chi} />
              </div>

              <div>
                <h3 className="!mb-2 text-[13px] font-bold text-[#17171A]">Hạng mục</h3>
                <div className="rounded-xl border border-[#e9e9e6] bg-white">
                  {order.san_pham.map((line, index) => (
                    <div key={index} className="flex items-start justify-between gap-4 border-b border-[#ededeb] px-4 py-2.5 last:border-b-0">
                      <div className="min-w-0">
                        <p className="text-[13px] text-[#17171A]">{line.ten}</p>
                        <p className="text-[11px] text-[#85888d]">{line.so_luong} × {money(line.don_gia)}{line.gia_goc > line.don_gia ? <s className="ml-2">{money(line.gia_goc)}</s> : null}</p>
                      </div>
                      <span className="whitespace-nowrap text-[13px] font-bold text-[#17171A]">{money(line.thanh_tien)}</span>
                    </div>
                  ))}
                </div>
              </div>

              <div className="ml-auto max-w-[320px] rounded-xl bg-[#f1f1ee] px-4 py-3">
                <Row label="Tạm tính" value={money(sum.tam_tinh)} />
                {sum.chiet_khau_sale > 0 ? <Row label="Chiết khấu khuyến mãi" value={`- ${money(sum.chiet_khau_sale)}`} /> : null}
                {sum.giam_gia > 0 ? <Row label={`Giảm giá${sum.ma_giam_gia ? ` (${sum.ma_giam_gia})` : ""}`} value={`- ${money(sum.giam_gia)}`} /> : null}
                <Row label="Phí vận chuyển" value={money(sum.phi_van_chuyen)} />
                <div className="mt-2 flex items-center justify-between border-t border-[#dcdcd8] pt-2">
                  <span className="text-[13px] font-medium text-[#17171A]">Tổng thanh toán</span>
                  <span className="text-base font-bold text-[#AC0021]">{money(sum.thanh_tien)}</span>
                </div>
              </div>

              <div className="grid gap-x-6 gap-y-3 rounded-xl border border-[#e9e9e6] bg-white px-4 py-3 sm:grid-cols-2">
                <Info2 label="Phương thức thanh toán" value={order.thanh_toan.phuong_thuc_text} />
                <Info2 label="Thời điểm thanh toán" value={time.thanh_toan || "Chưa thanh toán"} />
                {order.thanh_toan.ma_giao_dich ? <Info2 label="Mã giao dịch" value={`${order.thanh_toan.ma_giao_dich} (${order.thanh_toan.ngan_hang || "—"})`} /> : null}
                {order.nhan_hang.lich_lap_dat ? <Info2 label="Lịch lắp đặt" value={`${order.nhan_hang.lich_lap_dat.gio} ${order.nhan_hang.lich_lap_dat.ngay} · ${order.nhan_hang.lich_lap_dat.chuyen_gia || ""}`} /> : null}
                {order.ly_do_huy ? <Info2 label="Lý do hủy" value={order.ly_do_huy} /> : null}
              </div>

              <div className="flex items-start gap-2.5 rounded-xl bg-[#f1f1ee] px-4 py-3 text-[12px] text-[#5f6369]">
                <Info className="mt-0.5 h-3.5 w-3.5 shrink-0" />
                <span>{order.ma_hoa_don ? `Đã xuất hóa đơn ${order.ma_hoa_don}.` : "Hóa đơn sẽ được xuất sau khi đơn hàng hoàn tất."} Bạn có thể tải hóa đơn PDF bên dưới.</span>
              </div>
            </>
          ) : null}
        </div>

        <div className="flex justify-end gap-2 border-t border-[#ededeb] px-6 py-3">
          <button type="button" onClick={onClose} className="rounded-xl border border-[#d7d7d3] bg-white px-4 py-2 text-[13px] font-semibold">Đóng</button>
          <button type="button" disabled={!order || printing} onClick={() => void print()} className="inline-flex items-center gap-2 rounded-xl bg-[#17171A] px-4 py-2 text-[13px] font-semibold text-white disabled:opacity-50">
            <Receipt className="h-4 w-4" /> {printing ? "Đang tạo..." : "In hóa đơn PDF"}
          </button>
        </div>
      </div>
    </div>
  );
}
