import { Link } from "react-router-dom";
import { MapPin, Phone, Mail } from "lucide-react";
import { FaFacebookF, FaInstagram, FaYoutube, FaTiktok } from "react-icons/fa";

const LINK_SHOWROOM = [
    { ten: "Xe đang bán", duongDan: "/#xe-dang-ban" },
    { ten: "Hãng phụ tùng & gói độ", duongDan: "/hang-phu-tung" },
    { ten: "Đội ngũ chuyên gia", duongDan: "/chuyen-gia" },
    { ten: "Đăng ký lái thử", duongDan: "/#dang-ky-lai-thu" },
];

const LINK_DICH_VU = [
    { ten: "AI tư vấn gói độ", duongDan: "/#tro-ly-ai" },
    { ten: "AI tư vấn bảo dưỡng", duongDan: "/#tro-ly-ai" },
    { ten: "Garage của tôi", duongDan: "/garage" },
    { ten: "Khu quản trị", duongDan: "/admin" },
];

export default function Footer() {
    return (
        <footer className="bg-[#FAFAF9] px-12 pt-14 max-[860px]:px-5">
            <div className="grid grid-cols-1 gap-10 border-b border-[#ECECE9] pb-12 sm:grid-cols-2 lg:grid-cols-4">

                {/* Logo + mô tả + social */}
                <div>
                    <div className="mb-4 flex items-center gap-3">
                        <img className="w-[100px]" src="/src/assets/img/logo.png" alt="" />
                    </div>

                    <p className="mb-5 max-w-[280px] text-sm leading-[1.7] text-[#63635E]">
                        Showroom xe Porsche chính hãng kèm dịch vụ độ xe và bảo dưỡng chuyên sâu, cùng hai
                        trợ lý AI tư vấn 24/7.
                    </p>

                    <div className="flex gap-2.5">
                        {[FaFacebookF, FaInstagram, FaYoutube, FaTiktok].map((Icon, i) => (
                            <a
                                key={i}
                                href="#"
                                className="flex h-9 w-9 items-center justify-center rounded-full border border-[#ECECE9] text-[#17171A] hover:bg-[#F1F1EF]"
                            >
                                <Icon className="h-3.5 w-3.5" />
                            </a>
                        ))}
                    </div>
                </div>

                {/* Showroom */}
                <div>
                    <div className="mb-4 text-sm font-bold">Showroom</div>
                    <div className="flex flex-col gap-3">
                        {LINK_SHOWROOM.map((link) => (
                            <Link
                                key={link.ten}
                                to={link.duongDan}
                                className="text-sm text-[#63635E] hover:text-[#17171A]"
                            >
                                {link.ten}
                            </Link>
                        ))}
                    </div>
                </div>

                {/* Dịch vụ */}
                <div>
                    <div className="mb-4 text-sm font-bold">Dịch vụ</div>
                    <div className="flex flex-col gap-3">
                        {LINK_DICH_VU.map((link) => (
                            <Link
                                key={link.ten}
                                to={link.duongDan}
                                className="text-sm text-[#63635E] hover:text-[#17171A]"
                            >
                                {link.ten}
                            </Link>
                        ))}
                    </div>
                </div>

                {/* Liên hệ */}
                <div>
                    <div className="mb-4 text-sm font-bold">Liên hệ</div>
                    <div className="flex flex-col gap-3.5">
                        <div className="flex items-start gap-2.5 text-sm text-[#63635E]">
                            <MapPin className="mt-0.5 h-4 w-4 shrink-0" />
                            88 Nguyễn Hữu Cảnh, Quận 1, TP. Hồ Chí Minh
                        </div>
                        <div className="flex items-center gap-2.5 text-sm text-[#63635E]">
                            <Phone className="h-4 w-4 shrink-0" />
                            0900 123 456
                        </div>
                        <div className="flex items-center gap-2.5 text-sm text-[#63635E]">
                            <Mail className="h-4 w-4 shrink-0" />
                            sales@duanxe.vn
                        </div>
                    </div>
                </div>
            </div>

            {/* Copyright bar */}
            <div className="flex flex-col justify-center gap-3 py-6 text-xs text-[#8A8A85] sm:flex-row sm:items-center">
                <span>© 2026 DU_AN_XE. Giá và tồn kho sản phẩm được xác nhận theo dữ liệu showroom.</span>

                <div className="flex gap-5">
                    <a href="#" className="hover:text-[#17171A]">Điều khoản</a>
                    <a href="#" className="hover:text-[#17171A]">Bảo mật</a>
                </div>
            </div>
        </footer>
    );
}