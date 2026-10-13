import { createContext, useContext, useState, useEffect, useCallback } from "react";
import {
    dangNhapTheoVaiTro,
    dangXuatTheoVaiTro,
    getApiErrorMessage,
    kiemTraTokenTheoVaiTro,
} from "../api/Authapi";

const AuthContext = createContext(null);

export function AuthProvider({ children }) {
    const [nguoiDung, setNguoiDung] = useState(() => {
        const luu = localStorage.getItem("nguoiDung");
        return luu ? JSON.parse(luu) : null;
    });

    // Vai trò tách riêng ra cho tiện dùng (admin | chuyen-gia | khach-hang), nhưng vẫn
    // đồng bộ bên trong nguoiDung để không mất khi đọc lại từ localStorage.
    const vaiTro = nguoiDung?.vaiTro ?? null;

    const [dangTai, setDangTai] = useState(false);

    /**
     * Đăng nhập thật qua API. Trả về nguyên response {status, message, ...}
     * để trang DangNhapPage tự quyết định hiện lỗi gì, không ném exception.
     */
    const dangNhap = useCallback(async (vaiTroDangNhap, email, password) => {
        setDangTai(true);
        try {
            const ketQua = await dangNhapTheoVaiTro(vaiTroDangNhap, email, password);

            if (ketQua.status && ketQua.access_token) {
                const thongTinNguoiDung = {
                    vaiTro: vaiTroDangNhap,
                    access_token: ketQua.access_token,
                    // khách hàng có sẵn user {ho_ten, email} trong response login;
                    // admin/chuyên gia chưa có -> tạm để email, phần còn lại tự getProfile sau
                    ...(ketQua.user ?? { email }),
                };
                localStorage.setItem("nguoiDung", JSON.stringify(thongTinNguoiDung));
                setNguoiDung(thongTinNguoiDung);
            }

            return ketQua;
        } catch (err) {
            // Lỗi mạng / lỗi không mong muốn (BE lỗi 500...) — vẫn trả dạng đồng nhất
            const message = getApiErrorMessage(err, "Không thể kết nối máy chủ. Vui lòng thử lại.");
            return { status: false, message };
        } finally {
            setDangTai(false);
        }
    }, []);

    const dangXuat = useCallback(async () => {
        if (vaiTro) {
            try {
                await dangXuatTheoVaiTro(vaiTro);
            } catch {
                // Token có thể đã hết hạn từ trước -> vẫn cứ xóa phiên phía FE bên dưới
            }
        }
        localStorage.removeItem("nguoiDung");
        setNguoiDung(null);
    }, [vaiTro]);

    // Tự đăng xuất khi apiClient nhận 401 từ bất kỳ request nào (token hết hạn/bị thu hồi)
    useEffect(() => {
        const xuLyHetHan = () => {
            localStorage.removeItem("nguoiDung");
            setNguoiDung(null);
        };
        window.addEventListener("het-han-phien-dang-nhap", xuLyHetHan);
        return () => window.removeEventListener("het-han-phien-dang-nhap", xuLyHetHan);
    }, []);

    // Xác thực lại token với BE ngay khi app khởi động / F5 lại trang,
    // tránh tình trạng UI hiện "đã đăng nhập" nhưng token đã bị thu hồi phía server.
    useEffect(() => {
        if (!nguoiDung?.vaiTro) return;
        kiemTraTokenTheoVaiTro(nguoiDung.vaiTro).catch(() => {
            localStorage.removeItem("nguoiDung");
            setNguoiDung(null);
        });
        // chỉ chạy 1 lần lúc mount, không phụ thuộc nguoiDung để tránh loop
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    return (
        <AuthContext.Provider
            value={{
                nguoiDung,
                vaiTro,
                daDangNhap: !!nguoiDung,
                dangTai,
                dangNhap,
                dangXuat,
            }}
        >
            {children}
        </AuthContext.Provider>
    );
}

export function useAuth() {
    return useContext(AuthContext);
}