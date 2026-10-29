import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { ArrowLeft, Minus, Plus, ShoppingBag, Trash2 } from "lucide-react";
import Navbar from "../../components/layouts/partials/Navbar";
import Footer from "../../components/layouts/partials/Footer";
import { useCart } from "../../context/CartContext";
import { getGoiDoVaPhuTungClient } from "../../api/PublicApi";
import ProductDetailModal from "../../components/product/ProductDetailModal";
import { StoreProductCard, toStoreItem } from "../hang-phu-tung/HangPhuTungPage";

const money = (value) => `${Number(value).toLocaleString("vi-VN")} ₫`;

export default function GioHangPage() {
  const {
    items, itemCount, setQuantity, removeItem, clearCart,
    selectedItems, selectedCount, selectedSubtotal: subtotal, toggleSelected, setAllSelected,
  } = useCart();
  const [goiY, setGoiY] = useState([]);
  const [addedKey, setAddedKey] = useState("");
  const [sanPhamXem, setSanPhamXem] = useState(null);
  const { addItem } = useCart();

  useEffect(() => {
    let active = true;
    getGoiDoVaPhuTungClient()
      .then((data) => {
        if (!active) return;
        setGoiY(data.phuTung
          .map((item) => toStoreItem(item, "phu_tung"))
          .filter((item) => Number.isFinite(item.giaSo) && item.soLuong > 0));
      })
      .catch(() => {});
    return () => { active = false; };
  }, []);

  const sanPhamGoiY = goiY
    .filter((p) => !items.some((i) => i.type === p.type && String(i.id) === String(p.id)))
    .slice(0, 4);

  const themVaoGio = (product) => {
    if (!addItem(product)) return;
    const key = `${product.type}:${product.id}`;
    setAddedKey(key);
    window.setTimeout(() => setAddedKey(""), 1200);
  };
  const allSelected = items.length > 0 && selectedItems.length === items.length;

  return (
    <>
      <Navbar luonSang />
      <main className="min-h-screen bg-[#fafaf9] pt-[80px]">
        <header className="border-b border-[#e6e6e3] bg-[#f3f3f1] px-[1.3rem] py-6">
          <div className="mx-auto flex flex-wrap items-end justify-between gap-5">
            <div>
              <p className="mb-2 text-[12px] text-[#777a80]">Trang chủ　›　Giỏ hàng</p>
              <h1 className="!my-0 text-2xl font-extrabold tracking-tight sm:text-3xl">Giỏ hàng của bạn</h1>
              <p className="mt-2 text-[13px] text-[#73777d]">{itemCount} món đang chờ thanh toán. Kiểm tra lại cấu hình trước khi đặt.</p>
            </div>
            {items.length ? (
              <button type="button" onClick={clearCart} className="inline-flex items-center gap-2 rounded-lg border border-[#d9d9d5] bg-white px-3 py-2 text-[12px] font-semibold text-[#555a60] hover:bg-[#f7f7f5]">
                <Trash2 className="h-4 w-4" /> Xóa toàn bộ giỏ
              </button>
            ) : null}
          </div>
        </header>

        <div className="mx-auto grid gap-6 px-[1.3rem] py-8 lg:grid-cols-[minmax(0,1.55fr)_minmax(320px,0.9fr)]">
          {items.length ? (
            <>
              <section className="rounded-2xl border border-[#e9e9e6] bg-white p-4 sm:p-6">
                <label className="mb-5 flex cursor-pointer items-center gap-3 border-b border-[#ecece9] pb-4 text-sm font-semibold">
                  <input type="checkbox" checked={allSelected} onChange={(e) => setAllSelected(e.target.checked)} className="h-4 w-4 accent-[#AC0021]" />
                  Chọn tất cả ({selectedItems.length}/{items.length})
                </label>
                <div className="space-y-5">
                  {items.map((item) => (
                    <article key={`${item.type}:${item.id}`} className="flex flex-wrap items-start gap-4 border-b border-[#ecece9] pb-5 last:border-0 last:pb-0 sm:flex-nowrap">
                      <input
                        type="checkbox"
                        aria-label={`Chọn ${item.ten} để thanh toán`}
                        checked={item.selected !== false}
                        onChange={() => toggleSelected(item.type, item.id)}
                        className="mt-1 h-4 w-4 shrink-0 self-center accent-[#AC0021]"
                      />
                      <div className="h-28 w-full shrink-0 overflow-hidden rounded-xl bg-[#e7e8e5] sm:h-24 sm:w-32">
                        {item.anh ? <img src={item.anh} alt={item.ten} className="h-full w-full object-cover" /> : <div className="flex h-full items-center justify-center"><ShoppingBag className="h-8 w-8 text-[#9da1a4]" /></div>}
                      </div>
                      <div className="min-w-0 flex-1">
                        <div className="flex items-start justify-between gap-3">
                          <div className="min-w-0">
                            <span className="inline-flex rounded-full bg-[#faedce] px-2 py-0.5 text-[10px] font-semibold text-[#775a2d]">{item.type === "phu_tung" ? "Phụ tùng" : "Gói độ"}</span>
                            <h2 className="mt-1.5 text-[15px] font-bold text-[#17191c]">{item.ten}</h2>
                            <p className="mt-0.5 text-[12px] text-[#777a80]">{item.hangMuc || item.moTaNgan || "Phụ kiện Porsche"}</p>
                            {item.dongXeTuongThich?.length ? <p className="mt-0.5 text-[11px] text-[#85888d]">{item.dongXeTuongThich.join(" · ")}</p> : null}
                            <p className="mt-0.5 text-[11px] text-[#85888d]">Còn {item.soLuong} sản phẩm</p>
                          </div>
                          <button type="button" aria-label={`Xóa ${item.ten} khỏi giỏ`} onClick={() => removeItem(item.type, item.id)} className="shrink-0 rounded-lg p-1.5 text-[#85888d] hover:bg-[#fae9eb] hover:text-[#AC0021]"><Trash2 className="h-4 w-4" /></button>
                        </div>
                        <div className="mt-3 flex flex-wrap items-end justify-between gap-3">
                          <div className="inline-flex items-center rounded-lg border border-[#dededb]">
                            <button type="button" aria-label={`Giảm số lượng ${item.ten}`} disabled={item.quantity <= 1} onClick={() => setQuantity(item.type, item.id, item.quantity - 1)} className="p-2 text-[#777a80] disabled:opacity-40"><Minus className="h-3.5 w-3.5" /></button>
                            <span className="min-w-8 text-center text-[13px] font-semibold">{item.quantity}</span>
                            <button type="button" aria-label={`Tăng số lượng ${item.ten}`} disabled={item.quantity >= item.soLuong} onClick={() => setQuantity(item.type, item.id, item.quantity + 1)} className="p-2 text-[#777a80] disabled:opacity-40"><Plus className="h-3.5 w-3.5" /></button>
                          </div>
                          <div className="text-right">
                            <p className="text-[11px] text-[#85888d]">{money(item.giaSo)} / món</p>
                            <strong className="text-[15px] font-extrabold">{money(item.giaSo * item.quantity)}</strong>
                          </div>
                        </div>
                      </div>
                    </article>
                  ))}
                </div>
                <div className="mt-6 flex flex-wrap justify-between gap-3 border-t border-[#ecece9] pt-4 text-[13px]">
                  <Link to="/hang-phu-tung" className="inline-flex items-center gap-2 font-semibold text-[#AC0021] hover:underline"><ArrowLeft className="h-4 w-4" /> Tiếp tục chọn phụ kiện</Link>
                  <Link to="/#xe-dang-ban" className="text-[#555a60] hover:underline">Xem thêm xe showroom</Link>
                </div>
              </section>

              <aside className="h-fit rounded-2xl border border-[#e9e9e6] bg-white p-5 sm:p-6">
                <h2 className="text-base font-bold">Tóm tắt đơn hàng</h2>
                <div className="mt-4 space-y-2.5 text-[13px]">
                  <div className="flex justify-between gap-3 text-[#777a80]"><span>Tạm tính ({selectedCount} món đã chọn)</span><strong className="text-[#17191c]">{money(subtotal)}</strong></div>
                  <div className="flex justify-between gap-3 text-[#777a80]"><span>Giảm giá</span><span>—</span></div>
                  <div className="flex justify-between gap-3 text-[#777a80]"><span>Phí giao hàng</span><span className="text-[12px]">Chọn ở bước thanh toán</span></div>
                  <div className="border-t border-[#ecece9] pt-4">
                    <span className="block text-[11px] text-[#85888d]">Tổng thanh toán</span>
                    <strong className="mt-1 block text-xl font-extrabold text-[#AC0021]">{money(subtotal)}</strong>
                    <small className="mt-1 block text-xs text-[#85888d]">Phí giao hàng (nếu có) sẽ được xác nhận trước khi xử lý đơn.</small>
                  </div>
                </div>
                {selectedItems.length ? (
                  <Link to="/thanh-toan" className="mt-5 flex w-full items-center justify-center gap-2 rounded-lg bg-[#AC0021] px-4 py-3 text-[13px] font-bold text-white hover:bg-[#8C001B]">
                    Tiến hành thanh toán ({selectedItems.length}) <span aria-hidden="true">→</span>
                  </Link>
                ) : (
                  <button type="button" disabled className="mt-5 flex w-full cursor-not-allowed items-center justify-center gap-2 rounded-lg bg-[#AC0021] px-4 py-3 text-[13px] font-bold text-white opacity-40">
                    Chọn sản phẩm để thanh toán
                  </button>
                )}
                <ul className="mt-5 space-y-1.5 border-t border-[#ecece9] pt-4 text-[11px] text-[#777a80]">
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

        {sanPhamGoiY.length ? (
          <section className="mx-auto px-[1.3rem] pb-10">
            <div className="mb-4 flex flex-wrap items-end justify-between gap-3">
              <div>
                <h2 className="text-xl font-extrabold tracking-tight">Nâng cấp thêm cho xe của bạn</h2>
                <p className="mt-1 text-[13px] text-[#777a80]">Các gói phụ kiện được khách hàng chọn nhiều nhất cùng đơn hàng.</p>
              </div>
              <Link to="/hang-phu-tung" className="text-[13px] font-semibold text-[#AC0021] hover:underline">Xem toàn bộ →</Link>
            </div>
            <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
              {sanPhamGoiY.map((product) => (
                <StoreProductCard
                  key={`${product.type}:${product.id}`}
                  product={product}
                  onAdd={themVaoGio}
                  onSelect={setSanPhamXem}
                  justAdded={addedKey === `${product.type}:${product.id}`}
                />
              ))}
            </div>
          </section>
        ) : null}
      </main>
      {sanPhamXem ? <ProductDetailModal product={sanPhamXem} onClose={() => setSanPhamXem(null)} /> : null}
      <Footer />
    </>
  );
}
