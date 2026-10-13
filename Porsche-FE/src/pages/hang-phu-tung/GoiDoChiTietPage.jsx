import { useEffect, useState } from "react";
import { Link, useParams } from "react-router-dom";
import Navbar from "../../components/layouts/partials/Navbar";
import { getGoiDoClientById } from "../../api/PublicApi";

function layThongSo(thongSo) {
  if (!thongSo) return [];
  if (typeof thongSo === "string") return [thongSo];
  if (Array.isArray(thongSo)) {
    return thongSo.map((item) => typeof item === "string" ? item : JSON.stringify(item));
  }
  if (typeof thongSo === "object") {
    return Object.entries(thongSo).map(([ten, giaTri]) => `${ten.replaceAll("_", " ")}: ${giaTri}`);
  }
  return [];
}

export default function GoiDoChiTietPage() {
  const { goiSlug } = useParams();
  const [ketQua, setKetQua] = useState({ id: null, goi: null, loi: "" });
  const dangTai = ketQua.id !== goiSlug;
  const goi = dangTai ? null : ketQua.goi;
  const loi = dangTai ? "" : ketQua.loi;

  useEffect(() => {
    let active = true;
    getGoiDoClientById(goiSlug)
      .then((data) => {
        if (active) setKetQua({ id: goiSlug, goi: data, loi: "" });
      })
      .catch((error) => {
        if (active) setKetQua({
          id: goiSlug,
          goi: null,
          loi: error.response?.data?.message || "Không tải được thông tin gói độ.",
        });
      });
    return () => { active = false; };
  }, [goiSlug]);

  const thongSo = goi ? layThongSo(goi.thongSoChiTiet) : [];

  return (
    <>
      <Navbar luonSang />
      <div className="min-h-screen bg-[#FAFAF9] px-12 pb-16 pt-[130px] max-[860px]:px-5">
        {dangTai ? <p className="text-sm text-[#63635E]">Đang tải thông tin gói độ...</p> : null}
        {loi ? <p role="alert" className="text-sm text-red-700">{loi}</p> : null}
        {!dangTai && !loi && !goi ? (
          <p role="alert" className="text-sm text-[#63635E]">Không tìm thấy gói độ.</p>
        ) : null}
        {goi ? <>
          <Link
            to="/hang-phu-tung"
            className="mb-6 inline-block text-sm text-[#63635E]"
          >
            ← Tất cả gói độ
          </Link>

          <div className="grid grid-cols-1 gap-10 lg:grid-cols-[1.2fr_1fr]">
            <div className="overflow-hidden rounded-2xl border border-[#ECECE9] bg-[#F1F1EF]">
              {goi.anh ? (
                <img
                  src={goi.anh}
                  alt={goi.ten}
                  className="aspect-[4/3] w-full object-cover"
                  onError={(event) => { event.currentTarget.style.display = "none"; }}
                />
              ) : null}
            </div>

            <div>
              <div className="mb-2 text-xs font-semibold text-[#8A8A85]">
                {goi.hangMuc || "Gói độ Porsche"}
              </div>
              <h1 className="mb-4 text-[30px] font-extrabold tracking-[-0.3px]">{goi.ten}</h1>
              <p className="mb-5 text-[15px] leading-[1.7] text-[#63635E]">
                {goi.chiTiet || goi.moTaNgan || "Chưa có mô tả cho gói độ này."}
              </p>

              {thongSo.length > 0 ? (
                <div className="mb-6 rounded-xl border border-[#ECECE9] bg-white p-4">
                  <h2 className="mb-3 text-sm font-bold">Thông số chi tiết</h2>
                  <ul className="space-y-2 text-sm text-[#63635E]">
                    {thongSo.map((item, index) => <li key={index}>{item}</li>)}
                  </ul>
                </div>
              ) : null}

              {goi.gia ? <div className="mb-6 text-2xl font-extrabold text-[#B60F0F]">{goi.gia}</div> : null}

              <Link
                to={`/dat-coc/goi-do/${goi.id ?? goi.slug}`}
                className="rounded-lg bg-[#17171A] px-6 py-3.5 text-sm font-semibold text-white"
              >
                Đặt lịch khảo sát &amp; thi công
              </Link>
            </div>
          </div>
        </> : null}
      </div>
    </>
  );
}
