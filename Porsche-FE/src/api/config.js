const apiUrl = import.meta.env.VITE_API_URL?.trim() || "http://127.0.0.1:8000/api";

export const BASE_URL = apiUrl.replace(/\/+$/, "");
