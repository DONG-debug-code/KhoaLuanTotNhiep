import { useEffect, useRef, useState } from "react";
import { useNavigate } from "react-router-dom";
import { Bell, ChevronDown, Gauge, LogOut, Menu, Search, X } from "lucide-react";
import { useAuth } from "../../context/AuthContext";
import { getWorkspaceAssetUrl } from "../../api/WorkspaceApi";
import { initials } from "../../pages/chuyengia/workspace/expertUi";

export default function AdminWorkspaceLayout({
  groups, activeKey, onNavigate, title, search, onSearch,   alertCount = 0, onAlertClick, roleLabel = "Quản trị viên", children,
}) {
  const [menuMo, setMenuMo] = useState(false);
  const [profileMo, setProfileMo] = useState(false);
  const profileRef = useRef(null);
  const { nguoiDung, dangXuat } = useAuth();
  const navigate = useNavigate();
  const ten = nguoiDung?.ho_ten || "Quản trị viên";
  const avatar = getWorkspaceAssetUrl(nguoiDung?.avatar);

  useEffect(() => {
    if (!profileMo) return undefined;
    const close = (event) => {
      if (!profileRef.current?.contains(event.target)) setProfileMo(false);
    };
    document.addEventListener("mousedown", close);
    return () => document.removeEventListener("mousedown", close);
  }, [profileMo]);

  const xuLyDangXuat = async () => {
    await dangXuat();
    navigate("/");
  };

  const chonMuc = (key) => {
    onNavigate(key);
    setMenuMo(false);
  };

  return (
    <div className="min-h-screen bg-[#F6F5F2] text-[#17171A]">
      {menuMo ? <button type="button" aria-label="Đóng menu" onClick={() => setMenuMo(false)} className="fixed inset-0 z-[59] bg-black/50 lg:hidden" /> : null}

      <aside className={`fixed inset-y-0 left-0 z-[60] flex w-[270px] flex-col bg-[#0B0B0D] text-white transition-transform duration-200 lg:translate-x-0 ${menuMo ? "translate-x-0" : "-translate-x-full"}`}>
        <div className="flex h-[72px] shrink-0 items-center gap-3 border-b border-white/10 px-5">
          <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-[#D4202B]"><Gauge className="h-5 w-5" /></div>
          <div className="leading-tight">
            <p className="text-sm font-extrabold tracking-tight">DU_AN_XE</p>
            <p className="text-[11px] text-white/60">Bảng quản trị</p>
          </div>
          <button type="button" onClick={() => setMenuMo(false)} aria-label="Đóng menu" className="ml-auto rounded-lg p-2 lg:hidden"><X className="h-5 w-5" /></button>
        </div>

        <nav className="flex-1 space-y-5 overflow-y-auto px-3 py-4">
          {groups.map((group) => (
            <div key={group.label}>
              <p className="px-3 pb-2 text-[11px] font-semibold uppercase tracking-wider text-white/45">{group.label}</p>
              <div className="space-y-1">
                {group.items.map(({ key, label, icon: Icon, badge }) => (
                  <button
                    key={key}
                    type="button"
                    onClick={() => chonMuc(key)}
                    aria-current={activeKey === key ? "page" : undefined}
                    className={`flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-medium transition-colors ${activeKey === key ? "bg-white/12 text-white" : "text-white/75 hover:bg-white/8"}`}
                  >
                    <Icon className="h-[18px] w-[18px] shrink-0" />
                    <span className="min-w-0 flex-1 truncate">{label}</span>
                    {badge ? <span className="rounded-full bg-[#D4202B] px-2 py-0.5 text-[10px] font-bold">{badge}</span> : null}
                  </button>
                ))}
              </div>
            </div>
          ))}
        </nav>

        <div className="border-t border-white/10 p-3">
          <button type="button" onClick={xuLyDangXuat} className="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-medium text-white/75 hover:bg-white/8">
            <LogOut className="h-[18px] w-[18px]" /> Đăng xuất
          </button>
        </div>
      </aside>

      <div className="lg:pl-[270px]">
        <header className="sticky top-0 z-40 flex h-[72px] items-center gap-3 border-b border-[#E6E6E3] bg-[#FAFAF9]/95 px-4 backdrop-blur sm:px-8">
          <button type="button" onClick={() => setMenuMo(true)} aria-label="Mở menu" className="rounded-lg border border-[#E6E6E3] bg-white p-2 lg:hidden"><Menu className="h-5 w-5" /></button>
          <div className="min-w-0">
            <p className="truncate text-xs text-[#63635E]">Khu quản trị · {roleLabel}</p>
            <p className="truncate text-base font-extrabold tracking-tight">Trung tâm điều hành</p>
          </div>

          <div className="ml-auto flex items-center gap-3">
            <label className="hidden h-11 w-[280px] items-center gap-2 rounded-xl border border-[#E6E6E3] bg-white px-3 md:flex">
              <Search className="h-4 w-4 text-[#8A8A85]" />
              <input value={search} onChange={(event) => onSearch(event.target.value)} placeholder={`Tìm nhanh trong ${title.toLowerCase()}...`} className="w-full bg-transparent text-sm outline-none" />
            </label>
            <button type="button" onClick={onAlertClick} aria-label="Việc cần xử lý" className="relative rounded-xl border border-[#E6E6E3] bg-white p-3">
              <Bell className="h-4 w-4" />
              {alertCount > 0 ? <span className="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-[#D4202B] px-1 text-[10px] font-bold text-white">{alertCount}</span> : null}
            </button>
            <div ref={profileRef} className="relative">
              <button type="button" onClick={() => setProfileMo((value) => !value)} className="flex items-center gap-2.5 rounded-full border border-[#E6E6E3] bg-white py-1.5 pl-1.5 pr-3">
                {avatar ? <img src={avatar} alt={ten} className="h-9 w-9 rounded-full object-cover" /> : <span className="flex h-9 w-9 items-center justify-center rounded-full bg-[#B8862B] text-xs font-bold text-white">{initials(ten)}</span>}
                <span className="hidden text-left leading-tight sm:block">
                  <span className="block text-sm font-semibold">{ten}</span>
                  <span className="block text-[11px] text-[#63635E]">{nguoiDung?.email}</span>
                </span>
                <ChevronDown className="h-4 w-4 text-[#63635E]" />
              </button>
              {profileMo ? (
                <div className="absolute right-0 mt-2 w-48 rounded-xl border border-[#E6E6E3] bg-white p-1 shadow-lg">
                  <button type="button" onClick={xuLyDangXuat} className="flex w-full items-center gap-2 rounded-lg px-3 py-2.5 text-sm font-medium text-[#B60F0F] hover:bg-[#FBE9E9]"><LogOut className="h-4 w-4" /> Đăng xuất</button>
                </div>
              ) : null}
            </div>
          </div>
        </header>

        <main className="px-4 pb-10 pt-8 sm:px-8">{children}</main>
        <footer className="flex flex-wrap items-center justify-between gap-3 border-t border-[#E6E6E3] px-4 py-5 text-xs text-[#63635E] sm:px-8">
          <span>© {new Date().getFullYear()} DU_AN_XE · Bảng quản trị</span>
          <span>Dữ liệu hiển thị là dữ liệu thật từ hệ thống</span>
        </footer>
      </div>
    </div>
  );
}
