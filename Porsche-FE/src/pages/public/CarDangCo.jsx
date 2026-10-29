import { useEffect, useState } from "react";
import { BadgeCheck, Search } from "lucide-react";
import { CarItem } from "./CarItem";
import { getXeDangBanClient } from "../../api/PublicApi";

const BO_LOC = ["Tất cả", "911", "718", "Taycan", "Panamera", "Cayenne", "Macan"];
const SO_XE_MOI_TRANG = 9;

function layThongSo(xe, aliases) {
  for (const alias of aliases) {
    const giaTri = xe[alias];
    if (giaTri !== undefined && giaTri !== null && giaTri !== "") return giaTri;
  }

  const thongSo = xe.thongSoChiTiet;
  if (!thongSo || typeof thongSo !== "object") return "Đang cập nhật";

  const aliasesChuanHoa = aliases.map((ten) => (
    ten.toLocaleLowerCase().replace(/[_\s-]/g, "")
  ));
  const ketQua = Object.entries(thongSo).find(([ten]) => (
    aliasesChuanHoa.includes(ten.toLocaleLowerCase().replace(/[_\s-]/g, ""))
  ));
  return ketQua?.[1] ?? "Đang cập nhật";
}

function layDongXe(xe) {
  const tenXe = `${xe.mauXe || ""} ${xe.ten || ""}`.toLocaleLowerCase();
  return BO_LOC.slice(1).find((dong) => (
    new RegExp(`(^|[^a-z0-9])${dong.toLocaleLowerCase()}([^a-z0-9]|$)`).test(tenXe)
  )) || xe.mauXe || "Porsche";
}

function chuyenDoiXe(sanPham) {
  const dong = layDongXe(sanPham);
  return {
    ...sanPham,
    slug: String(sanPham.id),
    dong,
    nam: layThongSo(sanPham, ["nam", "namSanXuat", "nam_san_xuat", "year"]),
    trangThai: sanPham.soLuong > 0 ? "Sẵn xe" : "Đã bán",
    congSuat: layThongSo(sanPham, ["congSuat", "cong_suat", "cong_suat_cuc_dai", "power"]),
    toc0_100: layThongSo(sanPham, ["toc0_100", "tangToc", "tang_toc_0_100", "acceleration"]),
    tocDoToiDa: layThongSo(sanPham, ["tocDoToiDa", "toc_do_toi_da", "top_speed"]),
  };
}

