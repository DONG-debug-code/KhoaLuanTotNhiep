import { Link } from "react-router-dom";
import { ArrowLeft, Minus, Plus, ShoppingBag, Trash2 } from "lucide-react";
import Navbar from "../../components/layouts/partials/Navbar";
import Footer from "../../components/layouts/partials/Footer";
import { useCart } from "../../context/CartContext";

const money = (value) => `${Number(value).toLocaleString("vi-VN")} ₫`;

export default function GioHangPage() {
  const { items, itemCount, subtotal, setQuantity, removeItem, clearCart } = useCart();

  return (
    <>
      <Navbar luonSang />
      <main className="min-h-screen bg-[#fafaf9] pt-[84px]">
        <header className="border-b border-[#e6e6e3] bg-[#f3f3f1] px-5 py-8 sm:px-8 lg:px-12">
          <div className="mx-auto flex max-w-[1440px] flex-wrap items-end justify-between gap-5">
            <div>
              <p className="mb-2 text-sm text-[#777a80]">Trang chủ　›　Giỏ hàng</p>
              <h1 className="text-3xl font-extrabold tracking-tight sm:text-4xl">Giỏ hàng của bạn</h1>
              <p className="mt-2 text-sm text-[#73777d]">{itemCount} món đang chờ thanh toán. Kiểm tra lại cấu hình trước khi đặt.</p>
            </div>
            {items.length ? (
              <button type="button" onClick={clearCart} className="inline-flex items-center gap-2 rounded-lg border border-[#d9d9d5] bg-white px-4 py-2.5 text-sm font-semibold text-[#555a60] hover:bg-[#f7f7f5]">
                <Trash2 className="h-4 w-4" /> Xóa toàn bộ giỏ
              </button>
            ) : null}
          </div>
        </header>

        <div className="mx-auto grid max-w-[1440px] gap-6 px-5 py-8 sm:px-8 lg:grid-cols-[minmax(0,1.55fr)_minmax(320px,0.9fr)] lg:px-12">
          {items.length ? (
            <>
              <section className="rounded-2xl border border-[#e9e9e6] bg-white p-4 sm:p-6">
                <div className="space-y-5">
                  {items.map((item) => (
                    <article key={`${item.type}:${item.id}`} className="flex flex-wrap gap-4 border-b border-[#ecece9] pb-5 last:border-0 last:pb-0 sm:flex-nowrap">
                      <div className="h-28 w-full shrink-0 overflow-hidden rounded-xl bg-[#e7e8e5] sm:h-24 sm:w-32">
                        {item.anh ? <img src={item.anh} alt={item.ten} className="h-full w-full object-cover" /> : <div className="flex h-full items-center justify-center"><ShoppingBag className="h-8 w-8 text-[#9da1a4]" /></div>}
                      </div>
                      <div className="min-w-0 flex-1">
                        <span className="inline-flex rounded-full bg-[#faedce] px-2.5 py-1 text-[11px] font-semibold text-[#775a2d]">{item.type === "phu_tung" ? "Phụ tùng" : "Gói độ"}</span>
                        <h2 className="mt-2 font-bold text-[#17191c]">{item.ten}</h2>
                        <p className="mt-1 text-sm text-[#777a80]">{item.hangMuc || item.moTaNgan || "Phụ kiện Porsche"}</p>
                        <p className="mt-1 text-xs text-[#85888d]">Còn {item.soLuong} sản phẩm</p>
                        <div className="mt-4 flex flex-wrap items-center justify-between gap-4">
                          <div className="inline-flex items-center rounded-lg border border-[#dededb]">
                            <button type="button" aria-label={`Giảm số lượng ${item.ten}`} disabled={item.quantity <= 1} onClick={() => setQuantity(item.type, item.id, item.quantity - 1)} className="p-2.5 text-[#777a80] disabled:opacity-40"><Minus className="h-3.5 w-3.5" /></button>
                            <span className="min-w-9 text-center text-sm font-semibold">{item.quantity}</span>
                            <button type="button" aria-label={`Tăng số lượng ${item.ten}`} disabled={item.quantity >= item.soLuong} onClick={() => setQuantity(item.type, item.id, item.quantity + 1)} className="p-2.5 text-[#777a80] disabled:opacity-40"><Plus className="h-3.5 w-3.5" /></button>
                          </div>
                          <div className="flex items-center gap-4">
                            <strong className="text-base font-extrabold">{money(item.giaSo * item.quantity)}</strong>
                            <button type="button" aria-label={`Xóa ${item.ten} khỏi giỏ`} onClick={() => removeItem(item.type, item.id)} className="rounded-lg p-2 text-[#85888d] hover:bg-[#fae9eb] hover:text-[#c8102e]"><Trash2 className="h-4 w-4" /></button>
                          </div>
                        </div>
                        <p className="mt-1 text-xs text-[#85888d]">{money(item.giaSo)} / món</p>
                      </div>
                    </article>
                  ))}
                </div>
                <div className="mt-6 flex flex-wrap justify-between gap-3 border-t border-[#ecece9] pt-5 text-sm">
                  <Link to="/hang-phu-tung" className="inline-flex items-center gap-2 font-semibold text-[#c8102e] hover:underline"><ArrowLeft className="h-4 w-4" /> Tiếp tục chọn phụ kiện</Link>
                  <Link to="/" className="text-[#555a60] hover:underline">Quay lại trang chủ</Link>
                </div>
              </section>

              <aside className="h-fit rounded-2xl border border-[#e9e9e6] bg-white p-5 sm:p-6">
                <h2 className="text-lg font-bold">Tóm tắt đơn hàng</h2>
                <div className="mt-5 space-y-3 text-sm">
                  <div className="flex justify-between gap-3 text-[#777a80]"><span>Tạm tính ({itemCount} món)</span><strong className="text-[#17191c]">{money(subtotal)}</strong></div>
                  <div className="flex justify-between gap-3 text-[#777a80]"><span>Giảm giá</span><span>Áp dụng ở bước thanh toán</span></div>
                  <div className="flex justify-between gap-3 text-[#777a80]"><span>Phí giao hàng</span><span>Chọn ở bước thanh toán</span></div>
                  <div className="border-t border-[#ecece9] pt-4">
                    <span className="block text-xs text-[#85888d]">Tổng tạm tính</span>
                    <strong className="mt-1 block text-2xl font-extrabold text-[#b10e27]">{money(subtotal)}</strong>
                    <small className="mt-1 block text-xs text-[#85888d]">Phí giao hàng (nếu có) sẽ được xác nhận trước khi xử lý đơn.</small>
                  </div>
                </div>
                <Link to="/thanh-toan" className="mt-5 flex w-full items-center justify-center gap-2 rounded-lg bg-[#c8102e] px-4 py-3.5 text-sm font-bold text-white hover:bg-[#b10e27]">
                  Tiến hành thanh toán <span aria-hidden="true">→</span>
                </Link>
                <ul className="mt-5 space-y-2 border-t border-[#ecece9] pt-4 text-xs text-[#777a80]">
                  <li>• Đơn hàng sẽ được xác nhận bởi showroom.</li>
                  <li>• Chưa ghi nhận thanh toán trực tuyến.</li>
                  <li>• Tồn kho sẽ được kiểm tra lại khi đặt hàng.</li>
                </ul>
              </aside>
            </>
          ) : (
            <section className="rounded-2xl border border-dashed border-[#d9d9d5] bg-white px-6 py-16 text-center lg:col-span-2">
              <ShoppingBag className="mx-auto h-12 w-12 text-[#a0a2a4]" strokeWidth={1.3} />
              <h2 className="mt-4 text-xl font-bold">Giỏ hàng đang trống</h2>
              <p className="mt-2 text-sm text-[#777a80]">Chọn gói độ hoặc phụ kiện từ showroom để bắt đầu đặt hàng.</p>
              <Link to="/hang-phu-tung" className="mt-6 inline-flex rounded-lg bg-[#080a0d] px-5 py-3 text-sm font-semibold text-white">Khám phá sản phẩm</Link>
            </section>
          )}
        </div>
      </main>
      <Footer />
    </>
  );
}
