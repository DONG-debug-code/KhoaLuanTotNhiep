import { useCallback, useEffect, useState } from "react";
import {
  Activity, BarChart3, CalendarDays, ClipboardList, FileText,
  Gauge, LayoutDashboard, LockKeyhole, Package, RefreshCw, ShieldCheck, Users, Wrench,
} from "lucide-react";
import { apiClient } from "../../api/Authapi";
import { getWorkspaceData, postWorkspaceData } from "../../api/WorkspaceApi";
import WorkspaceLayout from "../../components/workspaces/WorkspaceLayout";
import WorkspaceTable from "../../components/workspaces/WorkspaceTable";
import OrderDetailModal from "../../components/workspaces/OrderDetailModal";

const ADMIN_NAV = [
  { key: "dashboard", label: "Tổng quan", icon: LayoutDashboard },
  { key: "profile", label: "Hồ sơ cá nhân", icon: Users },
  { key: "roles", label: "Chức vụ", icon: ShieldCheck },
  { key: "permissions", label: "Phân quyền", icon: LockKeyhole },
  { key: "employees", label: "Nhân viên", icon: Users },
  { key: "customers", label: "Khách hàng", icon: Users },
  { key: "inventory", label: "Kho sản phẩm", icon: Package },
  { key: "appointments", label: "Lịch workshop", icon: CalendarDays },
  { key: "invoices", label: "Hóa đơn", icon: FileText },
  { key: "slides", label: "Banner / slide", icon: Activity },
  { key: "reports", label: "Báo cáo", icon: BarChart3 },
];

const EXPERT_NAV = [
  { key: "profile", label: "Hồ sơ cá nhân", icon: Users },
  { key: "appointments", label: "Ca thi công", icon: CalendarDays },
  { key: "roster", label: "Lịch làm việc", icon: ClipboardList },
  { key: "bays", label: "Khu vực Workshop", icon: Wrench },
  { key: "customers", label: "Khách hàng phụ trách", icon: Users },
  { key: "vehicle-records", label: "Hồ sơ xe / Tuning", icon: Gauge },
];

const MODULES = {
  admin: {
    dashboard: { path: "dashboard/data", title: "Dashboard điều hành" },
    profile: { path: "profile/data", title: "Hồ sơ quản trị viên" },
    roles: { path: "chuc-vu/data", title: "Quản lý chức vụ" },
    permissions: { path: "phan-quyen/chi-tiet-data", title: "Ma trận phân quyền" },
    employees: { path: "nhan-vien/data", title: "Quản lý nhân viên" },
    customers: { path: "khach-hang/data", title: "Hồ sơ khách hàng" },
    inventory: { path: "products", title: "Kho xe và phụ tùng" },
    appointments: { path: "lich-hen/data", title: "Điều phối Workshop" },
    invoices: { path: "hoa-don/data", title: "Hóa đơn toàn hệ thống" },
    slides: { path: "slide/data", title: "Banner và slide marketing" },
    reports: { path: "thong-ke/do-xe-theo-chuyen-gia", title: "Báo cáo theo chuyên gia" },
  },
  "chuyen-gia": {
    profile: { path: "profile/data", title: "Hồ sơ chuyên gia" },
    appointments: { path: "lich-dat-do/data", title: "Lịch thi công được phân công" },
    roster: { path: "lich-lam-viec/data", title: "Lịch làm việc cá nhân" },
    bays: { path: "khu-vuc-workshop/data", title: "Trạng thái khu vực Workshop" },
    customers: { path: "khach-hang/data", title: "Khách hàng phụ trách" },
    "vehicle-records": { path: "ho-so-xe/data", title: "Hồ sơ bệnh án xe" },
  },
};

function toRows(data) {
  if (Array.isArray(data)) return data;
  if (Array.isArray(data?.data)) return data.data;
  return [];
}

function displayValue(value) {
  if (value === null || value === undefined || value === "") return "—";
  if (typeof value === "object") return JSON.stringify(value);
  return String(value);
}

function ObjectSummary({ value }) {
  if (!value || typeof value !== "object") {
    return <p className="text-sm text-[#63635E]">Chưa có dữ liệu thống kê.</p>;
  }
  const entries = Object.entries(value).filter(([key]) => !/password|token|secret/i.test(key));
  return (
    <div className="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
      {entries.map(([key, item]) => (
        <div key={key} className="rounded-xl border border-[#ECECE9] bg-[#FAFAF9] p-4">
          <div className="text-xs font-semibold uppercase tracking-wide text-[#8A8A85]">{key.replaceAll("_", " ")}</div>
          <div className="mt-2 break-words text-xl font-extrabold">{displayValue(item)}</div>
        </div>
      ))}
    </div>
  );
}

