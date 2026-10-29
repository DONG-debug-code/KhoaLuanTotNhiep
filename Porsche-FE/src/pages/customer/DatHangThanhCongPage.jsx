import { useEffect, useState } from "react";
import { Link, useParams, useSearchParams } from "react-router-dom";
import { AlertCircle, CheckCircle2, Clock3, FileText, Phone, ShoppingBag } from "lucide-react";
import Navbar from "../../components/layouts/partials/Navbar";
import Footer from "../../components/layouts/partials/Footer";
import { getWorkspaceAssetUrl, getWorkspaceData } from "../../api/WorkspaceApi";
import { useCart } from "../../context/CartContext";

const money = (value) => `${Number(value || 0).toLocaleString("vi-VN")} ₫`;
const dateTime = (value) => value ? new Date(value).toLocaleString("vi-VN") : new Date().toLocaleString("vi-VN");

export default function DatHangThanhCongPage() {
  const { orderId } = useParams();
  const { clearSelected: clearCart } = useCart();
  const [searchParams] = useSearchParams();
  const vnpayQuery = searchParams.get("vnp_SecureHash") ? searchParams.toString() : "";
  const isCardReturn = Boolean(vnpayQuery);
  const vnpayResponseCode = searchParams.get("vnp_ResponseCode");
  const [order, setOrder] = useState(() => {
    try {
      return JSON.parse(sessionStorage.getItem(`donHangVuaDat:${orderId}`) || "null");
    } catch {
      return null;
    }
  });
  const [error, setError] = useState("");
  const [confirming, setConfirming] = useState(isCardReturn);

  useEffect(() => {
    let active = true;
    const loadOrder = async () => {
      if (vnpayQuery) {
        try {
          await getWorkspaceData("khach-hang", "vnpay/xac-nhan", Object.fromEntries(new URLSearchParams(vnpayQuery)));
        } catch (requestError) {
          if (active) setError(requestError.response?.data?.message || "Không thể xác nhận kết quả thanh toán.");
        }
      }
      try {
        const orders = await getWorkspaceData("khach-hang", "don-hang/cua-toi");
        const savedOrder = Array.isArray(orders)
          ? orders.find((item) => String(item.id) === String(orderId))
          : null;
        if (active && savedOrder) setOrder((current) => ({ ...current, ...savedOrder }));
      } catch (requestError) {
        if (active) setError(requestError.response?.data?.message || "Chưa thể tải lại chi tiết đơn hàng.");
      } finally {
        if (active) setConfirming(false);
      }
    };
    void loadOrder();
    return () => {
      active = false;
    };
  }, [orderId, vnpayQuery]);

  const isPaid = order?.trang_thai_thanh_toan === "da_thanh_toan";
  useEffect(() => {
    if (isCardReturn && isPaid) clearCart();
  }, [isCardReturn, isPaid, clearCart]);
  const isCancelled = isCardReturn && !confirming && !isPaid && vnpayResponseCode === "24";
  const isFailed = isCardReturn && !confirming && !isPaid && !isCancelled;
  const title = confirming
    ? "Đang xác nhận thanh toán"
    : isCancelled
      ? "Bạn đã hủy thanh toán"
      : isFailed
        ? "Thanh toán không thành công"
        : isPaid
          ? "Thanh toán thành công"
          : "Đã tiếp nhận đơn hàng";
  const showWarning = isCancelled || isFailed;

  return (
    <>
      <Navbar luonSang />
      <main className="min-h-screen bg-[#fafaf9] pt-[80px]">
        <section className="bg-[#f3f3f1] px-[1.3rem] py-8 text-center">
          <span className={`mx-auto flex h-12 w-12 items-center justify-center rounded-full ${showWarning ? "bg-[#fae9eb] text-[#8C001B]" : "bg-[#b8914a] text-[#080a0d]"}`}>
            {showWarning ? <AlertCircle className="h-7 w-7" /> : <CheckCircle2 className="h-7 w-7" />}
          </span>
          <h1 className="!my-0 mt-3 text-[26px] font-extrabold tracking-tight">{title}</h1>
          <p className="mx-auto mt-2 max-w-xl text-[13px] leading-5 text-[#63666b]">
            {confirming
              ? "Vui lòng chờ trong giây lát, chúng tôi đang xác nhận giao dịch với VNPay."
              : showWarning
                ? "Giao dịch chưa được thực hiện. Đơn hàng đã được hủy và tồn kho được hoàn lại; bạn có thể đặt hàng lại."
                : isPaid
                  ? "VNPay đã xác nhận giao dịch. Email xác nhận thanh toán đã được gửi đến địa chỉ email đặt hàng."
                  : order?.phuong_thuc_tt === "tien_mat_khi_nhan_hang"
                    ? "Đơn hàng đã được ghi nhận. Bạn thanh toán tiền mặt cho nhân viên giao hàng khi nhận hàng."
                    : "Cảm ơn bạn đã đặt hàng tại Porsche. Showroom sẽ kiểm tra tồn kho và liên hệ xác nhận đơn hàng."}
          </p>
          <div className="mt-3 flex flex-wrap justify-center gap-2 text-[12px]">
            <span className="rounded-full border border-[#dededb] bg-white px-3 py-1.5">Mã đơn: <strong className="text-[#AC0021]">{order?.ma_don_hang || `DH-${orderId}`}</strong></span>
            <span className="rounded-full border border-[#dededb] bg-white px-3 py-1.5">{dateTime(order?.created_at)}</span>
          </div>
          {error ? <p role="alert" className="mt-4 text-[13px] text-[#8C001B]">{error}</p> : null}
        </section>

        <div className="mx-auto grid gap-5 px-[1.3rem] py-6 lg:grid-cols-[minmax(0,1.4fr)_minmax(300px,0.8fr)]">
          <div>
          <section className="rounded-2xl border border-[#e9e9e6] bg-white">
            <div className="flex items-center justify-between border-b border-[#ecece9] px-4 py-3"><h2 className="!mb-0 text-[15px] font-bold">Sản phẩm đã đặt</h2><span className="text-[13px] text-[#777a80]">{order?.chi_tiet?.length || 0} dòng</span></div>
            {order?.chi_tiet?.length ? order.chi_tiet.map((item) => (
              <article key={item.id} className="flex items-center gap-4 border-b border-[#ecece9] px-4 py-3 last:border-0">
                <div className="h-14 w-14 shrink-0 overflow-hidden rounded-lg bg-[#e7e8e5]">{item.hinh_anh ? <img src={getWorkspaceAssetUrl(item.hinh_anh)} alt="" className="h-full w-full object-cover" /> : <ShoppingBag className="m-5 h-6 w-6 text-[#85888d]" />}</div>
                <div className="min-w-0 flex-1"><h3 className="text-[14px] font-semibold">{item.ten_san_pham || "Sản phẩm showroom"}</h3><p className="mt-1 text-[11px] text-[#777a80]">Số lượng {item.so_luong} · {money(item.don_gia)} / món</p></div>
                <strong className="text-right text-[13px]">{money(item.thanh_tien)}</strong>
              </article>
            )) : <p className="p-5 text-[13px] text-[#777a80]">Đơn hàng đã được ghi nhận. Chi tiết đang được cập nhật.</p>}
            <div className="space-y-1.5 border-t border-[#ecece9] bg-[#f3f3f1] px-4 py-3 text-[13px]">
              <div className="flex justify-between"><span className="text-[#777a80]">Tạm tính</span><strong>{money(order?.tong_tien)}</strong></div>
              <div className="flex justify-between"><span className="text-[#777a80]">Giảm giá</span><span>{order?.giam_gia ? `− ${money(order.giam_gia)}` : "—"}</span></div>
              <div className="flex justify-between"><span className="text-[#777a80]">Phí giao hàng</span><span>{order?.phi_van_chuyen == null ? "Xác nhận sau" : order.phi_van_chuyen === 0 ? "Miễn phí" : money(order.phi_van_chuyen)}</span></div>
              <div className="flex justify-between border-t border-[#dededb] pt-2 text-[13px]"><strong>Tổng thanh toán</strong><strong className="text-lg text-[#AC0021]">{money(order?.thanh_tien)}</strong></div>
            </div>
          </section>
          {order?.ten_nguoi_nhan ? (
            <section className="mt-5 rounded-2xl border border-[#e9e9e6] bg-white p-4">
              <h2 className="!mb-0 text-[15px] font-bold">Thông tin giao nhận</h2>
              <dl className="mt-3 grid gap-x-6 gap-y-3 sm:grid-cols-2">
                <div><dt className="text-[11px] text-[#85888d]">Người đặt</dt><dd className="text-[13px] font-semibold">{order.ten_nguoi_nhan}</dd></div>
                <div><dt className="text-[11px] text-[#85888d]">Điện thoại</dt><dd className="text-[13px] font-semibold">{order.so_dien_thoai_nguoi_nhan}</dd></div>
                <div><dt className="text-[11px] text-[#85888d]">Hình thức nhận</dt><dd className="text-[13px] font-semibold">{order.phuong_thuc_nhan === "nhan_tai_showroom" ? "Nhận tại showroom" : "Giao tận nơi"}</dd></div>
                {order.phuong_thuc_nhan !== "nhan_tai_showroom" && order.dia_chi_nguoi_nhan ? (
                  <div><dt className="text-[11px] text-[#85888d]">Địa chỉ</dt><dd className="text-[13px] font-semibold">{order.dia_chi_nguoi_nhan}</dd></div>
                ) : null}
              </dl>
            </section>
          ) : null}
          </div>

          <aside className="space-y-4">
            <section className="rounded-2xl border border-[#e9e9e6] bg-white p-4">
              <h2 className="!mb-0 text-[15px] font-bold">Bước tiếp theo</h2>
              <ol className="mt-3 space-y-3 text-[13px]">
                <li className="flex gap-3"><Clock3 className="mt-0.5 h-4 w-4 shrink-0 text-[#b8914a]" /><span><strong className="block">Xác nhận đơn hàng</strong><small className="text-[11px] text-[#777a80]">Nhân viên showroom sẽ liên hệ với bạn.</small></span></li>
                <li className="flex gap-3"><FileText className="mt-0.5 h-4 w-4 shrink-0 text-[#b8914a]" /><span><strong className="block">Thanh toán</strong><small className="text-[11px] text-[#777a80]">Làm theo hướng dẫn sau khi đơn được xác nhận.</small></span></li>
                <li className="flex gap-3"><Phone className="mt-0.5 h-4 w-4 shrink-0 text-[#b8914a]" /><span><strong className="block">Nhận hàng</strong><small className="text-[11px] text-[#777a80]">Showroom xác nhận địa điểm, thời gian và phí giao hàng.</small></span></li>
              </ol>
            </section>
            <section className="rounded-2xl bg-[#0A0A0B] p-4 text-white">
              <p className="text-[12px] font-semibold text-[#D9A441]">Cần hỗ trợ?</p>
              <p className="mt-1 text-[15px] font-bold">Hotline 1900 0000</p>
              <p className="mt-1 text-[11px] text-white/70">Thứ 2 – Thứ 7, 8:00 – 18:00. Đọc mã đơn {order?.ma_don_hang || `DH-${orderId}`} để được hỗ trợ nhanh.</p>
            </section>
            <div className="space-y-2">
              <Link to="/garage?section=orders" className="flex w-full justify-center rounded-lg bg-[#AC0021] px-4 py-2.5 text-[13px] font-bold text-white">Xem lịch sử mua hàng</Link>
              <Link to="/hang-phu-tung" className="flex w-full justify-center rounded-lg border border-[#dededb] bg-white px-4 py-2.5 text-[13px] font-semibold">Tiếp tục xem phụ kiện</Link>
            </div>
          </aside>
        </div>
      </main>
      <Footer />
    </>
  );
}
