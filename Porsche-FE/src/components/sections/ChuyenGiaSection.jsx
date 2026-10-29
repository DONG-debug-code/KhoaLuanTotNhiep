import { useEffect, useState } from "react";
import { ChuyenGiaItem } from "../../pages/public/ChuyenGiaItem";
import { getChuyenGiaTuningClient } from "../../api/PublicApi";

export default function ChuyenGiaSection() {
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
    <div id="chuyen-gia" className="bg-[#FAFAF9] px-[1.3rem] py-16 max-[860px]:py-10">
      <h2 className="mb-3 text-[34px] font-extrabold tracking-[-0.3px] max-[860px]:text-[26px]">
        Đội ngũ chuyên gia / tuner
      </h2>
      <p className="mb-10 max-w-[620px] text-[15px] leading-[1.7] text-[#63635E]">
        Mỗi chuyên gia phụ trách một nhóm hạng mục riêng. Khi đặt lịch độ xe hoặc bảo dưỡng, bạn
        có thể chọn trực tiếp người thực hiện.
      </p>

      <div className="grid grid-cols-[repeat(auto-fill,minmax(300px,1fr))] gap-5">
        {loi ? <p role="alert" className="text-sm text-red-700">{loi}</p> : null}
        {dangTai ? <p className="text-sm text-[#63635E]">Đang tải chuyên gia...</p> : null}
        {!dangTai && !loi && chuyenGia.length === 0 ? <p className="text-sm text-[#63635E]">Chưa có dữ liệu chuyên gia.</p> : null}
        <ChuyenGiaItem CHUYEN_GIA={chuyenGia}/>
      </div>
    </div>
  );
}
