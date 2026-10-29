export const STAGE_META = {
  cho_xac_nhan: { label: "Chờ xác nhận", dot: "#B8862B", chip: "bg-[#FBF1DC] text-[#8A6414] border-[#F0DDB0]" },
  cho_nhan_xe: { label: "Chờ nhận xe", dot: "#B8862B", chip: "bg-[#FBF1DC] text-[#8A6414] border-[#F0DDB0]" },
  dang_thi_cong: { label: "Đang thi công", dot: "#AC0021", chip: "bg-[#FDE8E8] text-[#AC0021] border-[#F6C9C9]" },
  dang_test_dyno: { label: "Đang test Dyno", dot: "#A98A4B", chip: "bg-[#F3EBDD] text-[#7A5F25] border-[#E5D7BA]" },
  da_ban_giao: { label: "Hoàn thành", dot: "#17171A", chip: "bg-[#17171A] text-white border-[#17171A]" },
  da_huy: { label: "Đã hủy", dot: "#8B8B86", chip: "bg-[#F1F1EF] text-[#63635E] border-[#E4E4E0]" },
  trong: { label: "Trống", dot: "#6B6B66", chip: "bg-[#EEEEEB] text-[#4A4A46] border-[#E1E1DD]" },
  bao_tri: { label: "Bảo trì", dot: "#8B8B86", chip: "bg-[#EEEEEB] text-[#63635E] border-[#E1E1DD]" },
};

export function formatMoney(value) {
  return `${new Intl.NumberFormat("vi-VN").format(Number(value) || 0)} ₫`;
}

export function formatMillions(value) {
  const amount = Number(value) || 0;
  if (amount >= 1_000_000) {
    return `${new Intl.NumberFormat("vi-VN", { maximumFractionDigits: 1 }).format(amount / 1_000_000)} triệu`;
  }
  return formatMoney(amount);
}

export function formatDate(value) {
  if (!value) return "—";
  const [year, month, day] = String(value).slice(0, 10).split("-");
  return `${day}/${month}/${year}`;
}

export function formatDuration(minutes) {
  const value = Number(minutes) || 0;
  if (value % 60 === 0) return `${value / 60} giờ`;
  return `${(value / 60).toFixed(1).replace(".", ",")} giờ`;
}

export function initials(name) {
  return String(name || "?")
    .split(/\s+/)
    .filter(Boolean)
    .slice(-2)
    .map((part) => part[0])
    .join("")
    .toUpperCase();
}

export function bayShortName(name) {
  const match = String(name || "").match(/^Bay\s*(\d+)/i);
  return match ? `Bay ${match[1].padStart(2, "0")}` : name;
}

export function errorMessage(error, fallback) {
  return error?.response?.data?.message || error?.message || fallback;
}
