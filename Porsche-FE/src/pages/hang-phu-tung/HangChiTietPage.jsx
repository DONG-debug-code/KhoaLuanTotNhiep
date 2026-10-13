import { useEffect, useState } from "react";
import { useParams, Link } from "react-router-dom";
import Navbar from "../../components/layouts/partials/Navbar";
import { getHangPhuTungClientById } from "../../api/PublicApi";

export default function HangChiTietPage() {
  const { hangSlug } = useParams();
  const [ketQua, setKetQua] = useState({ id: null, hang: null, loi: "" });
  const dangTai = ketQua.id !== hangSlug;
  const hang = dangTai ? null : ketQua.hang;
  const loi = dangTai ? "" : ketQua.loi;

  useEffect(() => {
    let active = true;
    getHangPhuTungClientById(hangSlug)
      .then((data) => {
        if (active) setKetQua({ id: hangSlug, hang: data, loi: "" });
      })
      .catch((error) => {
        if (active) setKetQua({
          id: hangSlug,
          hang: null,
          loi: error.response?.data?.message || "Không tải được thông tin hãng phụ tùng.",
        });
      });
    return () => { active = false; };
  }, [hangSlug]);

  return (
    <>
      <Navbar luonSang/>
      <div className="min-h-screen bg-[#FAFAF9] px-12 pb-16 pt-[130px] max-[860px]:px-5">
        {dangTai ? <p className="text-sm text-[#63635E]">Đang tải thông tin hãng phụ tùng...</p> : null}
        {loi ? <p role="alert" className="text-sm text-red-700">{loi}</p> : null}
        {!dangTai && !loi && !hang ? <p role="alert" className="text-sm text-[#63635E]">Không tìm thấy hãng phụ tùng.</p> : null}
        {hang ? <>
        <div className="mb-10">
          <div className="mb-2 text-xs font-semibold text-[#8A8A85]">{hang.quocGia}</div>
          <h1 className="mb-3 text-[34px] font-extrabold tracking-[-0.3px]">{hang.ten}</h1>
          <p className="max-w-[620px] text-[15px] leading-[1.7] text-[#63635E]">{hang.moTa}</p>
        </div>

        <div className="grid grid-cols-[repeat(auto-fill,minmax(340px,1fr))] gap-6">
          {hang.goiDo.length === 0 ? (
            <p className="text-sm text-[#63635E]">Hãng này hiện chưa có gói độ được đăng tải.</p>
          ) : null}
          {hang.goiDo.map((goi) => (
            <Link
              key={goi.id ?? goi.slug}
              to={`/goi-do/${goi.id ?? goi.slug}`}
              className="group overflow-hidden rounded-2xl border border-[#ECECE9] bg-white shadow-sm transition-shadow hover:shadow-lg"
            >
              <div className="aspect-[4/3] overflow-hidden bg-[#F1F1EF]">
                <img
                  src={goi.anh}
                  alt={goi.ten}
                  className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110"
                  onError={(e) => (e.currentTarget.style.display = "none")}
                />
              </div>
              <div className="p-5">
                <div className="mb-1 text-xs font-semibold text-[#8A8A85]">
                  {goi.hangMuc}{goi.hangMuc && goi.dongXeTuongThich.length ? " · " : ""}{goi.dongXeTuongThich.join(", ")}
                </div>
                <div className="mb-1.5 text-lg font-bold">{goi.ten}</div>
                <div className="text-sm text-[#63635E]">{goi.moTaNgan}</div>
                <div className="mt-3 text-base font-extrabold text-[#B60F0F]">{goi.gia}</div>
              </div>
            </Link>
          ))}
        </div>
        </> : null}
      </div>
    </>
  );
}