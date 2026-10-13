import { useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import { Mail } from "lucide-react";
import {
  AuthError,
  AuthField,
  AuthLayout,
  AuthSubmit,
} from "../components/auth/AuthLayout";
import { getApiErrorMessage, quenMatKhauKhachHang } from "../api/Authapi";

export default function QuenMatKhauPage() {
  const navigate = useNavigate();
  const [email, setEmail] = useState("");
  const [loi, setLoi] = useState("");
  const [daGui, setDaGui] = useState(false);
  const [dangTai, setDangTai] = useState(false);

  const xuLyGuiYeuCau = async (event) => {
    event.preventDefault();
    setLoi("");
    setDangTai(true);

    try {
      const ketQua = await quenMatKhauKhachHang(email);
      if (ketQua.status) {
        setDaGui(true);
      } else {
        setLoi(ketQua.message || "Không tìm thấy tài khoản với email này.");
      }
    } catch (error) {
      setLoi(getApiErrorMessage(error, "Không thể kết nối máy chủ. Vui lòng thử lại."));
    } finally {
      setDangTai(false);
    }
  };

  if (daGui) {
    return (
      <AuthLayout
        eyebrow="Quên mật khẩu"
        title="Kiểm tra email của bạn"
        description={`Nếu email ${email} tồn tại trong hệ thống, mã hoặc liên kết đặt lại mật khẩu đã được gửi tới đó.`}
      >
        <AuthSubmit
          onClick={() => navigate(`/doi-mat-khau?email=${encodeURIComponent(email)}`)}
        >
          Nhập mã và tạo mật khẩu mới
        </AuthSubmit>
        <p className="mt-6 text-sm text-[#666970]">
          Nhớ ra mật khẩu rồi?{" "}
          <Link to="/dang-nhap" className="font-semibold text-[#C8102E] hover:underline">
            Quay lại đăng nhập
          </Link>
        </p>
      </AuthLayout>
    );
  }

  return (
    <AuthLayout
      eyebrow="Quên mật khẩu"
      title="Đặt lại mật khẩu"
      description="Nhập email đã đăng ký, chúng tôi sẽ gửi cho bạn liên kết đặt lại mật khẩu."
    >
      <AuthError>{loi}</AuthError>
      <form onSubmit={xuLyGuiYeuCau} className="flex flex-col gap-4">
        <AuthField
          label="Email"
          icon={Mail}
          type="email"
          value={email}
          onChange={(event) => setEmail(event.target.value)}
          placeholder="ban@example.com"
          autoComplete="email"
        />
        <AuthSubmit disabled={dangTai}>
          {dangTai ? "Đang gửi..." : "Gửi liên kết đặt lại"}
        </AuthSubmit>
      </form>
      <p className="mt-6 text-sm text-[#666970]">
        Nhớ ra mật khẩu rồi?{" "}
        <Link to="/dang-nhap" className="font-semibold text-[#C8102E] hover:underline">
          Quay lại đăng nhập
        </Link>
      </p>
    </AuthLayout>
  );
}
