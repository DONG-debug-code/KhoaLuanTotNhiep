import { useEffect, useState } from "react";
import { Link, useParams } from "react-router-dom";
import { CheckCircle2, Clock3, FileText, Phone, ShoppingBag } from "lucide-react";
import Navbar from "../../components/layouts/partials/Navbar";
import Footer from "../../components/layouts/partials/Footer";
import { getWorkspaceAssetUrl, getWorkspaceData } from "../../api/WorkspaceApi";

const money = (value) => `${Number(value || 0).toLocaleString("vi-VN")} ₫`;
const dateTime = (value) => value ? new Date(value).toLocaleString("vi-VN") : new Date().toLocaleString("vi-VN");

export default function DatHangThanhCongPage() {
  const { orderId } = useParams();
  const [order, setOrder] = useState(() => {
    try {
      return JSON.parse(sessionStorage.getItem(`donHangVuaDat:${orderId}`) || "null");
    } catch {
      return null;
    }
  });
  const [error, setError] = useState("");

  useEffect(() => {
    let active = true;
    getWorkspaceData("khach-hang", "don-hang/cua-toi")
      .then((orders) => {
        const savedOrder = Array.isArray(orders)
          ? orders.find((item) => String(item.id) === String(orderId))
          : null;
        if (active && savedOrder) setOrder((current) => ({ ...current, ...savedOrder }));
      })
      .catch((requestError) => {
        if (active) setError(requestError.response?.data?.message || "Chưa thể tải lại chi tiết đơn hàng.");
      });
    return () => { active = false; };
  }, [orderId]);

  return (
    <>
      <Navbar luonSang />
      <main className="min-h-screen bg-[#fafaf9] pt-[84px]">
        <section className="bg-[#f3f3f1] px-5 py-14 text-center sm:px-8">
          <span className="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-[#b8914a] text-[#080a0d]"><CheckCircle2 className="h-9 w-9" /></span>
          <h1 className="mt-5 text-3xl font-extrabold tracking-tight">Đặt hàng thành công</h1>
          <p className="mx-auto mt-3 max-w-xl text-sm leading-6 text-[#63666b]">Cảm ơn bạn đã đặt hàng tại DU_AN_XE. Showroom sẽ kiểm tra tồn kho và liên hệ xác nhận đơn hàng, giao nhận và phương thức thanh toán.</p>
          <div className="mt-5 flex flex-wrap justify-center gap-3 text-sm">
            <span className="rounded-full border border-[#dededb] bg-white px-4 py-2">Mã đơn: <strong className="text-[#c8102e]">{order?.ma_don_hang || `DH-${orderId}`}</strong></span>
            <span className="rounded-full border border-[#dededb] bg-white px-4 py-2">{dateTime(order?.created_at)}</span>
          </div>
          {error ? <p role="alert" className="mt-4 text-sm text-[#9d1026]">{error}</p> : null}
        </section>

        <div className="mx-auto grid max-w-[1280px] gap-6 px-5 py-8 sm:px-8 lg:grid-cols-[minmax(0,1.4fr)_minmax(300px,0.8fr)] lg:px-12">
          <section className="rounded-2xl border border-[#e9e9e6] bg-white">
            <div className="flex items-center justify-between border-b border-[#ecece9] p-5"><h2 className="font-bold">Sản phẩm đã đặt</h2><span className="text-sm text-[#777a80]">{order?.chi_tiet?.length || 0} dòng</span></div>
            {order?.chi_tiet?.length ? order.chi_tiet.map((item) => (
              <article key={item.id} className="flex items-center gap-4 border-b border-[#ecece9] p-5 last:border-0">
                <div className="h-16 w-16 shrink-0 overflow-hidden rounded-lg bg-[#e7e8e5]">{item.hinh_anh ? <img src={getWorkspaceAssetUrl(item.hinh_anh)} alt="" className="h-full w-full object-cover" /> : <ShoppingBag className="m-5 h-6 w-6 text-[#85888d]" />}</div>
                <div className="min-w-0 flex-1"><h3 className="font-semibold">{item.ten_san_pham || "Sản phẩm showroom"}</h3><p className="mt-1 text-xs text-[#777a80]">Số lượng {item.so_luong} · {money(item.don_gia)} / món</p></div>
                <strong className="text-right text-sm">{money(item.thanh_tien)}</strong>
              </article>
            )) : <p className="p-5 text-sm text-[#777a80]">Đơn hàng đã được ghi nhận. Chi tiết đang được cập nhật.</p>}
            <div className="space-y-2 border-t border-[#ecece9] bg-[#f3f3f1] p-5 text-sm">
              <div className="flex justify-between"><span className="text-[#777a80]">Tạm tính</span><strong>{money(order?.tong_tien)}</strong></div>
              <div className="flex justify-between"><span className="text-[#777a80]">Giảm giá</span><span>{order?.giam_gia ? `− ${money(order.giam_gia)}` : "—"}</span></div>
              <div className="flex justify-between"><span className="text-[#777a80]">Phí giao hàng</span><span>{order?.phi_van_chuyen == null ? "Xác nhận sau" : order.phi_van_chuyen === 0 ? "Miễn phí" : money(order.phi_van_chuyen)}</span></div>
              <div className="flex justify-between border-t border-[#dededb] pt-3 text-base"><strong>Tổng tạm tính</strong><strong className="text-[#b10e27]">{money(order?.thanh_tien)}</strong></div>
            </div>
          </section>

          <aside className="space-y-5">
            <section className="rounded-2xl border border-[#e9e9e6] bg-white p-5">
              <h2 className="font-bold">Bước tiếp theo</h2>
              <ol className="mt-4 space-y-4 text-sm">
                <li className="flex gap-3"><Clock3 className="mt-0.5 h-4 w-4 shrink-0 text-[#b8914a]" /><span><strong className="block">Xác nhận đơn hàng</strong><small className="text-[#777a80]">Nhân viên showroom sẽ liên hệ với bạn.</small></span></li>
                <li className="flex gap-3"><FileText className="mt-0.5 h-4 w-4 shrink-0 text-[#b8914a]" /><span><strong className="block">Thanh toán</strong><small className="text-[#777a80]">Làm theo hướng dẫn sau khi đơn được xác nhận.</small></span></li>
                <li className="flex gap-3"><Phone className="mt-0.5 h-4 w-4 shrink-0 text-[#b8914a]" /><span><strong className="block">Nhận hàng</strong><small className="text-[#777a80]">Showroom xác nhận địa điểm, thời gian và phí giao hàng.</small></span></li>
              </ol>
            </section>
            {order?.ten_nguoi_nhan ? (
              <section className="rounded-2xl border border-[#e9e9e6] bg-white p-5">
                <h2 className="font-bold">Thông tin giao nhận</h2>
                <p className="mt-3 text-sm font-semibold">{order.ten_nguoi_nhan}</p>
                <p className="mt-1 text-sm text-[#777a80]">{order.so_dien_thoai_nguoi_nhan}</p>
                <p className="mt-1 text-sm text-[#777a80]">{order.phuong_thuc_nhan === "nhan_tai_showroom" ? "Nhận tại showroom" : order.dia_chi_nguoi_nhan}</p>
              </section>
            ) : null}
            <div className="space-y-2">
              <Link to="/garage?section=orders" className="flex w-full justify-center rounded-lg bg-[#c8102e] px-4 py-3 text-sm font-bold text-white">Xem lịch sử mua hàng</Link>
              <Link to="/hang-phu-tung" className="flex w-full justify-center rounded-lg border border-[#dededb] bg-white px-4 py-3 text-sm font-semibold">Tiếp tục xem phụ kiện</Link>
            </div>
          </aside>
        </div>
      </main>
      <Footer />
    </>
  );
}
