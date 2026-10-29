import { useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import { ArrowLeft, LogOut, Menu, X } from "lucide-react";
import Navbar from "../layouts/partials/Navbar";
import { useAuth } from "../../context/AuthContext";

export default function WorkspaceLayout({ title, navigation, activeKey, onNavigate, children }) {
  const [menuMo, setMenuMo] = useState(false);
  const { nguoiDung, dangXuat } = useAuth();
  const navigate = useNavigate();

  const xuLyDangXuat = async () => {
    await dangXuat();
    navigate("/");
  };

  return (
    <div className="min-h-screen bg-[#FAFAF9] text-[#17171A]">
      <Navbar luonSang />
      <div className="mx-auto max-w-[1440px] px-6 pb-14 pt-[108px] max-[860px]:px-4 max-[860px]:pt-[82px]">
        <div className="mb-6 flex items-center justify-between gap-4">
          <div>
            <Link to="/" className="mb-2 inline-flex items-center gap-1.5 text-xs text-[#63635E] hover:text-[#AC0021]">
              <ArrowLeft className="h-3.5 w-3.5" /> Về trang chủ
            </Link>
            <h1 className="text-2xl font-extrabold tracking-tight">{title}</h1>
            <p className="mt-1 text-sm text-[#63635E]">
              Xin chào, {nguoiDung?.ho_ten || nguoiDung?.email || "người dùng"}.
            </p>
          </div>
          <button
            type="button"
            onClick={xuLyDangXuat}
            className="hidden items-center gap-2 rounded-lg border border-[#ECECE9] bg-white px-4 py-2.5 text-sm font-semibold text-[#AC0021] sm:flex"
          >
            <LogOut className="h-4 w-4" /> Đăng xuất
          </button>
          <button
            type="button"
            onClick={() => setMenuMo((value) => !value)}
            aria-label={menuMo ? "Đóng menu" : "Mở menu"}
            className="flex h-10 w-10 items-center justify-center rounded-lg border border-[#ECECE9] bg-white sm:hidden"
          >
            {menuMo ? <X className="h-5 w-5" /> : <Menu className="h-5 w-5" />}
          </button>
        </div>

        <div className="grid grid-cols-1 gap-5 lg:grid-cols-[240px_minmax(0,1fr)]">
          <aside className={`${menuMo ? "block" : "hidden"} h-fit rounded-2xl border border-[#ECECE9] bg-white p-2 lg:block`}>
            <nav className="flex flex-col gap-1">
              {navigation.map(({ key, label, icon: Icon }) => (
                <button
                  key={key}
                  type="button"
                  onClick={() => {
                    onNavigate(key);
                    setMenuMo(false);
                  }}
                  aria-current={activeKey === key ? "page" : undefined}
                  className={`flex items-center gap-3 rounded-xl px-3.5 py-3 text-left text-sm font-semibold transition-colors ${
                    activeKey === key
                      ? "bg-[#17171A] text-white"
                      : "text-[#4A4A46] hover:bg-[#F1F1EF]"
                  }`}
                >
                  <Icon className="h-4 w-4 shrink-0" />
                  {label}
                </button>
              ))}
            </nav>
            <button
              type="button"
              onClick={xuLyDangXuat}
              className="mt-2 flex w-full items-center gap-3 rounded-xl px-3.5 py-3 text-left text-sm font-semibold text-[#AC0021] hover:bg-[#FBE9E9] sm:hidden"
            >
              <LogOut className="h-4 w-4" /> Đăng xuất
            </button>
          </aside>

          <main className="min-w-0 rounded-2xl border border-[#ECECE9] bg-white p-5 sm:p-7">
            {children}
          </main>
        </div>
      </div>
    </div>
  );
}
