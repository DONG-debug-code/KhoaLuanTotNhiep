import React from 'react'
import { Link } from 'react-router-dom'

export const CarItem = ({ xeHienThi }) => {
    return (
        <>
            {xeHienThi.map((xe) => (
                <Link
                    to={`/xe-dang-ban/${xe.slug}`}
                    key={xe.slug}
                    className="group flex flex-col overflow-hidden rounded-2xl border border-[#ECECE9] bg-white shadow-sm transition-shadow duration-300 hover:shadow-lg"
                >
                    {/* Image */}
                    <div className="relative aspect-[4/3] overflow-hidden bg-[linear-gradient(160deg,#F4F4F2_0%,#E4E4E0_100%)]">
                        <img
                            src={xe.anhGallery?.[0]}
                            alt={xe.ten}
                            className="relative z-[1] h-full w-full object-cover transition-transform duration-500 ease-out group-hover:scale-110"
                            onError={(e) => {
                                e.currentTarget.style.display = "none";
                            }}
                        />

                        <span className="absolute left-3.5 top-3.5 z-[2] flex items-center gap-1.5 rounded-full bg-white/90 px-2 py-[1.5px] text-[12px] text-[#17171A]">
                            <span className="h-1.5 w-1.5 rounded-full bg-[#2FA84F]" />
                            {xe.trangThai}
                        </span>

                        {xe.nhan && (
                            <span className="absolute left-3.5 top-[50px] z-[2] rounded-full bg-[#17171A] px-3 py-[1.5px] text-[12px] text-white">
                                {xe.nhan}
                            </span>
                        )}
                    </div>

                    {/* Info */}
                    <div className="flex flex-1 flex-col px-5 pb-5 pt-4">
                        <div className="mb-1 text-[12px] font-semibold tracking-[0.3px] text-[#8A8A85]">
                            PORSCHE {xe.dong} · {xe.nam}
                        </div>

                        <div className="mb-2 text-xs-l font-bold">
                            {xe.ten}
                        </div>

                        <div className="grid grid-cols-3 gap-2 border-y border-[#ECECE9] py-2">
                            <div>
                                <div className="text-[12px] text-[#8A8A85]">Công suất</div>
                                <div className="mt-0.5 text-[14px] font-semibold">{xe.congSuat}</div>
                            </div>
                            <div>
                                <div className="text-[12px] text-[#8A8A85]">0–100 km/h</div>
                                <div className="mt-0.5 text-[14px] font-semibold">{xe.toc0_100}</div>
                            </div>
                            <div>
                                <div className="text-[12px] text-[#8A8A85]">Tốc độ tối đa</div>
                                <div className="mt-0.5 text-[14px] font-semibold">{xe.tocDoToiDa}</div>
                            </div>
                        </div>

                        <div className="mt-2 flex items-end justify-between gap-3">
                            <div>
                                <div className="text-[12px] text-[#8A8A85]">Giá niêm yết từ</div>
                                <div className="text-l font-extrabold text-[#c71430]">{xe.gia}</div>
                            </div>

                            <span className="flex shrink-0 items-center gap-1.5 rounded-lg bg-[#17171A] px-2 py-2 text-xs font-semibold text-white">
                                Xem chi tiết →
                            </span>
                        </div>
                    </div>
                </Link>
            ))}
        </>
    )
}