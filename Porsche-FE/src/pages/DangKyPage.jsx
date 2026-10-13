import { useState } from "react";
import { Link, useNavigate } from "react-router-dom";
import { LockKeyhole, Mail, Phone, UserRound } from "lucide-react";
import {
  AuthDivider,
  AuthError,
  AuthField,
  AuthLayout,
  AuthSubmit,
} from "../components/auth/AuthLayout";
import { dangKyKhachHang, getApiErrorMessage } from "../api/Authapi";

export default function DangKyPage() {
  const navigate = useNavigate();
  const [form, setForm] = useState({
    ho_ten: "",
    email: "",
    so_dien_thoai: "",
    password: "",
    password_confirmation: "",
  });
  const [loi, setLoi] = useState("");
  const [dangTai, setDangTai] = useState(false);
  const emailDaTonTai = /email.*(đã được sử dụng|đã tồn tại|already|taken)/i.test(loi);

  const capNhat = (truong) => (event) =>
    setForm((truoc) => ({ ...truoc, [truong]: event.target.value }));

  const xuLyDangKy = async (event) => {
    event.preventDefault();
    setLoi("");

    if (form.password !== form.password_confirmation) {
      setLoi("Mật khẩu xác nhận không khớp.");
      return;
    }

    setDangTai(true);
    try {
      const ketQua = await dangKyKhachHang(form);
      if (ketQua.status) {
        navigate(`/kich-hoat?email=${encodeURIComponent(form.email)}`);
      } else {
        setLoi(ketQua.message || "Đăng ký thất bại. Vui lòng kiểm tra lại thông tin.");
      }
    } catch (error) {
      setLoi(getApiErrorMessage(error, "Không thể kết nối máy chủ. Vui lòng thử lại."));
    } finally {
      setDangTai(false);
    }
  };

  return (
    <AuthLayout
      eyebrow="Tạo tài khoản"
      title="Tham gia Garage"
      description="Tạo tài khoản khách hàng để đặt lịch lái thử, theo dõi xe và sử dụng các dịch vụ của chúng tôi."
      contentClassName="max-w-[500px]"
    >
      <AuthError>{loi}</AuthError>
      {emailDaTonTai ? (
        <Link
          to="/kich-hoat"
          state={{ email: form.email }}
          className="-mt-2 mb-5 block text-sm font-semibold text-[#C8102E] hover:underline"
        >
          Nhập mã kích hoạt cho email này
        </Link>
      ) : null}
      <form onSubmit={xuLyDangKy} className="flex flex-col gap-3.5">
        <AuthField
          label="Họ và tên"
          icon={UserRound}
          value={form.ho_ten}
          onChange={capNhat("ho_ten")}
          placeholder="Nguyễn Văn An"
          autoComplete="name"
        />
        <AuthField
          label="Email"
          icon={Mail}
          type="email"
          value={form.email}
          onChange={capNhat("email")}
          placeholder="ban@example.com"
          autoComplete="email"
        />
        <AuthField
          label="Số điện thoại"
          icon={Phone}
          type="tel"
          value={form.so_dien_thoai}
          onChange={capNhat("so_dien_thoai")}
          placeholder="0900 123 456"
          autoComplete="tel"
        />
        <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
          <AuthField
            label="Mật khẩu"
            icon={LockKeyhole}
            type="password"
            value={form.password}
            onChange={capNhat("password")}
            placeholder="Tạo mật khẩu"
            autoComplete="new-password"
          />
          <AuthField
            label="Xác nhận mật khẩu"
            icon={LockKeyhole}
            type="password"
            value={form.password_confirmation}
            onChange={capNhat("password_confirmation")}
            placeholder="Nhập lại mật khẩu"
            autoComplete="new-password"
          />
        </div>
        <p className="text-xs leading-relaxed text-[#777A80]">
          Bằng việc đăng ký, bạn đồng ý với các điều khoản sử dụng và chính sách bảo mật của Garage.
        </p>
        <AuthSubmit disabled={dangTai}>
          {dangTai ? "Đang tạo tài khoản..." : "Tạo tài khoản"}
        </AuthSubmit>
      </form>
      <AuthDivider />
      <p className="text-sm text-[#666970]">
        Đã có tài khoản?{" "}
        <Link to="/dang-nhap" className="font-semibold text-[#C8102E] hover:underline">
          Đăng nhập
        </Link>
      </p>
    </AuthLayout>
  );
}
