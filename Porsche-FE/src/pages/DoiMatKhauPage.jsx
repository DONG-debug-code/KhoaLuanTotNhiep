import { useState } from "react";
import { useNavigate, useSearchParams, useLocation } from "react-router-dom";
import { KeyRound } from "lucide-react";
import { doiMatKhauKhachHang, getApiErrorMessage } from "../api/Authapi";

export default function DoiMatKhauPage() {
    const navigate = useNavigate();
    const location = useLocation();
    const [searchParams] = useSearchParams();

    // BE thường trả link dạng: /doi-mat-khau?email=...&token=...
    const [email, setEmail] = useState(searchParams.get("email") || location.state?.email || "");
    const tokenTuLienKet = searchParams.get("token") || "";
    const [maXacNhan, setMaXacNhan] = useState("");
    const token = tokenTuLienKet || maXacNhan.trim();

    const [matKhauMoi, setMatKhauMoi] = useState("");
    const [xacNhan, setXacNhan] = useState("");
    const [loi, setLoi] = useState("");
    const [thanhCong, setThanhCong] = useState(false);
    const [dangTai, setDangTai] = useState(false);

    const xuLyDoiMatKhau = async (e) => {
        e.preventDefault();
        setLoi("");

        if (matKhauMoi !== xacNhan) {
            setLoi("Mật khẩu xác nhận không khớp.");
            return;
        }
        if (!token) {
            setLoi("Liên kết không hợp lệ hoặc đã hết hạn. Vui lòng yêu cầu lại.");
            return;
        }

        setDangTai(true);
        try {
            const ketQua = await doiMatKhauKhachHang({
                email,
                token,
                password: matKhauMoi,
                password_confirmation: xacNhan,
            });
            if (ketQua.status) {
                setThanhCong(true);
                setTimeout(() => navigate("/dang-nhap"), 1800);
            } else {
                setLoi(ketQua.message || "Không thể đổi mật khẩu. Liên kết có thể đã hết hạn.");
            }
        } catch (err) {
            setLoi(getApiErrorMessage(err, "Không thể kết nối máy chủ. Vui lòng thử lại."));
        } finally {
            setDangTai(false);
        }
    };

    if (thanhCong) {
        return (
            <div className="flex min-h-screen items-center justify-center bg-[#FAFAF9] px-5">
                <div className="w-full max-w-[400px] rounded-2xl border border-[#ECECE9] bg-white p-8 text-center">
                    <h1 className="mb-2 text-xl font-extrabold">Đổi mật khẩu thành công</h1>
                    <p className="text-sm text-[#63635E]">Đang chuyển tới trang đăng nhập...</p>
                </div>
            </div>
        );
    }

    return (
        <div className="flex min-h-screen items-center justify-center bg-[#FAFAF9] px-5">
            <div className="w-full max-w-[400px] rounded-2xl border border-[#ECECE9] bg-white p-8">
                <h1 className="mb-2 text-2xl font-extrabold tracking-[-0.3px]">Đặt lại mật khẩu</h1>
                <p className="mb-6 text-sm text-[#63635E]">
                    Nhập mật khẩu mới cho tài khoản{" "}
                    <span className="font-semibold text-[#17171A]">{email}</span>.
                </p>

                {loi && (
                    <div className="mb-4 rounded-lg bg-[#FBE9E9] px-4 py-3 text-sm text-[#AC0021]">
                        {loi}
                    </div>
                )}

                <form onSubmit={xuLyDoiMatKhau} className="flex flex-col gap-4">
                    <div>
                        <label className="mb-1.5 block text-sm font-semibold">Email</label>
                        <input
                            type="email"
                            required
                            value={email}
                            onChange={(e) => setEmail(e.target.value)}
                            placeholder="ban@email.com"
                            className="w-full rounded-lg border border-[#ECECE9] px-4 py-3 text-sm outline-none focus:border-[#17171A]"
                        />
                    </div>
                    {!tokenTuLienKet && (
                        <div>
                            <label className="mb-1.5 block text-sm font-semibold">Mã xác nhận trong email</label>
                            <input
                                type="text"
                                required
                                value={maXacNhan}
                                onChange={(e) => setMaXacNhan(e.target.value)}
                                placeholder="Nhập mã hoặc token đã nhận"
                                className="w-full rounded-lg border border-[#ECECE9] px-4 py-3 text-sm outline-none focus:border-[#17171A]"
                            />
                        </div>
                    )}
                    <div>
                        <label className="mb-1.5 block text-sm font-semibold">Mật khẩu mới</label>
                        <input
                            type="password"
                            required
                            value={matKhauMoi}
                            onChange={(e) => setMatKhauMoi(e.target.value)}
                            placeholder="••••••••"
                            className="w-full rounded-lg border border-[#ECECE9] px-4 py-3 text-sm outline-none focus:border-[#17171A]"
                        />
                    </div>

                    <div>
                        <label className="mb-1.5 block text-sm font-semibold">Xác nhận mật khẩu</label>
                        <input
                            type="password"
                            required
                            value={xacNhan}
                            onChange={(e) => setXacNhan(e.target.value)}
                            placeholder="••••••••"
                            className="w-full rounded-lg border border-[#ECECE9] px-4 py-3 text-sm outline-none focus:border-[#17171A]"
                        />
                    </div>

                    <button
                        type="submit"
                        disabled={dangTai}
                        className="mt-2 flex items-center justify-center gap-2 rounded-lg bg-[#17171A] py-3.5 text-sm font-semibold text-white disabled:opacity-60"
                    >
                        <KeyRound className="h-4 w-4" />
                        {dangTai ? "Đang xử lý..." : "Đặt lại mật khẩu"}
                    </button>
                </form>
            </div>
        </div>
    );
}