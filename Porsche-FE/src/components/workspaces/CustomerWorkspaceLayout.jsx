import { useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import {
  CalendarDays, CarFront, ChevronDown, ClipboardList, LogOut,
  Menu, MessageCircle, Settings, ShoppingBag, Sparkles, Store, UserRound, X,
} from "lucide-react";
import { useAuth } from "../../context/AuthContext";
import { getWorkspaceAssetUrl } from "../../api/WorkspaceApi";

const NAV_ITEMS = [
  { key: "profile", label: "Hồ sơ cá nhân", icon: UserRound },
  { key: "garage", label: "Garage của tôi", icon: CarFront },
  { key: "appointments", label: "Lịch hẹn của tôi", icon: CalendarDays },
  { key: "orders", label: "Lịch sử mua hàng", icon: ClipboardList },
  { key: "booking", label: "Đặt lịch workshop", icon: ShoppingBag },
  { key: "ai-history", label: "Lịch sử chat AI", icon: MessageCircle },
];

export default function CustomerWorkspaceLayout({ activeKey, onNavigate, onBook, children }) {
  const [menuMo, setMenuMo] = useState(false);
  const [menuNguoiDungMo, setMenuNguoiDungMo] = useState(false);
  const { nguoiDung, dangXuat } = useAuth();
  const navigate = useNavigate();
  const ten = nguoiDung?.ho_ten || nguoiDung?.email || "Khách hàng";
  const chuCaiDau = ten.trim().charAt(0).toUpperCase();
  const avatarUrl = getWorkspaceAssetUrl(nguoiDung?.avatar);

  const xuLyDangXuat = async () => {
    await dangXuat();
    navigate("/");
  };

  const chonMuc = (key) => {
    onNavigate(key);
    setMenuMo(false);
  };

  return (
    <div className="min-h-screen bg-[#f3f3f1] text-[#17191c]">
      {menuMo ? (
        <button
          type="button"
          aria-label="Đóng menu"
          onClick={() => setMenuMo(false)}
          className="fixed inset-0 z-[59] bg-black/40 lg:hidden"
        />
      ) : null}

      <aside className={`fixed inset-y-0 left-0 z-[60] flex w-[264px] flex-col border-r border-[#e6e6e3] bg-[#fafaf9] transition-transform duration-200 lg:translate-x-0 ${menuMo ? "translate-x-0" : "-translate-x-full"}`}>
        <div className="flex h-[72px] shrink-0 items-center gap-3 border-b border-[#e6e6e3] px-5">
          <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-[#080a0d] text-white">
            <span className="text-sm font-bold">P</span>
          </div>
          <span className="text-base font-extrabold tracking-tight">DU_AN_XE</span>
          <button type="button" onClick={() => setMenuMo(false)} aria-label="Đóng menu" className="ml-auto rounded-lg p-2 lg:hidden">
            <X className="h-5 w-5" />
          </button>
        </div>

        <nav className="flex-1 space-y-1 overflow-y-auto px-3 py-3">
          {NAV_ITEMS.map(({ key, label, icon: Icon }) => (
            <button
              key={key}
              type="button"
              onClick={() => chonMuc(key)}
              aria-current={activeKey === key ? "page" : undefined}
              className={`flex w-full items-center gap-3 rounded-xl px-3.5 py-3 text-left text-sm font-medium transition-colors ${
                activeKey === key ? "bg-[#080a0d] text-white" : "text-[#50545a] hover:bg-[#f0f0ed]"
              }`}
            >
              <Icon className="h-[18px] w-[18px] shrink-0" />
              {label}
            </button>
          ))}

          <section className="mt-5 rounded-2xl border border-[#ecebe7] bg-[#f5f4f0] p-4">
            <div className="flex items-center gap-2.5">
              <span className="flex h-9 w-9 items-center justify-center rounded-xl bg-[#faedce] text-[#9b7437]">
                <Sparkles className="h-4 w-4" />
              </span>
              <span className="text-sm font-bold">Trợ lý AI 24/7</span>
            </div>
            <p className="mt-2 text-xs leading-5 text-[#73767a]">Tư vấn gói độ và bảo dưỡng theo xe của bạn.</p>
            <button type="button" onClick={() => chonMuc("ai-history")} className="mt-3 w-full rounded-lg bg-[#080a0d] px-3 py-2 text-xs font-semibold text-white">
              Tư vấn gói độ
            </button>
            <button type="button" onClick={() => chonMuc("booking")} className="mt-2 w-full rounded-lg border border-[#d7d7d3] bg-transparent px-3 py-2 text-xs font-semibold">
              Tư vấn bảo dưỡng
            </button>
          </section>
        </nav>

        <div className="space-y-1 border-t border-[#e6e6e3] p-3">
          <Link to="/" className="flex items-center gap-3 rounded-xl px-3 py-3 text-sm text-[#50545a] hover:bg-[#f0f0ed]">
            <Store className="h-[18px] w-[18px]" /> Về showroom
          </Link>
          <button type="button" onClick={() => void xuLyDangXuat()} className="flex w-full items-center gap-3 rounded-xl px-3 py-3 text-left text-sm text-[#c8102e] hover:bg-[#fae9eb]">
            <LogOut className="h-[18px] w-[18px]" /> Đăng xuất
          </button>
        </div>
      </aside>

      <div className="min-h-screen lg:pl-[264px]">
        <header className="fixed left-0 right-0 top-0 z-40 flex h-[72px] items-center justify-between border-b border-[#e6e6e3] bg-[#fafaf9]/95 px-4 backdrop-blur sm:px-6 lg:left-[264px] lg:px-7">
          <div className="flex items-center gap-3">
            <button type="button" onClick={() => setMenuMo(true)} aria-label="Mở menu" className="rounded-lg p-2 hover:bg-[#f0f0ed] lg:hidden">
              <Menu className="h-5 w-5" />
            </button>
            <div>
              <p className="text-xs text-[#82858a]">Khu vực khách hàng</p>
              <p className="text-sm font-semibold">Xin chào, {ten}</p>
            </div>
          </div>
          <div className="flex items-center gap-3">
            <button type="button" onClick={onBook} className="inline-flex items-center gap-2 rounded-lg bg-[#cf102d] px-3.5 py-2.5 text-xs font-bold text-white hover:bg-[#b80e28] sm:px-4 sm:text-sm">
              <span className="text-base leading-none">+</span> <span className="hidden sm:inline">Đặt lịch mới</span><span className="sm:hidden">Đặt lịch</span>
            </button>
            <div className="relative">
              <button type="button" onClick={() => setMenuNguoiDungMo((open) => !open)} className="flex items-center gap-2 rounded-full border border-[#ecebe7] bg-white py-1 pl-1 pr-2.5 text-left">
                {avatarUrl ? (
                  <img src={avatarUrl} alt="" className="h-8 w-8 rounded-full object-cover" />
                ) : (
                  <span className="flex h-8 w-8 items-center justify-center rounded-full bg-[#deded9] text-sm font-bold text-[#50545a]">{chuCaiDau}</span>
                )}
                <span className="hidden min-w-0 sm:block">
                  <span className="block max-w-[120px] truncate text-xs font-semibold">{ten}</span>
                  <span className="block text-[11px] text-[#937541]">Khách hàng</span>
                </span>
                <ChevronDown className="h-3.5 w-3.5 text-[#70737a]" />
              </button>
              {menuNguoiDungMo ? (
                <div className="absolute right-0 top-[calc(100%+8px)] w-48 rounded-xl border border-[#e6e6e3] bg-white p-1.5 shadow-lg">
                  <button type="button" onClick={() => { chonMuc("profile"); setMenuNguoiDungMo(false); }} className="flex w-full items-center gap-2 rounded-lg px-3 py-2.5 text-left text-sm hover:bg-[#f3f3f1]">
                    <Settings className="h-4 w-4" /> Hồ sơ cá nhân
                  </button>
                  <button type="button" onClick={() => void xuLyDangXuat()} className="flex w-full items-center gap-2 rounded-lg px-3 py-2.5 text-left text-sm text-[#c8102e] hover:bg-[#fae9eb]">
                    <LogOut className="h-4 w-4" /> Đăng xuất
                  </button>
                </div>
              ) : null}
            </div>
          </div>
        </header>

        <main className="min-h-screen px-4 pb-8 pt-[96px] sm:px-6 lg:px-7">
          {children}
        </main>
        <footer className="flex flex-wrap justify-between gap-3 border-t border-[#e6e6e3] bg-[#fafaf9] px-6 py-4 text-xs text-[#777a80] lg:px-7">
          <span>© {new Date().getFullYear()} DU_AN_XE · Khu vực khách hàng</span>
          <span className="flex gap-5"><a href="#terms" className="hover:text-[#17191c]">Điều khoản</a><a href="#privacy" className="hover:text-[#17191c]">Bảo mật</a></span>
        </footer>
      </div>
    </div>
  );
}
