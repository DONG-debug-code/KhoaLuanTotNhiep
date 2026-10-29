import { useState, useEffect, useRef } from "react";
import { Link, useNavigate, useLocation } from "react-router-dom";
import { User, LogOut, ChevronDown, ShoppingBag } from "lucide-react";
import { useAuth } from "../../../context/AuthContext";
import { useCart } from "../../../context/CartContext";

const NAV_LINKS = [
  { key: "home", ten: "Trang chủ", loai: "home" },
  { key: "xe-dang-ban", ten: "Xe đang bán", loai: "scroll", targetId: "xe-dang-ban" },
  { key: "hang-phu-tung", ten: "Gói độ & phụ kiện", loai: "route", duongDan: "/hang-phu-tung", targetId: "hang-phu-tung" },
  { key: "chuyen-gia", ten: "Chuyên gia", loai: "route", duongDan: "/chuyen-gia", targetId: "chuyen-gia" },
];

export default function Navbar({ luonSang = false }) {
  const [daCuon, setDaCuon] = useState(false);
  const [muctieuDangHien, setMucTieuDangHien] = useState("home");
  const [menuMo, setMenuMo] = useState(false);
  const navigate = useNavigate();
  const location = useLocation();
  const quanSatVien = useRef(null);
  const menuRef = useRef(null);

  const { nguoiDung, daDangNhap, dangXuat } = useAuth();
  const { itemCount } = useCart();

  useEffect(() => {
    if (luonSang) return;
    const xuLyScroll = () => setDaCuon(window.scrollY > 20);
    xuLyScroll();
    window.addEventListener("scroll", xuLyScroll);
    return () => window.removeEventListener("scroll", xuLyScroll);
  }, [luonSang]);

  useEffect(() => {
    if (location.pathname !== "/") return;

    const cacMucTieu = NAV_LINKS.filter((l) => l.targetId).map((l) => l.targetId);
    const phanTu = cacMucTieu.map((id) => document.getElementById(id)).filter(Boolean);
    if (phanTu.length === 0) return;

    quanSatVien.current = new IntersectionObserver(
      (entries) => {
        entries.forEach((entry) => {
          if (entry.isIntersecting) setMucTieuDangHien(entry.target.id);
        });
      },
      { rootMargin: "-45% 0px -50% 0px" }
    );
    phanTu.forEach((el) => quanSatVien.current.observe(el));

    const xuLyDauTrang = () => {
      if (window.scrollY < 100) setMucTieuDangHien("home");
    };
    window.addEventListener("scroll", xuLyDauTrang);

    return () => {
      quanSatVien.current?.disconnect();
      window.removeEventListener("scroll", xuLyDauTrang);
    };
  }, [location.pathname]);

  // Đóng dropdown khi click ra ngoài
  useEffect(() => {
    const xuLyClickNgoai = (e) => {
      if (menuRef.current && !menuRef.current.contains(e.target)) setMenuMo(false);
    };
    document.addEventListener("mousedown", xuLyClickNgoai);
    return () => document.removeEventListener("mousedown", xuLyClickNgoai);
  }, []);

  const dangSang = luonSang || daCuon;

  const veTrangChu = () => {
    if (location.pathname === "/") {
      window.scrollTo({ top: 0, behavior: "smooth" });
    } else {
      navigate("/");
    }
  };

  const cuonToiSection = (targetId) => {
    if (location.pathname === "/") {
      document.getElementById(targetId)?.scrollIntoView({ behavior: "smooth" });
    } else {
      navigate("/", { state: { cuonToi: targetId } });
    }
  };

  const kiemTraActive = (link) => {
    if (link.loai === "route" && location.pathname.startsWith(link.duongDan)) return true;
    if (location.pathname !== "/") return false;
    if (link.loai === "home") return muctieuDangHien === "home";
    return muctieuDangHien === link.targetId;
  };

  const xuLyDangXuat = async () => {
    setMenuMo(false);
    await dangXuat();
    navigate("/");
  };

  const tenHienThi = nguoiDung?.ho_ten || nguoiDung?.email || "Tài khoản";
  const chuCaiDau = tenHienThi.charAt(0).toUpperCase();
  const duongDanTaiKhoan = nguoiDung?.vaiTro === "admin"
    ? "/admin"
    : nguoiDung?.vaiTro === "chuyen-gia"
      ? "/chuyen-gia-workspace"
      : "/garage";
  const tenMenuTaiKhoan = nguoiDung?.vaiTro === "admin"
    ? "Quản trị hệ thống"
    : nguoiDung?.vaiTro === "chuyen-gia"
      ? "Workshop của tôi"
      : "Garage của tôi";

  return (
    <nav
      className={`fixed top-0 left-0 right-0 z-50 flex h-[80px] items-center justify-between
        px-12 max-[860px]:px-5
        transition-colors duration-300 ease-in-out border-b
        ${dangSang
          ? "bg-white/95 backdrop-blur border-[#ECECE9] shadow-sm"
          : "bg-transparent border-transparent"
        }`}
    >
      <button onClick={veTrangChu} className="flex items-center gap-2.5">
        <div className="flex h-12 w-[90px] items-center justify-center rounded-[9px] max-[860px]:h-9 max-[860px]:w-[70px]">
          <img src="/src/assets/img/logo.png" className="h-full w-full object-contain" />
        </div>
      </button>

      <div
        className={`flex gap-9 text-sm max-[860px]:hidden transition-colors duration-300
          ${dangSang ? "text-[#17171A]" : "text-[#d8d4d4]"}`}
      >
        {NAV_LINKS.map((link) => {
          const active = kiemTraActive(link);
          const lopChung = `cursor-pointer transition-colors duration-200 ${
            active ? "text-[#AC0021] font-semibold" : "hover:opacity-70"
          }`;

          if (link.loai === "home") {
            return (
              <button key={link.key} onClick={veTrangChu} className={lopChung}>
                {link.ten}
              </button>
            );
          }
          if (link.loai === "route") {
            return (
              <Link key={link.key} to={link.duongDan} className={lopChung}>
                {link.ten}
              </Link>
            );
          }
          return (
            <button key={link.key} onClick={() => cuonToiSection(link.targetId)} className={lopChung}>
              {link.ten}
            </button>
          );
        })}
      </div>

      <div className="flex items-center gap-3.5">
        <Link
          to="/gio-hang"
          aria-label={`Giỏ hàng, ${itemCount} sản phẩm`}
          className={`relative flex h-10 w-10 items-center justify-center rounded-lg transition-colors ${
            dangSang ? "text-[#17171A] hover:bg-[#F1F1EF]" : "text-white hover:bg-white/10"
          }`}
        >
          <ShoppingBag className="h-5 w-5" />
          {itemCount > 0 ? (
            <span className="absolute -right-1 -top-1 flex h-5 min-w-5 items-center justify-center rounded-full bg-[#AC0021] px-1 text-[10px] font-bold text-white">
              {itemCount > 99 ? "99+" : itemCount}
            </span>
          ) : null}
        </Link>
        {daDangNhap ? (
          <div ref={menuRef} className="relative">
            <button
              onClick={() => setMenuMo((truoc) => !truoc)}
              className={`flex items-center gap-2 rounded-lg border-none px-3.5 py-2.5 text-sm font-semibold transition-colors duration-300
                bg-[#17171A] text-white`}
            >
              <span className="flex h-6 w-6 items-center justify-center rounded-full bg-[#AC0021] text-xs font-bold text-white">
                {chuCaiDau}
              </span>
              <span className="max-w-[120px] truncate">{tenHienThi}</span>
              <ChevronDown className={`h-3.5 w-3.5 transition-transform ${menuMo ? "rotate-180" : ""}`} />
            </button>

            {menuMo && (
              <div className="absolute right-0 top-[calc(100%+8px)] w-56 overflow-hidden rounded-xl border border-[#ECECE9] bg-white py-1.5 shadow-lg">
                <Link
                  to={duongDanTaiKhoan}
                  onClick={() => setMenuMo(false)}
                  className="flex items-center gap-2.5 px-4 py-2.5 text-sm text-[#17171A] hover:bg-[#F1F1EF]"
                >
                  <User className="h-4 w-4" />
                  {tenMenuTaiKhoan}
                </Link>
                <button
                  onClick={xuLyDangXuat}
                  className="flex w-full items-center gap-2.5 px-4 py-2.5 text-left text-sm text-[#AC0021] hover:bg-[#FBE9E9]"
                >
                  <LogOut className="h-4 w-4" />
                  Đăng xuất
                </button>
              </div>
            )}
          </div>
        ) : (
          <Link
            to="/dang-nhap"
            className={`cursor-pointer rounded-lg border-none px-5 py-2.5 text-sm font-semibold transition-colors duration-300
              ${dangSang ? "bg-[#17171A] text-white" : "bg-[#F5F4F1] text-[#0A0A0B]"}`}
          >
            Đăng nhập ngay
          </Link>
        )}
      </div>
    </nav>
  );
}