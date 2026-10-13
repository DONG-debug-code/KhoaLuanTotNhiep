import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import Navbar from "../../components/layouts/partials/Navbar";
import { getChuyenGiaTuningClient } from "../../api/PublicApi";

export default function ChuyenGiaPage() {
  const [chuyenGia, setChuyenGia] = useState([]);
  const [loi, setLoi] = useState("");
  const [dangTai, setDangTai] = useState(true);

  useEffect(() => {
    getChuyenGiaTuningClient()
      .then(setChuyenGia)
    .catch((error) => setLoi(error.response?.data?.message || "API chuyên gia đang lỗi. Vui lòng kiểm tra BE và bảng dữ liệu chuyên gia."))
      .finally(() => setDangTai(false));
  }, []);

  return (
    <>
      <Navbar luonSang/>
      <div className="min-h-screen bg-[#FAFAF9] px-12 pb-16 pt-[130px] max-[860px]:px-5">
        <h1 className="mb-3 text-[34px] font-extrabold tracking-[-0.3px]">
          Đội ngũ chuyên gia
        </h1>
        <p className="mb-10 max-w-[620px] text-[15px] leading-[1.7] text-[#63635E]">
          Các chuyên gia trực tiếp thi công và tư vấn gói độ, mỗi người có thế
          mạnh riêng theo hạng mục và dòng xe.
        </p>

        <div className="grid grid-cols-[repeat(auto-fill,minmax(300px,1fr))] gap-6">
          {loi ? <p role="alert" className="text-sm text-red-700">{loi}</p> : null}
          {dangTai ? <p className="text-sm text-[#63635E]">Đang tải chuyên gia...</p> : null}
          {!dangTai && !loi && chuyenGia.length === 0 ? <p className="text-sm text-[#63635E]">Chưa có dữ liệu chuyên gia.</p> : null}
          {chuyenGia.map((cg) => (
            <Link
              key={cg.id ?? cg.slug}
              to={`/chuyen-gia/${cg.id ?? cg.slug}`}
              className="group overflow-hidden rounded-2xl border border-[#ECECE9] bg-white shadow-sm transition-shadow hover:shadow-lg"
            >
              <div className="aspect-[4/3] overflow-hidden bg-[#F1F1EF]">
                <img
                  src={cg.anh}
                  alt={cg.ten}
                  className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110"
                  onError={(e) => (e.currentTarget.style.display = "none")}
                />
              </div>

              <div className="p-5">
                <div className="mb-1.5 text-lg font-bold">{cg.ten}</div>
                <div className="mb-2 text-sm text-[#63635E]">{cg.chucDanh}</div>

                <div className="mb-3 flex flex-wrap gap-1.5">
                  {(Array.isArray(cg.dongXeChuyenMon) ? cg.dongXeChuyenMon : []).map((xe) => (
                    <span
                      key={xe}
                      className="rounded-full bg-[#F1F1EF] px-2.5 py-1 text-xs font-semibold text-[#4A4A46]"
                    >
                      {xe}
                    </span>
                  ))}
                </div>

                <div className="text-xs text-[#8A8A85]">
                  {cg.soNamKinhNghiem} năm kinh nghiệm
                </div>
              </div>
            </Link>
          ))}
        </div>
      </div>
    </>
  );
}