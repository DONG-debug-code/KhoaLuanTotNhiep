export const CHUYEN_GIA = [
  {
    slug: "nguyen-hoang-minh",
    ten: "Nguyễn Hoàng Minh",
    chucDanh: "Trưởng nhóm Tuner",
    trangThai: "san-sang",
    hangMucChuyenMon: ["Hiệu suất & âm thanh"],
    dongXeChuyenMon: ["911", "718"],
    anh: "/src/assets/img/chuyengia/minh.jpg",
    soNamKinhNghiem: 12,
    moTa: "Chuyên sâu nâng cấp hệ thống nạp – xả và tinh chỉnh ECU cho dòng 911. Từng làm việc tại trung tâm kỹ thuật Porsche châu Á – Thái Bình Dương.",
  },
  {
    slug: "tran-quoc-bao",
    ten: "Trần Quốc Bảo",
    chucDanh: "Chuyên gia hệ thống treo",
    trangThai: "dang-kin-lich",
    hangMucChuyenMon: ["Hệ thống treo"],
    dongXeChuyenMon: ["718", "Cayenne"],
    anh: "/src/assets/img/chuyengia/bao.jpg",
    soNamKinhNghiem: 8,
    moTa: "Chuyên gia cân chỉnh hệ thống treo và góc đặt bánh cho các mẫu Cayman, Cayenne. Đam mê các cung đường đèo và track day.",
  },
  {
    slug: "le-thi-thu-ha",
    ten: "Lê Thị Thu Hà",
    chucDanh: "Cố vấn kỹ thuật",
    trangThai: "san-sang",
    hangMucChuyenMon: ["Bảo dưỡng", "Xe điện"],
    dongXeChuyenMon: ["Taycan"],
    anh: "/src/assets/img/chuyengia/ha.jpg",
    soNamKinhNghiem: 6,
    moTa: "Cố vấn kỹ thuật cho dòng xe điện Taycan và các buổi bảo dưỡng định kỳ. Chứng chỉ kỹ thuật viên cao cấp của Porsche.",
  },
  {
    slug: "pham-tuan-kiet",
    ten: "Phạm Tuấn Kiệt",
    chucDanh: "Chuyên gia nội thất & độ xe",
    trangThai: "tam-nghi",
    hangMucChuyenMon: ["Ngoại thất", "Nội thất"],
    dongXeChuyenMon: ["Panamera", "Cayenne"],
    anh: "/src/assets/img/chuyengia/kiet.jpg",
    soNamKinhNghiem: 5,
    moTa: "Phụ trách các gói độ nội thất và tùy biến ngoại thất theo yêu cầu riêng của từng khách hàng.",
  },
];

export function goiYChuyenGia(goiDo) {
  return CHUYEN_GIA
    .map((cg) => {
      let diem = 0;
      if (cg.hangMucChuyenMon.includes(goiDo.hangMuc)) diem += 2;
      if (goiDo.dongXeTuongThich.some((xe) => cg.dongXeChuyenMon.includes(xe))) diem += 1;
      return { ...cg, diem };
    })
    .filter((cg) => cg.diem > 0)
    .sort((a, b) => b.diem - a.diem);
}