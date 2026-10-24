// src/api/authApi.js
// Lớp API dùng chung cho cả 3 vai trò (admin / chuyen-gia / khach-hang).
// Khớp đúng AuthController thật của DU_AN_XE: token là chuỗi random thô,
// mọi route sau khi login yêu cầu header Authorization: Bearer <access_token>.

import axios from "axios";
import { BASE_URL } from "./config";

export { BASE_URL };

export function getApiErrorMessage(error, fallback) {
  const responseData = error?.response?.data;
  const validationMessages = responseData?.errors
    ? Object.values(responseData.errors).flat().filter((message) => typeof message === "string")
    : [];

  return validationMessages.join(" ") || responseData?.message || fallback;
}

// vaiTro dùng đúng tên này ở khắp nơi trong app vì nó TRÙNG với prefix route của BE
// admin | chuyen-gia | khach-hang

/**
 * apiClient dùng cho MỌI request sau khi đã đăng nhập (không chỉ login).
 * Tự gắn Bearer token đọc từ localStorage["nguoiDung"].access_token.
 * Component gọi API nghiệp vụ khác chỉ cần: apiClient.get('/khach-hang/profile/data')
 */
export const apiClient = axios.create({ baseURL: BASE_URL });

apiClient.interceptors.request.use((config) => {
  const luu = localStorage.getItem("nguoiDung");
  if (luu) {
    const { access_token } = JSON.parse(luu);
    if (access_token) config.headers.Authorization = `Bearer ${access_token}`;
  }
  return config;
});

// Nếu token hết hạn/bị thu hồi, BE trả 401 -> phát 1 sự kiện toàn cục để
// AuthContext lắng nghe và tự đăng xuất (tránh import chéo vòng lặp giữa 2 file).
apiClient.interceptors.response.use(
  (res) => res,
  (err) => {
    if (err.response && err.response.status === 401) {
      window.dispatchEvent(new Event("het-han-phien-dang-nhap"));
    }
    return Promise.reject(err);
  }
);

/** Đăng nhập: BE tự xác định vai trò theo tên miền email. Trả {status, message, access_token, vai_tro, user?} */
export async function dangNhapTheoEmail(email, password) {
  const { data } = await axios.post(`${BASE_URL}/dang-nhap`, { email, password });
  return data;
}

/** Gọi API logout đúng route của vai trò đang đăng nhập */
export async function dangXuatTheoVaiTro(vaiTro) {
  const { data } = await apiClient.post(`/${vaiTro}/logout`);
  return data;
}

/** Kiểm tra token còn hợp lệ không (gọi lúc app khởi động / F5 lại trang) */
export async function kiemTraTokenTheoVaiTro(vaiTro) {
  const { data } = await apiClient.get(`/${vaiTro}/check-token`);
  return data;
}

// ---- Riêng khách hàng: đăng ký + kích hoạt + quên mật khẩu ----

export async function dangKyKhachHang({ ho_ten, email, so_dien_thoai, password, password_confirmation }) {
  const { data } = await axios.post(`${BASE_URL}/khach-hang/register`, {
    ho_ten,
    email,
    so_dien_thoai,
    password,
    password_confirmation, // bắt buộc, BE rule là 'confirmed'
  });
  return data;
}

export async function kichHoatKhachHang(email, ma_kich_hoat) {
  const { data } = await axios.post(`${BASE_URL}/khach-hang/kich-hoat`, { email, ma_kich_hoat });
  return data;
}

export async function quenMatKhauKhachHang(email) {
  const { data } = await axios.post(`${BASE_URL}/khach-hang/quen-mat-khau`, { email });
  return data;
}

export async function doiMatKhauKhachHang({ email, token, password, password_confirmation }) {
  const { data } = await axios.post(`${BASE_URL}/khach-hang/doi-mat-khau`, {
    email,
    token,
    password,
    password_confirmation,
  });
  return data;
}