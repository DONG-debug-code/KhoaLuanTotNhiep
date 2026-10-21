import { useCallback, useEffect, useMemo, useState } from "react";
import {
  AlertCircle, ArrowLeft, ArrowRight, CalendarCheck, CalendarDays, CarFront,
  Check, CheckCircle2, Clock3, CreditCard, FileText, LockKeyhole,
  MessageSquareText, RefreshCw, ShieldCheck, Smartphone, Sparkles,
  UserRound, UserRoundPen, Wrench,
} from "lucide-react";
import CustomerWorkspaceLayout from "../../components/workspaces/CustomerWorkspaceLayout";
import {
  changeWorkspacePassword, clearAIHistory, getAIHistory, getWorkspaceData,
  postWorkspaceData, updateWorkspaceProfile, uploadWorkspaceAvatar,
} from "../../api/WorkspaceApi";

const PROFILE_TABS = [
  { key: "details", label: "Thông tin cá nhân", icon: UserRound },
  { key: "avatar", label: "Ảnh đại diện", icon: UserRoundPen },
  { key: "password", label: "Đổi mật khẩu", icon: LockKeyhole },
  { key: "devices", label: "Thiết bị đăng nhập", icon: Smartphone },
];

const APPOINTMENT_FILTERS = [
  { key: "all", label: "Tất cả" },
  { key: "active", label: "Đang xử lý" },
  { key: "done", label: "Hoàn thành" },
  { key: "cancelled", label: "Đã hủy" },
];

const INITIAL_BOOKING = {
  bien_so_xe: "",
  ten_xe: "",
  ngay_dat: "",
  gio_dat: "",
  id_chuyen_gia: "",
  id_khu_vuc_workshop: "",
  ghi_chu: "",
};

function rowsFrom(data) {
  if (Array.isArray(data)) return data;
  if (Array.isArray(data?.data)) return data.data;
  return [];
}

function money(value) {
  const amount = Number(value);
  return Number.isFinite(amount) ? `${amount.toLocaleString("vi-VN")} ₫` : "—";
}

function formatDate(value) {
  if (!value) return "Chưa cập nhật";
  const date = new Date(value);
  return Number.isNaN(date.getTime()) ? String(value) : date.toLocaleDateString("vi-VN");
}

function appointmentState(status) {
  const value = String(status ?? "").toLowerCase();
  if (["2", "hoan_thanh", "hoàn thành", "đã hoàn thành"].includes(value)) return "done";
  if (["3", "da_huy", "đã hủy", "hủy"].includes(value)) return "cancelled";
  return "active";
}

function appointmentStateLabel(status) {
  const state = appointmentState(status);
  if (state === "done") return "Hoàn thành";
  if (state === "cancelled") return "Đã hủy";
  if (String(status) === "1") return "Đã xác nhận";
  if (String(status) === "0") return "Chờ xác nhận";
  return "Đang xử lý";
}

function appointmentDate(item) {
  if (!item.ngay_dat) return null;
  const date = new Date(`${item.ngay_dat}T${item.gio_dat || "00:00"}:00`);
  return Number.isNaN(date.getTime()) ? null : date;
}

function vehicleName(vehicle) {
  const text = vehicle.thong_tin_xe || vehicle.ten_xe || "Xe Porsche";
  return text.split(/[·|,]/)[0].trim() || "Xe Porsche";
}

function pageTitle(key) {
  return {
    profile: "Hồ sơ cá nhân",
    garage: "Garage của tôi",
    appointments: "Lịch hẹn của tôi",
    orders: "Lịch sử mua hàng",
    booking: "Đặt lịch workshop",
    "ai-history": "Lịch sử chat AI",
  }[key] || "Garage của tôi";
}

