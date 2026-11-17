import { useEffect, useState } from "react";
import { useParams, Link, Navigate } from "react-router-dom";
import {
  Car, ChevronLeft, ChevronRight, CalendarCheck,
  Phone, ShieldCheck, Crown, Palette, FileText,
} from "lucide-react";
import Navbar from "../../components/layouts/partials/Navbar";
import Breadcrumb from "../../components/common/Breadcrumb";
import Footer from "../../components/layouts/partials/Footer";
import { getSanPhamClientById } from "../../api/PublicApi";
import { HOTLINE_HIEN_THI, HOTLINE_LINK } from "../../constants/shop";
import ReviewsSection from "../../components/reviews/ReviewsSection";

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

function chuanHoaMaMau(maMau) {
  if (typeof maMau !== "string") return "";
  const maMauChuanHoa = maMau.trim().startsWith("#") ? maMau.trim() : `#${maMau.trim()}`;
  return /^#(?:[\da-f]{3}|[\da-f]{6})$/i.test(maMauChuanHoa) ? maMauChuanHoa : "";
}

function dinhDangGiaTri(giaTri) {
  if (giaTri === null || giaTri === undefined || giaTri === "") return "Đang cập nhật";
  if (typeof giaTri === "boolean") return giaTri ? "Có" : "Không";
  if (typeof giaTri === "object") return JSON.stringify(giaTri);
  return String(giaTri);
}

function dinhDangNgay(giaTri) {
  if (!giaTri) return "Đang cập nhật";
  const ngay = new Date(giaTri);
  return Number.isNaN(ngay.getTime()) ? String(giaTri) : ngay.toLocaleString("vi-VN");
}

const TEN_THONG_SO = {
  dongxe: "Dòng xe",
  doixe: "Đời xe",
  dongco: "Động cơ",
  congsuat: "Công suất cực đại",
  momenxoan: "Mô-men xoắn",
  tangtoc0100: "Tăng tốc 0–100 km/h",
  tocdotoida: "Tốc độ tối đa",
  hopso: "Hộp số",
  hedanhdong: "Hệ dẫn động",
  nhienlieu: "Nhiên liệu",
  dungluongpin: "Dung lượng pin",
  dienap: "Điện áp",
  quangduongwltp: "Quãng đường WLTP",
  sacdctoida: "Công suất sạc DC",
};

const KHOA_SAN_PHAM_DA_HIEN_THI = new Set([
  "id", "ten", "mau_xe_id", "mauXeId", "mau_xe", "mauXe", "gia", "giaSo",
  "so_luong", "soLuong", "loai_san_pham", "loaiSanPham", "trang_thai", "slug",
  "mo_ta", "moTa", "created_at", "updated_at", "thong_so_chi_tiet", "thongSoChiTiet",
  "mau_sacs", "mauSacs", "anh_san_phams", "anhSanPhams", "anh_360_gallery",
  "anh360Gallery", "anhGallery", "anh",
]);

function taoXeTuSanPham(sanPham) {
  const soLuong = Number(sanPham.soLuong) || 0;
  const thongSoChiTiet = sanPham.thongSoChiTiet ?? {};
  const anhGallery = sanPham.anhGallery ?? [];

  return {
    id: sanPham.id,
    slug: sanPham.slug,
    ten: sanPham.ten,
    dong: sanPham.mauXe,
    mauXeId: sanPham.mauXeId,
    gia: sanPham.gia,
    soLuong,
    loaiSanPham: dinhDangGiaTri(sanPham.loaiSanPham),
    trangThai: soLuong > 0 ? "Còn hàng" : "Hết hàng",
    moTa: dinhDangGiaTri(sanPham.moTa),
    ngayTao: dinhDangNgay(sanPham.created_at),
    ngayCapNhat: dinhDangNgay(sanPham.updated_at),
    anh: anhGallery[0] ?? "",
    anhGallery,
    anh360Gallery: sanPham.anh360Gallery ?? [],
    mauSacs: sanPham.mauSacs ?? [],
    thongSoChiTiet: Object.entries(thongSoChiTiet).map(([khoa, giaTri]) => ({
      nhan: TEN_THONG_SO[chuanHoaTen(khoa)] ?? khoa,
      giaTri: dinhDangGiaTri(giaTri),
    })),
    thongTinKhac: Object.entries(sanPham)
      .filter(([khoa, giaTri]) => (
        !KHOA_SAN_PHAM_DA_HIEN_THI.has(khoa)
        && (giaTri === null || typeof giaTri === "string" || typeof giaTri === "number" || typeof giaTri === "boolean")
      ))
      .map(([khoa, giaTri]) => ({
        nhan: khoa.replace(/_/g, " ").replace(/^./, (chu) => chu.toUpperCase()),
        giaTri: dinhDangGiaTri(giaTri),
      })),
  };
}


