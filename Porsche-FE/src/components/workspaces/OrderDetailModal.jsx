import { useEffect, useState } from "react";
import { Download, X } from "lucide-react";
import { downloadOrderInvoicePdf, getOrderDetail } from "../../api/WorkspaceApi";

const money = (value) => `${new Intl.NumberFormat("vi-VN").format(Number(value) || 0)} ₫`;

function Field({ label, value }) {
  return (
    <div className="flex justify-between gap-4 py-1.5 text-sm">
      <span className="text-[#777a80]">{label}</span>
      <span className="text-right font-medium text-[#17171A]">{value || "—"}</span>
    </div>
  );
}

function Section({ title, children }) {
  return (
    <section className="rounded-xl border border-[#e9e9e6] p-4">
      <h3 className="mb-2 text-xs font-bold uppercase tracking-wide text-[#c8102e]">{title}</h3>
      {children}
    </section>
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

  return (
    <div className="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/50 p-4" onClick={onClose}>
      <div className="my-8 w-full max-w-3xl rounded-2xl bg-white p-6 shadow-xl" onClick={(event) => event.stopPropagation()}>
        <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
          <div>
            <h2 className="text-lg font-bold">Chi tiết đơn hàng {order?.ma_don_hang}</h2>
            {order ? <p className="text-xs text-[#777a80]">{order.ma_hoa_don ? `Hóa đơn ${order.ma_hoa_don} · ` : ""}{order.trang_thai_text}</p> : null}
          </div>
          <div className="flex gap-2">
            <button type="button" disabled={!order || printing} onClick={() => void print()} className="inline-flex items-center gap-2 rounded-lg bg-[#c8102e] px-4 py-2 text-sm font-semibold text-white disabled:opacity-50">
              <Download className="h-4 w-4" /> {printing ? "Đang tạo..." : "In hóa đơn PDF"}
            </button>
            <button type="button" onClick={onClose} aria-label="Đóng" className="rounded-lg border border-[#d7d7d3] p-2"><X className="h-4 w-4" /></button>
          </div>
        </div>

        {error ? <p className="rounded-lg bg-[#fae9eb] p-3 text-sm text-[#c8102e]">{error}</p> : null}
        {!order && !error ? <p className="p-6 text-center text-sm text-[#777a80]">Đang tải...</p> : null}

        {order ? (
          <div className="space-y-4">
            <div className="grid gap-4 md:grid-cols-2">
              <Section title="Bên bán">
                <Field label="Tên" value={order.ben_ban.ten} />
                <Field label="Địa chỉ" value={order.ben_ban.dia_chi} />
                <Field label="Mã số thuế" value={order.ben_ban.ma_so_thue} />
                <Field label="Điện thoại" value={order.ben_ban.so_dien_thoai} />
                <Field label="Email" value={order.ben_ban.email} />
              </Section>
              <Section title="Bên mua">
                <Field label="Họ tên" value={order.ben_mua.ho_ten} />
                <Field label="Email" value={order.ben_mua.email} />
                <Field label="Điện thoại" value={order.ben_mua.so_dien_thoai} />
                <Field label="Địa chỉ" value={order.ben_mua.dia_chi} />
              </Section>
              <Section title="Thời gian & xử lý">
                <Field label="Thời gian mua hàng" value={time.dat_hang} />
                <Field label="Thời gian xác nhận" value={time.xac_nhan} />
                <Field label="Thời gian thanh toán" value={time.thanh_toan || "Chưa thanh toán"} />
                <Field label="Hoàn thành" value={time.hoan_thanh} />
                <Field label="Nhân viên xác nhận" value={order.nhan_vien_xac_nhan?.ho_ten} />
              </Section>
              <Section title="Thanh toán & nhận hàng">
                <Field label="Phương thức thanh toán" value={order.thanh_toan.phuong_thuc_text} />
                <Field label="Tình trạng" value={order.thanh_toan.trang_thai} />
                {order.thanh_toan.ma_giao_dich ? <Field label="Mã giao dịch" value={`${order.thanh_toan.ma_giao_dich} (${order.thanh_toan.ngan_hang || "—"})`} /> : null}
                <Field label="Hình thức nhận hàng" value={order.nhan_hang.hinh_thuc_text} />
                {order.nhan_hang.lich_lap_dat ? <Field label="Lịch lắp đặt" value={`${order.nhan_hang.lich_lap_dat.gio} ${order.nhan_hang.lich_lap_dat.ngay} · ${order.nhan_hang.lich_lap_dat.chuyen_gia || ""}`} /> : null}
                {order.ly_do_huy ? <Field label="Lý do hủy" value={order.ly_do_huy} /> : null}
              </Section>
            </div>

            <div className="overflow-x-auto rounded-xl border border-[#e9e9e6]">
              <table className="w-full text-sm">
                <thead className="bg-[#f6f6f4] text-left text-xs uppercase text-[#777a80]">
                  <tr><th className="p-3">Sản phẩm</th><th className="p-3 text-center">SL</th><th className="p-3 text-right">Đơn giá</th><th className="p-3 text-right">Thành tiền</th></tr>
                </thead>
                <tbody>
                  {order.san_pham.map((line, index) => (
                    <tr key={index} className="border-t border-[#ededeb]">
                      <td className="p-3">{line.ten}</td>
                      <td className="p-3 text-center">{line.so_luong}</td>
                      <td className="p-3 text-right">
                        {line.gia_goc > line.don_gia ? <s className="mr-2 text-xs text-[#999]">{money(line.gia_goc)}</s> : null}{money(line.don_gia)}
                      </td>
                      <td className="p-3 text-right font-medium">{money(line.thanh_tien)}</td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>

            <div className="ml-auto max-w-sm text-sm">
              <Field label="Tạm tính" value={money(sum.tam_tinh)} />
              {sum.chiet_khau_sale > 0 ? <Field label="Chiết khấu khuyến mãi" value={money(sum.chiet_khau_sale)} /> : null}
              <Field label={`Giảm giá${sum.ma_giam_gia ? ` (${sum.ma_giam_gia})` : ""}`} value={`- ${money(sum.giam_gia)}`} />
              <Field label="Phí vận chuyển" value={money(sum.phi_van_chuyen)} />
              <div className="mt-1 flex justify-between border-t-2 border-[#17171A] pt-2 text-base font-bold">
                <span>Tổng thanh toán</span><span>{money(sum.thanh_tien)}</span>
              </div>
            </div>
          </div>
        ) : null}
      </div>
    </div>
  );
}
