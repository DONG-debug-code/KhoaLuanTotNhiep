import { useEffect, useState } from "react";
import { Sparkles } from "lucide-react";
import { Link } from "react-router-dom";
import { GoiDoItem } from "../../pages/public/GoiDoItem";
import { PhuTungItem } from "../../pages/public/PhuTungItem";
import { getGoiDoVaPhuTungClient } from "../../api/PublicApi";
import ProductDetailModal from "../product/ProductDetailModal";

export default function HangPhuTungSection() {
  const [goiDo, setGoiDo] = useState([]);
  const [phuTung, setPhuTung] = useState([]);
  const [loiGoiDo, setLoiGoiDo] = useState("");
  const [loiPhuTung, setLoiPhuTung] = useState("");
  const [dangTai, setDangTai] = useState(true);
  const [selectedProduct, setSelectedProduct] = useState(null);

  const closeProductDetail = () => setSelectedProduct(null);

  useEffect(() => {
    getGoiDoVaPhuTungClient()
      .then((data) => {
        setGoiDo(data.goiDo);
        setPhuTung(data.phuTung);
        setLoiGoiDo(data.loiGoiDo);
        setLoiPhuTung(data.loiPhuTung);
      })
      .finally(() => setDangTai(false));
  }, []);

  return (
    <div className="bg-[#F3F3F0] px-6 py-16 max-[860px]:px-5 max-[860px]:py-10">
      <div className="mb-8 flex items-start justify-between gap-6 max-[860px]:flex-col">
        <div>
          <h2 className="mb-3 text-[34px] font-extrabold tracking-[-0.3px] max-[860px]:text-[26px]">
            Gói độ &amp; phụ tùng Porsche
          </h2>
          <p className="max-w-[620px] text-[15px] leading-[1.7] text-[#63635E]">
            Các gói nâng cấp hiệu suất, ngoại thất và công nghệ hiện có trong hệ thống showroom.
          </p>
        </div>

        <Link to="/hang-phu-tung" className="flex shrink-0 items-center gap-2 rounded-lg border border-[#ECECE9] bg-white px-4 py-2.5 text-sm font-semibold text-[#17171A]">
          <Sparkles className="h-4 w-4 text-[#c71430]" />
          Xem tất cả sản phẩm
        </Link>
      </div>

      <div className="grid grid-cols-[repeat(auto-fill,minmax(300px,1fr))] gap-5">
        {loiGoiDo ? <p role="alert" className="text-sm text-red-700">{loiGoiDo}</p> : null}
        {loiPhuTung ? <p role="alert" className="text-sm text-red-700">{loiPhuTung}</p> : null}
        {dangTai ? <p className="text-sm text-[#63635E]">Đang tải sản phẩm...</p> : null}
        {!dangTai && !loiGoiDo && !loiPhuTung && goiDo.length === 0 && phuTung.length === 0 ? (
          <p className="text-sm text-[#63635E]">Chưa có sản phẩm được đăng tải.</p>
        ) : null}
        <GoiDoItem goiDo={goiDo.slice(0, 4)} onSelect={setSelectedProduct} />
        <PhuTungItem phuTung={phuTung.slice(0, 4)} onSelect={setSelectedProduct} />
      </div>
      {selectedProduct ? <ProductDetailModal product={selectedProduct} onClose={closeProductDetail} /> : null}
    </div>
  );
}