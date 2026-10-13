import { Outlet, useLocation } from "react-router-dom";
import Navbar from "@/components/layouts/partials/Navbar";
 
// Danh sách route nào dùng Navbar tối (đè lên nền ảnh/video) — còn lại mặc định dùng "light".
// Thêm path vào đây khi có trang mới cần Navbar tối (ví dụ trang chi tiết có hero ảnh lớn).
const DARK_NAVBAR_PATHS = ["/"];
 
// Layout dùng chung cho toàn bộ khu vực công khai (chưa đăng nhập).
// Navbar chỉ render 1 lần ở đây — các page con không cần tự import Navbar nữa.
// <Outlet /> là nơi router sẽ "bơm" nội dung của từng page con vào.
export default function PublicLayout() {
  const { pathname } = useLocation();
  const variant = DARK_NAVBAR_PATHS.includes(pathname) ? "dark" : "light";
 
  return (
    <div className="min-h-screen bg-[#0A0A0B]">
      <Outlet />
    </div>
  );
}