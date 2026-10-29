import { useEffect, useState } from "react";
import { useParams, Link } from "react-router-dom";
import { CalendarCheck, Wallet, ShieldCheck, ChevronLeft } from "lucide-react";
import Navbar from "../../components/layouts/partials/Navbar";
import Footer from "../../components/layouts/partials/Footer";
import { useAuth } from "../../context/AuthContext";
import { dinhDangTien, layThongTinDatCoc } from "../../components/utils/datCocHelper";
import { getGoiDoClientById } from "../../api/PublicApi";

const PHUONG_THUC = [
  { ma: "vnpay", ten: "VNPay" },
  { ma: "momo", ten: "Momo" },
  { ma: "the", ten: "Thẻ tín dụng / ghi nợ" },
];

export default function DatCocPage() {
  const { loai, slug } = useParams();
  const { nguoiDung } = useAuth();
  const thongTinCoSan = layThongTinDatCoc(loai, slug);
  const [ketQuaGoiDo, setKetQuaGoiDo] = useState({ slug: null, thongTin: null, loi: "" });

  useEffect(() => {
    if (loai !== "goi-do" || thongTinCoSan) return undefined;

    let active = true;
    getGoiDoClientById(slug)
      .then((goi) => {
        if (!active) return;
        if (!goi) {
          setKetQuaGoiDo({ slug, thongTin: null, loi: "Không tìm thấy gói độ cần đặt lịch." });
          return;
        }

        const gia = goi.giaSo ?? 0;
        const tienCoc = Math.round((gia * 0.1) / 100000) * 100000;
        setKetQuaGoiDo({
          slug,
          thongTin: {
            tieuDe: `Đặt lịch khảo sát & thi công: ${goi.ten}`,
            moTaNgan: [goi.hangMuc, ...goi.dongXeTuongThich].filter(Boolean).join(" · "),
            anh: goi.anh,
            tienCoc,
            ghiChuCoc: "Cọc 10% giá gói độ để giữ lịch khảo sát xe. Giá cuối cùng sẽ được xác nhận sau khi kỹ thuật viên kiểm tra xe thực tế.",
          },
          loi: "",
        });
      })
      .catch((error) => {
        if (active) {
          setKetQuaGoiDo({
            slug,
            thongTin: null,
            loi: error.response?.data?.message || "Không tải được thông tin gói độ.",
          });
        }
      });

    return () => { active = false; };
  }, [loai, slug, thongTinCoSan]);

  const thongTinGoiDo = ketQuaGoiDo.slug === slug ? ketQuaGoiDo.thongTin : null;
  const loiGoiDo = ketQuaGoiDo.slug === slug ? ketQuaGoiDo.loi : "";
  const dangTaiGoiDo = loai === "goi-do" && !thongTinCoSan && ketQuaGoiDo.slug !== slug;
  const thongTin = thongTinCoSan ?? thongTinGoiDo;

  const [ngayMongMuon, setNgayMongMuon] = useState("");
  const [gioMongMuon, setGioMongMuon] = useState("");
  const [ghiChu, setGhiChu] = useState("");
  const [phuongThuc, setPhuongThuc] = useState("vnpay");
  const [dangXuLy, setDangXuLy] = useState(false);
  const [datCocThanhCong, setDatCocThanhCong] = useState(false);

  if (dangTaiGoiDo) {
    return (
      <>
        <Navbar luonSang />
        <div className="min-h-screen bg-[#FAFAF9] px-5 pt-[126px] text-center text-sm text-[#63635E]">
          Đang tải thông tin gói độ...
        </div>
      </>
    );
  }

  if (!thongTin) {
    return (
      <>
        <Navbar luonSang />
        <div className="min-h-screen bg-[#FAFAF9] px-5 pt-[126px] text-center">
          <p role="alert" className="mb-5 text-sm text-red-700">
            {loiGoiDo || "Không tìm thấy thông tin đặt lịch."}
          </p>
          <Link to="/hang-phu-tung" className="text-sm font-semibold text-[#17171A]">
            Quay lại danh sách gói độ
          </Link>
        </div>
      </>
    );
  }

  const xuLyDatCoc = (e) => {
    e.preventDefault();
    setDangXuLy(true);

    // TODO: thay bằng gọi API tạo giao dịch + redirect sang cổng thanh toán thật (VNPay/Momo) khi có BE
    setTimeout(() => {
      setDangXuLy(false);
      setDatCocThanhCong(true);
    }, 1200);
  };

  if (datCocThanhCong) {
    return (
      <>
        <Navbar luonSang />
        <div className="flex min-h-screen items-center justify-center bg-[#FAFAF9] px-5 pt-[96px]">
          <div className="w-full max-w-[440px] rounded-2xl border border-[#ECECE9] bg-white p-8 text-center">
            <span className="mx-auto mb-5 flex h-14 w-14 items-center justify-center rounded-full bg-[#E9F7EE] text-[#2FA84F]">
              <ShieldCheck className="h-7 w-7" />
            </span>
            <h1 className="mb-2 text-xl font-extrabold">Đặt cọc thành công</h1>
            <p className="mb-6 text-sm leading-[1.7] text-[#63635E]">
              Chúng tôi đã ghi nhận khoản cọc {dinhDangTien(thongTin.tienCoc)} cho{" "}
              <span className="font-semibold text-[#17171A]">{thongTin.tieuDe}</span>. Đội ngũ
              showroom sẽ liên hệ xác nhận lịch trong vòng 2 giờ làm việc.
            </p>
            <Link
              to="/"
              className="inline-block rounded-lg bg-[#17171A] px-6 py-3 text-sm font-semibold text-white"
            >
              Về trang chủ
            </Link>
          </div>
        </div>
        <Footer />
      </>
    );
  }

  return (
    <>
      <Navbar luonSang />

      <div className="min-h-screen bg-[#FAFAF9] px-[1.3rem] pb-16 pt-[96px] max-[860px]:pt-[80px]">
        <Link to="/" className="mb-6 inline-flex items-center gap-1.5 text-sm text-[#63635E]">
          <ChevronLeft className="h-4 w-4" /> Quay lại
        </Link>

        <div className="mx-auto grid max-w-[900px] grid-cols-1 gap-8 lg:grid-cols-[1fr_1.2fr]">

          {/* Tóm tắt */}
          <div className="h-fit rounded-2xl border border-[#ECECE9] bg-white p-6">
            {thongTin.anh && (
              <div className="mb-4 aspect-[16/10] overflow-hidden rounded-xl bg-[#F1F1EF]">
                <img
                  src={thongTin.anh}
                  alt={thongTin.tieuDe}
                  className="h-full w-full object-cover"
                  onError={(e) => (e.currentTarget.style.display = "none")}
                />
              </div>
            )}

            <div className="mb-1 text-xs font-semibold text-[#8A8A85]">{thongTin.moTaNgan}</div>
            <h1 className="mb-5 text-xl font-extrabold leading-[1.3]">{thongTin.tieuDe}</h1>

            <div className="mb-4 flex items-center justify-between border-t border-[#ECECE9] pt-4">
              <span className="flex items-center gap-2 text-sm text-[#63635E]">
                <Wallet className="h-4 w-4" /> Tiền đặt cọc
              </span>
              <span className="text-lg font-extrabold text-[#AC0021]">
                {dinhDangTien(thongTin.tienCoc)}
              </span>
            </div>

            <div className="rounded-lg bg-[#F1F1EF] p-3.5 text-xs leading-[1.6] text-[#4A4A46]">
              {thongTin.ghiChuCoc}
            </div>

            <div className="mt-4 text-xs text-[#8A8A85]">
              Đặt cọc với tài khoản:{" "}
              <span className="font-semibold text-[#17171A]">
                {nguoiDung?.soDienThoai || "Khách hàng"}
              </span>
            </div>
          </div>

          {/* Form */}
          <div className="rounded-2xl border border-[#ECECE9] bg-white p-6">
            <h2 className="mb-5 text-base font-bold">Chọn thời gian & thanh toán</h2>

            <form onSubmit={xuLyDatCoc} className="flex flex-col gap-4">
              <div className="grid grid-cols-2 gap-4">
                <div>
                  <label className="mb-1.5 block text-sm font-semibold">Ngày mong muốn</label>
                  <input
                    type="date"
                    required
                    value={ngayMongMuon}
                    onChange={(e) => setNgayMongMuon(e.target.value)}
                    className="w-full rounded-lg border border-[#ECECE9] px-4 py-3 text-sm outline-none focus:border-[#17171A]"
                  />
                </div>
                <div>
                  <label className="mb-1.5 block text-sm font-semibold">Khung giờ</label>
                  <input
                    type="time"
                    required
                    value={gioMongMuon}
                    onChange={(e) => setGioMongMuon(e.target.value)}
                    className="w-full rounded-lg border border-[#ECECE9] px-4 py-3 text-sm outline-none focus:border-[#17171A]"
                  />
                </div>
              </div>

              <div>
                <label className="mb-1.5 block text-sm font-semibold">Ghi chú (không bắt buộc)</label>
                <textarea
                  rows={3}
                  value={ghiChu}
                  onChange={(e) => setGhiChu(e.target.value)}
                  placeholder="Ví dụ: Tôi muốn hẹn vào buổi chiều, ưu tiên cuối tuần..."
                  className="w-full resize-none rounded-lg border border-[#ECECE9] px-4 py-3 text-sm outline-none focus:border-[#17171A]"
                />
              </div>

              <div>
                <label className="mb-2 block text-sm font-semibold">Phương thức thanh toán</label>
                <div className="grid grid-cols-3 gap-2.5">
                  {PHUONG_THUC.map((pt) => (
                    <button
                      key={pt.ma}
                      type="button"
                      onClick={() => setPhuongThuc(pt.ma)}
                      className={`rounded-lg border px-3 py-3 text-sm font-semibold transition-colors ${
                        phuongThuc === pt.ma
                          ? "border-[#17171A] bg-[#17171A] text-white"
                          : "border-[#ECECE9] text-[#4A4A46]"
                      }`}
                    >
                      {pt.ten}
                    </button>
                  ))}
                </div>
              </div>

              <button
                type="submit"
                disabled={dangXuLy}
                className="mt-2 flex items-center justify-center gap-2 rounded-lg bg-[#AC0021] py-3.5 text-sm font-semibold text-white disabled:opacity-60"
              >
                <CalendarCheck className="h-4 w-4" />
                {dangXuLy ? "Đang xử lý..." : `Xác nhận đặt cọc ${dinhDangTien(thongTin.tienCoc)}`}
              </button>

              <p className="text-center text-xs text-[#8A8A85]">
                Bằng việc xác nhận, bạn đồng ý với điều khoản đặt cọc và chính sách hoàn tiền của
                Porsche.
              </p>
            </form>
          </div>
        </div>
      </div>

      <Footer />
    </>
  );
}
