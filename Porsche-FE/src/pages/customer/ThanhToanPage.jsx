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

const TIME_SLOTS = ["08:00", "09:00", "10:00", "11:00", "13:00", "14:00", "15:00", "16:00", "17:00"];
const DONG_XE = ["Porsche 911", "Porsche 718 Cayman", "Porsche 718 Boxster", "Porsche Cayenne", "Porsche Macan", "Porsche Panamera", "Porsche Taycan", "Dòng xe khác"];
const DEFAULT_DELIVERY_MAX = 5000000;
const fieldClass = "mt-1.5 w-full rounded-lg border border-[#dededb] px-3 py-2 text-[13px] font-normal outline-none focus:border-[#202226]";

function CheckoutStep({ active }) {
  const steps = ["Thông tin", "Nhận hàng & thanh toán", "Xác nhận"];
  return (
    <div className="flex flex-wrap items-center gap-3">
      {steps.map((label, index) => {
        const number = index + 1;
        const complete = active > number;
        return (
          <div key={label} className="flex items-center gap-2">
            <span className={`flex h-7 w-7 items-center justify-center rounded-full border text-[11px] font-bold ${complete ? "border-[#b8914a] bg-[#b8914a] text-white" : active === number ? "border-[#080a0d] bg-[#080a0d] text-white" : "border-[#dededb] bg-white text-[#777a80]"}`}>
              {complete ? <Check className="h-4 w-4" /> : number}
            </span>
            <span className={`text-[13px] ${active === number ? "font-semibold text-[#17191c]" : "text-[#777a80]"}`}>{label}</span>
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
  const { selectedItems: items, selectedSubtotal: subtotal, clearSelected: clearCart } = useCart();
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
  const [booking, setBooking] = useState({ bien_so_xe: "", ten_xe: "", ngay_dat: "", gio_dat: "", id_chuyen_gia: "", id_khu_vuc_workshop: "" });
  const [workshopOptions, setWorkshopOptions] = useState({ chuyen_gias: [], khu_vuc_workshops: [], delivery_max_subtotal: DEFAULT_DELIVERY_MAX });
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

  useEffect(() => {
    let active = true;
    getWorkspaceData("khach-hang", "workshop/options")
      .then((response) => {
        const data = response?.data ?? response;
        if (!active || !data) return;
        setWorkshopOptions({
          chuyen_gias: Array.isArray(data.chuyen_gias) ? data.chuyen_gias : [],
          khu_vuc_workshops: Array.isArray(data.khu_vuc_workshops) ? data.khu_vuc_workshops : [],
          delivery_max_subtotal: Number(data.delivery_max_subtotal) || DEFAULT_DELIVERY_MAX,
        });
      })
      .catch(() => {});
    return () => { active = false; };
  }, []);

  const deliveryEligible = useMemo(
    () => items.every((item) => item.type !== "goi_do") && subtotal <= workshopOptions.delivery_max_subtotal,
    [items, subtotal, workshopOptions.delivery_max_subtotal],
  );
  const setBookingField = (event) => setBooking((current) => ({ ...current, [event.target.name]: event.target.value }));
  const [today] = useState(() => new Date(Date.now() - new Date().getTimezoneOffset() * 60000).toISOString().slice(0, 10));

  const validateReceiving = () => {
    if (form.phuong_thuc_nhan === "giao_tan_noi") {
      if (!deliveryEligible) return "Đơn hàng này cần lắp đặt tại showroom. Vui lòng chọn nhận tại showroom.";
      if (!form.dia_chi_nguoi_nhan.trim()) return "Vui lòng nhập địa chỉ giao hàng.";
      return "";
    }
    if (Object.values(booking).some((value) => !String(value).trim())) return "Vui lòng nhập đầy đủ thông tin xe và chọn lịch lắp đặt tại showroom.";
    if (new Date(`${booking.ngay_dat}T${booking.gio_dat}`).getTime() <= Date.now()) return "Khung giờ lắp đặt phải ở thời điểm trong tương lai.";
    return "";
  };

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
        lich_lap_dat: form.phuong_thuc_nhan === "nhan_tai_showroom" ? booking : undefined,
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
        <main className="flex min-h-[65vh] flex-col items-center justify-center px-5 pt-[80px] text-center">
          <h1 className="!my-0 text-xl font-extrabold">Chưa có sản phẩm nào được chọn</h1>
          <p className="mt-2 text-[13px] text-[#777a80]">Hãy chọn sản phẩm trong giỏ hàng trước khi thanh toán.</p>
          <Link to="/gio-hang" className="mt-5 rounded-lg bg-[#080a0d] px-5 py-3 text-[13px] font-semibold text-white">Về giỏ hàng</Link>
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
      <main className="min-h-screen bg-[#fafaf9] pt-[80px]">
        <header className="border-b border-[#e6e6e3] bg-[#f3f3f1] px-[1.3rem] py-5">
          <div className="mx-auto">
            <p className="mb-1.5 text-[12px] text-[#777a80]">Trang chủ　›　<Link to="/gio-hang" className="hover:underline">Giỏ hàng</Link>　›　Thanh toán</p>
            <h1 className="!my-0 text-[26px] font-extrabold tracking-tight">Hoàn tất đơn hàng</h1>
            <div className="mt-3"><CheckoutStep active={step} /></div>
          </div>
        </header>

        <div className="mx-auto grid gap-5 px-[1.3rem] py-6 lg:grid-cols-[minmax(0,1.45fr)_minmax(340px,0.85fr)]">
          <div>
            {error ? <p role="alert" className="mb-4 rounded-xl bg-[#fae9eb] px-4 py-3 text-[13px] text-[#8C001B]">{error}</p> : null}
            {notice ? <p role="status" className="mb-4 rounded-xl bg-[#e8f5ec] px-4 py-3 text-[13px] text-[#24613a]">{notice}</p> : null}

            {step === 1 ? (
              <form onSubmit={continueFromInformation} className="rounded-2xl border border-[#e9e9e6] bg-white p-5">
                <div className="mb-4 flex items-center gap-3">
                  <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-[#080a0d] text-white"><UserRound className="h-5 w-5" /></span>
                  <div><h2 className="font-bold">Thông tin người đặt hàng</h2><p className="mt-1 text-[11px] text-[#777a80]">Thông tin để lập đơn hàng và liên hệ giao nhận.</p></div>
                </div>
                <div className="grid gap-4 sm:grid-cols-2">
                  <label className="text-[13px] font-medium">Họ và tên *
                    <input required name="ten_nguoi_nhan" maxLength={255} value={form.ten_nguoi_nhan} onChange={setField} autoComplete="name" className="mt-1.5 w-full rounded-lg border border-[#dededb] px-3 py-2 text-[13px] font-normal outline-none focus:border-[#202226]" />
                  </label>
                  <label className="text-[13px] font-medium">Số điện thoại *
                    <input required name="so_dien_thoai_nguoi_nhan" maxLength={30} value={form.so_dien_thoai_nguoi_nhan} onChange={setField} autoComplete="tel" className="mt-1.5 w-full rounded-lg border border-[#dededb] px-3 py-2 text-[13px] font-normal outline-none focus:border-[#202226]" />
                  </label>
                  <label className="text-[13px] font-medium sm:col-span-2">Email *
                    <input required type="email" name="email_nguoi_nhan" maxLength={255} value={form.email_nguoi_nhan} onChange={setField} autoComplete="email" className="mt-1.5 w-full rounded-lg border border-[#dededb] px-3 py-2 text-[13px] font-normal outline-none focus:border-[#202226]" />
                  </label>
                  <label className="text-[13px] font-medium sm:col-span-2">Ghi chú cho showroom
                    <textarea name="ghi_chu_don_hang" maxLength={1000} rows={3} value={form.ghi_chu_don_hang} onChange={setField} placeholder="Thông tin thêm về đơn hàng..." className="mt-1.5 w-full resize-y rounded-lg border border-[#dededb] px-3 py-2 text-[13px] font-normal outline-none focus:border-[#202226]" />
                  </label>
                </div>
                <div className="mt-4 flex justify-between gap-3">
                  <Link to="/gio-hang" className="inline-flex items-center gap-2 rounded-lg border border-[#dededb] px-4 py-2.5 text-[13px] font-semibold"><ArrowLeft className="h-4 w-4" /> Về giỏ hàng</Link>
                  <button type="submit" className="rounded-lg bg-[#AC0021] px-5 py-2.5 text-[13px] font-bold text-white">Tiếp tục →</button>
                </div>
              </form>
            ) : null}

            {step === 2 ? (
              <div className="space-y-4">
                <section className="rounded-2xl border border-[#e9e9e6] bg-white p-5">
                  <div className="mb-4 flex items-center gap-3"><span className="flex h-9 w-9 items-center justify-center rounded-xl bg-[#080a0d] text-white"><Truck className="h-5 w-5" /></span><div><h2 className="font-bold">Hình thức nhận hàng</h2>                                    <p className="mt-1 text-[11px] text-[#777a80]">Nhận tại showroom để đặt lịch lắp đặt, hoặc giao tận nơi cho phụ kiện nhỏ lẻ.</p></div></div>
                  <div className="grid gap-3 sm:grid-cols-2">
                    {[
                                        { value: "nhan_tai_showroom", title: "Nhận & lắp đặt tại showroom", description: "Đặt lịch hẹn để kỹ thuật viên lắp đặt phụ tùng cho xe của bạn.", Icon: Store, disabled: false },
                                        { value: "giao_tan_noi", title: "Giao hàng tận nơi", description: deliveryEligible ? "Dành cho phụ kiện nhỏ lẻ, bạn có thể tự lắp. Phí giao hàng được showroom xác nhận." : `Chỉ áp dụng cho phụ kiện nhỏ lẻ (không gồm gói độ) có tổng tiền hàng tối đa ${money(workshopOptions.delivery_max_subtotal)}.`, Icon: Truck, disabled: !deliveryEligible },
                                      ].map(({ value, title, description, Icon, disabled }) => (
                                        <button key={value} type="button" disabled={disabled} onClick={() => setForm((current) => ({ ...current, phuong_thuc_nhan: value }))} className={`rounded-xl border p-4 text-left disabled:cursor-not-allowed disabled:opacity-50 ${form.phuong_thuc_nhan === value ? "border-[#17191c] bg-[#f5f5f3]" : "border-[#e9e9e6]"}`}>
                        <Icon className="mb-3 h-5 w-5 text-[#555a60]" /><strong className="block text-[13px]">{title}</strong><span className="mt-1 block text-[11px] leading-5 text-[#777a80]">{description}</span>
                      </button>
                    ))}
                  </div>
                                    {form.phuong_thuc_nhan === "nhan_tai_showroom" ? (
                                      <div className="mt-4 grid gap-3 sm:grid-cols-2">
                                        <p className="rounded-xl bg-[#f3f3f1] p-3 text-[11px] leading-5 text-[#63666b] sm:col-span-2">Lịch lắp đặt sẽ được lưu vào mục Lịch hẹn của bạn và gửi qua email xác nhận.</p>
                                        <label className="text-[13px] font-medium">Dòng xe *
                                          <select required name="ten_xe" value={booking.ten_xe} onChange={setBookingField} className={fieldClass}>
                                            <option value="">Chọn dòng xe</option>
                                            {DONG_XE.map((model) => <option key={model} value={model}>{model}</option>)}
                                          </select>
                                        </label>
                                        <label className="text-[13px] font-medium">Biển số xe *
                                          <input required name="bien_so_xe" maxLength={50} value={booking.bien_so_xe} onChange={setBookingField} placeholder="VD: 51A-123.45" className={fieldClass} />
                                        </label>
                                        <label className="text-[13px] font-medium">Ngày lắp đặt *
                                          <input required type="date" name="ngay_dat" min={today} value={booking.ngay_dat} onChange={setBookingField} className={fieldClass} />
                                        </label>
                                        <label className="text-[13px] font-medium">Giờ *
                                          <select required name="gio_dat" value={booking.gio_dat} onChange={setBookingField} className={fieldClass}>
                                            <option value="">Chọn giờ</option>
                                            {TIME_SLOTS.map((slot) => <option key={slot} value={slot}>{slot}</option>)}
                                          </select>
                                        </label>
                                        <label className="text-[13px] font-medium">Chuyên gia lắp đặt *
                                          <select required name="id_chuyen_gia" value={booking.id_chuyen_gia} onChange={setBookingField} className={fieldClass}>
                                            <option value="">Chọn chuyên gia</option>
                                            {workshopOptions.chuyen_gias.map((expert) => <option key={expert.id} value={expert.id}>{expert.ho_ten}</option>)}
                                          </select>
                                        </label>
                                        <label className="text-[13px] font-medium">Khu vực workshop *
                                          <select required name="id_khu_vuc_workshop" value={booking.id_khu_vuc_workshop} onChange={setBookingField} className={fieldClass}>
                                            <option value="">Chọn khu vực</option>
                                            {workshopOptions.khu_vuc_workshops.map((area) => <option key={area.id} value={area.id}>{area.ten_khu_vuc}</option>)}
                                          </select>
                                        </label>
                                      </div>
                                    ) : null}
                  {form.phuong_thuc_nhan === "giao_tan_noi" ? (
                    <label className="mt-5 block text-[13px] font-medium">Địa chỉ giao hàng *
                      <span className="mt-1.5 flex items-center gap-2 rounded-lg border border-[#dededb] px-3.5"><MapPin className="h-4 w-4 shrink-0 text-[#85888d]" /><input required name="dia_chi_nguoi_nhan" maxLength={1000} value={form.dia_chi_nguoi_nhan} onChange={setField} autoComplete="street-address" placeholder="Số nhà, đường, phường, quận, tỉnh/thành" className="w-full py-3 font-normal outline-none" /></span>
                    </label>
                  ) : null}
                </section>

                <section className="rounded-2xl border border-[#e9e9e6] bg-white p-5">
                  <div className="mb-4 flex items-center gap-3"><span className="flex h-9 w-9 items-center justify-center rounded-xl bg-[#080a0d] text-white"><Wallet className="h-5 w-5" /></span><div><h2 className="font-bold">Phương thức thanh toán</h2><p className="mt-1 text-[11px] text-[#777a80]">Thanh toán thẻ qua VNPay hoặc tiền mặt khi nhận hàng.</p></div></div>
                  <div className="grid gap-3 sm:grid-cols-2">
                    {PAYMENT_METHODS.map(({ value, label, hint, Icon }) => (
                      <button key={value} type="button" onClick={() => setForm((current) => ({ ...current, phuong_thuc_tt: value }))} className={`rounded-xl border p-4 text-left ${form.phuong_thuc_tt === value ? "border-[#17191c] bg-[#f5f5f3]" : "border-[#e9e9e6]"}`}>
                        <Icon className="mb-3 h-5 w-5 text-[#555a60]" /><strong className="block text-[13px]">{label}</strong><span className="mt-1 block text-[11px] leading-5 text-[#777a80]">{hint}</span>
                      </button>
                    ))}
                  </div>
                </section>
                <div className="flex justify-between gap-3">
                  <button type="button" onClick={() => setStep(1)} className="rounded-lg border border-[#dededb] px-4 py-2.5 text-[13px] font-semibold">← Quay lại</button>
                  <button type="button" onClick={() => { const problem = validateReceiving(); if (problem) { setError(problem); return; } setError(""); setStep(3); }} className="rounded-lg bg-[#AC0021] px-5 py-2.5 text-[13px] font-bold text-white">Tiếp tục →</button>
                </div>
              </div>
            ) : null}

            {step === 3 ? (
              <section className="rounded-2xl border border-[#e9e9e6] bg-white p-5">
                <h2 className="text-[15px] font-bold">Xác nhận thông tin đặt hàng</h2>
                <div className="mt-4 grid gap-5 sm:grid-cols-2">
                  <div>
                    <h3 className="text-[11px] font-bold uppercase tracking-wide text-[#85888d]">Người đặt hàng</h3>
                    <dl className="mt-3 space-y-2.5 text-[13px]">
                      {[
                        ["Họ tên", form.ten_nguoi_nhan],
                        ["Điện thoại", form.so_dien_thoai_nguoi_nhan],
                        ["Email", form.email_nguoi_nhan],
                        ["Địa chỉ", form.phuong_thuc_nhan === "giao_tan_noi" ? form.dia_chi_nguoi_nhan : "Nhận tại showroom"],
                      ].map(([label, value]) => <div key={label} className="flex justify-between gap-3 border-b border-[#ecece9] pb-2"><dt className="text-[#777a80]">{label}</dt><dd className="text-right font-medium">{value || "—"}</dd></div>)}
                    </dl>
                  </div>
                  <div>
                    <h3 className="text-[11px] font-bold uppercase tracking-wide text-[#85888d]">Nhận hàng & thanh toán</h3>
                    <dl className="mt-3 space-y-2.5 text-[13px]">
                      <div className="flex justify-between gap-3 border-b border-[#ecece9] pb-2"><dt className="text-[#777a80]">Hình thức nhận</dt><dd className="text-right font-medium">{form.phuong_thuc_nhan === "nhan_tai_showroom" ? "Nhận & lắp đặt tại showroom" : "Giao hàng tận nơi"}</dd></div>
                      {form.phuong_thuc_nhan === "nhan_tai_showroom" ? (
                        <div className="flex justify-between gap-3 border-b border-[#ecece9] pb-2"><dt className="text-[#777a80]">Lịch lắp đặt</dt><dd className="text-right font-medium">{booking.gio_dat} · {booking.ngay_dat ? new Date(booking.ngay_dat).toLocaleDateString("vi-VN") : ""}<br />{booking.ten_xe} · {booking.bien_so_xe.toUpperCase()}</dd></div>
                      ) : null}
                      <div className="flex justify-between gap-3 border-b border-[#ecece9] pb-2"><dt className="text-[#777a80]">Thanh toán</dt><dd className="text-right font-medium">{PAYMENT_METHODS.find((method) => method.value === form.phuong_thuc_tt)?.label}</dd></div>
                      <div className="flex justify-between gap-3 border-b border-[#ecece9] pb-2"><dt className="text-[#777a80]">Số món</dt><dd className="text-right font-medium">{items.reduce((count, item) => count + item.quantity, 0)} món sản phẩm</dd></div>
                      {form.ghi_chu_don_hang ? <div className="flex justify-between gap-3"><dt className="text-[#777a80]">Ghi chú</dt><dd className="text-right font-medium">{form.ghi_chu_don_hang}</dd></div> : null}
                    </dl>
                  </div>
                </div>
                <div className="mt-4 flex items-start gap-2 rounded-xl bg-[#f3f3f1] p-4 text-[11px] leading-5 text-[#63666b]"><Info className="mt-0.5 h-4 w-4 shrink-0" />Đơn hàng được lưu vào hệ thống và chờ showroom xác nhận. Đây chưa phải giao dịch thanh toán trực tuyến; không nhập thông tin thẻ hoặc mật khẩu ngân hàng tại đây.</div>
                <div className="mt-4 flex flex-wrap justify-between gap-3">
                  <button type="button" onClick={() => setStep(2)} className="rounded-lg border border-[#dededb] px-4 py-2.5 text-[13px] font-semibold">← Quay lại</button>
                  <button type="button" disabled={saving} onClick={() => void confirmOrder()} className="rounded-lg bg-[#AC0021] px-5 py-2.5 text-[13px] font-bold text-white disabled:opacity-50">{saving ? "Đang gửi đơn..." : "Xác nhận đặt hàng"}</button>
                </div>
              </section>
            ) : null}

            {step === 1 ? (
              <div className="mt-4 rounded-xl border border-[#e9e9e6] bg-white px-4 py-3 text-[11px] leading-5 text-[#777a80]">
                Bằng cách tiếp tục, bạn đồng ý để showroom dùng thông tin này để liên hệ xử lý đơn hàng.
              </div>
            ) : null}
          </div>

          <aside className="h-fit rounded-2xl border border-[#e9e9e6] bg-white p-5">
            <div className="flex items-center justify-between"><h2 className="text-[15px] font-bold">Đơn hàng của bạn</h2>{step === 3 ? <button type="button" onClick={() => setStep(1)} className="text-[13px] font-semibold text-[#AC0021]">Sửa</button> : null}</div>
            <div className="mt-4 max-h-72 space-y-3 overflow-y-auto">
              {items.map((item) => (
                <div key={`${item.type}:${item.id}`} className="flex items-center gap-3">
                  <div className="h-14 w-14 shrink-0 overflow-hidden rounded-lg bg-[#e7e8e5]">{item.anh ? <img src={item.anh} alt="" className="h-full w-full object-cover" /> : null}</div>
                  <div className="min-w-0 flex-1"><strong className="line-clamp-2 text-[13px]">{item.ten}</strong><p className="mt-1 text-[11px] text-[#777a80]">SL {item.quantity} · {item.type === "phu_tung" ? "Phụ tùng" : "Gói độ"}</p></div>
                  <span className="shrink-0 text-right text-[13px] font-semibold">{money(item.giaSo * item.quantity)}</span>
                </div>
              ))}
            </div>
            {step === 1 ? (
              <div className="mt-4 rounded-xl bg-[#f3f3f1] p-3">
                <label htmlFor="checkout-voucher" className="text-[11px] font-semibold">Chọn voucher của bạn</label>
                <div className="mt-2 flex flex-col gap-2 sm:flex-row">
                  <select
                    id="checkout-voucher"
                    value={voucherCode}
                    onChange={(event) => { setVoucherCode(event.target.value); setAppliedVoucher(null); setError(""); setNotice(""); }}
                    className="min-w-0 flex-1 rounded-lg border border-[#dededb] bg-white px-3 py-2 text-[13px] outline-none"
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
                  <button type="button" disabled={saving || !voucherCode} onClick={() => void applyVoucher()} className="rounded-lg bg-[#080a0d] px-3 py-2 text-[11px] font-semibold text-white disabled:opacity-50">Áp dụng</button>
                </div>
                {!availableVouchers.length ? <p className="mt-2 text-[11px] text-[#777a80]">Bạn chưa có voucher khả dụng.</p> : null}
              </div>
            ) : null}
            <div className="mt-4 space-y-2.5 border-t border-[#ecece9] pt-4 text-[13px]">
              <div className="flex justify-between text-[#777a80]"><span>Tạm tính</span><strong className="text-[#17191c]">{money(subtotal)}</strong></div>
              <div className="flex justify-between text-[#777a80]"><span>Giảm giá</span><span>{discount ? `− ${money(discount)}` : "—"}</span></div>
              <div className="flex justify-between text-[#777a80]"><span>Phí giao hàng</span><span>{form.phuong_thuc_nhan === "nhan_tai_showroom" ? "Miễn phí" : "Xác nhận sau"}</span></div>
              <div className="border-t border-[#ecece9] pt-4">
                <span className="text-[11px] text-[#85888d]">Tổng tạm tính</span>
                <strong className="mt-1 block text-xl font-extrabold text-[#AC0021]">{money(total)}</strong>
                <small className="mt-1 block text-[11px] text-[#85888d]">Chưa ghi nhận thanh toán. Phí giao hàng (nếu có) được xác nhận trước khi xử lý.</small>
              </div>
            </div>
          </aside>
        </div>
      </main>
      <Footer />
    </>
  );
}
