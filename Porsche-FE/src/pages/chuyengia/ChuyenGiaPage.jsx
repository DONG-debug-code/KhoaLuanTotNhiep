import { useEffect, useState } from "react";
import Navbar from "../../components/layouts/partials/Navbar";
import { ChuyenGiaItem } from "../public/ChuyenGiaItem";
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
      <div className="min-h-screen bg-[#FAFAF9] px-[1.3rem] pb-16 pt-[126px]">
        <h1 className="mb-3 text-[34px] font-extrabold tracking-[-0.3px]">
          Đội ngũ chuyên gia
        </h1>
        <p className="mb-10 max-w-[620px] text-[15px] leading-[1.7] text-[#63635E]">
          Các chuyên gia trực tiếp thi công và tư vấn gói độ, mỗi người có thế
          mạnh riêng theo hạng mục và dòng xe.
        </p>

        <div className="grid grid-cols-[repeat(auto-fill,minmax(280px,1fr))] gap-6">
          {loi ? <p role="alert" className="text-sm text-red-700">{loi}</p> : null}
          {dangTai ? <p className="text-sm text-[#63635E]">Đang tải chuyên gia...</p> : null}
          {!dangTai && !loi && chuyenGia.length === 0 ? <p className="text-sm text-[#63635E]">Chưa có dữ liệu chuyên gia.</p> : null}
          <ChuyenGiaItem CHUYEN_GIA={chuyenGia} />
        </div>
      </div>
    </>
  );
}
