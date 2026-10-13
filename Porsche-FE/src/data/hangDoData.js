export const HANG_DO = [
  {
    slug: "akrapovic",
    ten: "Akrapovič",
    quocGia: "Slovenia",
    tuNam: 1990,
    rating: 4.9,
    hangMucChinh: "Hệ thống xả titan",
    tags: ["Titan", "Giảm trọng lượng", "Âm thanh"],
    moTaNgan: "Thương hiệu xả cao cấp hàng đầu thế giới, nổi tiếng với hệ thống xả titan siêu nhẹ cho dòng xe hiệu năng cao.",
    chuyenMon: "Hệ thống pô hiệu suất & âm thanh",
    logo: "/src/assets/img/hangdo/akrapovic.png",
    anhBia: "/src/assets/img/hangdo/akrapovic-cover.jpg",
    moTa: "Đối tác độ pô titan/carbon chính hãng cho dòng 911 và 718, tối ưu âm thanh và giảm trọng lượng.",
    goiDo: [
      {
        slug: "akrapovic-po-titan-911",
        ten: "Pô Titan Evolution",
        dongXeTuongThich: ["911"],
        hangMuc: "Hiệu suất & âm thanh",
        gia: "285.000.000 đ",
        anh: "/src/assets/img/goido/akrapovic-po-titan-911.jpg",
        moTaNgan: "Giảm 40% trọng lượng, tăng công suất đầu ra, âm thanh đặc trưng.",
        chiTiet: "Hệ thống pô titan nguyên khối, đầu pô carbon, van điện tử điều chỉnh 3 chế độ âm thanh...",
      },
      {
        slug: "akrapovic-po-carbon-718",
        ten: "Pô Carbon GT Sport",
        dongXeTuongThich: ["718"],
        hangMuc: "Hiệu suất & âm thanh",
        gia: "195.000.000 đ",
        anh: "/src/assets/img/goido/akrapovic-po-carbon-718.jpg",
        moTaNgan: "Thiết kế đầu pô carbon dạng đôi, tối ưu cho đường đua.",
        chiTiet: "...",
      },
    ],
  },
  {
    slug: "techart",
    ten: "TechArt",
    quocGia: "Đức",
    tuNam: 1987,
    rating: 4.8,
    hangMucChinh: "Độ toàn diện ngoại thất",
    tags: ["Bodykit", "Khí động học", "Nội thất"],
    moTaNgan: "Chuyên gia độ Porsche toàn diện đến từ Đức, cung cấp bodykit khí động học, mâm, nội thất và nâng cấp hiệu năng.",
    chuyenMon: "Bodykit & nâng cấp ngoại thất",
    logo: "/src/assets/img/hangdo/techart.png",
    anhBia: "/src/assets/img/hangdo/techart-cover.jpg",
    moTa: "Chuyên bodykit khí động học, mâm forged và nội thất may đo cho toàn dải Porsche.",
    goiDo: [
      {
        slug: "techart-bodykit-cayenne",
        ten: "Bodykit Aerodynamic Cayenne",
        dongXeTuongThich: ["Cayenne"],
        hangMuc: "Ngoại thất",
        gia: "410.000.000 đ",
        anh: "/src/assets/img/goido/techart-bodykit-cayenne.jpg",
        moTaNgan: "Cản trước/sau carbon, ốp hông, cánh gió sau chỉnh được.",
        chiTiet: "...",
      },
    ],
  },
  {
    slug: "fi-exhaust",
    ten: "Fi Exhaust",
    quocGia: "Đài Loan",
    tuNam: 2005,
    rating: 4.7,
    hangMucChinh: "Hệ thống xả van điều khiển",
    tags: ["Van điều khiển", "Âm thanh", "Thép không gỉ"],
    moTaNgan: "Nổi bật với hệ thống xả van điều khiển cho phép thay đổi âm thanh theo chế độ lái, giá thành hợp lý.",
    chuyenMon: "Hệ thống xả tùy chỉnh âm thanh",
    logo: "/src/assets/img/hangdo/fi-exhaust.png",
    anhBia: "/src/assets/img/hangdo/fi-exhaust-cover.jpg",
    moTa: "Đối tác cung cấp hệ thống xả van điều khiển điện tử cho toàn dải Porsche.",
    goiDo: [],
  },
];

export function timHangTheoSlug(slug) {
  return HANG_DO.find((h) => h.slug === slug);
}

export function timGoiDoTheoSlug(slug) {
  for (const hang of HANG_DO) {
    const goi = hang.goiDo.find((g) => g.slug === slug);
    if (goi) return { ...goi, hang };
  }
  return null;
}