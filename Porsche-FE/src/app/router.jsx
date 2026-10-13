import { createBrowserRouter, RouterProvider } from "react-router-dom";
import PublicLayout from "@/components/layouts/PublicLayout";
import HomePage from "@/pages/public/HomePage";
import DangNhapPage from "@/pages/DangNhapPage";
import DangKyPage from "@/pages/DangKyPage";
import KichHoatPage from "@/pages/KichHoatPage";
import QuenMatKhauPage from "@/pages/QuenMatKhauPage";
import DoiMatKhauPage from "@/pages/DoiMatKhauPage";

const router = createBrowserRouter([
  {
    element: <PublicLayout />,
    children: [
      { path: "/", element: <HomePage /> },
      { path: "/dang-nhap", element: <DangNhapPage /> },
      { path: "/dang-ky", element: <DangKyPage /> },
      { path: "/kich-hoat", element: <KichHoatPage /> },
      { path: "/quen-mat-khau", element: <QuenMatKhauPage /> },
      { path: "/doi-mat-khau", element: <DoiMatKhauPage /> },
      // Thêm route công khai khác vào đây, ví dụ:
      // { path: "/hang-phu-tung", element: <BrandListPage /> },
      // { path: "/hang-phu-tung/:id", element: <BrandDetailPage /> },
      // { path: "/chuyen-gia", element: <ExpertListPage /> },
    ],
  },

  // Khu vực customer/expert/admin sẽ thêm layout riêng ở đây sau,
  // mỗi khu vực 1 object route cấp cao nhất như trên, không lồng vào PublicLayout.
]);

export default function AppRouter() {
  return <RouterProvider router={router} />;
}