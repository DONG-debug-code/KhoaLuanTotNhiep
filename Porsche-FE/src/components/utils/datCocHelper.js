import { timGoiDoTheoSlug } from "../../data/hangDoData";

// Mức đặt cọc theo từng loại dịch vụ
export function layThongTinDatCoc(loai, slug) {
  if (loai === "goi-do") {
    const ketQua = timGoiDoTheoSlug(slug);
    if (!ketQua) return null;
    const giaSo = Number(ketQua.gia.replace(/\D/g, ""));
    const tienCoc = Math.round((giaSo * 0.1) / 100000) * 100000; // cọc 10% giá gói, làm tròn trăm nghìn
    return {
      tieuDe: `Đặt lịch khảo sát & thi công: ${ketQua.ten}`,
      moTaNgan: `${ketQua.hang.ten} · ${ketQua.hangMuc}`,
      anh: ketQua.anh,
      tienCoc,
      ghiChuCoc: "Cọc 10% giá gói độ để giữ lịch khảo sát xe. Giá cuối cùng sẽ được xác nhận sau khi kỹ thuật viên kiểm tra xe thực tế.",
    };
  }

  if (loai === "bao-duong") {
    return {
      tieuDe: "Đặt lịch bảo dưỡng định kỳ",
      moTaNgan: "Dịch vụ bảo dưỡng chính hãng",
      anh: null,
      tienCoc: 500000,
      ghiChuCoc: "Khoản cọc sẽ được trừ trực tiếp vào hóa đơn bảo dưỡng.",
    };
  }

  return null;
}

export function dinhDangTien(so) {
  return so.toLocaleString("vi-VN") + " đ";
}