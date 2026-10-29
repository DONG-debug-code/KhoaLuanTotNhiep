import { useEffect, useState } from "react";
import { useParams, Link } from "react-router-dom";
import Navbar from "../../components/layouts/partials/Navbar";
import { ChuyenGiaAnh } from "../public/ChuyenGiaItem";
import ChuyenGiaDatLichForm from "./ChuyenGiaDatLichForm";
import { getChuyenGiaTuningClientById, getGoiDoClient } from "../../api/PublicApi";

export default function ChuyenGiaChiTietPage() {
  const { chuyenGiaSlug } = useParams();
  const [ketQua, setKetQua] = useState({ id: null, chuyenGia: null, loi: "" });
  const [ketQuaGoiDo, setKetQuaGoiDo] = useState({ id: null, goiDo: [], loi: "" });
  const dangTai = ketQua.id !== chuyenGiaSlug;
  const chuyenGia = dangTai ? null : ketQua.chuyenGia;
  const loi = dangTai ? "" : ketQua.loi;
  const dangTaiGoiDo = ketQuaGoiDo.id !== chuyenGiaSlug;
  const loiGoiDo = dangTaiGoiDo ? "" : ketQuaGoiDo.loi;

  useEffect(() => {
    let active = true;
    getChuyenGiaTuningClientById(chuyenGiaSlug)
      .then((data) => {
        if (active) setKetQua({ id: chuyenGiaSlug, chuyenGia: data, loi: "" });
      })
      .catch((error) => {
        if (active) setKetQua({
          id: chuyenGiaSlug,
          chuyenGia: null,
          loi: error.response?.data?.message || "Không tải được thông tin chuyên gia.",
        });
      });
    return () => { active = false; };
  }, [chuyenGiaSlug]);

  useEffect(() => {
    let active = true;
    getGoiDoClient()
      .then((goiDo) => {
        if (active) setKetQuaGoiDo({ id: chuyenGiaSlug, goiDo, loi: "" });
      })
      .catch((error) => {
        if (active) setKetQuaGoiDo({
          id: chuyenGiaSlug,
          goiDo: [],
          loi: error.response?.data?.message || "Không tải được danh sách gói độ liên quan.",
        });
      });
    return () => { active = false; };
  }, [chuyenGiaSlug]);

  const goiDoLienQuan = chuyenGia
    ? ketQuaGoiDo.goiDo
      .map((goi) => {
        const hangMucMatch = chuyenGia.hangMucChuyenMon.some((hangMuc) =>
          String(goi.hangMuc).toLocaleLowerCase().includes(String(hangMuc).toLocaleLowerCase())
        );
        const dongXeMatch = goi.dongXeTuongThich.some((dongXe) =>
          chuyenGia.dongXeChuyenMon.some((dongXeChuyenMon) =>
            String(dongXe).toLocaleLowerCase() === String(dongXeChuyenMon).toLocaleLowerCase()
          )
        );
        return { ...goi, diem: Number(hangMucMatch) * 2 + Number(dongXeMatch) };
      })
      .filter((goi) => goi.diem > 0)
      .sort((a, b) => b.diem - a.diem)
    : [];

  return (
    <>
      <Navbar luonSang/>
      <div className="min-h-screen bg-[#FAFAF9] px-[1.3rem] pb-16 pt-[126px]">
        {dangTai ? <p className="text-sm text-[#63635E]">Đang tải thông tin chuyên gia...</p> : null}
        {loi ? <p role="alert" className="text-sm text-red-700">{loi}</p> : null}
        {!dangTai && !loi && !chuyenGia ? <p role="alert" className="text-sm text-[#63635E]">Không tìm thấy chuyên gia.</p> : null}
        {chuyenGia ? <>
        <Link to="/chuyen-gia" className="mb-6 inline-block text-sm text-[#63635E]">
          ← Đội ngũ chuyên gia
        </Link>

        <div className="grid grid-cols-1 gap-10 lg:grid-cols-[380px_1fr]">
          <div className="group overflow-hidden rounded-2xl border border-[#ECECE9]">
            <ChuyenGiaAnh chuyenGia={chuyenGia} className="aspect-[4/3] w-full" />
          </div>

          <div>
            <h1 className="mb-2 text-[30px] font-extrabold tracking-[-0.3px]">
              {chuyenGia.ten}
            </h1>
            <div className="mb-5 text-base text-[#63635E]">{chuyenGia.chucDanh}</div>

            <p className="mb-6 max-w-[560px] text-[15px] leading-[1.7] text-[#63635E]">
              {chuyenGia.moTa}
            </p>

            <div className="mb-6 flex flex-wrap gap-6 border-y border-[#ECECE9] py-5">
              <div>
                <div className="text-xs text-[#8A8A85]">Kinh nghiệm</div>
                <div className="mt-0.5 text-base font-bold">
                  {chuyenGia.soNamKinhNghiem} năm
                </div>
              </div>
              <div>
                <div className="text-xs text-[#8A8A85]">Chuyên môn hạng mục</div>
                <div className="mt-0.5 text-base font-bold">
                  {chuyenGia.hangMucChuyenMon.join(", ")}
                </div>
              </div>
              <div>
                <div className="text-xs text-[#8A8A85]">Dòng xe</div>
                <div className="mt-0.5 text-base font-bold">
                  {chuyenGia.dongXeChuyenMon.join(", ")}
                </div>
              </div>
            </div>

            <div className="mb-6 grid gap-2 text-sm text-[#3B3B38] sm:grid-cols-2">
              {chuyenGia.maSoTuner ? <div><span className="text-[#8A8A85]">Mã tuner: </span>{chuyenGia.maSoTuner}</div> : null}
              {chuyenGia.email ? <div><span className="text-[#8A8A85]">Email: </span>{chuyenGia.email}</div> : null}
              {chuyenGia.soDienThoai ? <div><span className="text-[#8A8A85]">SĐT: </span>{chuyenGia.soDienThoai}</div> : null}
              {chuyenGia.gioiTinh ? <div><span className="text-[#8A8A85]">Giới tính: </span>{chuyenGia.gioiTinh}</div> : null}
              {chuyenGia.ngaySinh ? <div><span className="text-[#8A8A85]">Ngày sinh: </span>{chuyenGia.ngaySinh}</div> : null}
              {chuyenGia.diaChi ? <div><span className="text-[#8A8A85]">Địa chỉ: </span>{chuyenGia.diaChi}</div> : null}
              <div><span className="text-[#8A8A85]">Dự án hoàn thành: </span>{chuyenGia.soDuAn}</div>
              <div><span className="text-[#8A8A85]">Đánh giá: </span>{chuyenGia.diemDanhGia ? `${chuyenGia.diemDanhGia}/5 (${chuyenGia.soDanhGia} đánh giá)` : "Chưa có"}</div>
            </div>

            <a href="#dat-lich" className="inline-block rounded-lg bg-[#17171A] px-6 py-3.5 text-sm font-semibold text-white">
              Đặt lịch tư vấn với {chuyenGia.ten.split(" ").pop()}
            </a>
          </div>
        </div>

        <ChuyenGiaDatLichForm chuyenGia={chuyenGia} />

        <div className="mt-12 grid gap-8 lg:grid-cols-3">
          <section className="rounded-2xl border border-[#ECECE9] bg-white p-5">
            <h2 className="mb-4 text-lg font-extrabold">Lịch làm việc</h2>
            {chuyenGia.lichLamViec.length === 0 ? <p className="text-sm text-[#63635E]">Chưa có lịch làm việc.</p> : (
              <ul className="space-y-2 text-sm">
                {chuyenGia.lichLamViec.map((lich, index) => (
                  <li key={lich.id ?? index} className="flex justify-between gap-3">
                    <span>{lich.ngay_lam_viec}</span>
                    <span className="text-[#63635E]">{lich.ca_lam_viec}</span>
                  </li>
                ))}
              </ul>
            )}
          </section>

          <section className="rounded-2xl border border-[#ECECE9] bg-white p-5">
            <h2 className="mb-4 text-lg font-extrabold">Đánh giá</h2>
            {chuyenGia.danhGia.length === 0 ? <p className="text-sm text-[#63635E]">Chưa có đánh giá.</p> : (
              <ul className="space-y-3 text-sm">
                {chuyenGia.danhGia.map((dg, index) => (
                  <li key={dg.id ?? index}>
                    <div className="font-semibold">{dg.ten_khach_hang || "Khách hàng"} · {dg.so_sao}/5</div>
                    <div className="text-[#63635E]">{dg.noi_dung}</div>
                  </li>
                ))}
              </ul>
            )}
          </section>

          <section className="rounded-2xl border border-[#ECECE9] bg-white p-5">
            <h2 className="mb-4 text-lg font-extrabold">Lịch sử công việc</h2>
            {chuyenGia.lichSuCongViec.length === 0 ? <p className="text-sm text-[#63635E]">Chưa có công việc.</p> : (
              <ul className="space-y-3 text-sm">
                {chuyenGia.lichSuCongViec.map((cv, index) => (
                  <li key={cv.id ?? index}>
                    <div className="font-semibold">{cv.tieu_de}</div>
                    <div className="text-[#63635E]">{[cv.ten_xe, cv.ngay_hoan_thanh].filter(Boolean).join(" · ")}</div>
                  </li>
                ))}
              </ul>
            )}
          </section>
        </div>

        {/* Gói độ liên quan */}
        {loiGoiDo ? <p role="alert" className="mt-10 text-sm text-red-700">{loiGoiDo}</p> : null}
        {dangTaiGoiDo ? <p className="mt-10 text-sm text-[#63635E]">Đang tải gói độ phù hợp...</p> : null}
        {goiDoLienQuan.length > 0 && (
          <div className="mt-16">
            <h2 className="mb-6 text-2xl font-extrabold tracking-[-0.3px]">
              Gói độ {chuyenGia.ten.split(" ").pop()} thường thực hiện
            </h2>

            <div className="grid grid-cols-[repeat(auto-fill,minmax(300px,1fr))] gap-6">
              {goiDoLienQuan.map((goi) => (
                <Link
                  key={goi.id ?? goi.slug}
                  to={`/goi-do/${goi.slug}`}
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
                      {goi.hang.ten}{goi.hangMuc ? ` · ${goi.hangMuc}` : ""}
                    </div>
                    <div className="mb-1.5 text-lg font-bold">{goi.ten}</div>
                    <div className="text-base font-extrabold text-[#AC0021]">{goi.gia}</div>
                  </div>
                </Link>
              ))}
            </div>
          </div>
        )}
        </> : null}
      </div>
    </>
  );
}
