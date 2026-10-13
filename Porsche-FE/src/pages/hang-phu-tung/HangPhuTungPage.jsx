import { useEffect, useState } from "react";
import Navbar from "../../components/layouts/partials/Navbar";
import { GoiDoItem } from "../public/GoiDoItem";
import { PhuTungItem } from "../public/PhuTungItem";
import { getGoiDoVaPhuTungClient } from "../../api/PublicApi";


export default function HangPhuTungPage() {
  const [goiDo, setGoiDo] = useState([]);
  const [phuTung, setPhuTung] = useState([]);
  const [loiGoiDo, setLoiGoiDo] = useState("");
  const [loiPhuTung, setLoiPhuTung] = useState("");
  const [dangTai, setDangTai] = useState(true);

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
    <>
      <Navbar luonSang/>
      <div className="min-h-screen bg-[#FAFAF9] px-12 pb-16 pt-[130px] max-[860px]:px-5">
        <h1 className="mb-3 text-[34px] font-extrabold tracking-[-0.3px]">
          Gói độ &amp; phụ tùng Porsche
        </h1>
        <p className="mb-10 max-w-[620px] text-[15px] leading-[1.7] text-[#63635E]">
          Khám phá các gói nâng cấp hiệu suất, ngoại thất và công nghệ dành cho các dòng Porsche.
        </p>

        <div className="mb-10">
          <h2 className="mb-5 text-2xl font-bold">Gói độ</h2>
          {loiGoiDo ? <p role="alert" className="mb-4 text-sm text-red-700">{loiGoiDo}</p> : null}
          {dangTai ? <p className="text-sm text-[#63635E]">Đang tải gói độ...</p> : null}
          {!dangTai && !loiGoiDo && goiDo.length === 0 ? <p className="text-sm text-[#63635E]">Chưa có gói độ được đăng tải.</p> : null}
          <div className="grid grid-cols-[repeat(auto-fill,minmax(300px,1fr))] gap-6">
          <GoiDoItem goiDo={goiDo} />
          </div>
        </div>
        <div>
          <h2 className="mb-5 text-2xl font-bold">Phụ tùng</h2>
          {loiPhuTung ? <p role="alert" className="mb-4 text-sm text-red-700">{loiPhuTung}</p> : null}
          {dangTai ? <p className="text-sm text-[#63635E]">Đang tải phụ tùng...</p> : null}
          {!dangTai && !loiPhuTung && phuTung.length === 0 ? <p className="text-sm text-[#63635E]">Chưa có phụ tùng được đăng tải.</p> : null}
          <div className="grid grid-cols-[repeat(auto-fill,minmax(300px,1fr))] gap-6">
            <PhuTungItem phuTung={phuTung} />
          </div>
        </div>
      </div>
    </>
  );
}