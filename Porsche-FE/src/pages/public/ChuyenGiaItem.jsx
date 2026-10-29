import { Link } from "react-router-dom";
import { CalendarCheck } from "lucide-react";

const TRANG_THAI_HIEN_THI = {
  "san-sang": { nhan: "Sẵn sàng nhận lịch", cham: "bg-[#2FA84F]" },
  "dang-kin-lich": { nhan: "Đang kín lịch", cham: "bg-[#D9A441]" },
  "tam-nghi": { nhan: "Tạm nghỉ", cham: "bg-[#9A9992]" },
};

export const getTrangThaiChuyenGia = (value) => TRANG_THAI_HIEN_THI[value] ?? TRANG_THAI_HIEN_THI["san-sang"];

export function ChuyenGiaAnh({ chuyenGia, className = "" }) {
  const initials = String(chuyenGia.ten || "?").split(" ").slice(-2).map((word) => word[0]).join("").toUpperCase();

  return (
    <div className={`relative overflow-hidden bg-gradient-to-br from-[#2A2C31] to-[#0E0F12] ${className}`}>
      <span className="absolute inset-0 flex items-center justify-center text-6xl font-extrabold tracking-wider text-white/25">{initials}</span>
      {chuyenGia.anh ? (
        <img
          src={chuyenGia.anh}
          alt={chuyenGia.ten}
          className="absolute inset-0 h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
          onError={(event) => { event.currentTarget.style.display = "none"; }}
        />
      ) : null}
    </div>
  );
}

export const ChuyenGiaItem = ({ CHUYEN_GIA }) => (
  <>
    {CHUYEN_GIA.map((cg) => {
      const trangThai = getTrangThaiChuyenGia(cg.trangThai);
      return (
        <article key={cg.id ?? cg.slug} className="group flex flex-col overflow-hidden rounded-2xl border border-[#ECECE9] bg-white shadow-sm transition-shadow hover:shadow-lg">
          <Link to={`/chuyen-gia/${cg.id ?? cg.slug}`} className="relative block">
            <ChuyenGiaAnh chuyenGia={cg} className="aspect-[4/3.4]" />
            <span className="absolute right-3 top-3 flex items-center gap-1.5 rounded-full bg-white/90 px-2.5 py-1 text-[11px] font-semibold text-[#17171A]">
              <span className={`h-1.5 w-1.5 rounded-full ${trangThai.cham}`} />
              {trangThai.nhan}
            </span>
          </Link>

          <div className="flex flex-1 flex-col p-4">
            <Link to={`/chuyen-gia/${cg.id ?? cg.slug}`} className="text-[15px] font-bold hover:underline">{cg.ten}</Link>
            <div className="mb-2 text-[13px] text-[#63635E]">{cg.chucDanh}</div>
            <p className="mb-3 line-clamp-3 text-[13px] leading-[1.6] text-[#63635E]">{cg.moTa}</p>

            <div className="mb-4 flex flex-wrap gap-1.5">
              {cg.hangMucChuyenMon.map((tag) => (
                <span key={tag} className="rounded-full border border-[#DCDCD8] px-2.5 py-0.5 text-[11px] text-[#3B3B38]">{tag}</span>
              ))}
            </div>

            <div className="mt-auto grid grid-cols-2 gap-3 border-t border-[#ECECE9] pt-4 text-center">
              <div>
                <div className="text-[15px] font-bold">{cg.soDuAn}</div>
                <div className="text-[11px] text-[#8A8A85]">Dự án hoàn thành</div>
              </div>
              <div>
                <div className="text-[15px] font-bold">{cg.soNamKinhNghiem} năm</div>
                <div className="text-[11px] text-[#8A8A85]">Kinh nghiệm</div>
              </div>
            </div>

            <Link
              to={`/chuyen-gia/${cg.id ?? cg.slug}#dat-lich`}
              className="mt-4 flex items-center justify-center gap-2 rounded-lg border border-[#DCDCD8] py-2 text-[13px] font-semibold hover:bg-[#F6F6F4]"
            >
              <CalendarCheck className="h-4 w-4" /> Đăng ký tư vấn
            </Link>
          </div>
        </article>
      );
    })}
  </>
);
