import { useEffect, useState } from "react";
import { useParams, Link, Navigate } from "react-router-dom";
import {
  Gauge, Zap, Timer, Cog, Car, Fuel,
  ChevronLeft, ChevronRight, Play, CalendarCheck,
  Phone, ShieldCheck, Crown, Palette, Armchair,
  FileText, Check,
} from "lucide-react";
import Navbar from "../../components/layouts/partials/Navbar";
import Footer from "../../components/layouts/partials/Footer";
import { timXeTheoSlug } from "../../data/xeData";
import { getSanPhamClientById } from "../../api/PublicApi";

function chuanHoaTen(ten) {
  return String(ten)
    .normalize("NFD")
    .replace(/[\u0300-\u036f]/g, "")
    .toLocaleLowerCase()
    .replace(/[_\s-]/g, "");
}

function chuanHoaDongXe(dongXe) {
  return chuanHoaTen(dongXe).replace(/^porsche/, "");
}

function layGiaTriSanPham(sanPham, aliases, giaTriMacDinh = "") {
  for (const alias of aliases) {
    const giaTri = sanPham?.[alias];
    if (giaTri !== undefined && giaTri !== null && giaTri !== "") return giaTri;
  }

  const thongSo = sanPham?.thongSoChiTiet;
  if (thongSo && typeof thongSo === "object") {
    const tenCanTim = aliases.map(chuanHoaTen);
    const ketQua = Object.entries(thongSo).find(([ten]) => (
      tenCanTim.includes(chuanHoaTen(ten))
    ));
    if (ketQua?.[1] !== undefined && ketQua[1] !== null && ketQua[1] !== "") {
      return ketQua[1];
    }
  }

  return giaTriMacDinh;
}

function taoXeTuSanPham(sanPham, xeMau, xeSlug) {
  const ten = sanPham.ten || xeMau?.ten || "Xe Porsche";
  const anhGallery = sanPham.anhGallery?.length
    ? sanPham.anhGallery
    : xeMau?.anhGallery ?? [];
  const congSuat = layGiaTriSanPham(
    sanPham,
    ["congSuat", "cong_suat", "cong_suat_cuc_dai", "power"],
    xeMau?.congSuat || "Đang cập nhật"
  );
  const gia = sanPham.gia || xeMau?.gia || "Liên hệ";
  const phienBan = layGiaTriSanPham(sanPham, ["phienBan", "phien_ban"], null);
  const diemNhanTrangBi = layGiaTriSanPham(
    sanPham,
    ["diemNhanTrangBi", "diem_nhan_trang_bi"],
    null
  );

  return {
    ...xeMau,
    id: sanPham.id ?? xeMau?.id ?? xeSlug,
    slug: String(xeSlug),
    ten,
    dong: layGiaTriSanPham(
      sanPham,
      ["dong", "dongXe", "dong_xe", "mauXe", "mau_xe"],
      xeMau?.dong || ten.split(" ")[0]
    ),
    nam: layGiaTriSanPham(
      sanPham,
      ["nam", "namSanXuat", "nam_san_xuat", "year"],
      xeMau?.nam ?? "Đang cập nhật"
    ),
    trangThai: layGiaTriSanPham(
      sanPham,
      ["trangThai", "trang_thai"],
      xeMau?.trangThai || (sanPham.soLuong > 0 ? "Sẵn xe" : "Hết hàng")
    ),
    nhan: layGiaTriSanPham(sanPham, ["nhan"], xeMau?.nhan),
    anh: anhGallery[0] || sanPham.anh || xeMau?.anh || "",
    anhGallery,
    coVideoGioiThieu: layGiaTriSanPham(
      sanPham,
      ["coVideoGioiThieu", "co_video_gioi_thieu"],
      xeMau?.coVideoGioiThieu ?? false
    ),
    congSuat,
    toc0_100: layGiaTriSanPham(
      sanPham,
      ["toc0_100", "tangToc", "tang_toc_0_100", "acceleration"],
      xeMau?.toc0_100 || "Đang cập nhật"
    ),
    tocDoToiDa: layGiaTriSanPham(
      sanPham,
      ["tocDoToiDa", "toc_do_toi_da", "top_speed"],
      xeMau?.tocDoToiDa || "Đang cập nhật"
    ),
    hopSo: layGiaTriSanPham(
      sanPham,
      ["hopSo", "hop_so", "transmission"],
      xeMau?.hopSo || "Đang cập nhật"
    ),
    heDanDong: layGiaTriSanPham(
      sanPham,
      ["heDanDong", "he_dan_dong", "drivetrain"],
      xeMau?.heDanDong || "Đang cập nhật"
    ),
    nhienLieu: layGiaTriSanPham(
      sanPham,
      ["nhienLieu", "nhien_lieu", "fuel_type"],
      xeMau?.nhienLieu || "Đang cập nhật"
    ),
    gia,
    soKmDaDi: layGiaTriSanPham(
      sanPham,
      ["soKmDaDi", "so_km_da_di", "so_km", "mileage"],
      xeMau?.soKmDaDi || "Đang cập nhật"
    ),
    tinhTrang: layGiaTriSanPham(
      sanPham,
      ["tinhTrang", "tinh_trang"],
      xeMau?.tinhTrang || (sanPham.soLuong > 0 ? "Sẵn xe" : "Hết hàng")
    ),
    ngoaiThat: layGiaTriSanPham(
      sanPham,
      ["ngoaiThat", "ngoai_that", "mau_ngoai_that"],
      sanPham.mauSacs?.map((mau) => mau.ten).filter(Boolean).join(", ")
        || xeMau?.ngoaiThat
        || "Đang cập nhật"
    ),
    noiThat: layGiaTriSanPham(
      sanPham,
      ["noiThat", "noi_that", "mau_noi_that"],
      xeMau?.noiThat || "Đang cập nhật"
    ),
    lyDoChon: layGiaTriSanPham(
      sanPham,
      ["lyDoChon", "ly_do_chon", "moTa"],
      xeMau?.lyDoChon || sanPham.moTa || "Thông tin đang được cập nhật."
    ),
    diemNhanTrangBi: Array.isArray(diemNhanTrangBi)
      ? diemNhanTrangBi
      : xeMau?.diemNhanTrangBi ?? [],
    phienBan: Array.isArray(phienBan) && phienBan.length > 0
      ? phienBan
      : [{ ten, congSuat, gia }],
  };
}

