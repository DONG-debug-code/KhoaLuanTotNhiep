import axios from "axios";
import { apiClient, BASE_URL } from "./Authapi";

export async function askPorscheAI(message) {
  const client = localStorage.getItem("nguoiDung") ? apiClient : axios;
  const { data } = await client.post(`${BASE_URL}/ai/ask`, { message });
  return data?.data?.answer ?? data?.answer ?? "";
}

export async function getAIHistory() {
  const { data } = await apiClient.get("/khach-hang/ai/history");
  const history = data?.data?.data ?? data?.data;
  if (!Array.isArray(history)) {
    throw new Error("Dữ liệu lịch sử AI không hợp lệ.");
  }
  return history;
}

export async function clearAIHistory() {
  const { data } = await apiClient.delete("/khach-hang/ai/clear-history");
  return data;
}

export async function getWorkspaceData(role, path, params) {
  const { data } = await apiClient.get(`/${role}/${path}`, { params });
  return data?.data ?? data;
}

export function getWorkspaceAssetUrl(path) {
  if (!path) return "";
  try {
    return new URL(path, `${new URL(BASE_URL).origin}/`).toString();
  } catch {
    throw new Error("Đường dẫn ảnh hồ sơ không hợp lệ.");
  }
}

export async function postWorkspaceData(role, path, payload) {
  const { data } = await apiClient.post(`/${role}/${path}`, payload);
  return data;
}

export async function getOrderDetail(orderId, role = "khach-hang") {
  const { data } = await apiClient.get(`/${role}/don-hang/${orderId}/chi-tiet`);
  return data?.data;
}

export async function downloadOrderInvoicePdf(orderId, fileName, role = "khach-hang") {
  const response = await apiClient.get(`/${role}/don-hang/${orderId}/pdf`, { responseType: "blob" });
  const url = URL.createObjectURL(response.data);
  const link = document.createElement("a");
  link.href = url;
  link.download = `${fileName || `don-hang-${orderId}`}.pdf`;
  document.body.appendChild(link);
  link.click();
  link.remove();
  URL.revokeObjectURL(url);
}

export async function updateWorkspaceProfile(role, payload) {
  return postWorkspaceData(role, "profile/update", payload);
}

export async function uploadWorkspaceAvatar(role, file) {
  const payload = new FormData();
  payload.append("avatar", file);
  const { data } = await apiClient.post(`/${role}/profile/update-avatar`, payload);
  return data;
}

export async function changeWorkspacePassword(role, payload) {
  return postWorkspaceData(role, "profile/change-password", payload);
}
