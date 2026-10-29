import { useState } from "react";
import { ArrowLeft, CarFront, Check, Eye, EyeOff, ShieldCheck } from "lucide-react";
import { Link } from "react-router-dom";
import showroomCar from "../../assets/img/06.jpg";

const HIGHLIGHTS = [
  "Theo dõi tiến độ xe theo thời gian thực",
  "Quản lý toàn bộ xe trong Garage cá nhân",
  "Trợ lý AI tư vấn gói độ & bảo dưỡng 24/7",
];

export function AuthLayout({ eyebrow, title, description, children, contentClassName = "" }) {
  return (
    <main className="min-h-screen bg-white text-[#111318] lg:grid lg:grid-cols-2">
      <aside className="relative flex min-h-[310px] flex-col justify-between overflow-hidden bg-[#08090B] px-7 py-7 text-white sm:px-10 sm:py-9 lg:min-h-screen lg:px-[6.3%] lg:py-[6.3%]">
        <img
          src={showroomCar}
          alt=""
          className="absolute inset-0 h-full w-full object-cover object-center opacity-55"
          aria-hidden="true"
        />
        <div className="absolute inset-0 bg-[linear-gradient(115deg,rgba(60,3,8,0.68)_0%,rgba(3,5,8,0.66)_54%,rgba(2,3,5,0.94)_100%)]" />
        <div className="absolute inset-0 bg-[linear-gradient(0deg,rgba(0,0,0,0.72)_0%,transparent_62%)]" />

        <Link to="/" className="relative z-10 inline-flex w-fit items-center gap-3 text-white">
          <img src="/src/assets/img/logo.png" className="w-[150px]" />
        </Link>

        <div className="relative z-10 my-9 max-w-[550px] lg:my-0">
          <div className="mb-5 inline-flex items-center gap-2 rounded-full border border-white/25 bg-white/10 px-3.5 py-2 text-xs font-semibold text-white/95 backdrop-blur-sm">
            <ShieldCheck className="h-3.5 w-3.5" />
            Khu vực khách hàng
          </div>
          <h2 className="mb-6 max-w-[510px] text-[34px] font-extrabold leading-[1.08] tracking-[-1.2px] text-white sm:text-[38px] lg:text-[40px]">
            Garage &amp; dịch vụ độ xe của riêng bạn
          </h2>
          <ul className="flex flex-col gap-3.5">
            {HIGHLIGHTS.map((highlight) => (
              <li key={highlight} className="flex items-center gap-3 text-sm text-white/90 sm:text-[15px]">
                <Check className="h-4 w-4 shrink-0 text-[#D9B35F]" strokeWidth={2.5} />
                {highlight}
              </li>
            ))}
          </ul>
        </div>

        <p className="relative z-10 hidden text-xs text-white/70 lg:block">
          © 2026 Showroom. Bản quyền thuộc về Porsche. Mọi quyền được bảo lưu.
        </p>
      </aside>

      <section className="relative flex min-h-[calc(100svh-310px)] items-center justify-center bg-[#FCFCFB] px-5 py-10 sm:px-10 lg:min-h-screen lg:px-12">
        <Link
          to="/"
          className="absolute right-5 top-5 inline-flex items-center gap-2 rounded-lg border border-[#D7D8DA] px-2.5 py-1.5 text-xs font-semibold text-[#35383D] transition-colors hover:bg-white sm:right-8 sm:top-7 sm:text-sm"
        >
          <ArrowLeft className="h-2 w-2" />
          Về showroom
        </Link>

        <div className={`w-full pt-[75px] pb-[20px] max-w-[466px] ${contentClassName}`}>
          <div className="mb-7">
            <div className=" text-[11px] font-bold uppercase tracking-[1.35px] text-[#8A6D3B]">
              {eyebrow}
            </div>
            <h1 className="mb-2 m-0 text-[28px] font-extrabold leading-tight tracking-[-0.8px] text-[#111318] sm:text-[31px]">
              {title}
            </h1>
            {description ? (
              <p className="max-w-[470px] text-[15px] leading-[1.65] text-[#666970]">{description}</p>
            ) : null}
          </div>
          {children}
        </div>
      </section>
    </main>
  );
}

export default AuthLayout;

export function AuthField({
  label,
  icon: Icon,
  type = "text",
  value,
  onChange,
  placeholder,
  autoComplete,
  required = true,
  inputMode,
}) {
  const [showPassword, setShowPassword] = useState(false);
  const passwordField = type === "password";
  const inputType = passwordField && showPassword ? "text" : type;

  return (
    <label className="block">
      <span className="mb-0.5 block text-[13px] font-medium text-[#202329]">{label}</span>
      <span className="flex h-[44px] items-center gap-3 rounded-[9px] border border-[#DCDDDF] bg-white px-3.5 transition-colors focus-within:border-[#9A9CA1]">
        {Icon ? <Icon className="h-[16px] w-[16px] shrink-0 text-[#81858D]" strokeWidth={1.8} /> : null}
        <input
          type={inputType}
          required={required}
          value={value}
          onChange={onChange}
          placeholder={placeholder}
          autoComplete={autoComplete}
          inputMode={inputMode}
          className="h-full min-w-0 flex-1 border-0 bg-transparent text-sm text-[#202329] outline-none placeholder:text-[#9699A0]"
        />
        {passwordField ? (
          <button
            type="button"
            aria-label={showPassword ? "Ẩn mật khẩu" : "Hiện mật khẩu"}
            onClick={() => setShowPassword((visible) => !visible)}
            className="text-[#858992] hover:text-[#252830]"
          >
            {showPassword ? <EyeOff className="h-[17px] w-[17px]" /> : <Eye className="h-[17px] w-[17px]" />}
          </button>
        ) : null}
      </span>
    </label>
  );
}

export function AuthSubmit({ children, disabled = false, onClick, type = "submit" }) {
  return (
    <button
      type={type}
      disabled={disabled}
      onClick={onClick}
      className="flex min-h-[36px] w-full items-center justify-center gap-2 rounded-[9px] bg-[#AC0021] px-4 py-2 text-[16px] text-white transition-colors hover:bg-[#AC0021] disabled:cursor-wait disabled:opacity-60"
    >
      {children}
    </button>
  );
}

export function AuthError({ children }) {
  return children ? (
    <div role="alert" className="mb-3 rounded-lg border border-[#F4C7CD] bg-[#FFF0F1] px-4 py-2 text-sm leading-relaxed text-[#8C001B]">
      {children}
    </div>
  ) : null;
}

export function AuthDivider() {
  return (
    <div className="my-3 flex items-center gap-3 text-xs text-[#9A9CA1]">
      <span className="h-px flex-1 bg-[#E3E3E1]" />
      hoặc
      <span className="h-px flex-1 bg-[#E3E3E1]" />
    </div>
  );
}