export default function XeChiTietPage() {
  const { xeSlug } = useParams();
  const [ketQua, setKetQua] = useState({ id: null, xe: null, xeTuongTu: [], loi: "" });
  const [anhDangXem, setAnhDangXem] = useState(0);
  const dangTai = ketQua.id !== xeSlug;
  const xe = dangTai ? null : ketQua.xe;
  const loi = dangTai ? "" : ketQua.loi;

  useEffect(() => {
    let active = true;
    getSanPhamClientById(xeSlug)
      .then((result) => {
        if (!active) return;
        const xeMau = timXeTheoSlug(xeSlug);
        const xe = result?.sanPham
          ? taoXeTuSanPham(result.sanPham, xeMau, xeSlug)
          : null;
        const xeTuongTu = (result?.xeLienQuan ?? [])
          .map((sanPhamLienQuan) => taoXeTuSanPham(
            sanPhamLienQuan,
            null,
            sanPhamLienQuan.id
          ))
          .filter((xeLienQuan) => {
            if (!xe || String(xeLienQuan.id) === String(xe.id)) return false;
            if (xe.mauXeId && xeLienQuan.mauXeId) {
              return String(xe.mauXeId) === String(xeLienQuan.mauXeId);
            }
            return chuanHoaDongXe(xeLienQuan.dong) === chuanHoaDongXe(xe.dong);
          });
        setKetQua({
          id: xeSlug,
          xe,
          xeTuongTu,
          loi: "",
        });
      })
      .catch((error) => {
        if (active) {
          setKetQua({
            id: xeSlug,
            xe: null,
            xeTuongTu: [],
            loi: error.response?.data?.message || "Không tải được thông tin xe.",
          });
        }
      });

    return () => {
      active = false;
    };
  }, [xeSlug]);

  if (dangTai || loi) {
    return (
      <>
        <Navbar luonSang />
        <div className="min-h-screen bg-[#FAFAF9] px-12 pb-16 pt-[100px] text-sm text-[#63635E] max-[860px]:px-5 max-[860px]:pt-[84px]">
          {dangTai ? "Đang tải thông tin xe..." : null}
          {loi ? <p role="alert" className="text-red-700">{loi}</p> : null}
        </div>
        <Footer />
      </>
    );
  }

  if (!xe) return <Navigate to="/" replace />;

  const chuyenAnh = (huong) => {
    const tongSo = xe.anhGallery.length;
    setAnhDangXem((truoc) => (truoc + huong + tongSo) % tongSo);
  };
  const xeTuongTu = dangTai ? [] : ketQua.xeTuongTu;

  return (
    <>
      <Navbar luonSang />

      <div className="min-h-screen bg-[#FAFAF9] pb-16 pt-[84px] max-[860px]:pt-[84px]">
        {/* Breadcrumb */}
        <div className="border-b border-[#ECECE9] bg-[#F1F1EF] px-12 py-3 text-sm text-[#63635E] max-[860px]:px-5">
          <Link to="/" className="hover:text-[#17171A]">Trang chủ</Link>
          <span className="mx-2">›</span>
          <Link to="/#xe-dang-ban" className="hover:text-[#17171A]">Xe đang bán</Link>
          <span className="mx-2">›</span>
          <span className="text-[#17171A]">{xe.ten}</span>
        </div>

        <div className="grid grid-cols-1 gap-10 px-12 pt-8 lg:grid-cols-[1.3fr_1fr] max-[860px]:px-5">

          {/* === Gallery === */}
          <div>
            <div className="relative aspect-[16/10] overflow-hidden rounded-2xl border border-[#ECECE9] bg-[#F1F1EF]">
              <img
                src={xe.anhGallery[anhDangXem]}
                alt={xe.ten}
                className="h-full w-full object-cover"
                onError={(e) => (e.currentTarget.style.display = "none")}
              />

              <span className="absolute left-4 top-4 flex items-center gap-1.5 rounded-full bg-black/70 px-3 py-1.5 text-xs font-semibold text-white">
                <Crown className="h-3.5 w-3.5" />
                SHOWROOM CERTIFIED
              </span>

              {xe.nhan && (
                <span className="absolute right-4 top-4 rounded-full bg-[#B60F0F] px-3 py-1.5 text-xs font-semibold text-white">
                  {xe.nhan}
                </span>
              )}

              {xe.anhGallery.length > 1 && (
                <>
                  <button
                    onClick={() => chuyenAnh(-1)}
                    className="absolute left-3 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-black/50 text-white"
                  >
                    <ChevronLeft className="h-5 w-5" />
                  </button>
                  <button
                    onClick={() => chuyenAnh(1)}
                    className="absolute right-3 top-1/2 flex h-9 w-9 -translate-y-1/2 items-center justify-center rounded-full bg-black/50 text-white"
                  >
                    <ChevronRight className="h-5 w-5" />
                  </button>
                </>
              )}

              <span className="absolute bottom-4 left-4 rounded-full bg-black/60 px-3 py-1 text-xs font-semibold text-white">
                {String(anhDangXem + 1).padStart(2, "0")} / {String(xe.anhGallery.length).padStart(2, "0")}
              </span>

              {xe.coVideoGioiThieu && (
                <button className="absolute bottom-4 right-4 flex items-center gap-1.5 rounded-full bg-black/60 px-3 py-1.5 text-xs font-semibold text-white">
                  <Play className="h-3.5 w-3.5" />
                  Video giới thiệu
                </button>
              )}
            </div>

            {xe.anhGallery.length > 1 && (
              <div className="mt-3 flex gap-2.5">
                {xe.anhGallery.map((anh, i) => (
                  <button
                    key={i}
                    onClick={() => setAnhDangXem(i)}
                    className={`h-16 w-24 shrink-0 overflow-hidden rounded-lg border-2 ${
                      anhDangXem === i ? "border-[#B60F0F]" : "border-transparent"
                    }`}
                  >
                    <img src={anh} className="h-full w-full object-cover" onError={(e) => (e.currentTarget.style.display = "none")} />
                  </button>
                ))}
              </div>
            )}
          </div>

          {/* === Info panel === */}
          <div className="rounded-2xl border border-[#ECECE9] bg-white p-7">
            <div className="mb-4 flex flex-wrap items-center gap-2">
              <span className="flex items-center gap-1.5 rounded-full bg-[#F1F1EF] px-3 py-1 text-xs font-semibold text-[#17171A]">
                <span className="h-1.5 w-1.5 rounded-full bg-[#2FA84F]" />
                {xe.trangThai} tại showroom
              </span>
              {xe.nhan && (
                <span className="rounded-full bg-[#FBF3E4] px-3 py-1 text-xs font-semibold text-[#8A6D1F]">
                  {xe.nhan}
                </span>
              )}
            </div>

            <div className="mb-1 text-xs font-semibold text-[#8A8A85]">
              PORSCHE {xe.dong} · {xe.nam}
            </div>
            <h1 className="mb-5 text-3xl font-extrabold tracking-[-0.3px]">{xe.ten}</h1>

            <div className="mb-5 flex items-end justify-between border-y border-[#ECECE9] py-5">
              <div>
                <div className="mb-1 text-xs text-[#8A8A85]">Giá niêm yết</div>
                <div className="text-[28px] font-extrabold text-[#B60F0F]">{xe.gia}</div>
              </div>
              <div className="text-right">
                <div className="mb-1 text-xs text-[#8A8A85]">Số km đã đi</div>
                <div className="text-base font-bold">{xe.soKmDaDi}</div>
              </div>
            </div>

            <div className="mb-6 grid grid-cols-3 gap-3">
              <div className="rounded-xl border border-[#ECECE9] p-3.5 text-center">
                <Gauge className="mx-auto mb-2 h-5 w-5 text-[#8A8A85]" />
                <div className="text-xs text-[#8A8A85]">Công suất</div>
                <div className="text-sm font-bold">{xe.congSuat}</div>
              </div>
              <div className="rounded-xl border border-[#ECECE9] p-3.5 text-center">
                <Timer className="mx-auto mb-2 h-5 w-5 text-[#8A8A85]" />
                <div className="text-xs text-[#8A8A85]">0–100 km/h</div>
                <div className="text-sm font-bold">{xe.toc0_100}</div>
              </div>
              <div className="rounded-xl border border-[#ECECE9] p-3.5 text-center">
                <Zap className="mx-auto mb-2 h-5 w-5 text-[#8A8A85]" />
                <div className="text-xs text-[#8A8A85]">Tốc độ tối đa</div>
                <div className="text-sm font-bold">{xe.tocDoToiDa}</div>
              </div>
            </div>

            <button className="mb-3 flex w-full items-center justify-center gap-2 rounded-lg bg-[#B60F0F] py-3.5 text-sm font-semibold text-white">
              <CalendarCheck className="h-4 w-4" />
              Đặt lịch lái thử
            </button>

            <button className="mb-3 flex w-full items-center justify-center gap-2 rounded-lg border border-[#ECECE9] py-3.5 text-sm font-semibold text-[#17171A]">
              <Phone className="h-4 w-4" />
              Hotline 0900 123 456
            </button>

            <div className="mb-5 flex items-start gap-2 text-xs text-[#8A8A85]">
              <ShieldCheck className="mt-0.5 h-4 w-4 shrink-0 text-[#2FA84F]" />
              Bảo hành chính hãng, hồ sơ xuất xưởng minh bạch
            </div>

            <div className="flex items-start gap-2 rounded-lg bg-[#F1F1EF] p-3 text-xs text-[#4A4A46]">
              <Crown className="mt-0.5 h-4 w-4 shrink-0" />
              Tặng gói kiểm tra kỹ thuật 111 điểm trước bàn giao
            </div>

            <button className="mt-4 flex w-full items-center justify-between rounded-lg bg-[#17171A] px-5 py-3.5 text-sm font-semibold text-white">
              Cần tư vấn cấu hình?
              <span>Nhận báo giá →</span>
            </button>
          </div>
        </div>

        {/* === Thông số vận hành (nền đen) === */}
        <div className="mt-14 bg-[#0A0A0B] px-12 py-14 text-white max-[860px]:px-5">
          <div className="mb-1 text-xs font-bold uppercase tracking-wide text-[#B60F0F]">
            Thông số vận hành
          </div>
          <h2 className="mb-8 text-3xl font-extrabold tracking-[-0.3px]">
            Hiệu năng của {xe.ten}
          </h2>

          <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
            {[
              { icon: Gauge, nhan: "Công suất cực đại", giaTri: xe.congSuat },
              { icon: Zap, nhan: "Tăng tốc 0–100 km/h", giaTri: xe.toc0_100 },
              { icon: Timer, nhan: "Tốc độ tối đa", giaTri: xe.tocDoToiDa },
              { icon: Cog, nhan: "Hộp số", giaTri: xe.hopSo },
              { icon: Car, nhan: "Hệ dẫn động", giaTri: xe.heDanDong },
              { icon: Fuel, nhan: "Nhiên liệu", giaTri: xe.nhienLieu },
            ].map(({ icon: Icon, nhan, giaTri }) => (
              <div key={nhan} className="rounded-xl border border-white/10 p-4">
                <Icon className="mb-3 h-5 w-5 text-[#9A9992]" />
                <div className="mb-1 text-xs text-[#9A9992]">{nhan}</div>
                <div className="text-base font-bold">{giaTri}</div>
              </div>
            ))}
          </div>
        </div>

        {/* === Tổng quan + Thông tin nhanh === */}
        <div className="grid grid-cols-1 gap-10 px-12 pt-14 lg:grid-cols-[1.3fr_1fr] max-[860px]:px-5">
          <div>
            <div className="mb-1 text-xs font-bold uppercase tracking-wide text-[#B60F0F]">
              Tổng quan
            </div>
            <h2 className="mb-4 text-2xl font-extrabold tracking-[-0.3px]">
              Vì sao nên chọn chiếc {xe.ten} này
            </h2>
            <p className="mb-8 text-[15px] leading-[1.7] text-[#63635E]">{xe.lyDoChon}</p>

            <div className="mb-2 text-sm font-bold">Điểm nhấn trang bị</div>
            <div className="grid grid-cols-1 gap-3 sm:grid-cols-2">
              {xe.diemNhanTrangBi.map((diem) => (
                <div key={diem} className="flex items-center gap-3 rounded-xl bg-[#F1F1EF] px-4 py-3.5">
                  <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-[#8A6D1F]/15 text-[#8A6D1F]">
                    <Check className="h-3.5 w-3.5" />
                  </span>
                  <span className="text-sm font-semibold">{diem}</span>
                </div>
              ))}
            </div>
          </div>

          <div className="rounded-2xl border border-[#ECECE9] bg-white p-7">
            <div className="mb-5 text-sm font-bold">Thông tin nhanh</div>

            <div className="flex flex-col gap-4">
              <div className="flex items-center justify-between border-b border-[#ECECE9] pb-4">
                <span className="flex items-center gap-2.5 text-sm text-[#63635E]">
                  <Palette className="h-4 w-4" /> Ngoại thất
                </span>
                <span className="text-sm font-semibold">{xe.ngoaiThat}</span>
              </div>
              <div className="flex items-center justify-between border-b border-[#ECECE9] pb-4">
                <span className="flex items-center gap-2.5 text-sm text-[#63635E]">
                  <Armchair className="h-4 w-4" /> Nội thất
                </span>
                <span className="text-sm font-semibold">{xe.noiThat}</span>
              </div>
              <div className="flex items-center justify-between border-b border-[#ECECE9] pb-4">
                <span className="flex items-center gap-2.5 text-sm text-[#63635E]">
                  <Car className="h-4 w-4" /> Dòng xe
                </span>
                <span className="text-sm font-semibold">Porsche {xe.dong}</span>
              </div>
              <div className="flex items-center justify-between">
                <span className="flex items-center gap-2.5 text-sm text-[#63635E]">
                  <CalendarCheck className="h-4 w-4" /> Năm sản xuất
                </span>
                <span className="text-sm font-semibold">{xe.nam}</span>
              </div>
            </div>

            <div className="mt-6 rounded-xl border border-[#E9D9AE] bg-[#FBF3E4] p-5">
              <div className="mb-2 flex items-center gap-2 text-sm font-bold text-[#8A6D1F]">
                <Crown className="h-4 w-4" /> Kiểm định 111 điểm
              </div>
              <p className="mb-4 text-xs leading-[1.6] text-[#8A6D1F]">
                Mỗi xe đều trải qua quy trình kiểm tra 111 hạng mục trước khi bàn giao, kèm báo cáo
                tình trạng minh bạch.
              </p>
              <button className="flex w-full items-center justify-center gap-2 rounded-lg bg-[#8A6D1F] py-3 text-sm font-semibold text-white">
                <FileText className="h-4 w-4" />
                Yêu cầu báo cáo kiểm định
              </button>
            </div>
          </div>
        </div>

        {/* === Phiên bản & giá === */}
        <div className="mt-14 bg-[#F1F1EF] px-12 py-14 max-[860px]:px-5">
          <div className="mb-1 text-xs font-bold uppercase tracking-wide text-[#B60F0F]">
            Phiên bản & giá
          </div>
          <h2 className="mb-3 text-2xl font-extrabold tracking-[-0.3px]">Chọn phiên bản phù hợp</h2>
          <p className="mb-8 max-w-[560px] text-sm leading-[1.7] text-[#63635E]">
            Giá đã bao gồm thuế trước bạ theo quy định. Chi phí đăng ký, biển số và bảo hiểm sẽ
            được tư vấn chi tiết.
          </p>

          <div className="overflow-hidden rounded-2xl border border-[#ECECE9] bg-white">
            <div className="grid grid-cols-[2fr_1fr_1.2fr_1fr] gap-4 border-b border-[#ECECE9] bg-[#FAFAF9] px-6 py-3 text-xs font-bold uppercase tracking-wide text-[#8A8A85] max-[860px]:hidden">
              <div>Phiên bản</div>
              <div>Công suất</div>
              <div>Giá niêm yết</div>
              <div className="text-right">Hành động</div>
            </div>

            {xe.phienBan.map((pb) => (
              <div
                key={pb.ten}
                className="grid grid-cols-1 items-center gap-3 px-6 py-5 max-[860px]:gap-2 md:grid-cols-[2fr_1fr_1.2fr_1fr]"
              >
                <div className="flex items-center gap-2 font-bold">
                  {pb.ten}
                </div>
                <div className="text-sm text-[#63635E]">{pb.congSuat}</div>
                <div className="text-lg font-extrabold text-[#B60F0F]">{pb.gia}</div>
                <button className="flex items-center justify-center gap-1.5 rounded-lg bg-[#17171A] px-4 py-2.5 text-sm font-semibold text-white md:justify-self-end">
                  <CalendarCheck className="h-4 w-4" />
                  Đặt xe phiên bản này
                </button>
              </div>
            ))}
          </div>
        </div>

        {/* === Hồ sơ kỹ thuật đầy đủ === */}
        <div className="px-12 pt-14 max-[860px]:px-5">
          <div className="mb-1 text-xs font-bold uppercase tracking-wide text-[#B60F0F]">
            Thông số kỹ thuật
          </div>
          <h2 className="mb-3 text-2xl font-extrabold tracking-[-0.3px]">Hồ sơ kỹ thuật đầy đủ</h2>
          <p className="mb-8 max-w-[640px] text-sm leading-[1.7] text-[#63635E]">
            Toàn bộ thông số dưới đây được đối chiếu với hồ sơ xuất xưởng của xe và cập nhật theo
            tình trạng thực tế tại showroom.
          </p>

          <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div className="rounded-2xl border border-[#ECECE9] bg-white p-6">
              <div className="mb-4 flex items-center gap-2.5 font-bold">
                <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-[#17171A] text-white">
                  <Gauge className="h-4 w-4" />
                </span>
                Hiệu năng & vận hành
              </div>

              {[
                ["Công suất cực đại", xe.congSuat],
                ["Tăng tốc 0–100 km/h", xe.toc0_100],
                ["Tốc độ tối đa", xe.tocDoToiDa],
                ["Hộp số", xe.hopSo],
                ["Hệ dẫn động", xe.heDanDong],
                ["Nhiên liệu", xe.nhienLieu],
              ].map(([nhan, giaTri]) => (
                <div key={nhan} className="flex items-center justify-between border-b border-[#F1F1EF] py-3 text-sm last:border-0">
                  <span className="text-[#63635E]">{nhan}</span>
                  <span className="font-semibold">{giaTri}</span>
                </div>
              ))}
            </div>

            <div className="rounded-2xl border border-[#ECECE9] bg-white p-6">
              <div className="mb-4 flex items-center gap-2.5 font-bold">
                <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-[#17171A] text-white">
                  <Car className="h-4 w-4" />
                </span>
                Nhận diện & tình trạng
              </div>

              {[
                ["Dòng xe", `Porsche ${xe.dong}`],
                ["Năm sản xuất", xe.nam],
                ["Màu ngoại thất", xe.ngoaiThat],
                ["Chất liệu nội thất", xe.noiThat],
                ["Số km đã đi", xe.soKmDaDi],
                ["Tình trạng", xe.tinhTrang],
              ].map(([nhan, giaTri]) => (
                <div key={nhan} className="flex items-center justify-between border-b border-[#F1F1EF] py-3 text-sm last:border-0">
                  <span className="text-[#63635E]">{nhan}</span>
                  <span className="font-semibold">{giaTri}</span>
                </div>
              ))}
            </div>
          </div>

          <div className="mt-5 flex items-start gap-2 text-xs text-[#8A8A85]">
            <ShieldCheck className="mt-0.5 h-4 w-4 shrink-0" />
            Một số trang bị có thể thay đổi theo từng lô xe. Vui lòng liên hệ showroom để xác nhận
            cấu hình chính xác của xe trước khi quyết định.
          </div>
        </div>

        {xeTuongTu.length > 0 ? (
          <section className="mt-14 bg-[#F1F1EF] px-12 py-14 max-[860px]:px-5">
            <div className="mb-8">
              <div className="mb-1 text-xs font-bold uppercase tracking-wide text-[#B60F0F]">
                Gợi ý thêm
              </div>
              <h2 className="mb-2 text-2xl font-extrabold tracking-[-0.3px]">
                Xe tương tự tại showroom
              </h2>
              <p className="text-sm leading-[1.7] text-[#63635E]">
                Những mẫu Porsche {xe.dong} khác cùng dòng đang có tại showroom.
              </p>
            </div>

            <div className="grid grid-cols-1 gap-5 md:grid-cols-2 xl:grid-cols-3">
              {xeTuongTu.map((item) => (
                <Link
                  key={item.id}
                  to={`/xe-dang-ban/${item.id}`}
                  className="group flex flex-col overflow-hidden rounded-2xl border border-[#ECECE9] bg-white shadow-sm transition-shadow hover:shadow-lg"
                >
                  <div className="relative aspect-[4/3] overflow-hidden bg-[#F1F1EF]">
                    {item.anh ? (
                      <img
                        src={item.anh}
                        alt={item.ten}
                        className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
                        onError={(event) => { event.currentTarget.style.display = "none"; }}
                      />
                    ) : null}
                    <span className="absolute left-3.5 top-3.5 rounded-full bg-white/90 px-3 py-1 text-xs font-semibold text-[#17171A]">
                      <span className="mr-1.5 inline-block h-1.5 w-1.5 rounded-full bg-[#2FA84F]" />
                      {item.trangThai}
                    </span>
                    {item.nhan ? (
                      <span className="absolute left-3.5 top-[50px] rounded-full bg-[#17171A] px-3 py-1 text-xs text-white">
                        {item.nhan}
                      </span>
                    ) : null}
                  </div>
                  <div className="flex flex-1 flex-col p-5">
                    <div className="mb-1 text-xs font-semibold tracking-[0.3px] text-[#8A8A85]">
                      PORSCHE {item.dong} · {item.nam}
                    </div>
                    <div className="mb-3 font-bold">{item.ten}</div>
                    <div className="grid grid-cols-3 gap-2 border-y border-[#ECECE9] py-3">
                      {[
                        ["Công suất", item.congSuat],
                        ["0–100 km/h", item.toc0_100],
                        ["Tốc độ tối đa", item.tocDoToiDa],
                      ].map(([nhan, giaTri]) => (
                        <div key={nhan}>
                          <div className="text-xs text-[#8A8A85]">{nhan}</div>
                          <div className="mt-1 text-sm font-semibold">{giaTri}</div>
                        </div>
                      ))}
                    </div>
                    <div className="mt-3 flex items-end justify-between gap-3">
                      <div>
                        <div className="text-xs text-[#8A8A85]">Giá niêm yết từ</div>
                        <div className="font-extrabold text-[#B60F0F]">{item.gia}</div>
                      </div>
                      <span className="shrink-0 rounded-lg bg-[#17171A] px-3 py-2 text-xs font-semibold text-white">
                        Xem chi tiết →
                      </span>
                    </div>
                  </div>
                </Link>
              ))}
            </div>
          </section>
        ) : null}

      </div>

      <Footer />
    </>
  );
}