import { useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import { CalendarClock, LayoutDashboard, LogOut, Menu, Star, Store, UserRoundCheck, Warehouse, X, Gauge } from "lucide-react";
import { useAuth } from "../../context/AuthContext";
import { getWorkspaceAssetUrl } from "../../api/WorkspaceApi";
import { initials } from "../../pages/chuyengia/workspace/expertUi";

const NAV = [
  { key: "board", label: "Bảng làm việc", icon: LayoutDashboard },
  { key: "customers", label: "Khách hàng phụ trách", icon: UserRoundCheck },
  { key: "workshop", label: "Khu vực workshop", icon: Warehouse },
  { key: "roster", label: "Lịch làm việc", icon: CalendarClock },
];

export default function ExpertWorkspaceLayout({ activeKey, onNavigate, expert, dynoBusy, children }) {
  const [menuMo, setMenuMo] = useState(false);
  const { nguoiDung, dangXuat } = useAuth();
  const navigate = useNavigate();
  const ten = expert?.ho_ten || nguoiDung?.ho_ten || "Chuyên gia";
  const avatar = getWorkspaceAssetUrl(expert?.anh);

  const xuLyDangXuat = async () => {
    await dangXuat();
    navigate("/");
  };

  const chonMuc = (key) => {
    onNavigate(key);
    setMenuMo(false);
  };

  return (
    <div className="min-h-screen bg-[#F4F4F2] text-[#17171A]">
      {menuMo ? (
        <button type="button" aria-label="Đóng menu" onClick={() => setMenuMo(false)} className="fixed inset-0 z-[59] bg-black/40 lg:hidden" />
      ) : null}

      <aside className={`fixed inset-y-0 left-0 z-[60] flex w-[268px] flex-col border-r border-[#E6E6E3] bg-[#FAFAF9] transition-transform duration-200 lg:translate-x-0 ${menuMo ? "translate-x-0" : "-translate-x-full"}`}>
        <div className="flex h-[72px] shrink-0 items-center gap-3 border-b border-[#E6E6E3] px-5">
          <img src="/src/assets/img/logo.png" alt="Porsche" className="h-10 w-10 object-contain" />
          <div className="leading-tight">
            <p className="text-sm font-extrabold uppercase tracking-tight">Porsche</p>
            <p className="text-[11px] text-[#63635E]">Khu chuyên gia</p>
          </div>
          <button type="button" onClick={() => setMenuMo(false)} aria-label="Đóng menu" className="ml-auto rounded-lg p-2 lg:hidden">
            <X className="h-5 w-5" />
          </button>
        </div>

        <nav className="space-y-1 px-3 py-3">
          {NAV.map(({ key, label, icon: Icon }) => (
            <button
              key={key}
              type="button"
              onClick={() => chonMuc(key)}
              aria-current={activeKey === key ? "page" : undefined}
              className={`flex w-full items-center gap-3 rounded-xl px-3.5 py-3 text-left text-sm font-semibold transition-colors ${
                activeKey === key ? "bg-[#0B0B0D] text-white" : "text-[#3D3D39] hover:bg-[#EFEFEC]"
              }`}
            >
              <Icon className="h-[18px] w-[18px] shrink-0" />
              {label}
            </button>
          ))}
        </nav>

        <div className="mx-3 mt-2 rounded-2xl border border-[#ECECE9] bg-[#F4F3EF] p-4">
          <div className="flex items-center gap-2.5">
            <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-[#F8E9CC] text-[#8A6414]">
              <Gauge className="h-4 w-4" />
            </span>
            <p className="text-sm font-bold">Thiết bị Dyno</p>
          </div>
          <p className="mt-2.5 text-xs leading-relaxed text-[#63635E]">
            {dynoBusy ? "Dyno đang có xe chạy test. Chờ xe hiện tại hoàn tất trước khi chuyển ca mới." : "Dyno đang trống. Đặt khung giờ test trước khi nạp map."}
          </p>
          <button type="button" onClick={() => chonMuc("workshop")} className="mt-3 w-full rounded-lg bg-[#0B0B0D] py-2.5 text-xs font-semibold text-white">
            Xem khu vực
          </button>
        </div>

        <div className="mt-auto space-y-1 border-t border-[#E6E6E3] px-3 py-3">
          <Link to="/" className="flex items-center gap-3 rounded-xl px-3.5 py-3 text-sm font-semibold text-[#3D3D39] hover:bg-[#EFEFEC]">
            <Store className="h-[18px] w-[18px]" /> Về showroom
          </Link>
          <button type="button" onClick={xuLyDangXuat} className="flex w-full items-center gap-3 rounded-xl px-3.5 py-3 text-left text-sm font-semibold text-[#AC0021] hover:bg-[#FBE9E9]">
            <LogOut className="h-[18px] w-[18px]" /> Đăng xuất
          </button>
        </div>
      </aside>

      <div className="lg:pl-[268px]">
        <header className="sticky top-0 z-40 flex h-[72px] items-center gap-3 border-b border-[#E6E6E3] bg-[#FAFAF9]/95 px-4 backdrop-blur sm:px-8">
          <button type="button" onClick={() => setMenuMo(true)} aria-label="Mở menu" className="rounded-lg border border-[#E6E6E3] bg-white p-2 lg:hidden">
            <Menu className="h-5 w-5" />
          </button>
          <div className="min-w-0">
            <p className="truncate text-xs text-[#63635E]">Khu chuyên gia{expert?.dia_chi ? ` · ${expert.dia_chi}` : ""}</p>
            <p className="truncate text-base font-extrabold tracking-tight">Xin chào, {ten}</p>
          </div>
          <div className="ml-auto flex items-center gap-3">
            {expert ? (
              <span className="hidden items-center gap-1.5 rounded-full border border-[#F0DDB0] bg-[#FBF4E2] px-3 py-1.5 text-xs font-semibold text-[#6B5217] sm:inline-flex">
                <Star className="h-3.5 w-3.5 fill-current" />
                {expert.so_danh_gia ? Number(expert.diem_danh_gia).toFixed(1) : "Mới"} · {expert.so_ca_hoan_tat} ca
              </span>
            ) : null}
            <div className="flex items-center gap-2.5 rounded-full border border-[#E6E6E3] bg-white py-1.5 pl-1.5 pr-4">
              {avatar ? (
                <img src={avatar} alt={ten} className="h-9 w-9 rounded-full object-cover" />
              ) : (
                <span className="flex h-9 w-9 items-center justify-center rounded-full bg-[#AC0021] text-xs font-bold text-white">{initials(ten)}</span>
              )}
              <div className="hidden leading-tight sm:block">
                <p className="text-sm font-semibold">{ten}</p>
                <p className="text-[11px] text-[#63635E]">{expert?.chuc_danh || "Chuyên gia Tuner"}</p>
              </div>
            </div>
          </div>
        </header>

        <main className="px-4 pb-10 pt-8 sm:px-8">{children}</main>

        <footer className="flex flex-wrap items-center justify-between gap-3 border-t border-[#E6E6E3] px-4 py-5 text-xs text-[#63635E] sm:px-8">
          <span>© {new Date().getFullYear()} Porsche · Khu chuyên gia</span>
          <span>Dữ liệu hiển thị là dữ liệu thật từ hệ thống</span>
        </footer>
      </div>
    </div>
  );
}
