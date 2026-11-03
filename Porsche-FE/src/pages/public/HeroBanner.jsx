import React, { useEffect, useRef, useState } from 'react'
import { BASE_URL } from '../../api/config'

const HERO_VIDEO_URL = `${BASE_URL.replace(/\/api\/?$/, "")}/storage/products/herobanner.mp4`;

export const HeroBanner = ({ data }) => {
    const videoRef = useRef(null);
    const [muted, setMuted] = useState(true);

    // Trình duyệt chặn autoplay có tiếng: thử phát có tiếng trước,
    // nếu bị chặn thì phát tạm không tiếng và bật tiếng ở lần tương tác đầu tiên.
    useEffect(() => {
        const video = videoRef.current;
        if (!video) return undefined;

        const events = ["pointerdown", "keydown", "touchend"];
        const removeListeners = () => events.forEach((name) => window.removeEventListener(name, unmuteOnInteraction));

        function unmuteOnInteraction() {
            video.muted = false;
            video.play().then(() => {
                setMuted(false);
                removeListeners();
            }).catch(() => {});
        }

        video.muted = false;
        video.play().then(() => setMuted(false)).catch(() => {
            video.muted = true;
            video.play().catch(() => {});
            setMuted(true);
            events.forEach((name) => window.addEventListener(name, unmuteOnInteraction));
        });

        return removeListeners;
    }, []);

    const toggleMute = (e) => {
        e.stopPropagation();
        const video = videoRef.current;
        if (!video) return;
        video.muted = !video.muted;
        setMuted(video.muted);
        if (video.paused) video.play().catch(() => {});
    };

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
        <div className="relative select-none min-h-screen overflow-hidden flex flex-col font-sans text-white box-border">
            {/* Fallback background (hiện khi video lỗi/chưa load) */}
            <div className="absolute inset-0 z-0 bg-[radial-gradient(ellipse_at_30%_20%,rgba(60,20,20,0.4),transparent_60%),linear-gradient(180deg,#1a1a1c_0%,#0d0d0e_60%,#0A0A0B_100%)]" />

            <video
                ref={videoRef}
                className="absolute inset-0 z-0 w-full h-full object-cover"
                autoPlay
                loop
                playsInline
                onError={(e) => (e.currentTarget.style.display = "none")}
            >
                <source src={HERO_VIDEO_URL} type="video/mp4" />
            </video>

            <button
                type="button"
                onClick={toggleMute}
                aria-label={muted ? "Bật tiếng video" : "Tắt tiếng video"}
                className="absolute bottom-6 right-6 z-[3] flex items-center gap-2 bg-black/50 hover:bg-black/70 text-white text-[13px] px-4 py-2 rounded-full border border-white/20 cursor-pointer backdrop-blur"
            >
                {muted ? "🔇 Bật tiếng" : "🔊 Tắt tiếng"}
            </button>

            <div className="relative z-[2] flex flex-col min-h-screen">
                <div className="flex-1 flex flex-col items-start justify-center text-left px-5 min-[861px]:px-12 pt-24 pb-16">
                    <div className="inline-flex items-center gap-2 text-[12px] text-white border border-white/25 px-3.5 py-1 rounded-full mb-5">
                        <span className="w-1.5 h-1.5 rounded-full bg-[#AC0021]" />
                        Showroom Porsche · TP. Đà Nẵng
                    </div>

                    <h1 className="text-white text-[28px] md:text-[44px] leading-[1.1] font-bold max-w-[620px] mb-7 tracking-[-0.5px] [text-shadow:0_2px_14px_rgba(0,0,0,0.45)]">
                        Khí chất <span className="text-[#E0112F]">Porsche</span>,<br />
                        chuẩn mực showroom.
                    </h1>

                    <div className="flex flex-row flex-wrap gap-3 mb-10">
                        <button onClick={() => document.getElementById("xe-dang-ban")?.scrollIntoView({ behavior: "smooth" })}
                         className="bg-[#AC0021] hover:bg-[#c4002a] transition-colors text-white text-[14px] font-semibold px-5 py-3 rounded-[9px] border-0 cursor-pointer flex items-center justify-center gap-2.5">
                            Khám phá xe đang bán
                        </button>
                        <button 
                            onClick={() => document.getElementById("dang-ky-lai-thu")?.scrollIntoView({ behavior: "smooth" })}
                            className="bg-white/10 hover:bg-white/20 transition-colors backdrop-blur-sm text-white text-[14px] font-semibold px-5 py-3 rounded-[9px] border border-white/25 cursor-pointer flex items-center justify-center gap-2.5">
                            Đăng ký lái thử
                        </button>
                    </div>

                    <div className="flex flex-wrap gap-x-10 gap-y-4 border-t border-white/20 pt-6 [text-shadow:0_1px_8px_rgba(0,0,0,0.45)]">
                        <div>
                            <div className="text-[24px] font-bold mb-0.5">{getStat(["xe_dang_trung_bay", "xe_dang_ban", "cars"], "24+")}</div>
                            <div className="text-[12px] text-white">Xe đang trưng bày</div>
                        </div>
                        <div>
                            <div className="text-[24px] font-bold mb-0.5">{getStat(["hang_phu_tung", "hang_do", "brands"], 18)}</div>
                            <div className="text-[12px] text-white">Gói độ &amp; phụ tùng</div>
                        </div>
                        <div>
                            <div className="text-[24px] font-bold mb-0.5">{getStat(["chuyen_gia_tuning", "chuyen_gia", "experts"], 12)}</div>
                            <div className="text-[12px] text-white">Chuyên gia / tuner</div>
                        </div>
                        <div>
                            <div className="text-[24px] font-bold mb-0.5">{getStat(["nam_kinh_nghiem", "so_nam_kinh_nghiem"], 15)}</div>
                            <div className="text-[12px] text-white">Năm kinh nghiệm</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    )
}
