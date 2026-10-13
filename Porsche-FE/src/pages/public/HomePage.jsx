import { useEffect, useState } from "react";
import Navbar from "../../components/layouts/partials/Navbar";
import DangKyLaiThu from "../../components/sections/DangKyLaiThu";
import HangPhuTungSection from "../../components/sections/HangPhuTungSection";
import ChuyenGiaSection from "../../components/sections/ChuyenGiaSection";
import TroLyAISection from "../../components/sections/TroLyAISection";
import Footer from "../../components/layouts/partials/Footer";
import { HeroBanner } from "./HeroBanner";
import { CarDangCo } from "./CarDangCo";
import { useLocation } from "react-router-dom";
import { getHomePageData } from "../../api/PublicApi";


export default function HomePage() {
    const [homeData, setHomeData] = useState(null);
    const [loiTaiHome, setLoiTaiHome] = useState("");
    const location = useLocation();

    useEffect(() => {
        getHomePageData()
            .then(setHomeData)
            .catch((error) => setLoiTaiHome(error.response?.data?.message || "Không tải được dữ liệu trang chủ."));
    }, []);

    useEffect(() => {
        if (location.state?.cuonToi) {
            // Đợi 1 nhịp để DOM render xong rồi mới cuộn
            setTimeout(() => {
                document.getElementById(location.state.cuonToi)?.scrollIntoView({ behavior: "smooth" });
            }, 100);
        }
    }, [location.state]);

    return (
        <div>
            <Navbar />
            {/* herobanner */}
            <HeroBanner data={homeData} />
            {loiTaiHome ? <p role="alert" className="bg-[#FAFAF9] px-6 py-3 text-sm text-red-700">{loiTaiHome}</p> : null}
            {/* sanphamdangco */}
            <CarDangCo />

           
            <TroLyAISection />
            <HangPhuTungSection />
            <ChuyenGiaSection />

            {/* Dăng ký lái thử */}
            <DangKyLaiThu />
            <Footer />
        </div>
    );
}