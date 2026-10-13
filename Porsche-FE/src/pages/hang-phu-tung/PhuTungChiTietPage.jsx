import { useEffect, useState } from "react";
import { Package } from "lucide-react";
import { Link, useParams } from "react-router-dom";
import Navbar from "../../components/layouts/partials/Navbar";
import { getPhuTungClientById } from "../../api/PublicApi";

function PhuTungImage({ phuTung }) {
  const [imageError, setImageError] = useState(false);

  if (!phuTung.anh || imageError) {
    return (
      <div className="flex flex-col items-center gap-2 text-[#A7A69F]">
        <Package className="h-14 w-14" aria-hidden="true" />
        <span className="text-sm">Chưa có hình ảnh</span>
      </div>
    );
  }

  return (
    <img
      src={phuTung.anh}
      alt={phuTung.ten}
      className="h-full w-full object-cover"
      onError={() => setImageError(true)}
    />
  );
}

export default function PhuTungChiTietPage() {
  const { phuTungId } = useParams();
  const [ketQua, setKetQua] = useState({ id: null, phuTung: null, loi: "" });
  const dangTai = ketQua.id !== phuTungId;
  const phuTung = dangTai ? null : ketQua.phuTung;
  const loi = dangTai ? "" : ketQua.loi;

  useEffect(() => {
    let active = true;
    getPhuTungClientById(phuTungId)
      .then((data) => {
        if (active) setKetQua({ id: phuTungId, phuTung: data, loi: "" });
      })
      .catch((error) => {
        if (active) {
          setKetQua({
            id: phuTungId,
            phuTung: null,
            loi: error.response?.data?.message || "Không tải được thông tin phụ tùng.",
          });
        }
      });
    return () => { active = false; };
  }, [phuTungId]);

  return (
    <>
      <Navbar luonSang />
      <div className="min-h-screen bg-[#FAFAF9] px-12 pb-16 pt-[130px] max-[860px]:px-5">
        {dangTai ? <p className="text-sm text-[#63635E]">Đang tải thông tin phụ tùng...</p> : null}
        {loi ? <p role="alert" className="text-sm text-red-700">{loi}</p> : null}
        {!dangTai && !loi && !phuTung ? (
          <p role="alert" className="text-sm text-[#63635E]">Không tìm thấy phụ tùng.</p>
        ) : null}
        {phuTung ? <>
          <Link to="/hang-phu-tung" className="mb-6 inline-block text-sm text-[#63635E]">
            ← Tất cả gói độ &amp; phụ tùng
          </Link>
          <div className="grid grid-cols-1 gap-10 lg:grid-cols-[1.2fr_1fr]">
            <div className="flex aspect-[4/3] items-center justify-center overflow-hidden rounded-2xl border border-[#ECECE9] bg-[#F1F1EF]">
              <PhuTungImage key={phuTung.id} phuTung={phuTung} />
            </div>
            <div>
              <div className="mb-2 text-xs font-semibold uppercase tracking-wide text-[#8A8A85]">
                {phuTung.hangMuc || "Phụ tùng Porsche"}
              </div>
              <h1 className="mb-4 text-[30px] font-extrabold tracking-[-0.3px]">{phuTung.ten}</h1>
              <p className="mb-6 text-[15px] leading-[1.7] text-[#63635E]">
                {phuTung.moTaNgan || "Chưa có mô tả cho phụ tùng này."}
              </p>
              <div className="mb-5 text-2xl font-extrabold text-[#B60F0F]">
                {phuTung.gia || "Liên hệ"}
              </div>
              <div className={`text-sm font-semibold ${phuTung.soLuong > 0 ? "text-[#2FA84F]" : "text-[#8A8A85]"}`}>
                {phuTung.soLuong > 0 ? `Còn ${phuTung.soLuong} sản phẩm` : "Tạm hết hàng"}
              </div>
            </div>
          </div>
        </> : null}
      </div>
    </>
  );
}