function MetricCard({ label, value, icon: Icon, hint }) {
  return (
    <article className="rounded-2xl border border-[#e9e9e6] bg-white p-5">
      <div className="flex items-center justify-between gap-3">
        <span className="text-sm text-[#777a80]">{label}</span>
        <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-[#f3f3f1] text-[#565b61]">
          <Icon className="h-[18px] w-[18px]" />
        </span>
      </div>
      <p className="mt-3 text-2xl font-extrabold tracking-tight text-[#111317]">{value}</p>
      {hint ? <p className="mt-1 text-xs text-[#85888d]">{hint}</p> : null}
    </article>
  );
}

function CustomerPortalPage() {
  const [activeKey, setActiveKey] = useState("garage");
  const [profileTab, setProfileTab] = useState("details");
  const [profile, setProfile] = useState(null);
  const [appointments, setAppointments] = useState([]);
  const [bookingOptions, setBookingOptions] = useState({ chuyen_gias: [], khu_vuc_workshops: [] });
  const [invoices, setInvoices] = useState([]);
  const [history, setHistory] = useState([]);
  const [loading, setLoading] = useState(false);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState("");
  const [notice, setNotice] = useState("");
  const [appointmentFilter, setAppointmentFilter] = useState("all");
  const [bookingStep, setBookingStep] = useState(1);
  const [booking, setBooking] = useState(INITIAL_BOOKING);
  const [password, setPassword] = useState({ old_password: "", password: "", password_confirmation: "" });

  const loadData = useCallback(async () => {
    setLoading(true);
    setError("");
    try {
      if (activeKey === "profile" || activeKey === "garage") {
        const profileResponse = await getWorkspaceData("khach-hang", "profile/data");
        const data = profileResponse?.data ?? profileResponse;
        setProfile({ ...(data?.profile ?? data), garage: rowsFrom(data?.garage) });

        if (activeKey === "garage") {
          const [appointmentsResponse, invoiceResponse] = await Promise.all([
            getWorkspaceData("khach-hang", "lich-hen/data"),
            getWorkspaceData("khach-hang", "lich-su-mua-hang"),
          ]);
          setAppointments(rowsFrom(appointmentsResponse));
          setInvoices(rowsFrom(invoiceResponse));
        }
      } else if (activeKey === "appointments") {
        setAppointments(rowsFrom(await getWorkspaceData("khach-hang", "lich-hen/data")));
      } else if (activeKey === "booking") {
        const [response, profileResponse] = await Promise.all([
          getWorkspaceData("khach-hang", "workshop/options"),
          getWorkspaceData("khach-hang", "profile/data"),
        ]);
        const options = response?.data ?? response;
        const profileData = profileResponse?.data ?? profileResponse;
        setBookingOptions({
          chuyen_gias: rowsFrom(options?.chuyen_gias),
          khu_vuc_workshops: rowsFrom(options?.khu_vuc_workshops),
        });
        setProfile({ ...(profileData?.profile ?? profileData), garage: rowsFrom(profileData?.garage) });
      } else if (activeKey === "orders") {
        setInvoices(rowsFrom(await getWorkspaceData("khach-hang", "lich-su-mua-hang")));
      } else if (activeKey === "ai-history") {
        setHistory(await getAIHistory());
      }
    } catch (requestError) {
      setError(requestError.response?.data?.message || requestError.message || "Không thể tải dữ liệu.");
    } finally {
      setLoading(false);
    }
  }, [activeKey]);

  useEffect(() => {
    let active = true;
    Promise.resolve().then(() => {
      if (active) void loadData();
    });
    return () => {
      active = false;
    };
  }, [loadData]);

  const upcomingAppointments = useMemo(() => appointments.filter((item) => {
    const date = appointmentDate(item);
    return appointmentState(item.trang_thai) === "active" && date && date >= new Date();
  }), [appointments]);

  const serviceSpend = useMemo(() => invoices.reduce((total, invoice) => {
    return total + (Number(invoice.tong_tien ?? invoice.thanh_tien ?? invoice.so_tien) || 0);
  }, 0), [invoices]);

  const updateProfileField = (event) => {
    const { name, value } = event.target;
    setProfile((current) => ({ ...current, [name]: value }));
  };

  const saveProfile = async (event) => {
    event.preventDefault();
    setSaving(true);
    setError("");
    setNotice("");
    try {
      await updateWorkspaceProfile("khach-hang", {
        ho_ten: profile?.ho_ten ?? "",
        so_dien_thoai: profile?.so_dien_thoai ?? "",
        dia_chi: profile?.dia_chi ?? "",
      });
      setNotice("Đã cập nhật hồ sơ cá nhân.");
    } catch (requestError) {
      setError(requestError.response?.data?.message || requestError.message || "Không thể cập nhật hồ sơ.");
    } finally {
      setSaving(false);
    }
  };

  const saveAvatar = async (event) => {
    const file = event.target.files?.[0];
    if (!file) return;
    if (!["image/jpeg", "image/png", "image/webp"].includes(file.type) || file.size > 2 * 1024 * 1024) {
      setError("Ảnh đại diện phải là JPG, PNG hoặc WebP và không vượt quá 2 MB.");
      event.target.value = "";
      return;
    }
    setSaving(true);
    setError("");
    setNotice("");
    try {
      const result = await uploadWorkspaceAvatar("khach-hang", file);
      setProfile((current) => ({ ...current, avatar: result.avatar_url }));
      setNotice("Đã cập nhật ảnh đại diện.");
    } catch (requestError) {
      setError(requestError.response?.data?.message || requestError.message || "Không thể tải ảnh lên.");
    } finally {
      setSaving(false);
      event.target.value = "";
    }
  };

  const changePassword = async (event) => {
    event.preventDefault();
    setSaving(true);
    setError("");
    setNotice("");
    try {
      await changeWorkspacePassword("khach-hang", password);
      setPassword({ old_password: "", password: "", password_confirmation: "" });
      setNotice("Đã đổi mật khẩu.");
    } catch (requestError) {
      setError(requestError.response?.data?.message || requestError.message || "Không thể đổi mật khẩu.");
    } finally {
      setSaving(false);
    }
  };

  const submitBooking = async (event) => {
    event.preventDefault();
    setSaving(true);
    setError("");
    setNotice("");
    try {
      await postWorkspaceData("khach-hang", "dat-lich-kham", {
        ...booking,
        thong_tin_xe: [booking.ten_xe, booking.bien_so_xe].filter(Boolean).join(" · "),
      });
      setBooking(INITIAL_BOOKING);
      setBookingStep(1);
      setNotice("Đã gửi yêu cầu đặt lịch. Bạn có thể theo dõi trạng thái ở mục Lịch hẹn của tôi.");
      setActiveKey("appointments");
    } catch (requestError) {
      setError(requestError.response?.data?.message || requestError.message || "Không thể tạo lịch hẹn.");
      setBookingStep(3);
    } finally {
      setSaving(false);
    }
  };

  const cancelAppointment = async (appointment) => {
    if (!window.confirm(`Bạn muốn hủy phiếu ${appointment.ma_dat_lich || appointment.id}?`)) return;
    setSaving(true);
    setError("");
    setNotice("");
    try {
      await postWorkspaceData("khach-hang", "huy-lich-kham", {
        id: appointment.id,
        ma_dat_lich: appointment.ma_dat_lich,
      });
      await loadData();
      setNotice("Đã gửi yêu cầu hủy lịch hẹn.");
    } catch (requestError) {
      setError(requestError.response?.data?.message || requestError.message || "Không thể hủy lịch hẹn.");
    } finally {
      setSaving(false);
    }
  };

  const clearHistory = async () => {
    if (!window.confirm("Xóa toàn bộ lịch sử tư vấn AI của tài khoản?")) return;
    setSaving(true);
    setError("");
    setNotice("");
    try {
      await clearAIHistory();
      setHistory([]);
      setNotice("Đã xóa lịch sử tư vấn AI.");
    } catch (requestError) {
      setError(requestError.response?.data?.message || requestError.message || "Không thể xóa lịch sử AI.");
    } finally {
      setSaving(false);
    }
  };

  const goTo = (key) => {
    setError("");
    setNotice("");
    setActiveKey(key);
    if (key === "booking") setBookingStep(1);
  };

  const filteredAppointments = appointments.filter((item) => (
    appointmentFilter === "all" || appointmentState(item.trang_thai) === appointmentFilter
  ));

  const profileSummary = (
    <section className="h-fit rounded-2xl border border-[#e9e9e6] bg-white p-5 text-center">
      <div className="mx-auto flex h-24 w-24 items-center justify-center overflow-hidden rounded-full border-4 border-[#f0f0ed] bg-[#e6e7e4] text-3xl font-bold text-[#555a60]">
        {profile?.avatar ? <img src={profile.avatar} alt="" className="h-full w-full object-cover" /> : profile?.ho_ten?.[0] || "P"}
      </div>
      <h2 className="mt-3 text-lg font-bold">{profile?.ho_ten || "Khách hàng"}</h2>
      <span className="mt-1 inline-flex rounded-full bg-[#faedce] px-3 py-1 text-xs font-medium text-[#775a2d]">Thành viên Porsche</span>
      <div className="my-5 space-y-3 border-y border-[#ecece9] py-4 text-left text-sm text-[#5f6369]">
        <p className="flex items-center gap-2.5 break-all"><MessageSquareText className="h-4 w-4 shrink-0 text-[#8c9298]" />{profile?.email || "—"}</p>
        <p className="flex items-center gap-2.5"><Smartphone className="h-4 w-4 shrink-0 text-[#8c9298]" />{profile?.so_dien_thoai || "Chưa cập nhật"}</p>
        <p className="flex items-center gap-2.5"><Clock3 className="h-4 w-4 shrink-0 text-[#8c9298]" />Thành viên từ {profile?.created_at ? formatDate(profile.created_at) : "—"}</p>
      </div>
      <div className="grid grid-cols-2 gap-3">
        <div className="rounded-xl bg-[#f4f4f1] px-2 py-3"><strong className="block text-xl">{profile?.garage?.length ?? 0}</strong><span className="text-xs text-[#777a80]">Xe của tôi</span></div>
        <div className="rounded-xl bg-[#f4f4f1] px-2 py-3"><strong className="block text-xl">{appointments.length}</strong><span className="text-xs text-[#777a80]">Lịch dịch vụ</span></div>
      </div>
    </section>
  );

  return (
    <CustomerWorkspaceLayout activeKey={activeKey} onNavigate={goTo} onBook={() => goTo("booking")}>
      <div className="mb-6 flex flex-wrap items-end justify-between gap-4 border-b border-[#e5e5e1] pb-5">
        <div>
          <h1 className="!mb-1 !mt-0 !text-3xl !font-extrabold !tracking-tight">{pageTitle(activeKey)}</h1>
          <p className="text-sm text-[#73777d]">
            {activeKey === "garage" ? "Tình trạng xe, lịch bảo dưỡng và chi phí dịch vụ của bạn."
              : activeKey === "profile" ? "Quản lý thông tin tài khoản, ảnh đại diện và bảo mật đăng nhập."
                : activeKey === "appointments" ? "Theo dõi tiến độ và quản lý lịch dịch vụ Workshop."
                  : activeKey === "orders" ? "Theo dõi hóa đơn và các khoản thanh toán dịch vụ."
                    : activeKey === "booking" ? "Chọn xe, chuyên gia và thời gian phù hợp cho lịch Workshop."
                      : "Các cuộc trò chuyện tư vấn đã được lưu trong tài khoản."}
          </p>
        </div>
        <button type="button" onClick={() => void loadData()} disabled={loading} aria-label="Làm mới dữ liệu" className="flex h-10 w-10 items-center justify-center rounded-lg border border-[#d9d9d5] bg-white text-[#565b61] hover:bg-[#f7f7f5] disabled:opacity-50">
          <RefreshCw className={`h-4 w-4 ${loading ? "animate-spin" : ""}`} />
        </button>
      </div>

      {error ? <p role="alert" className="mb-4 flex items-start gap-2 rounded-xl bg-[#fae9eb] px-4 py-3 text-sm text-[#9d1026]"><AlertCircle className="mt-0.5 h-4 w-4 shrink-0" />{error}</p> : null}
      {notice ? <p role="status" className="mb-4 flex items-start gap-2 rounded-xl bg-[#e8f5ec] px-4 py-3 text-sm text-[#24613a]"><CheckCircle2 className="mt-0.5 h-4 w-4 shrink-0" />{notice}</p> : null}
      {loading ? <p className="mb-4 text-sm text-[#73777d]">Đang tải dữ liệu...</p> : null}

      {!loading && activeKey === "garage" ? (
        <>
          <div className="mb-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <MetricCard label="Xe đang sở hữu" value={profile?.garage?.length ?? 0} icon={CarFront} />
            <MetricCard label="Lịch hẹn sắp tới" value={upcomingAppointments.length} icon={CalendarCheck} />
            <MetricCard label="Hạng mục đến kỳ" value="—" icon={Wrench} hint="Chưa có dữ liệu bảo dưỡng định kỳ" />
            <MetricCard label="Chi phí dịch vụ đã ghi nhận" value={serviceSpend ? money(serviceSpend) : "—"} icon={CreditCard} hint="Từ hóa đơn hoàn tất" />
          </div>

          <div className="grid gap-6 xl:grid-cols-[minmax(0,1.8fr)_minmax(280px,0.8fr)]">
            <section>
              <div className="mb-3 flex items-center justify-between gap-3">
                <h2 className="text-lg font-bold">Xe của tôi</h2>
                <button type="button" onClick={() => goTo("booking")} className="text-sm font-semibold text-[#b10e27] hover:underline">Đặt lịch Workshop</button>
              </div>
              {profile?.garage?.length ? (
                <div className="space-y-4">
                  {profile.garage.map((vehicle, index) => (
                    <article key={`${vehicle.bien_so_xe || "vehicle"}-${index}`} className="overflow-hidden rounded-2xl border border-[#e9e9e6] bg-white sm:flex">
                      <div className="flex min-h-[170px] items-center justify-center bg-[#e7e8e5] p-6 sm:w-[34%]">
                        <CarFront className="h-20 w-20 text-[#9da1a4]" strokeWidth={1} />
                      </div>
                      <div className="flex-1 p-5">
                        <div className="flex flex-wrap items-start justify-between gap-3">
                          <div>
                            <h3 className="text-lg font-bold">{vehicleName(vehicle)}</h3>
                            {vehicle.bien_so_xe ? <p className="mt-1 font-mono text-sm text-[#73777d]">{vehicle.bien_so_xe}</p> : null}
                          </div>
                          <span className="rounded-full border border-[#e7e7e3] px-3 py-1 text-xs text-[#62666b]">Trong garage</span>
                        </div>
                        <div className="mt-5 grid gap-3 text-sm sm:grid-cols-3">
                          <div><span className="block text-xs text-[#85888d]">Thông tin xe</span><strong className="mt-1 block">{vehicle.thong_tin_xe || "Chưa cập nhật"}</strong></div>
                          <div><span className="block text-xs text-[#85888d]">Lịch sử Workshop</span><strong className="mt-1 block">{appointments.filter((item) => item.bien_so_xe === vehicle.bien_so_xe).length} lịch</strong></div>
                          <div><span className="block text-xs text-[#85888d]">Bảo dưỡng kế tiếp</span><strong className="mt-1 block text-[#777a80]">Chưa có dữ liệu</strong></div>
                        </div>
                        <div className="mt-5 flex flex-wrap gap-2">
                          <button type="button" onClick={() => { setBooking((current) => ({ ...current, bien_so_xe: vehicle.bien_so_xe || "", ten_xe: vehicleName(vehicle) })); goTo("booking"); }} className="rounded-lg bg-[#080a0d] px-4 py-2 text-xs font-semibold text-white">Tư vấn bảo dưỡng</button>
                          <button type="button" onClick={() => goTo("booking")} className="rounded-lg border border-[#d7d7d3] px-4 py-2 text-xs font-semibold">Tư vấn gói độ</button>
                        </div>
                      </div>
                    </article>
                  ))}
                </div>
              ) : (
                <div className="rounded-2xl border border-dashed border-[#d9d9d5] bg-white p-8 text-center">
                  <CarFront className="mx-auto h-9 w-9 text-[#9da1a4]" />
                  <p className="mt-3 font-semibold">Chưa có xe trong garage</p>
                  <p className="mt-1 text-sm text-[#777a80]">Xe sẽ xuất hiện sau khi có lịch Workshop gắn với tài khoản.</p>
                  <button type="button" onClick={() => goTo("booking")} className="mt-4 rounded-lg bg-[#080a0d] px-4 py-2 text-sm font-semibold text-white">Đặt lịch Workshop</button>
                </div>
              )}
            </section>

            <section className="h-fit rounded-2xl border border-[#e9e9e6] bg-white p-5">
              <h2 className="text-lg font-bold">Tình trạng bảo dưỡng</h2>
              <p className="mt-1 text-sm text-[#777a80]">Thông tin hiển thị theo lịch sử dịch vụ đã có.</p>
              {appointments.length ? (
                <div className="mt-4 space-y-3">
                  {appointments.slice(0, 4).map((item) => (
                    <article key={item.id} className="rounded-xl border border-[#ededeb] p-4">
                      <div className="flex items-start justify-between gap-3">
                        <div>
                          <p className="font-semibold">{item.thong_tin_xe || item.bien_so_xe || "Lịch Workshop"}</p>
                          <p className="mt-1 text-xs text-[#777a80]">{formatDate(item.ngay_dat)}{item.gio_dat ? ` · ${item.gio_dat}` : ""}</p>
                        </div>
                        <span className="shrink-0 rounded-full bg-[#f1f1ef] px-2.5 py-1 text-[11px] font-medium">{appointmentStateLabel(item.trang_thai)}</span>
                      </div>
                    </article>
                  ))}
                  <button type="button" onClick={() => goTo("appointments")} className="text-sm font-semibold text-[#b10e27] hover:underline">Xem tất cả lịch hẹn</button>
                </div>
              ) : (
                <div className="mt-4 rounded-xl bg-[#f5f5f3] p-4 text-sm text-[#777a80]">Chưa có lịch sử bảo dưỡng. Đặt lịch để theo dõi dịch vụ tại đây.</div>
              )}
            </section>
          </div>
        </>
      ) : null}

      {!loading && activeKey === "profile" ? (
        <div className="grid items-start gap-6 xl:grid-cols-[318px_minmax(0,1fr)]">
          {profileSummary}
          <section className="min-w-0">
            <div className="mb-5 grid grid-cols-2 gap-1 rounded-2xl border border-[#e9e9e6] bg-white p-1.5 sm:grid-cols-4">
              {PROFILE_TABS.map(({ key, label, icon: Icon }) => (
                <button key={key} type="button" onClick={() => setProfileTab(key)} className={`flex min-h-11 items-center justify-center gap-2 rounded-xl px-2 text-xs font-medium transition-colors sm:text-sm ${profileTab === key ? "bg-[#080a0d] text-white" : "text-[#555a60] hover:bg-[#f3f3f1]"}`}>
                  <Icon className="h-4 w-4 shrink-0" />{label}
                </button>
              ))}
            </div>

            {profileTab === "details" ? (
              <form onSubmit={saveProfile} className="rounded-2xl border border-[#e9e9e6] bg-white p-5 sm:p-6">
                <h2 className="font-bold">Thông tin cá nhân</h2>
                <p className="mt-1 text-sm text-[#777a80]">Thông tin này dùng cho liên hệ dịch vụ và hồ sơ xe.</p>
                <div className="mt-5 grid gap-4 md:grid-cols-2">
                  <label className="text-sm font-medium">Họ và tên<input required name="ho_ten" value={profile?.ho_ten ?? ""} onChange={updateProfileField} className="mt-1.5 w-full rounded-lg border border-[#dededb] bg-white px-3.5 py-3 font-normal outline-none focus:border-[#202226]" /></label>
                  <label className="text-sm font-medium">Email<input value={profile?.email ?? ""} readOnly className="mt-1.5 w-full rounded-lg border border-[#e9e9e6] bg-[#f4f4f2] px-3.5 py-3 font-normal text-[#777a80]" /></label>
                  <label className="text-sm font-medium">Số điện thoại<input required name="so_dien_thoai" value={profile?.so_dien_thoai ?? ""} onChange={updateProfileField} className="mt-1.5 w-full rounded-lg border border-[#dededb] bg-white px-3.5 py-3 font-normal outline-none focus:border-[#202226]" /></label>
                  <label className="text-sm font-medium md:col-span-2">Địa chỉ<input name="dia_chi" value={profile?.dia_chi ?? ""} onChange={updateProfileField} className="mt-1.5 w-full rounded-lg border border-[#dededb] bg-white px-3.5 py-3 font-normal outline-none focus:border-[#202226]" /></label>
                </div>
                <button type="submit" disabled={saving || !profile} className="mt-5 rounded-lg bg-[#cf102d] px-5 py-2.5 text-sm font-semibold text-white disabled:opacity-50">{saving ? "Đang lưu..." : "Lưu thay đổi"}</button>
              </form>
            ) : null}

            {profileTab === "avatar" ? (
              <section className="rounded-2xl border border-[#e9e9e6] bg-white p-5 sm:p-6">
                <h2 className="font-bold">Ảnh đại diện</h2>
                <p className="mt-1 text-sm text-[#777a80]">Chọn ảnh cá nhân để cập nhật ảnh đại diện (JPG, PNG hoặc WebP, tối đa 2 MB).</p>
                <div className="mt-5 flex flex-wrap items-center gap-5">
                  <div className="flex h-28 w-28 items-center justify-center overflow-hidden rounded-full bg-[#e7e8e5] text-3xl font-bold text-[#555a60]">
                    {profile?.avatar ? <img src={profile.avatar} alt="" className="h-full w-full object-cover" /> : profile?.ho_ten?.[0] || "P"}
                  </div>
                  <label className="inline-flex cursor-pointer items-center gap-2 rounded-lg border border-[#d7d7d3] px-4 py-2.5 text-sm font-semibold">
                    <UserRoundPen className="h-4 w-4" /> Tải ảnh lên
                    <input type="file" accept="image/jpeg,image/png,image/webp" onChange={saveAvatar} className="sr-only" />
                  </label>
                </div>
              </section>
            ) : null}

            {profileTab === "password" ? (
              <form onSubmit={changePassword} className="rounded-2xl border border-[#e9e9e6] bg-white p-5 sm:p-6">
                <h2 className="font-bold">Đổi mật khẩu</h2>
                <p className="mt-1 text-sm text-[#777a80]">Nên đổi mật khẩu định kỳ để bảo vệ tài khoản của bạn.</p>
                <div className="mt-5 grid max-w-xl gap-4">
                  <label className="text-sm font-medium">Mật khẩu hiện tại<input required type="password" autoComplete="current-password" value={password.old_password} onChange={(event) => setPassword({ ...password, old_password: event.target.value })} className="mt-1.5 w-full rounded-lg border border-[#dededb] px-3.5 py-3 font-normal outline-none focus:border-[#202226]" /></label>
                  <label className="text-sm font-medium">Mật khẩu mới<input required minLength={6} type="password" autoComplete="new-password" value={password.password} onChange={(event) => setPassword({ ...password, password: event.target.value })} className="mt-1.5 w-full rounded-lg border border-[#dededb] px-3.5 py-3 font-normal outline-none focus:border-[#202226]" /><span className="mt-1 block text-xs text-[#85888d]">Tối thiểu 6 ký tự.</span></label>
                  <label className="text-sm font-medium">Xác nhận mật khẩu mới<input required minLength={6} type="password" autoComplete="new-password" value={password.password_confirmation} onChange={(event) => setPassword({ ...password, password_confirmation: event.target.value })} className="mt-1.5 w-full rounded-lg border border-[#dededb] px-3.5 py-3 font-normal outline-none focus:border-[#202226]" /></label>
                </div>
                <button type="submit" disabled={saving} className="mt-5 rounded-lg bg-[#080a0d] px-5 py-2.5 text-sm font-semibold text-white disabled:opacity-50">{saving ? "Đang cập nhật..." : "Đổi mật khẩu"}</button>
              </form>
            ) : null}

            {profileTab === "devices" ? (
              <section className="rounded-2xl border border-[#e9e9e6] bg-white p-5 sm:p-6">
                <div className="flex items-start gap-3">
                  <ShieldCheck className="mt-0.5 h-5 w-5 text-[#777a80]" />
                  <div>
                    <h2 className="font-bold">Thiết bị đăng nhập</h2>
                    <p className="mt-1 text-sm leading-6 text-[#777a80]">Hệ thống hiện chưa cung cấp API liệt kê hoặc kết thúc phiên đăng nhập theo từng thiết bị. Bạn có thể đổi mật khẩu để bảo vệ tài khoản.</p>
                  </div>
                </div>
                <button type="button" onClick={() => setProfileTab("password")} className="mt-5 rounded-lg border border-[#d7d7d3] px-4 py-2.5 text-sm font-semibold">Đổi mật khẩu</button>
              </section>
            ) : null}
          </section>
        </div>
      ) : null}

      {!loading && activeKey === "appointments" ? (
        <section>
          <div className="mb-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <MetricCard label="Tổng lịch hẹn" value={appointments.length} icon={CalendarDays} />
            <MetricCard label="Đang xử lý" value={appointments.filter((item) => appointmentState(item.trang_thai) === "active").length} icon={Clock3} />
            <MetricCard label="Hoàn thành" value={appointments.filter((item) => appointmentState(item.trang_thai) === "done").length} icon={CheckCircle2} />
            <MetricCard label="Đã hủy" value={appointments.filter((item) => appointmentState(item.trang_thai) === "cancelled").length} icon={AlertCircle} />
          </div>
          <div className="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-[#e9e9e6] bg-white p-2">
            <div className="flex flex-wrap gap-1">
              {APPOINTMENT_FILTERS.map((filter) => (
                <button key={filter.key} type="button" onClick={() => setAppointmentFilter(filter.key)} className={`rounded-xl px-4 py-2 text-sm font-medium ${appointmentFilter === filter.key ? "bg-[#080a0d] text-white" : "text-[#555a60] hover:bg-[#f3f3f1]"}`}>{filter.label}</button>
              ))}
            </div>
            <button type="button" onClick={() => goTo("booking")} className="rounded-lg bg-[#cf102d] px-4 py-2.5 text-sm font-semibold text-white">+ Đặt lịch mới</button>
          </div>
          {filteredAppointments.length ? (
            <div className="space-y-3">
              {filteredAppointments.map((item) => (
                <article key={item.id} className="flex flex-wrap items-center justify-between gap-4 rounded-2xl border border-[#e9e9e6] bg-white p-5">
                  <div className="flex min-w-0 items-start gap-3">
                    <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#fae9eb] text-[#c8102e]"><Wrench className="h-5 w-5" /></span>
                    <div className="min-w-0">
                      <h3 className="font-semibold">{item.thong_tin_xe || item.bien_so_xe || "Lịch Workshop"}</h3>
                      <p className="mt-1 text-xs text-[#777a80]">{item.ma_dat_lich ? `${item.ma_dat_lich} · ` : ""}{item.bien_so_xe || ""}</p>
                      <div className="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-[#73777d]">
                        <span className="inline-flex items-center gap-1"><CalendarDays className="h-3.5 w-3.5" />{formatDate(item.ngay_dat)}{item.gio_dat ? ` · ${item.gio_dat}` : ""}</span>
                        {item.ten_chuyen_gia ? <span className="inline-flex items-center gap-1"><UserRound className="h-3.5 w-3.5" />{item.ten_chuyen_gia}</span> : null}
                      </div>
                    </div>
                  </div>
                  <div className="flex items-center gap-3">
                    <span className="rounded-full bg-[#f1f1ef] px-3 py-1 text-xs font-semibold">{appointmentStateLabel(item.trang_thai)}</span>
                    {appointmentState(item.trang_thai) === "active" ? <button type="button" disabled={saving} onClick={() => void cancelAppointment(item)} className="text-sm font-semibold text-[#c8102e] disabled:opacity-50">Hủy lịch</button> : null}
                  </div>
                </article>
              ))}
            </div>
          ) : <div className="rounded-2xl border border-dashed border-[#d9d9d5] bg-white p-8 text-center text-sm text-[#777a80]">Không có lịch hẹn trong danh mục này.</div>}
        </section>
      ) : null}

      {!loading && activeKey === "orders" ? (
        <section>
          <div className="mb-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <MetricCard label="Tổng hóa đơn" value={invoices.length} icon={FileText} />
            <MetricCard label="Tổng đã thanh toán" value={serviceSpend ? money(serviceSpend) : "—"} icon={CreditCard} />
            <MetricCard label="Xe trong garage" value={profile?.garage?.length ?? "—"} icon={CarFront} />
            <MetricCard label="Lịch dịch vụ" value={appointments.length || "—"} icon={Wrench} />
          </div>
          {invoices.length ? (
            <div className="space-y-3">
              {invoices.map((invoice, index) => (
                <details key={invoice.id ?? index} className="group rounded-2xl border border-[#e9e9e6] bg-white p-5">
                  <summary className="flex cursor-pointer list-none flex-wrap items-center justify-between gap-4">
                    <div className="flex items-center gap-3">
                      <span className="flex h-11 w-11 items-center justify-center rounded-xl bg-[#fae9eb] text-[#c8102e]"><CarFront className="h-5 w-5" /></span>
                      <div>
                        <p className="font-semibold">{invoice.ma_hoa_don || `Hóa đơn #${invoice.id ?? index + 1}`}</p>
                        <p className="mt-1 text-xs text-[#777a80]">{formatDate(invoice.created_at || invoice.ngay_lap)} · {invoice.ghi_chu || "Hóa đơn dịch vụ"}</p>
                      </div>
                    </div>
                    <div className="flex items-center gap-4">
                      <span className="text-right"><small className="block text-xs text-[#85888d]">Tổng thanh toán</small><strong>{money(invoice.tong_tien ?? invoice.thanh_tien ?? invoice.so_tien)}</strong></span>
                      <span className="rounded-lg border border-[#d7d7d3] px-3 py-2 text-xs font-semibold">Xem chi tiết</span>
                    </div>
                  </summary>
                  <div className="mt-4 border-t border-[#ededeb] pt-4 text-sm text-[#5f6369]">
                    <p><span className="font-semibold">Mã hóa đơn:</span> {invoice.ma_hoa_don || invoice.id || "—"}</p>
                    <p className="mt-2"><span className="font-semibold">Ngày lập:</span> {formatDate(invoice.created_at || invoice.ngay_lap)}</p>
                    <p className="mt-2"><span className="font-semibold">Trạng thái:</span> {invoice.trang_thai || "Đã thanh toán"}</p>
                  </div>
                </details>
              ))}
            </div>
          ) : <div className="rounded-2xl border border-dashed border-[#d9d9d5] bg-white p-8 text-center text-sm text-[#777a80]">Chưa có hóa đơn mua hàng hoặc dịch vụ.</div>}
        </section>
      ) : null}

      {!loading && activeKey === "booking" ? (
        <section>
          <div className="mb-6 grid grid-cols-4 rounded-2xl border border-[#e9e9e6] bg-white p-4">
            {["Chọn xe", "Chọn chuyên gia", "Xe & thời gian", "Xác nhận"].map((label, index) => {
              const step = index + 1;
              const complete = bookingStep > step;
              const current = bookingStep === step;
              return (
                <div key={label} className={`flex items-center gap-2 ${step < 4 ? "after:ml-2 after:h-px after:flex-1 after:bg-[#dededb] after:content-['']" : ""}`}>
                  <span className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-full border text-sm font-semibold ${current ? "border-[#c8102e] bg-[#fae9eb] text-[#c8102e]" : complete ? "border-[#c8102e] bg-[#c8102e] text-white" : "border-[#e1e1de] text-[#85888d]"}`}>
                    {complete ? <Check className="h-4 w-4" /> : step}
                  </span>
                  <span className={`hidden min-w-0 sm:block ${current ? "text-[#c8102e]" : "text-[#777a80]"}`}><small className="block text-[10px] uppercase">Bước {step}</small><span className="text-xs font-medium">{label}</span></span>
                </div>
              );
            })}
          </div>

          <form onSubmit={submitBooking} className="rounded-2xl border border-[#e9e9e6] bg-white p-5 sm:p-6">
            {bookingStep === 1 ? (
              <>
                <h2 className="font-bold">Chọn xe</h2>
                <p className="mt-1 text-sm text-[#777a80]">Chọn xe trong Garage của bạn hoặc nhập thông tin xe.</p>
                {profile?.garage?.length ? (
                  <div className="mt-4 grid gap-3 md:grid-cols-2">
                    {profile.garage.map((vehicle, index) => {
                      const selected = booking.bien_so_xe === vehicle.bien_so_xe;
                      return <button key={`${vehicle.bien_so_xe || "vehicle"}-${index}`} type="button" onClick={() => setBooking({ ...booking, bien_so_xe: vehicle.bien_so_xe || "", ten_xe: vehicleName(vehicle) })} className={`rounded-xl border p-4 text-left ${selected ? "border-[#cf102d] bg-[#fff4f4]" : "border-[#e9e9e6] hover:border-[#c7c7c2]"}`}><strong className="block">{vehicleName(vehicle)}</strong><span className="mt-1 block text-sm text-[#777a80]">{vehicle.bien_so_xe || "Chưa có biển số"}</span></button>;
                    })}
                  </div>
                ) : null}
                <div className="mt-5 grid gap-4 md:grid-cols-2">
                  <label className="text-sm font-medium">Mẫu xe<input required value={booking.ten_xe} onChange={(event) => setBooking({ ...booking, ten_xe: event.target.value })} placeholder="Ví dụ: Porsche 911 Carrera" className="mt-1.5 w-full rounded-lg border border-[#dededb] px-3.5 py-3 font-normal" /></label>
                  <label className="text-sm font-medium">Biển số xe<input required value={booking.bien_so_xe} onChange={(event) => setBooking({ ...booking, bien_so_xe: event.target.value })} className="mt-1.5 w-full rounded-lg border border-[#dededb] px-3.5 py-3 font-normal" /></label>
                </div>
              </>
            ) : null}

            {bookingStep === 2 ? (
              <>
                <h2 className="font-bold">Chọn chuyên gia</h2>
                <p className="mt-1 text-sm text-[#777a80]">Chọn chuyên gia phù hợp cho yêu cầu Workshop của bạn.</p>
                <label className="mt-5 block max-w-xl text-sm font-medium">Chuyên gia phụ trách<select required value={booking.id_chuyen_gia} onChange={(event) => setBooking({ ...booking, id_chuyen_gia: event.target.value })} className="mt-1.5 w-full rounded-lg border border-[#dededb] bg-white px-3.5 py-3 font-normal"><option value="">Chọn chuyên gia</option>{bookingOptions.chuyen_gias.map((expert) => <option key={expert.id} value={expert.id}>{expert.ho_ten}{expert.ma_so_tuner ? ` · ${expert.ma_so_tuner}` : ""}</option>)}</select></label>
                {!bookingOptions.chuyen_gias.length ? <p className="mt-3 text-sm text-[#777a80]">Hiện chưa có chuyên gia khả dụng. Vui lòng thử lại sau.</p> : null}
              </>
            ) : null}

            {bookingStep === 3 ? (
              <>
                <h2 className="font-bold">Chọn ngày và thời gian</h2>
                <p className="mt-1 text-sm text-[#777a80]">Chọn thời gian mong muốn và khu vực Workshop.</p>
                <div className="mt-5 grid gap-4 md:grid-cols-2">
                  <label className="text-sm font-medium">Ngày mong muốn<input required type="date" min={new Date().toISOString().slice(0, 10)} value={booking.ngay_dat} onChange={(event) => setBooking({ ...booking, ngay_dat: event.target.value })} className="mt-1.5 w-full rounded-lg border border-[#dededb] px-3.5 py-3 font-normal" /></label>
                  <label className="text-sm font-medium">Giờ mong muốn<input required type="time" value={booking.gio_dat} onChange={(event) => setBooking({ ...booking, gio_dat: event.target.value })} className="mt-1.5 w-full rounded-lg border border-[#dededb] px-3.5 py-3 font-normal" /></label>
                  <label className="text-sm font-medium md:col-span-2">Khu vực Workshop<select required value={booking.id_khu_vuc_workshop} onChange={(event) => setBooking({ ...booking, id_khu_vuc_workshop: event.target.value })} className="mt-1.5 w-full rounded-lg border border-[#dededb] bg-white px-3.5 py-3 font-normal"><option value="">Chọn khu vực</option>{bookingOptions.khu_vuc_workshops.map((area) => <option key={area.id} value={area.id}>{area.ten_khu_vuc}</option>)}</select></label>
                  <label className="text-sm font-medium md:col-span-2">Ghi chú cho chuyên gia (tùy chọn)<textarea maxLength={1000} rows={4} value={booking.ghi_chu} onChange={(event) => setBooking({ ...booking, ghi_chu: event.target.value })} placeholder="Mô tả tình trạng xe hoặc yêu cầu riêng của bạn..." className="mt-1.5 w-full resize-y rounded-lg border border-[#dededb] px-3.5 py-3 font-normal" /></label>
                </div>
              </>
            ) : null}

            {bookingStep === 4 ? (
              <>
                <h2 className="font-bold">Xác nhận lịch hẹn</h2>
                <p className="mt-1 text-sm text-[#777a80]">Kiểm tra thông tin trước khi gửi yêu cầu đặt lịch.</p>
                <dl className="mt-5 grid gap-4 rounded-xl bg-[#f5f5f3] p-5 text-sm sm:grid-cols-2">
                  <div><dt className="text-[#777a80]">Xe</dt><dd className="mt-1 font-semibold">{booking.ten_xe} · {booking.bien_so_xe}</dd></div>
                  <div><dt className="text-[#777a80]">Chuyên gia</dt><dd className="mt-1 font-semibold">{bookingOptions.chuyen_gias.find((item) => String(item.id) === String(booking.id_chuyen_gia))?.ho_ten || "—"}</dd></div>
                  <div><dt className="text-[#777a80]">Ngày và giờ</dt><dd className="mt-1 font-semibold">{formatDate(booking.ngay_dat)} · {booking.gio_dat}</dd></div>
                  <div><dt className="text-[#777a80]">Khu vực</dt><dd className="mt-1 font-semibold">{bookingOptions.khu_vuc_workshops.find((item) => String(item.id) === String(booking.id_khu_vuc_workshop))?.ten_khu_vuc || "—"}</dd></div>
                  {booking.ghi_chu ? <div className="sm:col-span-2"><dt className="text-[#777a80]">Ghi chú</dt><dd className="mt-1 font-semibold">{booking.ghi_chu}</dd></div> : null}
                </dl>
              </>
            ) : null}

            <div className="mt-7 flex flex-wrap items-center justify-between gap-3 border-t border-[#ededeb] pt-5">
              {bookingStep > 1 ? <button type="button" onClick={() => setBookingStep((step) => step - 1)} className="inline-flex items-center gap-2 rounded-lg border border-[#d7d7d3] px-4 py-2.5 text-sm font-semibold"><ArrowLeft className="h-4 w-4" /> Quay lại</button> : <button type="button" onClick={() => goTo("appointments")} className="inline-flex items-center gap-2 rounded-lg border border-[#d7d7d3] px-4 py-2.5 text-sm font-semibold">Lịch hẹn của tôi</button>}
              {bookingStep < 4 ? (
                <button type="button" onClick={() => {
                  setError("");
                  if (bookingStep === 1 && (!booking.ten_xe || !booking.bien_so_xe)) return setError("Vui lòng chọn xe hoặc nhập mẫu xe và biển số.");
                  if (bookingStep === 2 && !booking.id_chuyen_gia) return setError("Vui lòng chọn chuyên gia.");
                  if (bookingStep === 3 && (!booking.ngay_dat || !booking.gio_dat || !booking.id_khu_vuc_workshop)) return setError("Vui lòng chọn ngày, giờ và khu vực Workshop.");
                  setBookingStep((step) => step + 1);
                }} className="inline-flex items-center gap-2 rounded-lg bg-[#080a0d] px-5 py-2.5 text-sm font-semibold text-white">Tiếp tục <ArrowRight className="h-4 w-4" /></button>
              ) : (
                <button type="submit" disabled={saving} className="inline-flex items-center gap-2 rounded-lg bg-[#080a0d] px-5 py-2.5 text-sm font-semibold text-white disabled:opacity-50">{saving ? "Đang gửi..." : "Xác nhận đặt lịch"} <Check className="h-4 w-4" /></button>
              )}
            </div>
          </form>
        </section>
      ) : null}

      {!loading && activeKey === "ai-history" ? (
        <section>
          <div className="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-[#e9e9e6] bg-white p-4">
            <p className="text-sm text-[#777a80]">Các câu hỏi và câu trả lời đã lưu trong tài khoản.</p>
            <button type="button" onClick={() => void clearHistory()} disabled={saving || history.length === 0} className="rounded-lg border border-[#efc8ce] px-4 py-2 text-sm font-semibold text-[#b10e27] disabled:opacity-50">{saving ? "Đang xóa..." : "Xóa toàn bộ"}</button>
          </div>
          {history.length ? (
            <div className="space-y-3">
              {history.map((item) => (
                <article key={item.id} className="rounded-2xl border border-[#e9e9e6] bg-white p-5">
                  <p className="mb-3 flex items-center gap-2 text-xs text-[#85888d]"><Sparkles className="h-4 w-4 text-[#a17d42]" />{item.created_at ? new Date(item.created_at).toLocaleString("vi-VN") : ""}</p>
                  <p className="font-semibold">Bạn: {item.input_user}</p>
                  <p className="mt-2 whitespace-pre-wrap text-sm leading-6 text-[#555a60]">Trợ lý: {item.output_ai}</p>
                </article>
              ))}
            </div>
          ) : <p className="rounded-2xl border border-dashed border-[#d9d9d5] bg-white px-4 py-8 text-center text-sm text-[#777a80]">Chưa có lịch sử tư vấn AI.</p>}
        </section>
      ) : null}
    </CustomerWorkspaceLayout>
  );
}

export default CustomerPortalPage;
