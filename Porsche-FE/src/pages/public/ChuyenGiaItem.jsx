import React from 'react'
import { Link } from 'react-router-dom';

const TRANG_THAI_HIEN_THI = {
  "san-sang": { nhan: "Sẵn sàng nhận lịch", cham: "bg-[#2FA84F]", nen: "bg-white/90" },
  "dang-kin-lich": { nhan: "Đang kín lịch", cham: "bg-[#D9A441]", nen: "bg-white/90" },
  "tam-nghi": { nhan: "Tạm nghỉ", cham: "bg-[#9A9992]", nen: "bg-white/90" },
};

export const ChuyenGiaItem = ({CHUYEN_GIA}) => {
    return (
        <>
            {CHUYEN_GIA.map((cg) => {
                const trangThai = TRANG_THAI_HIEN_THI[cg.trangThai] ?? TRANG_THAI_HIEN_THI["san-sang"];
                return (
                    <Link
                        key={cg.slug}
                        to={`/chuyen-gia/${cg.id ?? cg.slug}`}
                        className="group overflow-hidden rounded-2xl border border-[#ECECE9] bg-white shadow-sm transition-shadow hover:shadow-lg"
                    >
                        <div className="relative aspect-[4/4] overflow-hidden bg-[#F1F1EF]">
                            <img
                                src={cg.anh}
                                alt={cg.ten}
                                className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-110"
                                onError={(e) => (e.currentTarget.style.display = "none")}
                            />

                            <span className={`absolute right-3 top-3 flex items-center gap-1.5 rounded-full ${trangThai.nen} px-2.5 py-1 text-xs font-semibold text-[#17171A]`}>
                                <span className={`h-1.5 w-1.5 rounded-full ${trangThai.cham}`} />
                                {trangThai.nhan}
                            </span>
                        </div>

                        <div className="p-4">
                            <div className="mb-0.5 font-bold">{cg.ten}</div>
                            <div className="mb-2 text-sm text-[#63635E]">{cg.chucDanh}</div>
                            <p className="text-xs leading-[1.6] text-[#8A8A85] line-clamp-3">{cg.moTa}</p>
                        </div>
                    </Link>
                );
            })}
        </>
    )
}
