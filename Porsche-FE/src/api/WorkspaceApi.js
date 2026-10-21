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

export async function postWorkspaceData(role, path, payload) {
  const { data } = await apiClient.post(`/${role}/${path}`, payload);
  return data;
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
