import { Wrench, HeartPulse, MessageCircle } from "lucide-react";

const GOI_Y_DO_XE = ["Gói độ cho 911 Carrera", "Âm thanh xả sporty hơn", "Nâng cấp treo đường phố"];
const GOI_Y_BAO_DUONG = ["Xe 15.000 km cần làm gì?", "Khi nào thay dầu PDK?", "Dấu hiệu thay má phanh"];

export default function TroLyAISection() {
  return (
    <div className="bg-[#0A0A0B] px-[1.3rem] py-20 text-white max-[860px]:py-14">
      <h2 className="mb-4 max-w-[720px] text-[38px] text-white font-extrabold leading-[1.15] tracking-[-0.3px] max-[860px]:text-[26px]">
        Hai trợ lý AI hỗ trợ sau khi bạn đã có xe
      </h2>
      <p className="mb-12 max-w-[680px] text-[15px] leading-[1.7] text-[#9A9992]">
        Đây là kênh hỗ trợ phụ, luôn sẵn ở góc màn hình. Bạn có thể hỏi nhanh về gói độ phù hợp
        hoặc lịch bảo dưỡng định kỳ, rồi chuyển thẳng sang đặt lịch với chuyên gia.
      </p>

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
        <div className="rounded-2xl border border-white/10 bg-white/[0.03] p-8">
          <div className="mb-5 flex items-center gap-3.5">
            <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#AC0021]">
              <Wrench className="h-5 w-5 text-white" />
            </span>
            <div>
              <div className="text-lg font-bold">Tư vấn gói độ xe</div>
              <div className="text-xs text-[#9A9992]">Trợ lý AI · phản hồi tức thời 24/7</div>
            </div>
          </div>

          <p className="mb-5 text-[15px] leading-[1.7] text-[#C9C8C3]">
            Dành cho khách đã sở hữu Porsche. AI gợi ý gói nâng cấp theo dòng xe, mục đích sử
            dụng và ngân sách — từ hệ thống xả, treo, mâm cho tới nội thất.
          </p>

          <div className="mb-6 flex flex-wrap gap-2">
            {GOI_Y_DO_XE.map((goiY) => (
              <button key={goiY} className="rounded-full border border-white/15 px-3.5 py-1.5 text-xs text-[#E8E7E2]">
                {goiY}
              </button>
            ))}
          </div>

          <button className="flex items-center gap-2 rounded-lg bg-white px-5 py-3 text-sm font-semibold text-[#0A0A0B]">
            <MessageCircle className="h-4 w-4" />
            Bắt đầu tư vấn
          </button>
        </div>

        <div className="rounded-2xl border border-white/10 bg-white/[0.03] p-8">
          <div className="mb-5 flex items-center gap-3.5">
            <span className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-[#B08D57]">
              <HeartPulse className="h-5 w-5 text-white" />
            </span>
            <div>
              <div className="text-lg font-bold">Tư vấn bảo dưỡng</div>
              <div className="text-xs text-[#9A9992]">Trợ lý AI · phản hồi tức thời 24/7</div>
            </div>
          </div>

          <p className="mb-5 text-[15px] leading-[1.7] text-[#C9C8C3]">
            AI đối chiếu số km và lịch sử bảo dưỡng để nhắc hạng mục đến kỳ: thay dầu, má phanh,
            lọc gió, dầu hộp số PDK — kèm thời điểm nên đặt lịch.
          </p>

          <div className="mb-6 flex flex-wrap gap-2">
            {GOI_Y_BAO_DUONG.map((goiY) => (
              <button key={goiY} className="rounded-full border border-white/15 px-3.5 py-1.5 text-xs text-[#E8E7E2]">
                {goiY}
              </button>
            ))}
          </div>

          <button className="flex items-center gap-2 rounded-lg bg-white px-5 py-3 text-sm font-semibold text-[#0A0A0B]">
            <MessageCircle className="h-4 w-4" />
            Bắt đầu tư vấn
          </button>
        </div>
      </div>
    </div>
  );
}