export default function OperationsWorkspacePage({ role }) {
  const isAdmin = role === "admin";
  const navigation = isAdmin ? ADMIN_NAV : EXPERT_NAV;
  const [activeKey, setActiveKey] = useState(isAdmin ? "dashboard" : "appointments");
  const [rows, setRows] = useState([]);
  const [summary, setSummary] = useState(null);
  const [dangTai, setDangTai] = useState(false);
  const [dangLuu, setDangLuu] = useState(false);
  const [loi, setLoi] = useState("");
  const [thongBao, setThongBao] = useState("");
  const [formData, setFormData] = useState({});
  const [boLoc, setBoLoc] = useState({ tu_ngay: "", den_ngay: "" });
  const [selectedAppointment, setSelectedAppointment] = useState(null);
  const [selectedCustomer, setSelectedCustomer] = useState(false);
  const [selectedRole, setSelectedRole] = useState(false);
  const [selectedSlide, setSelectedSlide] = useState(false);
  const [chiTietHoaDon, setChiTietHoaDon] = useState(null);
  const [orderModalId, setOrderModalId] = useState(null);
  const [historyRows, setHistoryRows] = useState(null);
  const [profile, setProfile] = useState(null);
  const [permissionData, setPermissionData] = useState(null);
  const [searchTerm, setSearchTerm] = useState("");

  const taiDuLieu = useCallback(async () => {
    setDangTai(true);
    setLoi("");
    setThongBao("");
    try {
      const module = MODULES[role][activeKey];
      if (activeKey === "reports") {
        const response = await postWorkspaceData("admin", module.path, boLoc);
        setRows(toRows(response?.data));
        setSummary(null);
      } else {
        const result = await getWorkspaceData(role, module.path);
        if (activeKey === "dashboard" || activeKey === "profile") {
          setSummary(result?.data ?? result);
          if (activeKey === "profile") setProfile(result?.data ?? result);
          setRows([]);
        } else if (activeKey === "permissions") {
          const matrix = result?.data ?? result;
          setPermissionData(matrix);
          setRows(toRows(matrix?.phan_quyens));
          setSummary(null);
        } else {
          setRows(toRows(result));
          setSummary(null);
        }
      }
    } catch (error) {
      setLoi(error.response?.data?.message || error.message || "Không thể tải dữ liệu.");
      setRows([]);
      setSummary(null);
    } finally {
      setDangTai(false);
    }
  }, [activeKey, boLoc, role]);

  useEffect(() => {
    let active = true;
    Promise.resolve().then(() => {
      if (active) void taiDuLieu();
    });
    return () => { active = false; };
  }, [taiDuLieu]);

  const capNhatForm = (event) => {
    setFormData((value) => ({ ...value, [event.target.name]: event.target.value }));
  };

  const guiForm = async (event, path, payload = formData) => {
    event?.preventDefault();
    setDangLuu(true);
    setLoi("");
    setThongBao("");
    try {
      const result = await postWorkspaceData(role, path, payload);
      setFormData({});
      await taiDuLieu();
      setThongBao(result?.message || "Đã lưu thay đổi.");
      return true;
    } catch (error) {
      setLoi(error.response?.data?.message || error.message || "Không thể lưu thay đổi.");
      return false;
    } finally {
      setDangLuu(false);
    }
  };

  const guiHoSoXe = async (event) => {
    event.preventDefault();
    const path = formData.id ? "ho-so-xe/update" : "ho-so-xe/create";
    const payload = {
      ...formData,
      ...(formData.id ? { id: Number(formData.id) } : {}),
      id_khach_hang: Number(formData.id_khach_hang),
    };
    const saved = await guiForm(event, path, payload);
    if (saved) setFormData({});
  };

  const doiTrangThai = async (path, row) => {
    await guiForm(null, path, { id: row.id });
  };

  const xoaBanGhi = async (path, row, label) => {
    if (!window.confirm(`Bạn có chắc muốn xóa ${label}?`)) return;
    await guiForm(null, path, { id: row.id });
  };

  const xuatExcelKhachHang = async () => {
    setDangLuu(true);
    setLoi("");
    try {
      const response = await apiClient.post("/admin/khach-hang/xuat-excel", {}, { responseType: "blob" });
      const fileUrl = URL.createObjectURL(response.data);
      const anchor = document.createElement("a");
      anchor.href = fileUrl;
      anchor.download = "danh-sach-khach-hang.xlsx";
      anchor.click();
      URL.revokeObjectURL(fileUrl);
      setThongBao("Đã tải báo cáo khách hàng.");
    } catch (error) {
      setLoi(error.response?.data?.message || "Không thể xuất báo cáo khách hàng.");
    } finally {
      setDangLuu(false);
    }
  };

  const kiemTraMaPhieu = async (event) => {
    event.preventDefault();
    setDangLuu(true);
    setLoi("");
    setThongBao("");
    try {
      const response = await postWorkspaceData("admin", "kiem-tra-phieu-dat-lich", {
        ma_dat_lich: formData.ma_dat_lich,
      });
      setThongBao(response?.message || "Đã kiểm tra mã phiếu.");
    } catch (error) {
      setLoi(error.response?.data?.message || "Không tìm thấy phiếu đặt lịch.");
    } finally {
      setDangLuu(false);
    }
  };

  const xemLichSuKhach = async (customer) => {
    setDangTai(true);
    setLoi("");
    try {
      const data = await postWorkspaceData("chuyen-gia", "khach-hang/lich-su-do", {
        id_khach_hang: customer.id,
      });
      setHistoryRows({ customer, rows: toRows(data?.data) });
    } catch (error) {
      setLoi(error.response?.data?.message || "Không thể tải lịch sử nâng cấp của khách.");
    } finally {
      setDangTai(false);
    }
  };

  const xemChiTietHoaDon = async (invoice) => {
    if (invoice.don_hang_id) {
      setOrderModalId(invoice.don_hang_id);
      return;
    }
    setDangTai(true);
    setLoi("");
    try {
      const result = await postWorkspaceData("admin", "hoa-don/chi-tiet", { id: invoice.id });
      setChiTietHoaDon(result?.data ?? result);
    } catch (error) {
      setLoi(error.response?.data?.message || "Không thể tải chi tiết hóa đơn.");
    } finally {
      setDangTai(false);
    }
  };

  const capNhatKho = async (row, action) => {
    const rawQuantity = window.prompt(`Nhập số lượng ${action === "restock" ? "nhập" : "xuất"} kho:`);
    if (rawQuantity === null) return;
    const quantity = Number(rawQuantity);
    if (!Number.isInteger(quantity) || quantity < 1) {
      setLoi("Số lượng phải là số nguyên lớn hơn 0.");
      return;
    }
    const reason = window.prompt("Lý do điều chỉnh kho (không bắt buộc):") || "";
    await guiForm(null, `products/${row.id}/${action === "restock" ? "restock" : "export-stock"}`, {
      so_luong: quantity,
      ly_do: reason,
    });
  };

  const dongBoanTatLich = async (event) => {
    event.preventDefault();
    let chiTiet;
    try {
      chiTiet = JSON.parse(formData.chi_tiet_kt || "[]");
      if (!Array.isArray(chiTiet)) throw new Error("Chi tiết hóa đơn phải là một danh sách.");
    } catch (error) {
      setLoi(error.message || "Chi tiết hóa đơn phải là JSON hợp lệ.");
      return;
    }
    const saved = await guiForm(event, "lich-hen/hoan-thanh", {
      id: selectedAppointment.id,
      tong_tien: Number(formData.tong_tien),
      chi_tiet_kt: chiTiet,
    });
    if (saved) setSelectedAppointment(null);
  };

  const rowsWithActions = rows.map((row) => {
    let actions = null;
    if (isAdmin && activeKey === "employees") {
      actions = <button type="button" disabled={dangLuu} onClick={() => void doiTrangThai("nhan-vien/change-status", row)} className="rounded-lg border border-[#ECECE9] px-3 py-1.5 text-xs font-semibold">Khóa / mở khóa</button>;
    } else if (isAdmin && activeKey === "roles") {
      actions = (
        <div className="flex justify-end gap-2">
          <button type="button" onClick={() => { setSelectedRole(true); setFormData({ id: row.id, ten_chuc_vu: row.ten_chuc_vu, ma_chuc_vu: row.ma_chuc_vu, trang_thai: String(row.trang_thai) }); }} className="rounded-lg border border-[#ECECE9] px-2 py-1.5 text-xs font-semibold">Sửa</button>
          <button type="button" disabled={dangLuu} onClick={() => void doiTrangThai("chuc-vu/change-status", row)} className="rounded-lg border border-[#ECECE9] px-2 py-1.5 text-xs font-semibold">Trạng thái</button>
          <button type="button" disabled={dangLuu} onClick={() => void xoaBanGhi("chuc-vu/delete", row, `chức vụ ${row.ten_chuc_vu}`)} className="rounded-lg border border-[#F1CACA] px-2 py-1.5 text-xs font-semibold text-[#B60F0F]">Xóa</button>
        </div>
      );
    } else if (isAdmin && activeKey === "customers") {
      actions = (
        <div className="flex justify-end gap-2">
          <button type="button" onClick={() => { setSelectedCustomer(true); setFormData({ id: row.id, ho_ten: row.ho_ten, email: row.email, so_dien_thoai: row.so_dien_thoai, dia_chi: row.dia_chi || "" }); }} className="rounded-lg border border-[#ECECE9] px-2.5 py-1.5 text-xs font-semibold">Sửa</button>
          <button type="button" disabled={dangLuu} onClick={() => void doiTrangThai("khach-hang/change-status", row)} className="rounded-lg border border-[#F1CACA] px-2.5 py-1.5 text-xs font-semibold text-[#B60F0F]">Đổi trạng thái</button>
        </div>
      );
    } else if (isAdmin && activeKey === "inventory") {
      actions = (
        <div className="flex justify-end gap-2">
          <button type="button" disabled={dangLuu} onClick={() => void capNhatKho(row, "restock")} className="rounded-lg border border-[#ECECE9] px-2 py-1.5 text-xs font-semibold">Nhập kho</button>
          <button type="button" disabled={dangLuu} onClick={() => void capNhatKho(row, "export")} className="rounded-lg border border-[#F1CACA] px-2 py-1.5 text-xs font-semibold text-[#B60F0F]">Xuất kho</button>
        </div>
      );
    } else if (isAdmin && activeKey === "appointments") {
      actions = (
        <div className="flex justify-end gap-2">
          {Number(row.trang_thai) === 0 ? <button type="button" disabled={dangLuu} onClick={() => void guiForm(null, "lich-hen/xac-nhan", { id: row.id })} className="rounded-lg bg-[#17171A] px-2.5 py-1.5 text-xs font-semibold text-white">Duyệt</button> : null}
          {Number(row.trang_thai) === 1 ? <button type="button" disabled={dangLuu} onClick={() => { setSelectedAppointment(row); setFormData({ tong_tien: "", chi_tiet_kt: "[]" }); }} className="rounded-lg bg-[#B60F0F] px-2.5 py-1.5 text-xs font-semibold text-white">Nghiệm thu</button> : null}
        </div>
      );
    } else if (isAdmin && activeKey === "invoices") {
      actions = <button type="button" disabled={dangTai} onClick={() => void xemChiTietHoaDon(row)} className="rounded-lg border border-[#ECECE9] px-3 py-1.5 text-xs font-semibold">Chi tiết</button>;
    } else if (isAdmin && activeKey === "slides") {
      actions = (
        <div className="flex justify-end gap-2">
          <button type="button" onClick={() => { setSelectedSlide(true); setFormData({ id: row.id, tieu_de: row.tieu_de, hinh_anh: row.hinh_anh, duong_dan: row.duong_dan || "", thu_tu: row.thu_tu, trang_thai: String(row.trang_thai) }); }} className="rounded-lg border border-[#ECECE9] px-2 py-1.5 text-xs font-semibold">Sửa</button>
          <button type="button" disabled={dangLuu} onClick={() => void doiTrangThai("slide/change-status", row)} className="rounded-lg border border-[#ECECE9] px-2 py-1.5 text-xs font-semibold">Ẩn / hiện</button>
          <button type="button" disabled={dangLuu} onClick={() => void xoaBanGhi("slide/delete", row, `slide ${row.tieu_de}`)} className="rounded-lg border border-[#F1CACA] px-2 py-1.5 text-xs font-semibold text-[#B60F0F]">Xóa</button>
        </div>
      );
    } else if (!isAdmin && activeKey === "appointments") {
      const status = Number(row.trang_thai);
      actions = (
        <div className="flex justify-end gap-2">
          {status === 0 ? <button type="button" disabled={dangLuu} onClick={() => void guiForm(null, "lich-dat-do/cap-nhat", { id: row.id, trang_thai: 1 })} className="rounded-lg bg-[#17171A] px-2.5 py-1.5 text-xs font-semibold text-white">Xác nhận</button> : null}
          {status === 1 ? <button type="button" disabled={dangLuu} onClick={() => void guiForm(null, "lich-dat-do/cap-nhat", { id: row.id, trang_thai: 2 })} className="rounded-lg bg-[#2FA84F] px-2.5 py-1.5 text-xs font-semibold text-white">Hoàn thành</button> : null}
          {status === 0 || status === 1 ? <button type="button" disabled={dangLuu} onClick={() => void guiForm(null, "lich-dat-do/cap-nhat", { id: row.id, trang_thai: 3 })} className="rounded-lg border border-[#F1CACA] px-2.5 py-1.5 text-xs font-semibold text-[#B60F0F]">Từ chối</button> : null}
        </div>
      );
    } else if (!isAdmin && activeKey === "roster") {
      actions = <button type="button" disabled={dangLuu} onClick={() => void guiForm(null, "lich-lam-viec/xoa", { id: row.id })} className="rounded-lg border border-[#F1CACA] px-2.5 py-1.5 text-xs font-semibold text-[#B60F0F]">Xóa ca</button>;
    } else if (!isAdmin && activeKey === "customers") {
      actions = <button type="button" disabled={dangTai} onClick={() => void xemLichSuKhach(row)} className="rounded-lg border border-[#ECECE9] px-3 py-1.5 text-xs font-semibold">Lịch sử độ xe</button>;
    } else if (!isAdmin && activeKey === "vehicle-records") {
      actions = <button type="button" onClick={() => setFormData({ id: row.id, id_khach_hang: row.id_khach_hang, bien_so: row.bien_so, ten_xe: row.ten_xe, so_khung: row.so_khung || "", thong_so_tuning: row.thong_so_tuning || "" })} className="rounded-lg border border-[#ECECE9] px-3 py-1.5 text-xs font-semibold">Cập nhật tuning</button>;
    }
    return actions ? { ...row, __actions: actions } : row;
  });

  const title = MODULES[role][activeKey].title;
  const searchedRows = rowsWithActions.filter((row) => (
    !searchTerm.trim()
    || Object.entries(row)
      .filter(([key, value]) => !key.startsWith("__") && typeof value !== "object")
      .some(([, value]) => String(value ?? "").toLocaleLowerCase().includes(searchTerm.trim().toLocaleLowerCase()))
  ));

  return (
    <WorkspaceLayout title={isAdmin ? "Quản trị hệ thống" : "Workshop chuyên gia"} navigation={navigation} activeKey={activeKey} onNavigate={(key) => { setActiveKey(key); setHistoryRows(null); setChiTietHoaDon(null); }}>
      <div className="mb-5 flex items-center justify-between gap-4">
        <div>
          <h2 className="text-lg font-extrabold">{title}</h2>
          <p className="mt-1 text-sm text-[#63635E]">
            {isAdmin ? "Theo dõi và vận hành các nghiệp vụ showroom, workshop." : "Theo dõi ca làm, khu vực thi công và hồ sơ chủ xe được phân công."}
          </p>
        </div>
        <button type="button" onClick={() => void taiDuLieu()} disabled={dangTai} aria-label="Làm mới dữ liệu" className="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg border border-[#ECECE9] hover:bg-[#F1F1EF] disabled:opacity-50">
          <RefreshCw className={`h-4 w-4 ${dangTai ? "animate-spin" : ""}`} />
        </button>
      </div>

      {loi ? <p role="alert" className="mb-4 rounded-lg bg-[#FBE9E9] px-4 py-3 text-sm text-red-800">{loi}</p> : null}
      {thongBao ? <p role="status" className="mb-4 rounded-lg bg-[#E9F7EE] px-4 py-3 text-sm text-green-800">{thongBao}</p> : null}
      {dangTai ? <p className="py-5 text-sm text-[#63635E]">Đang tải dữ liệu...</p> : null}

      {isAdmin && activeKey === "dashboard" && !dangTai ? <ObjectSummary value={summary} /> : null}

      {activeKey === "profile" && !dangTai ? (
        <div className="space-y-6">
          <form onSubmit={(event) => void guiForm(event, "profile/update")} className="max-w-2xl rounded-xl border border-[#ECECE9] p-5">
            <label className="block text-sm font-semibold">Họ và tên
              <input name="ho_ten" required value={formData.ho_ten ?? profile?.ho_ten ?? ""} onChange={capNhatForm} className="mt-1.5 w-full rounded-lg border border-[#DCDCD7] px-3.5 py-2.5 font-normal" />
            </label>
            {!isAdmin ? (
              <>
                <label className="mt-4 block text-sm font-semibold">Số điện thoại
                  <input name="so_dien_thoai" required value={formData.so_dien_thoai ?? profile?.so_dien_thoai ?? ""} onChange={capNhatForm} className="mt-1.5 w-full rounded-lg border border-[#DCDCD7] px-3.5 py-2.5 font-normal" />
                </label>
                <label className="mt-4 block text-sm font-semibold">Kinh nghiệm
                  <textarea name="kinh_nghiem" rows={3} value={formData.kinh_nghiem ?? profile?.kinh_nghiem ?? ""} onChange={capNhatForm} className="mt-1.5 w-full rounded-lg border border-[#DCDCD7] px-3.5 py-2.5 font-normal" />
                </label>
              </>
            ) : null}
            <button disabled={dangLuu} className="mt-4 rounded-lg bg-[#17171A] px-5 py-2.5 text-sm font-semibold text-white disabled:opacity-50">Lưu hồ sơ</button>
          </form>
          <form onSubmit={(event) => void guiForm(event, "profile/change-password")} className="grid max-w-3xl gap-3 rounded-xl border border-[#ECECE9] p-5 md:grid-cols-3">
            <input name="old_password" required type="password" placeholder="Mật khẩu hiện tại" value={formData.old_password ?? ""} onChange={capNhatForm} className="rounded-lg border border-[#DCDCD7] px-3.5 py-2.5 text-sm" />
            <input name="password" required minLength={6} type="password" placeholder="Mật khẩu mới" value={formData.password ?? ""} onChange={capNhatForm} className="rounded-lg border border-[#DCDCD7] px-3.5 py-2.5 text-sm" />
            <input name="password_confirmation" required minLength={6} type="password" placeholder="Xác nhận mật khẩu" value={formData.password_confirmation ?? ""} onChange={capNhatForm} className="rounded-lg border border-[#DCDCD7] px-3.5 py-2.5 text-sm" />
            <button disabled={dangLuu} className="w-fit rounded-lg border border-[#17171A] px-5 py-2.5 text-sm font-semibold">Đổi mật khẩu</button>
          </form>
        </div>
      ) : null}

      {isAdmin && activeKey === "permissions" && permissionData ? (
        <form
          onSubmit={(event) => {
            event.preventDefault();
            const idChucNangs = String(formData.id_chuc_nangs ?? "")
              .split(",")
              .map((id) => Number(id.trim()))
              .filter((id) => Number.isInteger(id) && id > 0);
            void guiForm(event, "phan-quyen/create", {
              id_chuc_vu: Number(formData.id_chuc_vu),
              id_chuc_nangs: idChucNangs,
            });
          }}
          className="mb-5 rounded-xl bg-[#FAFAF9] p-4"
        >
          <div className="grid gap-3 md:grid-cols-[1fr_1fr_auto]">
            <select name="id_chuc_vu" required value={formData.id_chuc_vu ?? ""} onChange={capNhatForm} className="rounded-lg border border-[#DCDCD7] bg-white px-3 py-2.5 text-sm">
              <option value="" disabled>Chọn chức vụ</option>
              {(permissionData.chuc_vus ?? []).map((item) => <option key={item.id} value={item.id}>{item.ten_chuc_vu}</option>)}
            </select>
            <input name="id_chuc_nangs" required placeholder="ID chức năng, cách nhau bằng dấu phẩy" value={formData.id_chuc_nangs ?? ""} onChange={capNhatForm} className="rounded-lg border border-[#DCDCD7] px-3 py-2.5 text-sm" />
            <button disabled={dangLuu} className="rounded-lg bg-[#17171A] px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-50">Lưu ma trận quyền</button>
          </div>
          <p className="mt-3 text-xs text-[#63635E]">
            Chức năng: {(permissionData.chuc_nangs ?? []).map((item) => `${item.id}: ${item.ten_chuc_nang || item.ten}`).join(" · ")}
          </p>
        </form>
      ) : null}

      {isAdmin && activeKey === "roles" ? (
        <form onSubmit={(event) => void guiForm(event, "chuc-vu/create")} className="mb-5 grid gap-3 rounded-xl bg-[#FAFAF9] p-4 md:grid-cols-[1fr_1fr_auto]">
          <input name="ten_chuc_vu" required placeholder="Tên chức vụ" value={formData.ten_chuc_vu ?? ""} onChange={capNhatForm} className="rounded-lg border border-[#DCDCD7] px-3 py-2.5 text-sm" />
          <input name="ma_chuc_vu" required placeholder="Mã chức vụ" value={formData.ma_chuc_vu ?? ""} onChange={capNhatForm} className="rounded-lg border border-[#DCDCD7] px-3 py-2.5 text-sm" />
          <button disabled={dangLuu} className="rounded-lg bg-[#17171A] px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-50">Thêm chức vụ</button>
        </form>
      ) : null}

      {["roles", "employees", "customers", "inventory", "appointments", "invoices", "slides"].includes(activeKey) ? (
        <label className="mb-4 block max-w-md">
          <span className="sr-only">Tìm kiếm dữ liệu</span>
          <input type="search" value={searchTerm} onChange={(event) => setSearchTerm(event.target.value)} placeholder="Tìm trong danh sách..." className="w-full rounded-lg border border-[#DCDCD7] px-3.5 py-2.5 text-sm outline-none focus:border-[#17171A]" />
        </label>
      ) : null}

      {isAdmin && activeKey === "employees" ? (
        <form onSubmit={(event) => void guiForm(event, "nhan-vien/create")} className="mb-5 grid gap-3 rounded-xl bg-[#FAFAF9] p-4 md:grid-cols-3">
          <input name="ho_ten" required placeholder="Họ tên nhân viên" value={formData.ho_ten ?? ""} onChange={capNhatForm} className="rounded-lg border border-[#DCDCD7] px-3 py-2.5 text-sm" />
          <input name="email" required type="email" placeholder="Email" value={formData.email ?? ""} onChange={capNhatForm} className="rounded-lg border border-[#DCDCD7] px-3 py-2.5 text-sm" />
          <input name="so_dien_thoai" required placeholder="Số điện thoại" value={formData.so_dien_thoai ?? ""} onChange={capNhatForm} className="rounded-lg border border-[#DCDCD7] px-3 py-2.5 text-sm" />
          <input name="password" required minLength={6} type="password" placeholder="Mật khẩu tạm thời" value={formData.password ?? ""} onChange={capNhatForm} className="rounded-lg border border-[#DCDCD7] px-3 py-2.5 text-sm" />
          <input name="id_chuc_vu" required type="number" min="1" placeholder="ID chức vụ" value={formData.id_chuc_vu ?? ""} onChange={capNhatForm} className="rounded-lg border border-[#DCDCD7] px-3 py-2.5 text-sm" />
          <button disabled={dangLuu} className="rounded-lg bg-[#17171A] px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-50">Tạo tài khoản</button>
        </form>
      ) : null}

      {isAdmin && activeKey === "inventory" ? (
        <form onSubmit={(event) => void guiForm(event, "products")} className="mb-5 grid gap-3 rounded-xl bg-[#FAFAF9] p-4 md:grid-cols-3">
          <input name="ten_san_pham" required placeholder="Tên xe / phụ tùng" value={formData.ten_san_pham ?? ""} onChange={capNhatForm} className="rounded-lg border border-[#DCDCD7] px-3 py-2.5 text-sm" />
          <select name="loai_san_pham" required value={formData.loai_san_pham ?? ""} onChange={capNhatForm} className="rounded-lg border border-[#DCDCD7] bg-white px-3 py-2.5 text-sm">
            <option value="" disabled>Loại sản phẩm</option>
            <option value="xe_nguyen_chiec">Xe nguyên chiếc</option>
            <option value="phan_cung">Phụ tùng / phần cứng</option>
            <option value="phan_mem">Phần mềm</option>
            <option value="dien_tu">Điện tử</option>
            <option value="cong_nghe_moi">Công nghệ mới</option>
          </select>
          <input name="gia" required type="number" min="0" placeholder="Giá bán" value={formData.gia ?? ""} onChange={capNhatForm} className="rounded-lg border border-[#DCDCD7] px-3 py-2.5 text-sm" />
          <input name="so_luong_kho" required type="number" min="0" placeholder="Tồn kho ban đầu" value={formData.so_luong_kho ?? ""} onChange={capNhatForm} className="rounded-lg border border-[#DCDCD7] px-3 py-2.5 text-sm" />
          <input name="danh_muc_id" type="number" min="1" placeholder="ID danh mục (nếu có)" value={formData.danh_muc_id ?? ""} onChange={capNhatForm} className="rounded-lg border border-[#DCDCD7] px-3 py-2.5 text-sm" />
          <button disabled={dangLuu} className="rounded-lg bg-[#17171A] px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-50">Thêm sản phẩm</button>
        </form>
      ) : null}

      {!isAdmin && activeKey === "roster" ? (
        <form onSubmit={(event) => void guiForm(event, "lich-lam-viec/dang-ky")} className="mb-5 grid gap-3 rounded-xl bg-[#FAFAF9] p-4 md:grid-cols-[1fr_1fr_auto]">
          <input name="ngay_lam_viec" required type="date" min={new Date().toISOString().slice(0, 10)} value={formData.ngay_lam_viec ?? ""} onChange={capNhatForm} className="rounded-lg border border-[#DCDCD7] px-3 py-2.5 text-sm" />
          <select name="ca_lam_viec" required value={formData.ca_lam_viec ?? ""} onChange={capNhatForm} className="rounded-lg border border-[#DCDCD7] bg-white px-3 py-2.5 text-sm">
            <option value="" disabled>Chọn ca</option>
            <option value="Ca sáng (08:00 - 12:00)">Ca sáng (08:00 - 12:00)</option>
            <option value="Ca chiều (13:00 - 17:00)">Ca chiều (13:00 - 17:00)</option>
          </select>
          <button disabled={dangLuu} className="rounded-lg bg-[#17171A] px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-50">Đăng ký ca</button>
        </form>
      ) : null}

      {!isAdmin && activeKey === "vehicle-records" ? (
        <form onSubmit={guiHoSoXe} className="mb-5 grid gap-3 rounded-xl bg-[#FAFAF9] p-4 md:grid-cols-2">
          <input name="id_khach_hang" required type="number" min="1" placeholder="ID khách hàng được phân công" value={formData.id_khach_hang ?? ""} onChange={capNhatForm} className="rounded-lg border border-[#DCDCD7] px-3 py-2.5 text-sm" />
          <input name="bien_so" required placeholder="Biển số xe" value={formData.bien_so ?? ""} onChange={capNhatForm} className="rounded-lg border border-[#DCDCD7] px-3 py-2.5 text-sm" />
          <input name="ten_xe" required placeholder="Tên mẫu xe" value={formData.ten_xe ?? ""} onChange={capNhatForm} className="rounded-lg border border-[#DCDCD7] px-3 py-2.5 text-sm" />
          <input name="so_khung" placeholder="Số khung" value={formData.so_khung ?? ""} onChange={capNhatForm} className="rounded-lg border border-[#DCDCD7] px-3 py-2.5 text-sm" />
          <textarea name="thong_so_tuning" rows={3} placeholder="Hồ sơ tình trạng xe, phương án sửa chữa, thông số ECU / Dyno / Remap" value={formData.thong_so_tuning ?? ""} onChange={capNhatForm} className="rounded-lg border border-[#DCDCD7] px-3 py-2.5 text-sm md:col-span-2" />
          <button disabled={dangLuu} className="w-fit rounded-lg bg-[#17171A] px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-50">{formData.id ? "Cập nhật hồ sơ xe" : "Lập hồ sơ xe"}</button>
          {formData.id ? <button type="button" onClick={() => setFormData({})} className="w-fit rounded-lg border border-[#ECECE9] px-4 py-2.5 text-sm font-semibold">Bỏ chọn hồ sơ</button> : null}
        </form>
      ) : null}

      {isAdmin && activeKey === "customers" ? (
        <div className="mb-4 flex justify-end">
          <button type="button" disabled={dangLuu} onClick={() => void xuatExcelKhachHang()} className="rounded-lg border border-[#ECECE9] px-4 py-2.5 text-sm font-semibold disabled:opacity-50">Xuất Excel</button>
        </div>
      ) : null}

      {isAdmin && activeKey === "slides" ? (
        <form onSubmit={(event) => void guiForm(event, "slide/create")} className="mb-5 grid gap-3 rounded-xl bg-[#FAFAF9] p-4 md:grid-cols-[1fr_2fr_1fr_auto]">
          <input name="tieu_de" required placeholder="Tiêu đề" value={formData.tieu_de ?? ""} onChange={capNhatForm} className="rounded-lg border border-[#DCDCD7] px-3 py-2.5 text-sm" />
          <input name="hinh_anh" required placeholder="URL hoặc đường dẫn ảnh" value={formData.hinh_anh ?? ""} onChange={capNhatForm} className="rounded-lg border border-[#DCDCD7] px-3 py-2.5 text-sm" />
          <input name="thu_tu" required type="number" min="0" placeholder="Thứ tự" value={formData.thu_tu ?? ""} onChange={capNhatForm} className="rounded-lg border border-[#DCDCD7] px-3 py-2.5 text-sm" />
          <button disabled={dangLuu} className="rounded-lg bg-[#17171A] px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-50">Thêm slide</button>
        </form>
      ) : null}

      {isAdmin && activeKey === "appointments" ? (
        <form onSubmit={kiemTraMaPhieu} className="mb-5 flex flex-wrap gap-3 rounded-xl bg-[#FAFAF9] p-4">
          <input name="ma_dat_lich" required placeholder="Nhập mã phiếu đặt lịch để đối soát" value={formData.ma_dat_lich ?? ""} onChange={capNhatForm} className="min-w-[240px] flex-1 rounded-lg border border-[#DCDCD7] px-3 py-2.5 text-sm" />
          <button disabled={dangLuu} className="rounded-lg bg-[#17171A] px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-50">Kiểm tra phiếu</button>
        </form>
      ) : null}

      {isAdmin && activeKey === "reports" ? (
        <form onSubmit={(event) => { event.preventDefault(); void taiDuLieu(); }} className="mb-5 flex flex-wrap items-end gap-3 rounded-xl bg-[#FAFAF9] p-4">
          <label className="text-sm font-semibold">Từ ngày<input type="date" value={boLoc.tu_ngay} onChange={(event) => setBoLoc({ ...boLoc, tu_ngay: event.target.value })} className="mt-1 block rounded-lg border border-[#DCDCD7] px-3 py-2 text-sm font-normal" /></label>
          <label className="text-sm font-semibold">Đến ngày<input type="date" min={boLoc.tu_ngay || undefined} value={boLoc.den_ngay} onChange={(event) => setBoLoc({ ...boLoc, den_ngay: event.target.value })} className="mt-1 block rounded-lg border border-[#DCDCD7] px-3 py-2 text-sm font-normal" /></label>
          <button disabled={dangTai} className="rounded-lg bg-[#17171A] px-4 py-2.5 text-sm font-semibold text-white">Lọc báo cáo</button>
        </form>
      ) : null}

      {isAdmin && activeKey === "roles" && !dangTai ? <WorkspaceTable rows={searchedRows} emptyMessage="Chưa có chức vụ." /> : null}
      {isAdmin && activeKey === "employees" && !dangTai ? <WorkspaceTable rows={searchedRows} emptyMessage="Chưa có nhân viên." /> : null}
      {isAdmin && activeKey === "customers" && !dangTai ? <WorkspaceTable rows={searchedRows} emptyMessage="Chưa có khách hàng." /> : null}
      {isAdmin && activeKey === "inventory" && !dangTai ? <WorkspaceTable rows={searchedRows} emptyMessage="Kho chưa có sản phẩm." /> : null}
      {isAdmin && activeKey === "appointments" && !dangTai ? <WorkspaceTable rows={searchedRows} emptyMessage="Chưa có lịch workshop." /> : null}
      {isAdmin && activeKey === "invoices" && !dangTai ? <WorkspaceTable rows={searchedRows} emptyMessage="Chưa phát sinh hóa đơn." /> : null}
      {isAdmin && activeKey === "slides" && !dangTai ? <WorkspaceTable rows={searchedRows} emptyMessage="Chưa có banner hoặc slide." /> : null}
      {isAdmin && activeKey === "permissions" && !dangTai ? <WorkspaceTable rows={rows} emptyMessage="Ma trận phân quyền chưa được cấu hình." /> : null}
      {isAdmin && activeKey === "reports" && !dangTai ? <WorkspaceTable rows={rows} emptyMessage="Không có dữ liệu báo cáo trong khoảng thời gian đã chọn." /> : null}

      {!isAdmin && activeKey === "customers" && !dangTai ? (
        <>
          {historyRows ? (
            <section className="mb-5 rounded-xl border border-[#ECECE9] p-4">
              <div className="mb-3 flex items-center justify-between">
                <h3 className="font-bold">Lịch sử độ xe: {historyRows.customer.ho_ten}</h3>
                <button type="button" onClick={() => setHistoryRows(null)} className="text-sm font-semibold text-[#B60F0F]">Đóng</button>
              </div>
              <WorkspaceTable rows={historyRows.rows} emptyMessage="Khách hàng chưa có lịch sử nâng cấp." />
            </section>
          ) : null}
          <WorkspaceTable rows={searchedRows} emptyMessage="Chưa có khách hàng được phân công." />
        </>
      ) : null}
      {!isAdmin && activeKey !== "customers" && !dangTai ? <WorkspaceTable rows={activeKey === "vehicle-records" ? searchedRows : rows} emptyMessage="Chưa có dữ liệu." /> : null}

      {selectedAppointment ? (
        <div className="fixed inset-0 z-[80] flex items-center justify-center bg-black/50 p-4" role="presentation">
          <form onSubmit={(event) => void dongBoanTatLich(event)} className="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl" role="dialog" aria-modal="true" aria-labelledby="complete-appointment-title">
            <h3 id="complete-appointment-title" className="mb-4 text-lg font-extrabold">Nghiệm thu lịch hẹn</h3>
            <label className="mb-4 block text-sm font-semibold">Tổng chi phí<input required type="number" min="0" value={formData.tong_tien ?? ""} onChange={(event) => setFormData({ ...formData, tong_tien: event.target.value })} className="mt-1.5 w-full rounded-lg border border-[#DCDCD7] px-3.5 py-2.5 font-normal" /></label>
            <label className="block text-sm font-semibold">Chi tiết vật tư JSON<textarea rows={5} value={formData.chi_tiet_kt ?? "[]"} onChange={(event) => setFormData({ ...formData, chi_tiet_kt: event.target.value })} className="mt-1.5 w-full rounded-lg border border-[#DCDCD7] px-3.5 py-2.5 font-mono text-xs font-normal" /></label>
            <p className="mt-2 text-xs text-[#63635E]">Mỗi phần tử gồm ten_vat_tu, so_luong và don_gia. Ví dụ: []</p>
            <div className="mt-5 flex justify-end gap-2">
              <button type="button" onClick={() => setSelectedAppointment(null)} className="rounded-lg border border-[#ECECE9] px-4 py-2.5 text-sm font-semibold">Hủy</button>
              <button disabled={dangLuu} className="rounded-lg bg-[#B60F0F] px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-50">Hoàn thành</button>
            </div>
          </form>
        </div>
      ) : null}

      {selectedCustomer ? (
        <div className="fixed inset-0 z-[80] flex items-center justify-center bg-black/50 p-4">
          <form onSubmit={async (event) => { const saved = await guiForm(event, "khach-hang/update"); if (saved) setSelectedCustomer(false); }} className="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl" role="dialog" aria-modal="true">
            <h3 className="mb-4 text-lg font-extrabold">Cập nhật hồ sơ khách hàng</h3>
            {[
              ["ho_ten", "Họ tên", "text"],
              ["email", "Email", "email"],
              ["so_dien_thoai", "Số điện thoại", "tel"],
              ["dia_chi", "Địa chỉ", "text"],
            ].map(([name, label, type]) => (
              <label key={name} className="mb-3 block text-sm font-semibold">{label}
                <input name={name} type={type} required={name !== "dia_chi"} value={formData[name] ?? ""} onChange={capNhatForm} className="mt-1.5 w-full rounded-lg border border-[#DCDCD7] px-3.5 py-2.5 font-normal" />
              </label>
            ))}
            <div className="mt-5 flex justify-end gap-2">
              <button type="button" onClick={() => setSelectedCustomer(false)} className="rounded-lg border border-[#ECECE9] px-4 py-2.5 text-sm font-semibold">Đóng</button>
              <button disabled={dangLuu} className="rounded-lg bg-[#17171A] px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-50">Lưu thay đổi</button>
            </div>
          </form>
        </div>
      ) : null}

      {selectedRole ? (
        <div className="fixed inset-0 z-[80] flex items-center justify-center bg-black/50 p-4">
          <form onSubmit={async (event) => { const saved = await guiForm(event, "chuc-vu/update"); if (saved) setSelectedRole(false); }} className="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl" role="dialog" aria-modal="true">
            <h3 className="mb-4 text-lg font-extrabold">Cập nhật chức vụ</h3>
            <input type="hidden" name="id" value={formData.id ?? ""} />
            <label className="mb-3 block text-sm font-semibold">Tên chức vụ<input required value={formData.ten_chuc_vu ?? ""} onChange={(event) => setFormData({ ...formData, ten_chuc_vu: event.target.value })} className="mt-1.5 w-full rounded-lg border border-[#DCDCD7] px-3.5 py-2.5 font-normal" /></label>
            <label className="mb-3 block text-sm font-semibold">Mã chức vụ<input required value={formData.ma_chuc_vu ?? ""} onChange={(event) => setFormData({ ...formData, ma_chuc_vu: event.target.value })} className="mt-1.5 w-full rounded-lg border border-[#DCDCD7] px-3.5 py-2.5 font-normal" /></label>
            <label className="mb-3 block text-sm font-semibold">Trạng thái<select value={formData.trang_thai ?? "1"} onChange={(event) => setFormData({ ...formData, trang_thai: event.target.value })} className="mt-1.5 w-full rounded-lg border border-[#DCDCD7] bg-white px-3.5 py-2.5 font-normal"><option value="1">Hoạt động</option><option value="0">Ngừng hoạt động</option></select></label>
            <div className="mt-5 flex justify-end gap-2">
              <button type="button" onClick={() => setSelectedRole(false)} className="rounded-lg border border-[#ECECE9] px-4 py-2.5 text-sm font-semibold">Đóng</button>
              <button disabled={dangLuu} className="rounded-lg bg-[#17171A] px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-50">Lưu thay đổi</button>
            </div>
          </form>
        </div>
      ) : null}

      {selectedSlide ? (
        <div className="fixed inset-0 z-[80] flex items-center justify-center bg-black/50 p-4">
          <form onSubmit={async (event) => { const saved = await guiForm(event, "slide/update"); if (saved) setSelectedSlide(false); }} className="w-full max-w-lg rounded-2xl bg-white p-6 shadow-2xl" role="dialog" aria-modal="true">
            <h3 className="mb-4 text-lg font-extrabold">Cập nhật slide / banner</h3>
            <input type="hidden" name="id" value={formData.id ?? ""} />
            <label className="mb-3 block text-sm font-semibold">Tiêu đề<input required value={formData.tieu_de ?? ""} onChange={(event) => setFormData({ ...formData, tieu_de: event.target.value })} className="mt-1.5 w-full rounded-lg border border-[#DCDCD7] px-3.5 py-2.5 font-normal" /></label>
            <label className="mb-3 block text-sm font-semibold">Đường dẫn ảnh<input required value={formData.hinh_anh ?? ""} onChange={(event) => setFormData({ ...formData, hinh_anh: event.target.value })} className="mt-1.5 w-full rounded-lg border border-[#DCDCD7] px-3.5 py-2.5 font-normal" /></label>
            <label className="mb-3 block text-sm font-semibold">Liên kết<input value={formData.duong_dan ?? ""} onChange={(event) => setFormData({ ...formData, duong_dan: event.target.value })} className="mt-1.5 w-full rounded-lg border border-[#DCDCD7] px-3.5 py-2.5 font-normal" /></label>
            <div className="grid grid-cols-2 gap-3">
              <label className="text-sm font-semibold">Thứ tự<input required type="number" min="0" value={formData.thu_tu ?? 0} onChange={(event) => setFormData({ ...formData, thu_tu: event.target.value })} className="mt-1.5 w-full rounded-lg border border-[#DCDCD7] px-3.5 py-2.5 font-normal" /></label>
              <label className="text-sm font-semibold">Trạng thái<select value={formData.trang_thai ?? "1"} onChange={(event) => setFormData({ ...formData, trang_thai: event.target.value })} className="mt-1.5 w-full rounded-lg border border-[#DCDCD7] bg-white px-3.5 py-2.5 font-normal"><option value="1">Hiển thị</option><option value="0">Ẩn</option></select></label>
            </div>
            <div className="mt-5 flex justify-end gap-2">
              <button type="button" onClick={() => setSelectedSlide(false)} className="rounded-lg border border-[#ECECE9] px-4 py-2.5 text-sm font-semibold">Đóng</button>
              <button disabled={dangLuu} className="rounded-lg bg-[#17171A] px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-50">Lưu thay đổi</button>
            </div>
          </form>
        </div>
      ) : null}

      {orderModalId ? <OrderDetailModal orderId={orderModalId} role={role} onClose={() => setOrderModalId(null)} /> : null}
      {chiTietHoaDon ? (
        <section className="mt-5 rounded-xl border border-[#ECECE9] p-4">
          <div className="mb-3 flex items-center justify-between">
            <h3 className="font-bold">Chi tiết hóa đơn</h3>
            <button type="button" onClick={() => setChiTietHoaDon(null)} className="text-sm font-semibold text-[#B60F0F]">Đóng</button>
          </div>
          <pre className="overflow-x-auto whitespace-pre-wrap rounded-lg bg-[#FAFAF9] p-3 text-xs">{JSON.stringify(chiTietHoaDon, null, 2)}</pre>
        </section>
      ) : null}
    </WorkspaceLayout>
  );
}
