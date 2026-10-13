import React from 'react'

export const HeroBanner = ({ data }) => {
    const home = data?.data ?? data ?? {};
    const getStat = (keys, fallback) => {
        for (const key of keys) {
            const value = home?.[key];
            if (Array.isArray(value)) return value.length;
            if (typeof value === "number" || (typeof value === "string" && value.trim())) return value;
        }
        return fallback;
    };

    return (
        <div className="relative select-none min-h-screen overflow-hidden flex flex-col font-sans text-[#F5F4F1] box-border">
            {/* Fallback background (hiện khi video lỗi/chưa load) */}
            <div className="absolute inset-0 z-0 bg-[radial-gradient(ellipse_at_30%_20%,rgba(60,20,20,0.4),transparent_60%),linear-gradient(180deg,#1a1a1c_0%,#0d0d0e_60%,#0A0A0B_100%)]" />

            <video
                className="absolute inset-0 z-0 w-full h-full object-cover"
                autoPlay
                muted
                loop
                playsInline
                onError={(e) => (e.currentTarget.style.display = "none")}
            >
                <source src="src/assets/video/01.mp4" type="video/mp4" />
            </video>

            <div className="absolute inset-0 z-[1] bg-[linear-gradient(180deg,rgba(0,0,0,0.55)_0%,rgba(0,0,0,0.25)_55%,rgba(0,0,0,0.85)_90%)]" />

            <div className="relative z-[2] flex flex-col min-h-screen">
                <div className="flex-1 flex flex-col items-center justify-center text-center px-6 pb-10">
                    <div className="inline-flex items-center gap-2 text-[13px] text-[#E8E7E2] border border-white/[0.18] px-4 py-1 rounded-full mt-20 mb-7">
                        <span className="w-1.5 h-1.5 rounded-full bg-[#c71430]" />
                        Showroom Porsche · TP. Đà Nẵng
                    </div>

                    <h1 className="text-[#d8d4d4] text-[34px] m-0 md:text-[70px] leading-[1.1] font-extrabold max-w-[820px] mb-6 tracking-[-0.5px]">
                        Khí chất <span className="text-[#c71430]">Porsche</span>,<br />
                        chuẩn mực showroom.
                    </h1>

                    <p className="text-base leading-[1.2] text-[#d8d4d4] max-w-[640px] mb-9">
                        Tuyển chọn những mẫu Porsche 911, 718, Taycan, Panamera, Cayenne và
                        Macan đang sẵn hàng. Xem thông số, so sánh phiên bản và nhận tư
                        vấn trực tiếp từ đội ngũ chuyên gia — kèm hai trợ lý AI hỗ trợ
                        chọn gói độ và lịch bảo dưỡng.
                    </p>

                    <div className="flex flex-col md:flex-row gap-3.5 mb-10 w-full max-w-[320px] md:max-w-none md:w-auto">
                        <button onClick={() => document.getElementById("xe-dang-ban")?.scrollIntoView({ behavior: "smooth" })}
                         className="bg-[#c71430] text-white text-[15px] font-semibold px-6 py-3.5 rounded-[9px] border-0 cursor-pointer flex items-center justify-center gap-2.5">
                            Khám phá xe đang bán
                        </button>
                        <button 
                            onClick={() => document.getElementById("dang-ky-lai-thu")?.scrollIntoView({ behavior: "smooth" })}
                            className="bg-white/[0.06] text-[#F5F4F1] text-[15px] font-semibold px-6 py-3.5 rounded-[9px] border border-white/[0.14] cursor-pointer flex items-center justify-center gap-2.5">
                            Đăng ký lái thử
                        </button>
                    </div>

                    <div className="flex flex-wrap gap-7 md:gap-16 justify-center md:justify-start border-t border-white/10 pt-8">
                        <div>
                            <div className="text-[32px] font-extrabold mb-1">{getStat(["xe_dang_trung_bay", "xe_dang_ban", "cars"], "24+")}</div>
                            <div className="text-[13px] text-[#9A9992]">Xe đang trưng bày</div>
                        </div>
                        <div>
                            <div className="text-[32px] font-extrabold mb-1">{getStat(["hang_phu_tung", "hang_do", "brands"], 18)}</div>
                            <div className="text-[13px] text-[#9A9992]">Gói độ &amp; phụ tùng</div>
                        </div>
                        <div>
                            <div className="text-[32px] font-extrabold mb-1">{getStat(["chuyen_gia_tuning", "chuyen_gia", "experts"], 12)}</div>
                            <div className="text-[13px] text-[#9A9992]">Chuyên gia / tuner</div>
                        </div>
                        <div>
                            <div className="text-[32px] font-extrabold mb-1">{getStat(["nam_kinh_nghiem", "so_nam_kinh_nghiem"], 15)}</div>
                            <div className="text-[13px] text-[#9A9992]">Năm kinh nghiệm</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    )
}