export default function XeChiTietPage() {
  const { xeSlug } = useParams();
  const [ketQua, setKetQua] = useState({ id: null, xe: null, xeTuongTu: [], loi: "" });
  const [anhDangXem, setAnhDangXem] = useState(0);
  const [dangXem360, setDangXem360] = useState(false);
  const dangTai = ketQua.id !== xeSlug;
  const xe = dangTai ? null : ketQua.xe;
  const loi = dangTai ? "" : ketQua.loi;
  

  useEffect(() => {
    let active = true;
    getSanPhamClientById(xeSlug)
      .then((result) => {
        if (!active) return;
        setAnhDangXem(0);
        setDangXem360(false);
        const trangThaiSanPham = result?.sanPham?.trang_thai ?? result?.sanPham?.trangThai;
        const dangKinhDoanh = trangThaiSanPham === undefined
          ? Number(result?.sanPham?.soLuong) > 0
          : String(trangThaiSanPham) === "1";
        const xe = result?.sanPham && dangKinhDoanh
          ? taoXeTuSanPham(result.sanPham)
          : null;
        const xeTuongTu = (result?.xeLienQuan ?? [])
          .map(taoXeTuSanPham)
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
        <div className="min-h-screen bg-[#FAFAF9] px-[1.3rem] pb-16 pt-[96px] text-[13px] text-[#63635E] max-[860px]:pt-[80px]">
          {dangTai ? "Đang tải thông tin xe..." : null}
          {loi ? <p role="alert" className="text-red-700">{loi}</p> : null}
        </div>
        <Footer />
      </>
    );
  }

  if (!xe) return <Navigate to="/" replace />;

  const danhSachAnhDangXem = dangXem360 && xe.anh360Gallery.length
    ? xe.anh360Gallery
    : xe.anhGallery;
  const chuyenAnh = (huong) => {
    const tongSo = danhSachAnhDangXem.length;
    setAnhDangXem((truoc) => (truoc + huong + tongSo) % tongSo);
  };
  const xeTuongTu = dangTai ? [] : ketQua.xeTuongTu.slice(0, 4);

  return (
    <>
      <Navbar luonSang />

      <div className="min-h-screen bg-[#FAFAF9] pb-10 pt-[80px] max-[860px]:pt-[80px]">
        {/* Breadcrumb */}
        <Breadcrumb
          items={[
            { label: "Trang chủ", to: "/" },
            { label: "Xe đang bán", to: "/#xe-dang-ban" },
            { label: xe.ten },
          ]}
        />

        <div className="grid grid-cols-1 gap-6 px-[1.3rem] pt-5 lg:grid-cols-[1.3fr_1fr]">

          {/* === Gallery === */}
          <div>
            <div className="relative aspect-[16/10] overflow-hidden rounded-2xl border border-[#ECECE9] bg-[#F1F1EF]">
              <img
                src={danhSachAnhDangXem[anhDangXem]}
                alt={xe.ten}
                className="h-full w-full object-cover"
                onError={(e) => (e.currentTarget.style.display = "none")}
              />

              <span className="absolute left-4 top-4 flex items-center gap-1.5 rounded-full bg-black/70 px-3 py-1.5 text-[11px] font-semibold text-white">
                <Crown className="h-3.5 w-3.5" />
                SHOWROOM CERTIFIED
              </span>

              {danhSachAnhDangXem.length > 1 && (
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

              <span className="absolute bottom-4 left-4 rounded-full bg-black/60 px-3 py-1 text-[11px] font-semibold text-white">
                {String(anhDangXem + 1).padStart(2, "0")} / {String(danhSachAnhDangXem.length).padStart(2, "0")}
              </span>

              {xe.anh360Gallery.length > 0 ? (
                <button
                  type="button"
                  onClick={() => { setDangXem360((value) => !value); setAnhDangXem(0); }}
                  className="absolute bottom-4 right-4 rounded-full bg-black/70 px-3 py-1.5 text-[11px] font-semibold text-white"
                >
                  {dangXem360 ? "Ảnh thường" : "Xem ảnh 360°"}
                </button>
              ) : null}
            </div>

            {danhSachAnhDangXem.length > 1 && (
              <div className="mt-3 flex gap-2.5">
                {danhSachAnhDangXem.map((anh, i) => (
                  <button
                    key={i}
                    onClick={() => setAnhDangXem(i)}
                    aria-label={`Xem ảnh ${i + 1}`}
                    className={`h-16 w-24 shrink-0 overflow-hidden rounded-lg border-2 ${
                      anhDangXem === i ? "border-[#AC0021]" : "border-transparent"
                    }`}
                  >
                    <img src={anh} alt={`${xe.ten} - ảnh ${i + 1}`} className="h-full w-full object-cover" onError={(e) => (e.currentTarget.style.display = "none")} />
                  </button>
                ))}
              </div>
            )}
          </div>

          {/* === Info panel === */}
          <div className="rounded-2xl border border-[#ECECE9] bg-white p-5">
            <div className="mb-4 flex flex-wrap items-center gap-2">
              <span className="flex items-center gap-1.5 rounded-full bg-[#F1F1EF] px-3 py-1 text-[11px] font-semibold text-[#17171A]">
                <span className="h-1.5 w-1.5 rounded-full bg-[#2FA84F]" />
                {xe.trangThai} tại showroom
              </span>
              {xe.loaiSanPham !== "Đang cập nhật" && (
                <span className="rounded-full bg-[#FBF3E4] px-3 py-1 text-[11px] font-semibold text-[#8A6D1F]">
                  {xe.loaiSanPham}
                </span>
              )}
            </div>

            <div className="mb-1 text-[11px] font-semibold text-[#8A8A85]">
              PORSCHE {xe.dong}
            </div>
            <h1 className="mb-5 text-[26px] font-extrabold tracking-[-0.3px]">{xe.ten}</h1>

            <div className="mb-5 flex items-end justify-between border-y border-[#ECECE9] py-5">
              <div>
                <div className="mb-1 text-[11px] text-[#8A8A85]">Giá niêm yết</div>
                <div className="text-[24px] font-extrabold text-[#AC0021]">{xe.gia}</div>
              </div>
              <div className="text-right">
                <div className="mb-1 text-[11px] text-[#8A8A85]">Số lượng</div>
                <div className="text-[14px] font-bold">{xe.soLuong}</div>
              </div>
            </div>

            <div className="mb-6 grid grid-cols-2 gap-3">
              <div className="rounded-xl border border-[#ECECE9] p-3.5 text-center">
                <Car className="mx-auto mb-2 h-5 w-5 text-[#8A8A85]" />
                <div className="text-[11px] text-[#8A8A85]">Dòng xe</div>
                <div className="text-[13px] font-bold">{xe.dong}</div>
              </div>
              <div className="rounded-xl border border-[#ECECE9] p-3.5 text-center">
                <CalendarCheck className="mx-auto mb-2 h-5 w-5 text-[#8A8A85]" />
                <div className="text-[11px] text-[#8A8A85]">Ngày nhập</div>
                <div className="text-[13px] font-bold">{xe.ngayTao}</div>
              </div>
            </div>

            <Link
              to="/#dang-ky-lai-thu"
              className="mb-3 flex w-full items-center justify-center gap-2 rounded-lg bg-[#AC0021] py-3.5 text-[13px] font-semibold text-white"
            >
              <CalendarCheck className="h-4 w-4" />
              Đặt lịch lái thử
            </Link>

            <a
              href={HOTLINE_LINK}
              className="mb-3 flex w-full items-center justify-center gap-2 rounded-lg border border-[#ECECE9] py-3.5 text-[13px] font-semibold text-[#17171A]"
            >
              <Phone className="h-4 w-4" />
              Hotline {HOTLINE_HIEN_THI}
            </a>

            <p className="mb-3 text-[11px] leading-[1.6] text-[#8A8A85]">
              Xe không bán trực tuyến. Vui lòng gọi hotline để trao đổi trực tiếp với nhân viên tư vấn.
            </p>

            <div className="mb-5 flex items-start gap-2 text-[11px] text-[#8A8A85]">
              <ShieldCheck className="mt-0.5 h-4 w-4 shrink-0 text-[#2FA84F]" />
              Bảo hành chính hãng, hồ sơ xuất xưởng minh bạch
            </div>

            <div className="flex items-start gap-2 rounded-lg bg-[#F1F1EF] p-3 text-[11px] text-[#4A4A46]">
              <Crown className="mt-0.5 h-4 w-4 shrink-0" />
              Tặng gói kiểm tra kỹ thuật 111 điểm trước bàn giao
            </div>

            <button className="mt-4 flex w-full items-center justify-between rounded-lg bg-[#17171A] px-5 py-3.5 text-[13px] font-semibold text-white">
              Cần tư vấn cấu hình?
              <span>Nhận báo giá →</span>
            </button>
          </div>
        </div>

        {/* === Thông số kỹ thuật (nền đen) === */}
        <div className="mt-10 bg-[#0A0A0B] px-[1.3rem] py-8 text-white">
          <div className="mb-1 text-[11px] font-bold uppercase tracking-wide text-[#AC0021]">
            Thông số kỹ thuật
          </div>
          <h2 className="mb-5 text-[26px] font-extrabold tracking-[-0.3px]">
            Hiệu năng của {xe.ten}
          </h2>

          {xe.thongSoChiTiet.length > 0 ? (
            <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
              {xe.thongSoChiTiet.map(({ nhan, giaTri }) => (
                <div key={nhan} className="rounded-xl border border-white/10 p-4">
                  <div className="mb-1 text-[11px] text-[#9A9992]">{nhan}</div>
                  <div className="text-[14px] font-bold">{giaTri}</div>
                </div>
              ))}
            </div>
          ) : (
            <p className="text-[13px] text-[#9A9992]">Đang cập nhật thông số kỹ thuật.</p>
          )}
        </div>

        {/* === Tổng quan + Thông tin nhanh === */}
        <div className="grid grid-cols-1 gap-10 px-[1.3rem] pt-14 lg:grid-cols-[1.3fr_1fr]">
          <div>
            <div className="mb-1 text-[11px] font-bold uppercase tracking-wide text-[#AC0021]">
              Tổng quan
            </div>
            <h2 className="mb-4 text-[20px] font-extrabold tracking-[-0.3px]">
              Mô tả sản phẩm {xe.ten}
            </h2>
            <p className="mb-5 whitespace-pre-line text-[13px] leading-[1.7] text-[#63635E]">{xe.moTa}</p>
          </div>

          <div className="rounded-2xl border border-[#ECECE9] bg-white p-5">
            <div className="mb-5 text-[13px] font-bold">Thông tin nhanh</div>

            <div className="flex flex-col gap-4">
              <div className="flex items-center justify-between border-b border-[#ECECE9] pb-4">
                <span className="flex items-center gap-2.5 text-[13px] text-[#63635E]">
                  <Car className="h-4 w-4" /> Dòng xe
                </span>
                <span className="text-[13px] font-semibold">{xe.dong}</span>
              </div>
              <div className="flex items-center justify-between border-b border-[#ECECE9] pb-4">
                <span className="flex items-center gap-2.5 text-[13px] text-[#63635E]">
                  <Palette className="h-4 w-4" /> Màu sắc
                </span>
                <span className="text-[13px] font-semibold">
                  {xe.mauSacs.map((mau) => mau.ten).join(", ") || "Đang cập nhật"}
                </span>
              </div>
              <div className="flex items-center justify-between border-b border-[#ECECE9] pb-4">
                <span className="flex items-center gap-2.5 text-[13px] text-[#63635E]">
                  <CalendarCheck className="h-4 w-4" /> Ngày nhập
                </span>
                <span className="text-[13px] font-semibold">{xe.ngayTao}</span>
              </div>
              <div className="flex items-center justify-between">
                <span className="flex items-center gap-2.5 text-[13px] text-[#63635E]">
                  <CalendarCheck className="h-4 w-4" /> Cập nhật lần cuối
                </span>
                <span className="text-[13px] font-semibold">{xe.ngayCapNhat}</span>
              </div>
            </div>

            {xe.mauSacs.length > 0 ? (
              <div className="mt-5 border-t border-[#ECECE9] pt-4">
                <div className="mb-3 text-[13px] font-bold">Màu sơn & mã màu</div>
                <div className="flex flex-wrap gap-3">
                  {xe.mauSacs.map((mau, index) => {
                    const maMau = chuanHoaMaMau(mau.maMau);
                    return (
                      <div key={`${mau.ten}-${index}`} className="flex items-center gap-2 rounded-lg bg-[#F1F1EF] px-3 py-2">
                        <span
                          aria-hidden="true"
                          className="h-5 w-5 rounded-full border border-black/15"
                          style={maMau ? { backgroundColor: maMau } : undefined}
                        />
                        <span className="text-[11px] font-semibold">{mau.ten}</span>
                        {maMau ? <code className="text-[11px] text-[#63635E]">{maMau.toUpperCase()}</code> : null}
                      </div>
                    );
                  })}
                </div>
              </div>
            ) : null}

            <div className="mt-6 rounded-xl border border-[#E9D9AE] bg-[#FBF3E4] p-4">
              <div className="mb-2 flex items-center gap-2 text-[13px] font-bold text-[#8A6D1F]">
                <Crown className="h-4 w-4" /> Kiểm định 111 điểm
              </div>
              <p className="mb-4 text-[11px] leading-[1.6] text-[#8A6D1F]">
                Mỗi xe đều trải qua quy trình kiểm tra 111 hạng mục trước khi bàn giao, kèm báo cáo
                tình trạng minh bạch.
              </p>
              <button className="flex w-full items-center justify-center gap-2 rounded-lg bg-[#8A6D1F] py-3 text-[13px] font-semibold text-white">
                <FileText className="h-4 w-4" />
                Yêu cầu báo cáo kiểm định
              </button>
            </div>
          </div>
        </div>

        {/* === Hồ sơ kỹ thuật đầy đủ === */}
        <div className="px-[1.3rem] pt-14">
          <div className="mb-1 text-[11px] font-bold uppercase tracking-wide text-[#AC0021]">
            Thông số kỹ thuật
          </div>
          <h2 className="mb-3 text-[20px] font-extrabold tracking-[-0.3px]">Hồ sơ kỹ thuật đầy đủ</h2>
          <p className="mb-5 max-w-[640px] text-[13px] leading-[1.7] text-[#63635E]">
            Toàn bộ thông số dưới đây được đối chiếu với hồ sơ xuất xưởng của xe và cập nhật theo
            tình trạng thực tế tại showroom.
          </p>

          <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
            <div className="rounded-2xl border border-[#ECECE9] bg-white p-5">
              <div className="mb-4 flex items-center gap-2.5 font-bold">
                <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-[#17171A] text-white">
                  <Car className="h-4 w-4" />
                </span>
                Thông tin sản phẩm
              </div>

              {[
                ["Mã sản phẩm", xe.id],
                ["Tên sản phẩm", xe.ten],
                ["Dòng xe", xe.dong],
                ["Mã mẫu xe", xe.mauXeId],
                ["Giá niêm yết", xe.gia],
                ["Số lượng", xe.soLuong],
                ["Loại sản phẩm", xe.loaiSanPham],
                ["Trạng thái", xe.trangThai],
                ["Slug", xe.slug],
                ["Ngày nhập", xe.ngayTao],
                ["Cập nhật lần cuối", xe.ngayCapNhat],
                ...xe.thongTinKhac.map(({ nhan, giaTri }) => [nhan, giaTri]),
              ].map(([nhan, giaTri]) => (
                <div key={nhan} className="flex items-center justify-between gap-4 border-b border-[#F1F1EF] py-3 text-[13px] last:border-0">
                  <span className="text-[#63635E]">{nhan}</span>
                  <span className="text-right font-semibold">{dinhDangGiaTri(giaTri)}</span>
                </div>
              ))}
            </div>

            <div className="rounded-2xl border border-[#ECECE9] bg-white p-5">
              <div className="mb-4 flex items-center gap-2.5 font-bold">
                <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-[#17171A] text-white">
                  <Palette className="h-4 w-4" />
                </span>
                Màu sơn & hình ảnh
              </div>

              {xe.mauSacs.length > 0 ? xe.mauSacs.map((mau, index) => {
                const maMau = chuanHoaMaMau(mau.maMau);
                return (
                  <div key={`${mau.ten}-${index}`} className="flex items-center justify-between gap-4 border-b border-[#F1F1EF] py-3 text-[13px] last:border-0">
                    <span className="text-[#63635E]">{mau.ten}</span>
                    <span className="flex items-center gap-2 font-semibold">
                      {maMau ? <span aria-hidden="true" className="h-4 w-4 rounded-full border border-black/15" style={{ backgroundColor: maMau }} /> : null}
                      {maMau ? maMau.toUpperCase() : "Đang cập nhật"}
                    </span>
                  </div>
                );
              }) : (
                <div className="py-3 text-[13px] text-[#63635E]">Đang cập nhật</div>
              )}

              {[
                ["Số ảnh thường", xe.anhGallery.length],
                ["Số ảnh 360°", xe.anh360Gallery.length],
              ].map(([nhan, giaTri]) => (
                <div key={nhan} className="flex items-center justify-between border-b border-[#F1F1EF] py-3 text-[13px] last:border-0">
                  <span className="text-[#63635E]">{nhan}</span>
                  <span className="font-semibold">{giaTri}</span>
                </div>
              ))}
            </div>
          </div>

          <div className="mt-5 flex items-start gap-2 text-[11px] text-[#8A8A85]">
            <ShieldCheck className="mt-0.5 h-4 w-4 shrink-0" />
            Một số trang bị có thể thay đổi theo từng lô xe. Vui lòng liên hệ showroom để xác nhận
            cấu hình chính xác của xe trước khi quyết định.
          </div>
        </div>

        {xeTuongTu.length > 0 ? (
          <section className="mt-10 bg-[#F1F1EF] px-[1.3rem] py-8">
            <div className="mb-5">
              <div className="mb-1 text-[11px] font-bold uppercase tracking-wide text-[#AC0021]">
                Gợi ý thêm
              </div>
              <h2 className="mb-2 text-[20px] font-extrabold tracking-[-0.3px]">
                Xe tương tự tại showroom
              </h2>
              <p className="text-[13px] leading-[1.7] text-[#63635E]">
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
                    <span className="absolute left-3.5 top-3.5 rounded-full bg-white/90 px-3 py-1 text-[11px] font-semibold text-[#17171A]">
                      <span className="mr-1.5 inline-block h-1.5 w-1.5 rounded-full bg-[#2FA84F]" />
                      {item.trangThai}
                    </span>
                  </div>
                  <div className="flex flex-1 flex-col p-4">
                    <div className="mb-1 text-[11px] font-semibold tracking-[0.3px] text-[#8A8A85]">
                      PORSCHE {item.dong}
                    </div>
                    <div className="mb-3 font-bold">{item.ten}</div>
                    <div className="grid grid-cols-3 gap-2 border-y border-[#ECECE9] py-3">
                      {[
                        ["Số lượng", item.soLuong],
                        ["Loại", item.loaiSanPham],
                        ["Ngày nhập", item.ngayTao],
                      ].map(([nhan, giaTri]) => (
                        <div key={nhan}>
                          <div className="text-[11px] text-[#8A8A85]">{nhan}</div>
                          <div className="mt-1 text-[13px] font-semibold">{giaTri}</div>
                        </div>
                      ))}
                    </div>
                    <div className="mt-3 flex items-end justify-between gap-3">
                      <div>
                        <div className="text-[11px] text-[#8A8A85]">Giá niêm yết từ</div>
                        <div className="font-extrabold text-[#AC0021]">{item.gia}</div>
                      </div>
                      <span className="shrink-0 rounded-lg bg-[#17171A] px-3 py-2 text-[11px] font-semibold text-white">
                        Xem chi tiết →
                      </span>
                    </div>
                  </div>
                </Link>
              ))}
            </div>
          </section>
        ) : null}

        <div className="mx-auto mt-10 max-w-3xl px-[1.3rem]">
          <ReviewsSection loai="san_pham" id={xe.id} title="Đánh giá xe" />
        </div>
      </div>

      <Footer />
    </>
  );
}
