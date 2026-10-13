import { Navigate, useLocation } from "react-router-dom";
import { useAuth } from "../context/AuthContext";

export default function ProtectedRoute({ children }) {
    const { daDangNhap } = useAuth();
    const location = useLocation();

    if (!daDangNhap) {
        // Lưu lại đường dẫn khách đang muốn vào, để đăng nhập xong quay lại đúng chỗ
        return <Navigate to="/dang-nhap" state={{ from: location }} replace />;
    }

    return children;
}