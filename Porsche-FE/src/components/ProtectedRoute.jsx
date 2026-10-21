import { Navigate, useLocation } from "react-router-dom";
import { useAuth } from "../context/AuthContext";

const ROLE_HOME = {
    admin: "/admin",
    "chuyen-gia": "/chuyen-gia-workspace",
    "khach-hang": "/garage",
};

export default function ProtectedRoute({ children, roles }) {
    const { daDangNhap, vaiTro } = useAuth();
    const location = useLocation();

    if (!daDangNhap) {
        // Lưu lại đường dẫn khách đang muốn vào, để đăng nhập xong quay lại đúng chỗ
        return <Navigate to="/dang-nhap" state={{ from: location }} replace />;
    }

    if (roles?.length && !roles.includes(vaiTro)) {
        return <Navigate to={ROLE_HOME[vaiTro] || "/"} replace />;
    }

    return children;
}