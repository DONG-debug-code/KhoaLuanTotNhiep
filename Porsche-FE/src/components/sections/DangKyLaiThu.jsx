import { useState } from "react";
import { MapPin, Phone, Send, Maximize2 } from "lucide-react";
import { dangKyLaiThu } from "../../api/PublicApi";
import { getApiErrorMessage } from "../../api/Authapi";
import { HOTLINE_HIEN_THI } from "../../constants/shop";

const DONG_XE = ["911", "718", "Taycan", "Panamera", "Cayenne", "Macan"];
const GIO_LAI_THU = ["08:00", "09:00", "10:00", "11:00", "13:00", "14:00", "15:00", "16:00", "17:00"];
const FORM_TRONG = { ho_ten: "", so_dien_thoai: "", email: "", dong_xe: "", ngay_hen: "", gio_hen: "", ghi_chu: "" };

const layNgayMai = () => {
    const ngay = new Date();
    ngay.setDate(ngay.getDate() + 1);
    return new Date(ngay.getTime() - ngay.getTimezoneOffset() * 60000).toISOString().slice(0, 10);
};

export default function DangKyLaiThu() {
    const [form, setForm] = useState(FORM_TRONG);
    const [dangGui, setDangGui] = useState(false);
    const [ketQua, setKetQua] = useState(null);

    const capNhat = (truong) => (event) => setForm((cu) => ({ ...cu, [truong]: event.target.value }));

    const guiYeuCau = async (event) => {
        event.preventDefault();
        setKetQua(null);
        setDangGui(true);
        try {
            const du_lieu = Object.fromEntries(Object.entries(form).filter(([, giaTri]) => giaTri !== ""));
            const phanHoi = await dangKyLaiThu(du_lieu);
            setKetQua({ thanhCong: true, noiDung: phanHoi.message });
            setForm(FORM_TRONG);
        } catch (error) {
            setKetQua({ thanhCong: false, noiDung: getApiErrorMessage(error, "Không thể gửi yêu cầu. Vui lòng thử lại.") });
        } finally {
            setDangGui(false);
        }
    };

    return (
        <div id="dang-ky-lai-thu" className="bg-[#F3F3F0] scroll-mt-[80px] py-16 max-[860px]:py-10 px-[1.3rem]">
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
                                    {HOTLINE_HIEN_THI} · 08:00 – 20:00 hằng ngày
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
                <form onSubmit={guiYeuCau} className="rounded-2xl border border-[#ECECE9] bg-white p-8 max-[860px]:p-5">
                    <div className="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <label className="mb-1.5 block text-sm font-semibold">
                                Họ và tên <span className="text-[#E23C3C]">*</span>
                            </label>
                            <input
                                type="text"
                                required
                                value={form.ho_ten}
                                onChange={capNhat("ho_ten")}
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
                                required
                                value={form.so_dien_thoai}
                                onChange={capNhat("so_dien_thoai")}
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
                            required
                            value={form.email}
                            onChange={capNhat("email")}
                            placeholder="ban@email.com"
                            className="w-full rounded-lg border border-[#ECECE9] px-4 py-3 text-sm outline-none focus:border-[#17171A]"
                        />
                    </div>

                    <div className="mt-5">
                        <label className="mb-1.5 block text-sm font-semibold">Dòng xe quan tâm</label>
                        <select
                            value={form.dong_xe}
                            onChange={capNhat("dong_xe")}
                            className="w-full rounded-lg border border-[#ECECE9] px-4 py-3 text-sm text-[#17171A] outline-none focus:border-[#17171A]"
                        >
                            <option value="">Chọn dòng xe</option>
                            {DONG_XE.map((xe) => (
                                <option key={xe} value={xe}>{xe}</option>
                            ))}
                        </select>
                    </div>

                    <div className="mt-5 grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <label className="mb-1.5 block text-sm font-semibold">
                                Ngày lái thử <span className="text-[#E23C3C]">*</span>
                            </label>
                            <input
                                type="date"
                                required
                                min={layNgayMai()}
                                value={form.ngay_hen}
                                onChange={capNhat("ngay_hen")}
                                className="w-full rounded-lg border border-[#ECECE9] px-4 py-3 text-sm outline-none focus:border-[#17171A]"
                            />
                        </div>
                        <div>
                            <label className="mb-1.5 block text-sm font-semibold">
                                Giờ lái thử <span className="text-[#E23C3C]">*</span>
                            </label>
                            <select
                                required
                                value={form.gio_hen}
                                onChange={capNhat("gio_hen")}
                                className="w-full rounded-lg border border-[#ECECE9] px-4 py-3 text-sm text-[#17171A] outline-none focus:border-[#17171A]"
                            >
                                <option value="">Chọn giờ</option>
                                {GIO_LAI_THU.map((gio) => (
                                    <option key={gio} value={gio}>{gio}</option>
                                ))}
                            </select>
                        </div>
                    </div>

                    <div className="mt-5">
                        <label className="mb-1.5 block text-sm font-semibold">Ghi chú</label>
                        <textarea
                            rows={4}
                            value={form.ghi_chu}
                            onChange={capNhat("ghi_chu")}
                            placeholder="Ví dụ: Tôi muốn lái thử 911 Carrera GTS vào cuối tuần, quan tâm gói độ xả Akrapovič."
                            className="w-full resize-none rounded-lg border border-[#ECECE9] px-4 py-3 text-sm outline-none focus:border-[#17171A]"
                        />
                    </div>

                    {ketQua && (
                        <div
                            role="status"
                            className={`mt-5 rounded-lg px-4 py-3 text-sm ${ketQua.thanhCong ? "bg-[#E8F6EC] text-[#1F7A3A]" : "bg-[#FBE9E9] text-[#AC0021]"}`}
                        >
                            {ketQua.noiDung}
                        </div>
                    )}

                    <button
                        type="submit"
                        disabled={dangGui}
                        className="mt-6 flex w-full items-center justify-center gap-2 rounded-lg bg-[#AC0021] py-3.5 text-[15px] font-semibold text-white disabled:opacity-60"
                    >
                        <Send className="h-4 w-4" />
                        {dangGui ? "Đang gửi..." : "Gửi yêu cầu tư vấn"}
                    </button>

                    <p className="mt-4 text-xs leading-[1.6] text-[#8A8A85]">
                        Bằng việc gửi thông tin, bạn đồng ý để chúng tôi liên hệ tư vấn qua
                        điện thoại hoặc email. Thông tin thời gian và địa điểm lái thử sẽ được gửi qua email,
                        bạn có thể trả lời trực tiếp email đó để trao đổi với nhân viên.
                    </p>
                </form>
            </div>
        </div>
    );
}