export const CarDangCo = () => {
  const [danhSachXe, setDanhSachXe] = useState([]);
  const [locDang, setLocDang] = useState("Tất cả");
  const [tuKhoa, setTuKhoa] = useState("");
  const [giaTu, setGiaTu] = useState("");
  const [giaDen, setGiaDen] = useState("");
  const [trang, setTrang] = useState(1);
  const [dangTai, setDangTai] = useState(true);
  const [loi, setLoi] = useState("");

  useEffect(() => {
    let active = true;
    getXeDangBanClient()
      .then((data) => {
        if (active) setDanhSachXe(data.map(chuyenDoiXe));
      })
      .catch((error) => {
        if (active) {
          setLoi(error.response?.data?.message || error.message || "Không tải được danh sách xe.");
        }
      })
      .finally(() => {
        if (active) setDangTai(false);
      });

    return () => {
      active = false;
    };
  }, []);

  const xeLoc = danhSachXe.filter((xe) => {
    const khopDong = locDang === "Tất cả" || xe.dong === locDang;
    const tuKhoaChuanHoa = tuKhoa.trim().toLocaleLowerCase();
    const khopTuKhoa = !tuKhoaChuanHoa
      || `${xe.ten} ${xe.dong} ${xe.nam}`.toLocaleLowerCase().includes(tuKhoaChuanHoa);
    const khopGiaTu = !giaTu || (xe.giaSo !== null && xe.giaSo >= Number(giaTu));
    const khopGiaDen = !giaDen || (xe.giaSo !== null && xe.giaSo <= Number(giaDen));
    return khopDong && khopTuKhoa && khopGiaTu && khopGiaDen;
  });
  const trangCuoi = Math.max(1, Math.ceil(xeLoc.length / SO_XE_MOI_TRANG));
  const xeHienThi = xeLoc.slice(
    (trang - 1) * SO_XE_MOI_TRANG,
    trang * SO_XE_MOI_TRANG
  );

  return (
    <div id="xe-dang-ban" className="scroll-mt-[80px] relative min-h-screen px-[1.3rem] bg-[#FAFAF9] font-['Inter',sans-serif] text-[#17171A]">
      <div className="flex items-start justify-between gap-6 border-b border-[#ECECE9] pb-7 pt-10 max-[860px]:flex-col max-[860px]:pb-5 max-[860px]:pt-7">
        <div>
          <h1 className="mb-3 text-[34px] font-extrabold tracking-[-0.3px]">
            Xe Porsche đang có tại showroom
          </h1>

          <p className="max-w-[620px] text-[15px] leading-[1.7] text-[#63635E]">
            Mỗi xe đều kèm hồ sơ kỹ thuật đầy đủ, lịch sử bảo dưỡng và hỗ trợ
            đăng ký. Giá niêm yết đã bao gồm thuế trước bạ theo quy định.
          </p>
        </div>

        <div className="flex shrink-0 items-center gap-3 whitespace-nowrap self-end rounded-xl border border-[#ECECE9] bg-[#F1F1EF] px-[18px] py-1.5">
          <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#FBE9E9] text-[#AC0021]">
            <BadgeCheck className="h-5 w-5" strokeWidth={2.5} />
          </span>

          <div className="text-left">
            <div className="text-[14px] items-start font-bold">
              {xeLoc.length} xe phù hợp
            </div>

            <div className="text-[12px] text-[#8A8A85]">
              Cập nhật từ hệ thống quản lý showroom
            </div>
          </div>
        </div>
      </div>

      <div className=" my-6 flex w-full flex-wrap gap-2       rounded-[50px] bg-[#F1F1EF] p-1 max-[860px]:mx-5 max-[860px]:my-5">
        {BO_LOC.map((loc) => (
          <button
            key={loc}
            className={`
                          cursor-pointer rounded-[50px] border-none px-4 py-1.5
                    text-[13px] font-medium
                    ${locDang === loc
                                ? "bg-[#17171A] font-semibold text-white"
                                : "bg-transparent text-[#4A4A46]"
                            }
                `}
            onClick={() => {
              setLocDang(loc);
              setTrang(1);
            }}
          >
            {loc}
          </button>
        ))}
      </div>

      <div className="mb-6 grid items-end gap-3 sm:grid-cols-[minmax(220px,1fr)_180px_180px]">
        <label className="relative block">
          <span className="sr-only">Tìm xe theo tên hoặc dòng xe</span>
          <Search className="absolute left-3.5 top-1/2 h-4 w-4 -translate-y-1/2 text-[#8A8A85]" />
          <input
            type="search"
            value={tuKhoa}
            onChange={(event) => { setTuKhoa(event.target.value); setTrang(1); }}
            placeholder="Tìm tên xe hoặc dòng xe"
            className="w-full rounded-xl border border-[#DCDCD7] bg-white h-10 pl-10 pr-4 text-[13px] outline-none focus:border-[#17171A]"
          />
        </label>
        <label className="text-[11px] font-semibold text-[#63635E]">
          Giá từ (VNĐ)
          <input
            type="number"
            min="0"
            value={giaTu}
            onChange={(event) => { setGiaTu(event.target.value); setTrang(1); }}
            className="mt-1 w-full rounded-xl border border-[#DCDCD7] bg-white h-10 px-3.5 text-[13px] font-normal text-[#17171A] outline-none focus:border-[#17171A]"
          />
        </label>
        <label className="text-[11px] font-semibold text-[#63635E]">
          Giá đến (VNĐ)
          <input
            type="number"
            min="0"
            value={giaDen}
            onChange={(event) => { setGiaDen(event.target.value); setTrang(1); }}
            className="mt-1 w-full rounded-xl border border-[#DCDCD7] bg-white h-10 px-3.5 text-[13px] font-normal text-[#17171A] outline-none focus:border-[#17171A]"
          />
        </label>
      </div>

      {loi ? <p role="alert" className="text-sm text-red-700">{loi}</p> : null}
      {dangTai ? <p className="text-sm text-[#63635E]">Đang tải danh sách xe...</p> : null}
      {!dangTai && !loi && danhSachXe.length === 0 ? (
        <p className="text-sm text-[#63635E]">Hiện chưa có xe tại showroom.</p>
      ) : null}
      {!dangTai && !loi && danhSachXe.length > 0 && xeHienThi.length === 0 ? (
        <p className="text-sm text-[#63635E]">Không có xe phù hợp với điều kiện tìm kiếm.</p>
      ) : null}

      <div className={`grid grid-cols-[repeat(auto-fill,minmax(360px,1fr))] gap-4 pt-2 ${
        trangCuoi > 1 ? "pb-8 max-[860px]:pb-6" : "pb-16 max-[860px]:pb-12"
      }`}>
        <CarItem xeHienThi={xeHienThi} />
      </div>

      {trangCuoi > 1 ? (
        <div className="flex items-center justify-center gap-4 pb-16">
          <button
            type="button"
            disabled={trang <= 1 || dangTai}
            onClick={() => setTrang((trangHienTai) => Math.max(trangHienTai - 1, 1))}
            className="flex items-center gap-1 rounded-lg border border-[#DCDCD7] px-4 py-2 text-sm font-semibold disabled:opacity-40"
          >
            Trước
          </button>
          <span className="text-sm text-[#63635E]">Trang {trang} / {trangCuoi}</span>
          <button
            type="button"
            disabled={trang >= trangCuoi || dangTai}
            onClick={() => setTrang((trangHienTai) => Math.min(trangHienTai + 1, trangCuoi))}
            className="flex items-center gap-1 rounded-lg border border-[#DCDCD7] px-4 py-2 text-sm font-semibold disabled:opacity-40"
          >
            Sau
          </button>
        </div>
      ) : null}
    </div>
  );
};
