import { useCallback, useEffect, useState } from "react";
import {
  Building2, CalendarClock, CalendarDays, FileText, LayoutDashboard, Package, ShoppingBag,
  UserRound, UserRoundCog, Users,
} from "lucide-react";
import { useAuth } from "../../context/AuthContext";
import { getWorkspaceData, postWorkspaceData } from "../../api/WorkspaceApi";
import AdminWorkspaceLayout from "../../components/workspaces/AdminWorkspaceLayout";
import OrderDetailModal from "../../components/workspaces/OrderDetailModal";
import { errorMessage } from "../chuyengia/workspace/expertUi";
import AdminDashboardView from "./AdminDashboardView";
import {
  AppointmentsView, BaysView, CustomersView, ExpertsView, InvoicesView, OrdersView,
  ProductsView, RosterView, StaffView,
} from "./AdminViews";

const SOURCES = {
  dashboard: ["dashboard"],
  appointments: ["lich-hen", "chuyen-gia", "khu-vuc"],
  orders: ["don-hang"],
  invoices: ["hoa-don"],
  roster: ["lich-lam-viec"],
  bays: ["khu-vuc"],
  customers: ["khach-hang"],
  experts: ["chuyen-gia"],
  staff: ["nhan-vien"],
  products: ["san-pham"],
};

const TITLES = {
  dashboard: "Dashboard", appointments: "Lịch hẹn", orders: "Đơn hàng", invoices: "Hóa đơn",
  roster: "Lịch làm việc chuyên gia", bays: "Khu vực workshop", customers: "Khách hàng",
  experts: "Chuyên gia / tuner", staff: "Nhân viên", products: "Xe & phụ tùng",
};

const GROUPS = [
  { label: "Tổng quan", items: [{ key: "dashboard", label: TITLES.dashboard, icon: LayoutDashboard }] },
  {
    label: "Vận hành",
    items: [
      { key: "appointments", label: TITLES.appointments, icon: CalendarDays },
      { key: "orders", label: TITLES.orders, icon: ShoppingBag },
      { key: "invoices", label: TITLES.invoices, icon: FileText },
      { key: "roster", label: TITLES.roster, icon: CalendarClock },
      { key: "bays", label: TITLES.bays, icon: Building2 },
    ],
  },
  {
    label: "Danh mục",
    items: [
      { key: "customers", label: TITLES.customers, icon: Users },
      { key: "experts", label: TITLES.experts, icon: UserRound },
      { key: "staff", label: TITLES.staff, icon: UserRoundCog },
      { key: "products", label: TITLES.products, icon: Package },
    ],
  },
];

const STAFF_KEYS = ["dashboard", "appointments", "orders", "invoices", "customers", "products"];
const STAFF_GROUPS = GROUPS
  .map((group) => ({ ...group, items: group.items.filter((item) => STAFF_KEYS.includes(item.key)) }))
  .filter((group) => group.items.length);

const rowsOf = (value) => (Array.isArray(value) ? value : []);

export default function AdminWorkspacePage() {
  const { nguoiDung } = useAuth();
  const isStaff = /@staff\./i.test(nguoiDung?.email || "");
  const [activeKey, setActiveKey] = useState("dashboard");
  const [data, setData] = useState({});
  const [loading, setLoading] = useState(true);
  const [busy, setBusy] = useState(false);
  const [error, setError] = useState("");
  const [notice, setNotice] = useState("");
  const [search, setSearch] = useState("");
  const [orderModalId, setOrderModalId] = useState(null);
  const [alertCount, setAlertCount] = useState(0);

  const load = useCallback(async (key) => {
    setLoading(true);
    setError("");
    try {
      const sources = SOURCES[key];
      const results = await Promise.all(sources.map((source) => getWorkspaceData("admin", `workspace/${source}`)));
      setData(Object.fromEntries(sources.map((source, index) => [source, results[index]])));
      if (key === "dashboard") {
        const stat = results[0].thong_ke;
        setAlertCount(stat.can_xu_ly + stat.don_can_xu_ly);
      }
    } catch (err) {
      setData({});
      setError(errorMessage(err, "Không thể tải dữ liệu quản trị."));
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    let active = true;
    Promise.resolve().then(() => { if (active) void load(activeKey); });
    return () => { active = false; };
  }, [activeKey, load]);

  const navigate = (key) => {
    setActiveKey(key);
    setSearch("");
    setNotice("");
  };

  const run = async (path, payload, message) => {
    setBusy(true);
    setError("");
    setNotice("");
    try {
      await postWorkspaceData("admin", path, payload);
      setNotice(message);
      await load(activeKey);
      return true;
    } catch (err) {
      setError(errorMessage(err, "Không thể thực hiện thao tác."));
      return false;
    } finally {
      setBusy(false);
    }
  };

  const view = () => {
    if (activeKey === "dashboard") return <AdminDashboardView data={data.dashboard} onNavigate={navigate} />;
    if (activeKey === "appointments") {
      return <AppointmentsView rows={rowsOf(data["lich-hen"])} experts={rowsOf(data["chuyen-gia"])} bays={rowsOf(data["khu-vuc"])} search={search} run={run} busy={busy} />;
    }
    if (activeKey === "orders") return <OrdersView rows={rowsOf(data["don-hang"])} search={search} run={run} busy={busy} openOrder={setOrderModalId} />;
    if (activeKey === "invoices") return <InvoicesView rows={rowsOf(data["hoa-don"])} search={search} openOrder={setOrderModalId} />;
    if (activeKey === "roster") return <RosterView rows={rowsOf(data["lich-lam-viec"])} search={search} />;
    if (activeKey === "bays") return <BaysView rows={rowsOf(data["khu-vuc"])} search={search} />;
    if (activeKey === "customers") return <CustomersView rows={rowsOf(data["khach-hang"])} search={search} />;
    if (activeKey === "experts") return <ExpertsView rows={rowsOf(data["chuyen-gia"])} search={search} />;
    if (activeKey === "staff") return <StaffView rows={rowsOf(data["nhan-vien"])} search={search} />;
    return <ProductsView rows={rowsOf(data["san-pham"])} search={search} />;
  };

  const hasData = SOURCES[activeKey].every((source) => data[source] !== undefined);

  return (
    <AdminWorkspaceLayout
      groups={isStaff ? STAFF_GROUPS : GROUPS}
      roleLabel={isStaff ? "Nhân viên workshop" : "Quản trị viên"}
      activeKey={activeKey}
      onNavigate={navigate}
      title={TITLES[activeKey]}
      search={search}
      onSearch={setSearch}
      alertCount={alertCount}
      onAlertClick={() => navigate(alertCount ? "appointments" : "dashboard")}
    >
      {activeKey !== "dashboard" ? <h1 className="mb-6 text-3xl font-extrabold tracking-tight">{TITLES[activeKey]}</h1> : null}
      {error ? <p role="alert" className="mb-5 rounded-xl border border-[#F6C9C9] bg-[#FDE8E8] px-4 py-3 text-sm text-[#AC0021]">{error}</p> : null}
      {notice ? <p role="status" className="mb-5 rounded-xl border border-[#BFE5CD] bg-[#E4F5EA] px-4 py-3 text-sm text-[#1F7A3D]">{notice}</p> : null}
      {loading && !hasData ? <p className="py-16 text-center text-sm text-[#63635E]">Đang tải dữ liệu...</p> : null}
      {hasData ? view() : null}
      {orderModalId ? <OrderDetailModal orderId={orderModalId} role="admin" onClose={() => setOrderModalId(null)} /> : null}
    </AdminWorkspaceLayout>
  );
}
