import { MapPin, Phone, Send, Maximize2 } from "lucide-react";

const DONG_XE = ["911", "718", "Taycan", "Panamera", "Cayenne", "Macan"];

export default function DangKyLaiThu() {
    return (
        <div id="dang-ky-lai-thu" className="bg-[#F3F3F0] scroll-mt-[77.3px] py-16 max-[860px]:px-5 max-[860px]:py-10 px-6">
            <div className="grid grid-cols-1 gap-14 lg:grid-cols-2">

                {/* Left */}
                <div>
                    <h2 className="mb-4 text-[30px] font-extrabold leading-[1.15] tracking-[-0.3px] max-[860px]:text-[28px]">
                        Đăng ký lái thử &amp; nhận tư vấn mua xe
                    </h2>

                    <p className="mb-6 max-w-[520px] text-[13px] leading-[1.7] text-[#63635E]">
                        Để lại thông tin, đội ngũ tư vấn showroom sẽ liên hệ trong vòng 24
                        giờ làm việc. Bạn có thể nêu rõ dòng xe quan tâm và mong muốn về
                        cấu hình.
                    </p>

                    {/* Info blocks */}
                    <div className="mb-8 flex flex-col gap-3">
                        <div className="flex items-start gap-3.5">
                            <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white shadow-sm">
                                <MapPin className="h-4.5 w-4.5 text-[#17171A]" />
                            </span>
                            <div>
                                <div className="text-[15px] font-bold">Showroom &amp; xưởng dịch vụ</div>
                                <div className="mt-0.5 text-sm text-[#63635E]">
                                    88 Nguyễn Hữu Cảnh, Quận 1, TP. Hồ Chí Minh
                                </div>
                            </div>
                        </div>

                        <div className="flex items-start gap-3.5">
                            <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white shadow-sm">
                                <Phone className="h-4.5 w-4.5 text-[#17171A]" />
                            </span>
                            <div>
                                <div className="text-[15px] font-bold">Hotline kinh doanh</div>
                                <div className="mt-0.5 text-sm text-[#63635E]">
                                    0900 123 456 · 08:00 – 20:00 hằng ngày
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Map */}
                    <div className="relative aspect-[16/10] overflow-hidden rounded-2xl border border-[#ECECE9]">
                        <iframe
                            title="Bản đồ showroom"
                            src="https://www.google.com/maps?q=88+Nguyễn+Hữu+Cảnh,+Quận+1,+TP.+Hồ+Chí+Minh&output=embed"
                            className="h-full w-full"
                            loading="lazy"
                        />
                        <a
                            href="https://www.google.com/maps?q=88+Nguyễn+Hữu+Cảnh,+Quận+1,+TP.+Hồ+Chí+Minh"
                            target="_blank"
                            rel="noreferrer"
                            className="absolute left-3.5 top-3.5 flex items-center gap-1.5 rounded-lg bg-white px-3 py-2 text-sm font-semibold text-[#1a56db] shadow-sm"
                        >
                            Mở trong Maps ↗
                        </a>
                        <button
                            type="button"
                            className="absolute bottom-3.5 right-3.5 flex h-9 w-9 items-center justify-center rounded-full bg-white shadow-sm"
                        >
                            <Maximize2 className="h-4 w-4 text-[#17171A]" />
                        </button>
                    </div>
                </div>

                {/* Right — Form */}
                <div className="rounded-2xl border border-[#ECECE9] bg-white p-8 max-[860px]:p-5">
                    <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <label className="mb-1.5 block text-sm font-semibold">
                                Họ và tên <span className="text-[#E23C3C]">*</span>
                            </label>
                            <input
                                type="text"
                                placeholder="Nguyễn Văn An"
                                className="w-full rounded-lg border border-[#ECECE9] px-4 py-3 text-sm outline-none focus:border-[#17171A]"
                            />
                        </div>

                        <div>
                            <label className="mb-1.5 block text-sm font-semibold">
                                Số điện thoại <span className="text-[#E23C3C]">*</span>
                            </label>
                            <input
                                type="tel"
                                placeholder="0900 123 456"
                                className="w-full rounded-lg border border-[#ECECE9] px-4 py-3 text-sm outline-none focus:border-[#17171A]"
                            />
                        </div>
                    </div>

                    <div className="mt-5">
                        <label className="mb-1.5 block text-sm font-semibold">
                            Email <span className="text-[#E23C3C]">*</span>
                        </label>
                        <input
                            type="email"
                            placeholder="ban@email.com"
                            className="w-full rounded-lg border border-[#ECECE9] px-4 py-3 text-sm outline-none focus:border-[#17171A]"
                        />
                    </div>

                    <div className="mt-5">
                        <label className="mb-1.5 block text-sm font-semibold">Dòng xe quan tâm</label>
                        <select
                            defaultValue=""
                            className="w-full rounded-lg border border-[#ECECE9] px-4 py-3 text-sm text-[#17171A] outline-none focus:border-[#17171A]"
                        >
                            <option value="" disabled>Chọn dòng xe</option>
                            {DONG_XE.map((xe) => (
                                <option key={xe} value={xe}>{xe}</option>
                            ))}
                        </select>
                    </div>

                    <div className="mt-5">
                        <label className="mb-1.5 block text-sm font-semibold">Ghi chú</label>
                        <textarea
                            rows={4}
                            placeholder="Ví dụ: Tôi muốn lái thử 911 Carrera GTS vào cuối tuần, quan tâm gói độ xả Akrapovič."
                            className="w-full resize-none rounded-lg border border-[#ECECE9] px-4 py-3 text-sm outline-none focus:border-[#17171A]"
                        />
                    </div>

                    <button
                        type="submit"
                        className="mt-6 flex w-full items-center justify-center gap-2 rounded-lg bg-[#B60F0F] py-3.5 text-[15px] font-semibold text-white"
                    >
                        <Send className="h-4 w-4" />
                        Gửi yêu cầu tư vấn
                    </button>

                    <p className="mt-4 text-xs leading-[1.6] text-[#8A8A85]">
                        Bằng việc gửi thông tin, bạn đồng ý để chúng tôi liên hệ tư vấn qua
                        điện thoại hoặc email.
                    </p>
                </div>
            </div>
        </div>
    );
}