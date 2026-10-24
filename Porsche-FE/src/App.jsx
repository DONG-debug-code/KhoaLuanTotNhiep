import './App.css'
import { Routes, Route } from 'react-router-dom'
import HomePage from './pages/public/HomePage'
import HangPhuTungPage from './pages/hang-phu-tung/HangPhuTungPage'
import HangChiTietPage from './pages/hang-phu-tung/HangChiTietPage'
import GoiDoChiTietPage from './pages/hang-phu-tung/GoiDoChiTietPage'
import PhuTungChiTietPage from './pages/hang-phu-tung/PhuTungChiTietPage'
import ChuyenGiaChiTietPage from './pages/chuyengia/ChuyenGiaChiTietPage'
import ChuyenGiaPage from './pages/chuyengia/ChuyenGiaPage'
import DangNhapPage from './pages/DangNhapPage'
import ProtectedRoute from './components/ProtectedRoute'
import XeChiTietPage from './pages/xe/XeChiTietPage'
import ScrollToTop from './components/common/ScrollToTop'
import TroLyAIChat from './components/chat/TroLyAIChat'
import DatCocPage from './pages/customer/DatCocPage'
import DangKyPage from './pages/DangKyPage'
import KichHoatPage from './pages/KichHoatPage'
import QuenMatKhauPage from './pages/QuenMatKhauPage'
import DoiMatKhauPage from './pages/DoiMatKhauPage'
import CustomerPortalPage from './pages/customer/CustomerPortalPage'
import GioHangPage from './pages/customer/GioHangPage'
import ThanhToanPage from './pages/customer/ThanhToanPage'
import DatHangThanhCongPage from './pages/customer/DatHangThanhCongPage'
import OperationsWorkspacePage from './pages/staff/OperationsWorkspacePage'
import ChuyenGiaWorkspacePage from './pages/chuyengia/ChuyenGiaWorkspacePage'

function App() {
  return (
    <>
      <ScrollToTop />
      <Routes>
        <Route path="/" element={<HomePage />} />
        <Route path="/hang-phu-tung" element={<HangPhuTungPage />} />
        <Route path="/hang-phu-tung/:hangSlug" element={<HangChiTietPage />} />
        <Route path="/chuyen-gia" element={<ChuyenGiaPage />} />
        <Route path="/chuyen-gia/:chuyenGiaSlug" element={<ChuyenGiaChiTietPage />} />
        <Route path="/dang-nhap" element={<DangNhapPage />} />
        <Route path="/xe-dang-ban/:xeSlug" element={<XeChiTietPage />} />
        <Route
          path="/dat-coc/:loai/:slug"
          element={
            <ProtectedRoute>
              <DatCocPage />
            </ProtectedRoute>
          }
        />

        <Route
          path="/goi-do/:goiSlug"
          element={<GoiDoChiTietPage />}
        />
        <Route path="/phu-tung/:phuTungId" element={<PhuTungChiTietPage />} />
        <Route path="/gio-hang" element={<GioHangPage />} />
        <Route
          path="/thanh-toan"
          element={
            <ProtectedRoute roles={["khach-hang"]}>
              <ThanhToanPage />
            </ProtectedRoute>
          }
        />
        <Route
          path="/dat-hang-thanh-cong/:orderId"
          element={
            <ProtectedRoute roles={["khach-hang"]}>
              <DatHangThanhCongPage />
            </ProtectedRoute>
          }
        />

        <Route path="/dang-ky" element={<DangKyPage />} />
        <Route path="/kich-hoat" element={<KichHoatPage />} />
        <Route path="/quen-mat-khau" element={<QuenMatKhauPage />} />
        <Route path="/doi-mat-khau" element={<DoiMatKhauPage />} />
        <Route
          path="/garage"
          element={
            <ProtectedRoute roles={["khach-hang"]}>
              <CustomerPortalPage />
            </ProtectedRoute>
          }
        />
        <Route
          path="/admin"
          element={
            <ProtectedRoute roles={["admin"]}>
              <OperationsWorkspacePage role="admin" />
            </ProtectedRoute>
          }
        />
        <Route
          path="/chuyen-gia-workspace"
          element={
            <ProtectedRoute roles={["chuyen-gia"]}>
              <ChuyenGiaWorkspacePage />
            </ProtectedRoute>
          }
        />
      </Routes>
      <TroLyAIChat />
    </>
  )
}

export default App