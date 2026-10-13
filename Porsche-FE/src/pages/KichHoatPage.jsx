import { useRef, useState } from "react";
import { Link, useLocation, useNavigate, useSearchParams } from "react-router-dom";
import { Mail, ShieldCheck } from "lucide-react";
import {
  AuthError,
  AuthField,
  AuthLayout,
  AuthSubmit,
} from "../components/auth/AuthLayout";
import { getApiErrorMessage, kichHoatKhachHang } from "../api/Authapi";

const SO_KY_TU = 6;

export default function KichHoatPage() {
  const navigate = useNavigate();
  const location = useLocation();
  const [searchParams] = useSearchParams();
  const [email, setEmail] = useState(searchParams.get("email") || location.state?.email || "");
  const [maKichHoat, setMaKichHoat] = useState(Array(SO_KY_TU).fill(""));
  const [loi, setLoi] = useState("");
  const [thanhCong, setThanhCong] = useState(false);
  const [dangTai, setDangTai] = useState(false);
  const inputRefs = useRef([]);

  const capNhatMa = (index, value) => {
    const digits = value.replace(/\D/g, "");
    if (!digits) {
      setMaKichHoat((current) => current.map((digit, position) => position === index ? "" : digit));
      return;
    }

    const incoming = digits.slice(0, SO_KY_TU - index);
    setMaKichHoat((current) => {
      const chars = [...current];
      [...incoming].forEach((digit, offset) => {
        chars[index + offset] = digit;
      });
      return chars;
    });
    inputRefs.current[Math.min(index + incoming.length, SO_KY_TU - 1)]?.focus();
  };

  const xuLyDanMa = (event) => {
    const digits = event.clipboardData.getData("text").replace(/\D/g, "").slice(0, SO_KY_TU);
    if (!digits) return;
    event.preventDefault();
    setMaKichHoat([...digits].concat(Array(SO_KY_TU - digits.length).fill("")));
    inputRefs.current[Math.min(digits.length, SO_KY_TU - 1)]?.focus();
  };

  const xuLyPhimMa = (index, event) => {
    if (event.key === "Backspace" && !maKichHoat[index] && index > 0) {
      inputRefs.current[index - 1]?.focus();
    }
    if (event.key === "ArrowLeft" && index > 0) inputRefs.current[index - 1]?.focus();
    if (event.key === "ArrowRight" && index < SO_KY_TU - 1) inputRefs.current[index + 1]?.focus();
  };

  const xuLyKichHoat = async (event) => {
    event.preventDefault();
    setLoi("");
    const code = maKichHoat.join("");
    if (code.length !== SO_KY_TU) {
      setLoi("Vui lòng nhập đủ 6 chữ số xác thực.");
      return;
    }

    setDangTai(true);
    try {
      const ketQua = await kichHoatKhachHang(email, code);
      if (ketQua.status) {
        setThanhCong(true);
      } else {
        setLoi(ketQua.message || "Mã kích hoạt không đúng hoặc đã hết hạn.");
      }
    } catch (error) {
      setLoi(getApiErrorMessage(error, "Không thể kết nối máy chủ. Vui lòng thử lại."));
    } finally {
      setDangTai(false);
    }
  };

  if (thanhCong) {
    return (
      <AuthLayout
        eyebrow="Kích hoạt tài khoản"
        title="Email đã được xác thực"
        description={`Tài khoản khách hàng ${email} đã sẵn sàng. Bạn có thể đăng nhập để bắt đầu sử dụng Garage.`}
      >
        <AuthSubmit onClick={() => navigate("/dang-nhap", { replace: true })}>
          <ShieldCheck className="h-4 w-4" />
          Đi tới đăng nhập
        </AuthSubmit>
      </AuthLayout>
    );
  }

  return (
    <AuthLayout
      eyebrow="Kích hoạt tài khoản"
      title="Xác thực email của bạn"
      description="Nhập mã 6 chữ số đã được gửi tới email đăng ký để kích hoạt tài khoản khách hàng."
    >
      <AuthError>{loi}</AuthError>
      <form onSubmit={xuLyKichHoat} className="flex flex-col gap-5">
        <AuthField
          label="Email đăng ký"
          icon={Mail}
          type="email"
          value={email}
          onChange={(event) => setEmail(event.target.value)}
          placeholder="ban@example.com"
          autoComplete="email"
        />
        <fieldset>
          <legend className="mb-2 block text-[13px] font-medium text-[#202329]">Mã xác thực</legend>
          <div className="grid grid-cols-6 gap-2">
            {Array.from({ length: SO_KY_TU }, (_, index) => (
              <input
                key={index}
                ref={(element) => { inputRefs.current[index] = element; }}
                type="text"
                inputMode="numeric"
                autoComplete={index === 0 ? "one-time-code" : "off"}
                aria-label={`Chữ số xác thực ${index + 1}`}
                value={maKichHoat[index]}
                onChange={(event) => capNhatMa(index, event.target.value)}
                onPaste={xuLyDanMa}
                onKeyDown={(event) => xuLyPhimMa(index, event)}
                className="h-[50px] min-w-0 rounded-[9px] border border-[#DCDDDF] bg-white text-center text-lg font-semibold text-[#17191E] outline-none transition-colors focus:border-[#C8102E] focus:ring-2 focus:ring-[#C8102E]/10"
              />
            ))}
          </div>
        </fieldset>
        <div className="-mt-2 flex items-center justify-between text-xs text-[#858991]">
          <span>Không nhận được mã?</span>
          <Link to="/dang-ky" className="font-semibold text-[#C8102E] hover:underline">
            Đăng ký lại
          </Link>
        </div>
        <AuthSubmit disabled={dangTai}>
          {dangTai ? "Đang xác nhận..." : "Kích hoạt tài khoản"}
        </AuthSubmit>
      </form>
      <p className="mt-6 text-sm text-[#666970]">
        Đã kích hoạt rồi?{" "}
        <Link to="/dang-nhap" className="font-semibold text-[#C8102E] hover:underline">
          Đăng nhập
        </Link>
      </p>
    </AuthLayout>
  );
}
