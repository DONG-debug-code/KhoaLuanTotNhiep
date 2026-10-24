import { useState } from "react";
import { Link, useLocation, useNavigate } from "react-router-dom";
import { LockKeyhole, Mail, ShieldCheck } from "lucide-react";
import {
  AuthDivider,
  AuthError,
  AuthField,
  AuthLayout,
  AuthSubmit,
} from "../components/auth/AuthLayout";
import { useAuth } from "../context/AuthContext";

const DUONG_DAN_VAI_TRO = {
  "khach-hang": "/garage",
  admin: "/admin",
  "chuyen-gia": "/chuyen-gia-workspace",
};

export default function DangNhapPage() {
  const { dangNhap, dangTai } = useAuth();
  const navigate = useNavigate();
  const location = useLocation();
  const [email, setEmail] = useState("");
  const [matKhau, setMatKhau] = useState("");
  const [loi, setLoi] = useState("");
  const [ghiNho, setGhiNho] = useState(false);
  const pendingCartItem = location.state?.pendingCartItem;

  const xuLyDangNhap = async (event) => {
    event.preventDefault();
    setLoi("");
    const ketQua = await dangNhap(email, matKhau);

    if (ketQua.status) {
      const laKhach = ketQua.vai_tro === "khach-hang";
      const returnPath = laKhach
        ? location.state?.from?.pathname || DUONG_DAN_VAI_TRO["khach-hang"]
        : DUONG_DAN_VAI_TRO[ketQua.vai_tro] || "/";
      navigate(returnPath, {
        replace: true,
        state: laKhach ? (pendingCartItem ? { pendingCartItem } : location.state?.from?.state) : undefined,
      });
    } else {
      setLoi(ketQua.message || "Đăng nhập thất bại. Vui lòng kiểm tra lại email/mật khẩu.");
    }
  };

  return (
    <AuthLayout
      eyebrow="Đăng nhập"
      title="Chào mừng trở lại"
      description="Đăng nhập để quản lý Garage, theo dõi lịch độ xe và trò chuyện với trợ lý AI."
    >
      <AuthError>{loi}</AuthError>
      <form onSubmit={xuLyDangNhap} className="flex flex-col gap-[15px]">
        <AuthField
          label="Email"
          icon={Mail}
          type="email"
          value={email}
          onChange={(event) => setEmail(event.target.value)}
          placeholder="ban@example.com"
          autoComplete="email"
        />
        <AuthField
          label="Mật khẩu"
          icon={LockKeyhole}
          type="password"
          value={matKhau}
          onChange={(event) => setMatKhau(event.target.value)}
          placeholder="Nhập mật khẩu"
          autoComplete="current-password"
        />
        <div className="-mt-1 flex items-center justify-between text-[13px]">
          <label className="flex cursor-pointer items-center gap-2 text-[#666970]">
            <input
              type="checkbox"
              checked={ghiNho}
              onChange={(event) => setGhiNho(event.target.checked)}
              className="h-4 w-4 rounded border-[#C9CACE] accent-[#C8102E]"
            />
            Ghi nhớ đăng nhập
          </label>
          <Link to="/quen-mat-khau" className="font-semibold text-[#C8102E] hover:underline">
            Quên mật khẩu?
          </Link>
        </div>
        <AuthSubmit disabled={dangTai}>
          {dangTai ? "Đang đăng nhập..." : "Đăng nhập"}
        </AuthSubmit>
      </form>

      <AuthDivider />

      <Link
        to="/kich-hoat"
        state={{
          from: location.state?.from,
          pendingCartItem,
        }}
        className="flex min-h-[36px] w-full items-center justify-center gap-2 rounded-[9px] border border-[#C9CACE] bg-white px-4 py-2.5 text-sm font-semibold text-[#272A30] transition-colors hover:bg-[#F7F7F6]"
      >
        <ShieldCheck className="h-4 w-4" />
        Kích hoạt tài khoản
      </Link>

      <p className="mt-4 text-sm text-[#666970]">
        Chưa có tài khoản?{" "}
        <Link
          to="/dang-ky"
          state={{ from: location.state?.from, pendingCartItem }}
          className="font-semibold text-[#C8102E] hover:underline"
        >
          Đăng ký ngay
        </Link>
      </p>
    </AuthLayout>
  );
}
