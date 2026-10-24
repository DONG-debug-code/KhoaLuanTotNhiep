import { useEffect, useMemo, useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import { ArrowLeft, Check, CreditCard, Info, MapPin, Store, Truck, UserRound, Wallet } from "lucide-react";
import Navbar from "../../components/layouts/partials/Navbar";
import Footer from "../../components/layouts/partials/Footer";
import { useAuth } from "../../context/AuthContext";
import { useCart } from "../../context/CartContext";
import { postWorkspaceData, getWorkspaceData } from "../../api/WorkspaceApi";

const money = (value) => `${Number(value).toLocaleString("vi-VN")} ₫`;

function isVoucherAvailable(voucher) {
  const now = Date.now();
  const startsAt = voucher.ngay_bat_dau ? new Date(voucher.ngay_bat_dau).getTime() : null;
  const endsAt = voucher.ngay_ket_thuc ? new Date(voucher.ngay_ket_thuc).getTime() : null;

  return Number(voucher.trang_thai) === 1
    && (startsAt === null || startsAt <= now)
    && (endsAt === null || endsAt >= now)
    && Number(voucher.so_luong) > Number(voucher.tong_so_lan_da_su_dung);
}

const PAYMENT_METHODS = [
  { value: "the", label: "Thẻ ATM / Visa / QR qua VNPay", hint: "Thanh toán bảo mật qua cổng VNPay.", Icon: CreditCard },
  { value: "tien_mat_khi_nhan_hang", label: "Tiền mặt khi nhận hàng", hint: "Thanh toán cho nhân viên giao hàng khi nhận đơn.", Icon: Store },
];

function CheckoutStep({ active }) {
  const steps = ["Thông tin", "Nhận hàng & thanh toán", "Xác nhận"];
  return (
    <div className="flex flex-wrap items-center gap-3">
      {steps.map((label, index) => {
        const number = index + 1;
        const complete = active > number;
        return (
          <div key={label} className="flex items-center gap-2">
            <span className={`flex h-8 w-8 items-center justify-center rounded-full border text-xs font-bold ${complete ? "border-[#b8914a] bg-[#b8914a] text-white" : active === number ? "border-[#080a0d] bg-[#080a0d] text-white" : "border-[#dededb] bg-white text-[#777a80]"}`}>
              {complete ? <Check className="h-4 w-4" /> : number}
            </span>
            <span className={`text-sm ${active === number ? "font-semibold text-[#17191c]" : "text-[#777a80]"}`}>{label}</span>
            {number < steps.length ? <span className="mx-1 text-[#b5b6b7]">›</span> : null}
          </div>
        );
      })}
    </div>
  );
}

export default function ThanhToanPage() {
  const navigate = useNavigate();
  const { nguoiDung } = useAuth();
  const { items, subtotal, clearCart } = useCart();
  const [step, setStep] = useState(1);
  const [form, setForm] = useState({
    ten_nguoi_nhan: nguoiDung?.ho_ten || "",
    email_nguoi_nhan: nguoiDung?.email || "",
    so_dien_thoai_nguoi_nhan: nguoiDung?.so_dien_thoai || "",
    dia_chi_nguoi_nhan: nguoiDung?.dia_chi || "",
    ghi_chu_don_hang: "",
    phuong_thuc_nhan: "nhan_tai_showroom",
    phuong_thuc_tt: "the",
  });
  const [voucherCode, setVoucherCode] = useState("");
  const [vouchers, setVouchers] = useState([]);
  const [appliedVoucher, setAppliedVoucher] = useState(null);
  const [error, setError] = useState("");
  const [notice, setNotice] = useState("");
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    let active = true;
    getWorkspaceData("khach-hang", "profile/data")
      .then((response) => {
        const data = response?.profile ?? response?.data?.profile ?? response;
        if (!active || !data) return;
        setForm((current) => ({
          ...current,
          ten_nguoi_nhan: current.ten_nguoi_nhan || data.ho_ten || "",
          email_nguoi_nhan: current.email_nguoi_nhan || data.email || "",
          so_dien_thoai_nguoi_nhan: current.so_dien_thoai_nguoi_nhan || data.so_dien_thoai || "",
          dia_chi_nguoi_nhan: current.dia_chi_nguoi_nhan || data.dia_chi || "",
        }));
        setVouchers(Array.isArray(response?.vouchers) ? response.vouchers : []);
      })
      .catch((requestError) => {
        if (active) {
          setError(requestError.response?.data?.message || "Không tải được thông tin cá nhân. Bạn vẫn có thể tự điền thông tin nhận hàng.");
        }
      });
    return () => { active = false; };
  }, []);

  const discount = Number(appliedVoucher?.so_tien_giam) || 0;
  const availableVouchers = useMemo(() => vouchers.filter(isVoucherAvailable), [vouchers]);
  const total = useMemo(() => Math.max(0, subtotal - discount), [discount, subtotal]);
  const setField = (event) => setForm((current) => ({ ...current, [event.target.name]: event.target.value }));

  const applyVoucher = async () => {
    if (!voucherCode.trim()) return;
    setSaving(true);
    setError("");
    setNotice("");
    try {
      const response = await postWorkspaceData("khach-hang", "ap-ma-giam-gia", {
        ma_voucher: voucherCode.trim(),
        tong_tien: subtotal,
      });
      if (!response?.status || !response.data) {
        throw new Error(response?.message || "Không thể áp dụng mã giảm giá.");
      }
      setAppliedVoucher(response.data);
      setNotice(response.message || "Đã áp dụng mã giảm giá.");
    } catch (requestError) {
      setAppliedVoucher(null);
      setError(requestError.response?.data?.message || requestError.message || "Không thể áp dụng mã giảm giá.");
    } finally {
      setSaving(false);
    }
  };

  const confirmOrder = async () => {
    setSaving(true);
    setError("");
    try {
      const response = await postWorkspaceData("khach-hang", "dat-don-hang", {
        items: items.map((item) => ({
          type: item.type,
          id: item.id,
          quantity: item.quantity,
        })),
        ...form,
        ma_voucher: appliedVoucher?.ma_voucher || undefined,
      });
      if (!response?.status || !response.data?.don_hang_id) {
        throw new Error(response?.message || "Không thể tạo đơn hàng.");
      }
      if (response.data.checkout_url) {
        window.location.assign(response.data.checkout_url);
        return;
      }
      const orderId = response.data.don_hang_id;
      window.sessionStorage.setItem(`donHangVuaDat:${orderId}`, JSON.stringify(response.data));
      clearCart();
      navigate(`/dat-hang-thanh-cong/${orderId}`);
    } catch (requestError) {
      setError(requestError.response?.data?.message || requestError.message || "Không thể tạo đơn hàng.");
    } finally {
      setSaving(false);
    }
  };

  if (!items.length) {
    return (
      <>
        <Navbar luonSang />
        <main className="flex min-h-[65vh] flex-col items-center justify-center px-5 pt-[84px] text-center">
          <h1 className="text-2xl font-extrabold">Giỏ hàng đang trống</h1>
          <p className="mt-2 text-sm text-[#777a80]">Hãy thêm sản phẩm trước khi thanh toán.</p>
          <Link to="/hang-phu-tung" className="mt-5 rounded-lg bg-[#080a0d] px-5 py-3 text-sm font-semibold text-white">Quay lại showroom</Link>
        </main>
        <Footer />
      </>
    );
  }

  const continueFromInformation = (event) => {
    event.preventDefault();
    setError("");
    setStep(2);
  };

  return (
    <>
      <Navbar luonSang />
      <main className="min-h-screen bg-[#fafaf9] pt-[84px]">
        <header className="border-b border-[#e6e6e3] bg-[#f3f3f1] px-5 py-7 sm:px-8 lg:px-12">
          <div className="mx-auto max-w-[1440px]">
            <p className="mb-2 text-sm text-[#777a80]">Trang chủ　›　<Link to="/gio-hang" className="hover:underline">Giỏ hàng</Link>　›　Thanh toán</p>
            <h1 className="text-3xl font-extrabold tracking-tight">Hoàn tất đơn hàng</h1>
            <div className="mt-5"><CheckoutStep active={step} /></div>
          </div>
        </header>

        <div className="mx-auto grid max-w-[1440px] gap-6 px-5 py-8 sm:px-8 lg:grid-cols-[minmax(0,1.45fr)_minmax(340px,0.85fr)] lg:px-12">
          <div>
            {error ? <p role="alert" className="mb-5 rounded-xl bg-[#fae9eb] px-4 py-3 text-sm text-[#9d1026]">{error}</p> : null}
            {notice ? <p role="status" className="mb-5 rounded-xl bg-[#e8f5ec] px-4 py-3 text-sm text-[#24613a]">{notice}</p> : null}

            {step === 1 ? (
              <form onSubmit={continueFromInformation} className="rounded-2xl border border-[#e9e9e6] bg-white p-5 sm:p-7">
                <div className="mb-6 flex items-center gap-3">
                  <span className="flex h-11 w-11 items-center justify-center rounded-xl bg-[#080a0d] text-white"><UserRound className="h-5 w-5" /></span>
                  <div><h2 className="font-bold">Thông tin người đặt hàng</h2><p className="mt-1 text-xs text-[#777a80]">Thông tin để lập đơn hàng và liên hệ giao nhận.</p></div>
                </div>
                <div className="grid gap-4 sm:grid-cols-2">
                  <label className="text-sm font-medium">Họ và tên *
                    <input required name="ten_nguoi_nhan" maxLength={255} value={form.ten_nguoi_nhan} onChange={setField} autoComplete="name" className="mt-1.5 w-full rounded-lg border border-[#dededb] px-3.5 py-3 font-normal outline-none focus:border-[#202226]" />
                  </label>
                  <label className="text-sm font-medium">Số điện thoại *
                    <input required name="so_dien_thoai_nguoi_nhan" maxLength={30} value={form.so_dien_thoai_nguoi_nhan} onChange={setField} autoComplete="tel" className="mt-1.5 w-full rounded-lg border border-[#dededb] px-3.5 py-3 font-normal outline-none focus:border-[#202226]" />
                  </label>
                  <label className="text-sm font-medium sm:col-span-2">Email *
                    <input required type="email" name="email_nguoi_nhan" maxLength={255} value={form.email_nguoi_nhan} onChange={setField} autoComplete="email" className="mt-1.5 w-full rounded-lg border border-[#dededb] px-3.5 py-3 font-normal outline-none focus:border-[#202226]" />
                  </label>
                  <label className="text-sm font-medium sm:col-span-2">Ghi chú cho showroom
                    <textarea name="ghi_chu_don_hang" maxLength={1000} rows={3} value={form.ghi_chu_don_hang} onChange={setField} placeholder="Thông tin thêm về đơn hàng..." className="mt-1.5 w-full resize-y rounded-lg border border-[#dededb] px-3.5 py-3 font-normal outline-none focus:border-[#202226]" />
                  </label>
                </div>
                <div className="mt-6 flex justify-between gap-3">
                  <Link to="/gio-hang" className="inline-flex items-center gap-2 rounded-lg border border-[#dededb] px-4 py-3 text-sm font-semibold"><ArrowLeft className="h-4 w-4" /> Về giỏ hàng</Link>
                  <button type="submit" className="rounded-lg bg-[#c8102e] px-5 py-3 text-sm font-bold text-white">Tiếp tục →</button>
                </div>
              </form>
            ) : null}

            {step === 2 ? (
              <div className="space-y-5">
                <section className="rounded-2xl border border-[#e9e9e6] bg-white p-5 sm:p-7">
                  <div className="mb-5 flex items-center gap-3"><span className="flex h-11 w-11 items-center justify-center rounded-xl bg-[#080a0d] text-white"><Truck className="h-5 w-5" /></span><div><h2 className="font-bold">Hình thức nhận hàng</h2><p className="mt-1 text-xs text-[#777a80]">Chọn nhận tại showroom hoặc giao hàng tận nơi.</p></div></div>
                  <div className="grid gap-3 sm:grid-cols-2">
                    {[
                      { value: "nhan_tai_showroom", title: "Nhận tại showroom", description: "Showroom xác nhận địa điểm và thời gian nhận.", Icon: Store },
                      { value: "giao_tan_noi", title: "Giao hàng tận nơi", description: "Phí giao hàng sẽ được showroom xác nhận trước khi xử lý.", Icon: Truck },
                    ].map(({ value, title, description, Icon }) => (
                      <button key={value} type="button" onClick={() => setForm((current) => ({ ...current, phuong_thuc_nhan: value }))} className={`rounded-xl border p-4 text-left ${form.phuong_thuc_nhan === value ? "border-[#17191c] bg-[#f5f5f3]" : "border-[#e9e9e6]"}`}>
                        <Icon className="mb-3 h-5 w-5 text-[#555a60]" /><strong className="block text-sm">{title}</strong><span className="mt-1 block text-xs leading-5 text-[#777a80]">{description}</span>
                      </button>
                    ))}
                  </div>
                  {form.phuong_thuc_nhan === "giao_tan_noi" ? (
                    <label className="mt-5 block text-sm font-medium">Địa chỉ giao hàng *
                      <span className="mt-1.5 flex items-center gap-2 rounded-lg border border-[#dededb] px-3.5"><MapPin className="h-4 w-4 shrink-0 text-[#85888d]" /><input required name="dia_chi_nguoi_nhan" maxLength={1000} value={form.dia_chi_nguoi_nhan} onChange={setField} autoComplete="street-address" placeholder="Số nhà, đường, phường, quận, tỉnh/thành" className="w-full py-3 font-normal outline-none" /></span>
                    </label>
                  ) : null}
                </section>

                <section className="rounded-2xl border border-[#e9e9e6] bg-white p-5 sm:p-7">
                  <div className="mb-5 flex items-center gap-3"><span className="flex h-11 w-11 items-center justify-center rounded-xl bg-[#080a0d] text-white"><Wallet className="h-5 w-5" /></span><div><h2 className="font-bold">Phương thức thanh toán</h2><p className="mt-1 text-xs text-[#777a80]">Thanh toán thẻ qua VNPay hoặc tiền mặt khi nhận hàng.</p></div></div>
                  <div className="grid gap-3 sm:grid-cols-2">
                    {PAYMENT_METHODS.map(({ value, label, hint, Icon }) => (
                      <button key={value} type="button" onClick={() => setForm((current) => ({ ...current, phuong_thuc_tt: value }))} className={`rounded-xl border p-4 text-left ${form.phuong_thuc_tt === value ? "border-[#17191c] bg-[#f5f5f3]" : "border-[#e9e9e6]"}`}>
                        <Icon className="mb-3 h-5 w-5 text-[#555a60]" /><strong className="block text-sm">{label}</strong><span className="mt-1 block text-xs leading-5 text-[#777a80]">{hint}</span>
                      </button>
                    ))}
                  </div>
                </section>
                <div className="flex justify-between gap-3">
                  <button type="button" onClick={() => setStep(1)} className="rounded-lg border border-[#dededb] px-4 py-3 text-sm font-semibold">← Quay lại</button>
                  <button type="button" onClick={() => { if (form.phuong_thuc_nhan === "giao_tan_noi" && !form.dia_chi_nguoi_nhan.trim()) { setError("Vui lòng nhập địa chỉ giao hàng."); return; } setError(""); setStep(3); }} className="rounded-lg bg-[#c8102e] px-5 py-3 text-sm font-bold text-white">Tiếp tục →</button>
                </div>
              </div>
            ) : null}

            {step === 3 ? (
              <section className="rounded-2xl border border-[#e9e9e6] bg-white p-5 sm:p-7">
                <h2 className="text-lg font-bold">Xác nhận thông tin đặt hàng</h2>
                <div className="mt-5 grid gap-6 sm:grid-cols-2">
                  <div>
                    <h3 className="text-xs font-bold uppercase tracking-wide text-[#85888d]">Người đặt hàng</h3>
                    <dl className="mt-3 space-y-3 text-sm">
                      {[
                        ["Họ tên", form.ten_nguoi_nhan],
                        ["Điện thoại", form.so_dien_thoai_nguoi_nhan],
                        ["Email", form.email_nguoi_nhan],
                        ["Địa chỉ", form.phuong_thuc_nhan === "giao_tan_noi" ? form.dia_chi_nguoi_nhan : "Nhận tại showroom"],
                      ].map(([label, value]) => <div key={label} className="flex justify-between gap-3 border-b border-[#ecece9] pb-2"><dt className="text-[#777a80]">{label}</dt><dd className="text-right font-medium">{value || "—"}</dd></div>)}
                    </dl>
                  </div>
                  <div>
                    <h3 className="text-xs font-bold uppercase tracking-wide text-[#85888d]">Nhận hàng & thanh toán</h3>
                    <dl className="mt-3 space-y-3 text-sm">
                      <div className="flex justify-between gap-3 border-b border-[#ecece9] pb-2"><dt className="text-[#777a80]">Hình thức nhận</dt><dd className="text-right font-medium">{form.phuong_thuc_nhan === "nhan_tai_showroom" ? "Nhận tại showroom" : "Giao hàng tận nơi"}</dd></div>
                      <div className="flex justify-between gap-3 border-b border-[#ecece9] pb-2"><dt className="text-[#777a80]">Thanh toán</dt><dd className="text-right font-medium">{PAYMENT_METHODS.find((method) => method.value === form.phuong_thuc_tt)?.label}</dd></div>
                      <div className="flex justify-between gap-3 border-b border-[#ecece9] pb-2"><dt className="text-[#777a80]">Số món</dt><dd className="text-right font-medium">{items.reduce((count, item) => count + item.quantity, 0)} món sản phẩm</dd></div>
                      {form.ghi_chu_don_hang ? <div className="flex justify-between gap-3"><dt className="text-[#777a80]">Ghi chú</dt><dd className="text-right font-medium">{form.ghi_chu_don_hang}</dd></div> : null}
                    </dl>
                  </div>
                </div>
                <div className="mt-6 flex items-start gap-2 rounded-xl bg-[#f3f3f1] p-4 text-xs leading-5 text-[#63666b]"><Info className="mt-0.5 h-4 w-4 shrink-0" />Đơn hàng được lưu vào hệ thống và chờ showroom xác nhận. Đây chưa phải giao dịch thanh toán trực tuyến; không nhập thông tin thẻ hoặc mật khẩu ngân hàng tại đây.</div>
                <div className="mt-6 flex flex-wrap justify-between gap-3">
                  <button type="button" onClick={() => setStep(2)} className="rounded-lg border border-[#dededb] px-4 py-3 text-sm font-semibold">← Quay lại</button>
                  <button type="button" disabled={saving} onClick={() => void confirmOrder()} className="rounded-lg bg-[#c8102e] px-5 py-3 text-sm font-bold text-white disabled:opacity-50">{saving ? "Đang gửi đơn..." : "Xác nhận đặt hàng"}</button>
                </div>
              </section>
            ) : null}

            {step === 1 ? (
              <div className="mt-4 rounded-xl border border-[#e9e9e6] bg-white px-4 py-3 text-xs leading-5 text-[#777a80]">
                Bằng cách tiếp tục, bạn đồng ý để showroom dùng thông tin này để liên hệ xử lý đơn hàng.
              </div>
            ) : null}
          </div>

          <aside className="h-fit rounded-2xl border border-[#e9e9e6] bg-white p-5 sm:p-6">
            <div className="flex items-center justify-between"><h2 className="text-lg font-bold">Đơn hàng của bạn</h2>{step === 3 ? <button type="button" onClick={() => setStep(1)} className="text-sm font-semibold text-[#c8102e]">Sửa</button> : null}</div>
            <div className="mt-5 max-h-72 space-y-4 overflow-y-auto">
              {items.map((item) => (
                <div key={`${item.type}:${item.id}`} className="flex items-center gap-3">
                  <div className="h-14 w-14 shrink-0 overflow-hidden rounded-lg bg-[#e7e8e5]">{item.anh ? <img src={item.anh} alt="" className="h-full w-full object-cover" /> : null}</div>
                  <div className="min-w-0 flex-1"><strong className="line-clamp-2 text-sm">{item.ten}</strong><p className="mt-1 text-xs text-[#777a80]">SL {item.quantity} · {item.type === "phu_tung" ? "Phụ tùng" : "Gói độ"}</p></div>
                  <span className="shrink-0 text-right text-sm font-semibold">{money(item.giaSo * item.quantity)}</span>
                </div>
              ))}
            </div>
            {step === 1 ? (
              <div className="mt-5 rounded-xl bg-[#f3f3f1] p-3.5">
                <label htmlFor="checkout-voucher" className="text-xs font-semibold">Chọn voucher của bạn</label>
                <div className="mt-2 flex flex-col gap-2 sm:flex-row">
                  <select
                    id="checkout-voucher"
                    value={voucherCode}
                    onChange={(event) => { setVoucherCode(event.target.value); setAppliedVoucher(null); setError(""); setNotice(""); }}
                    className="min-w-0 flex-1 rounded-lg border border-[#dededb] bg-white px-3 py-2.5 text-sm outline-none"
                  >
                    <option value="">Chọn mã giảm giá</option>
                    {availableVouchers.map((voucher) => {
                      const discountLabel = voucher.loai === "phan_tram"
                        ? `${voucher.gia_tri}%`
                        : money(voucher.gia_tri);
                      return (
                        <option key={voucher.id} value={voucher.code}>
                          {voucher.code} · giảm {discountLabel}
                        </option>
                      );
                    })}
                  </select>
                  <button type="button" disabled={saving || !voucherCode} onClick={() => void applyVoucher()} className="rounded-lg bg-[#080a0d] px-3 py-2.5 text-xs font-semibold text-white disabled:opacity-50">Áp dụng</button>
                </div>
                {!availableVouchers.length ? <p className="mt-2 text-xs text-[#777a80]">Bạn chưa có voucher khả dụng.</p> : null}
              </div>
            ) : null}
            <div className="mt-5 space-y-3 border-t border-[#ecece9] pt-4 text-sm">
              <div className="flex justify-between text-[#777a80]"><span>Tạm tính</span><strong className="text-[#17191c]">{money(subtotal)}</strong></div>
              <div className="flex justify-between text-[#777a80]"><span>Giảm giá</span><span>{discount ? `− ${money(discount)}` : "—"}</span></div>
              <div className="flex justify-between text-[#777a80]"><span>Phí giao hàng</span><span>{form.phuong_thuc_nhan === "nhan_tai_showroom" ? "Miễn phí" : "Xác nhận sau"}</span></div>
              <div className="border-t border-[#ecece9] pt-4">
                <span className="text-xs text-[#85888d]">Tổng tạm tính</span>
                <strong className="mt-1 block text-2xl font-extrabold text-[#b10e27]">{money(total)}</strong>
                <small className="mt-1 block text-xs text-[#85888d]">Chưa ghi nhận thanh toán. Phí giao hàng (nếu có) được xác nhận trước khi xử lý.</small>
              </div>
            </div>
          </aside>
        </div>
      </main>
      <Footer />
    </>
  );
}
