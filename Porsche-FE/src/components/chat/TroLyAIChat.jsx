import { useState, useRef, useEffect } from "react";
import { Wrench, HeartPulse, History, Trash2, X, Sparkles, Send } from "lucide-react";
import { useAuth } from "../../context/AuthContext";
import { askPorscheAI, clearAIHistory, getAIHistory } from "../../api/WorkspaceApi";

const TAB = {
  goiDo: {
    ten: "Tư vấn gói độ",
    icon: Wrench,
    moTa: "Gợi ý gói độ theo xe & ngân sách",
    tinNhanMoDau: [
      "Xin chào, tôi là Trợ lý độ xe của DU_AN_XE. Bạn đang sở hữu dòng Porsche nào và muốn nâng cấp theo hướng nào ạ?",
      "Tôi có thể gợi ý gói độ theo xe, ngân sách và mục đích sử dụng — đường phố, track day hay thuần thẩm mỹ.",
    ],
    goiY: [
      "Gợi ý gói độ cho 911 Carrera của tôi",
      "Tôi muốn âm thanh xả sporty hơn",
      "Nâng cấp treo cho đường phố",
      "Gói độ nào giữ nguyên bảo hành?",
    ],
  },
  baoDuong: {
    ten: "Tư vấn bảo dưỡng",
    icon: HeartPulse,
    moTa: "Nhắc lịch bảo dưỡng theo số km",
    tinNhanMoDau: [
      "Xin chào, tôi là Trợ lý bảo dưỡng của DU_AN_XE. Cho tôi biết dòng xe và số km hiện tại để tư vấn chính xác nhé.",
    ],
    goiY: [
      "Xe 15.000 km cần làm gì?",
      "Khi nào thay dầu PDK?",
      "Dấu hiệu thay má phanh",
      "Đặt lịch bảo dưỡng gần nhất",
    ],
  },
};

export default function TroLyAIChat() {
  const { vaiTro } = useAuth();
  const [dangMo, setDangMo] = useState(false);
  const [tabDangChon, setTabDangChon] = useState("goiDo");
  const [lichSuTinNhan, setLichSuTinNhan] = useState({
    goiDo: TAB.goiDo.tinNhanMoDau.map((noiDung) => ({ nguoiGui: "ai", noiDung })),
    baoDuong: TAB.baoDuong.tinNhanMoDau.map((noiDung) => ({ nguoiGui: "ai", noiDung })),
  });
  const [noiDungNhap, setNoiDungNhap] = useState("");
  const [dangGui, setDangGui] = useState(false);
  const [dangTaiLichSu, setDangTaiLichSu] = useState(false);
  const [dangXoaLichSu, setDangXoaLichSu] = useState(false);
  const [loiChat, setLoiChat] = useState("");
  const [lichSuDaTai, setLichSuDaTai] = useState(false);
  const khungCuoiRef = useRef(null);

  const tab = TAB[tabDangChon];
  const tinNhanHienTai = lichSuTinNhan[tabDangChon];

  useEffect(() => {
    khungCuoiRef.current?.scrollIntoView({ behavior: "smooth" });
  }, [tinNhanHienTai, dangMo]);

  const guiTinNhan = async (noiDung) => {
    const cauHoi = noiDung.trim();
    if (!cauHoi || dangGui) return;

    setLoiChat("");
    setDangGui(true);
    setLichSuDaTai(false);
    setNoiDungNhap("");
    setLichSuTinNhan((truoc) => ({
      ...truoc,
      [tabDangChon]: [...truoc[tabDangChon], { nguoiGui: "khach", noiDung: cauHoi }],
    }));

    try {
      const traLoi = await askPorscheAI(cauHoi);
      if (!traLoi) throw new Error("Trợ lý AI chưa gửi được câu trả lời. Vui lòng thử lại.");
      setLichSuTinNhan((truoc) => ({
        ...truoc,
        [tabDangChon]: [...truoc[tabDangChon], { nguoiGui: "ai", noiDung: traLoi }],
      }));
    } catch (error) {
      setLoiChat(
        error.response?.data?.message
          || error.message
          || "Không thể kết nối Trợ lý AI. Vui lòng thử lại."
      );
    } finally {
      setDangGui(false);
    }
  };

  const taiLichSu = async () => {
    if (vaiTro !== "khach-hang" || dangTaiLichSu) return;
    setLoiChat("");
    setDangTaiLichSu(true);
    try {
      const history = await getAIHistory();
      const messages = history.slice().reverse().flatMap((item) => [
        { nguoiGui: "khach", noiDung: item.input_user },
        { nguoiGui: "ai", noiDung: item.output_ai },
      ]);
      setLichSuTinNhan((truoc) => ({
        ...truoc,
        [tabDangChon]: messages.length
          ? messages
          : TAB[tabDangChon].tinNhanMoDau.map((noiDung) => ({ nguoiGui: "ai", noiDung })),
      }));
      setLichSuDaTai(true);
    } catch (error) {
      setLoiChat(error.response?.data?.message || "Không thể tải lịch sử tư vấn.");
    } finally {
      setDangTaiLichSu(false);
    }
  };

  const xoaLichSu = async () => {
    if (vaiTro !== "khach-hang" || dangXoaLichSu) return;
    setLoiChat("");
    setDangXoaLichSu(true);
    try {
      await clearAIHistory();
      setLichSuTinNhan((truoc) => ({
        ...truoc,
        [tabDangChon]: TAB[tabDangChon].tinNhanMoDau.map((noiDung) => ({ nguoiGui: "ai", noiDung })),
      }));
      setLichSuDaTai(false);
    } catch (error) {
      setLoiChat(error.response?.data?.message || "Không thể xóa lịch sử tư vấn.");
    } finally {
      setDangXoaLichSu(false);
    }
  };

  return (
    <>
      {/* Nút mở chat */}
      {!dangMo && (
        <button
          onClick={() => setDangMo(true)}
          className="fixed bottom-7 right-7 z-[60] flex items-center gap-2.5 rounded-full border-0 bg-[#000] px-[12px] py-2 text-sm font-semibold text-white shadow-[0_8px_24px_rgba(226,60,60,0.35)]"
        >
          <div className="h-[35px] bg-[#c71430] w-[35px] flex items-center justify-center rounded-full">
            <Sparkles  />
          </div>
          Trợ lý AI tư vấn
        </button>
      )}

      {/* Panel chat */}
      {dangMo && (
        <div className="fixed bottom-7 right-7 z-[60] flex h-[min(600px,calc(100vh-56px))] w-[min(403px,calc(100vw-32px))] flex-col overflow-hidden rounded-2xl border border-[#ECECE9] bg-[#FAFAF9] shadow-2xl">

          {/* Header */}
          <div className="bg-[#0A0A0B] text-white">
            <div className="px-3 pb-2 pt-2 flex items-center gap-3">
              <span className="flex h-9 w-9 shrink-0 items-center justify-center rounded-full bg-[#B60F0F]">
                <tab.icon className="h-4.5 w-4.5 text-white" />
              </span>

              <div className="flex-1 leading-4">
                <div className="text-[14px] font-bold">Trợ lý AI</div>
                <div className="text-[12px] text-[#fff] font-sans">{tab.moTa}</div>
              </div>

              <button
                onClick={taiLichSu}
                disabled={vaiTro !== "khach-hang" || dangTaiLichSu}
                title={vaiTro === "khach-hang" ? "Tải lịch sử tư vấn" : "Đăng nhập tài khoản khách hàng để xem lịch sử"}
                className="flex h-8 w-8 items-center justify-center rounded-full text-[#9A9992] hover:bg-white/10 hover:text-white disabled:cursor-not-allowed disabled:opacity-40"
              >
                <History className="h-4 w-4" />
              </button>
              <button
                onClick={() => setDangMo(false)}
                className="flex h-8 w-8 items-center justify-center rounded-full text-[#9A9992] hover:bg-white/10 hover:text-white"
              >
                <X className="h-4 w-4" />
              </button>
            </div>

            {/* Tabs */}
            <div className="flex gap-1.5 bg-[#f3f3f0] p-2">
              {Object.entries(TAB).map(([key, val]) => (
                <button
                  key={key}
                  onClick={() => setTabDangChon(key)}
                  className={`flex flex-1 items-center justify-center gap-1.5 rounded-[50px] py-1.5 text-[12px] font-semibold transition-colors ${tabDangChon === key
                      ? "bg-[#000] text-[#fff]"
                      : "text-[#313131] hover:text-[#000]"
                    }`}
                >
                  <val.icon className="h-3.5 w-3.5" />
                  {val.ten}
                </button>
              ))}
            </div>
          </div>

          {/* Nội dung chat */}
          <div className="flex-1 overflow-y-auto bg-[#fafaf9] px-4 py-5">
            {lichSuDaTai && vaiTro === "khach-hang" ? (
              <div className="mb-4 flex items-center justify-between rounded-lg border border-[#ECECE9] bg-white px-3 py-2 text-xs">
                <span>Lịch sử tư vấn đã lưu</span>
                <button
                  type="button"
                  onClick={xoaLichSu}
                  disabled={dangXoaLichSu}
                  className="flex items-center gap-1.5 font-semibold text-[#B60F0F] disabled:opacity-50"
                >
                  <Trash2 className="h-3.5 w-3.5" />
                  {dangXoaLichSu ? "Đang xóa..." : "Xóa lịch sử"}
                </button>
              </div>
            ) : null}
            <div className="flex flex-col gap-4">
              {tinNhanHienTai.map((tin, i) => (
                <div
                  key={i}
                  className={`flex items-start gap-2.5 ${tin.nguoiGui === "khach" ? "flex-row-reverse" : ""}`}
                >
                  {tin.nguoiGui === "ai" && (
                    <span className="flex h-7 w-7 shrink-0 items-center justify-center rounded-full bg-[#B60F0F]">
                      <Sparkles className="h-3.5 w-3.5 text-white" />
                    </span>
                  )}
                  <div
                    className={`max-w-[80%] rounded-2xl px-4 py-3 text-[14px] leading-[1.6] ${tin.nguoiGui === "khach"
                        ? "bg-[#17171A] text-white"
                        : "bg-[#f3f3f0] text-[#17171A] border border-[#ECECE9]"
                      }`}
                  >
                    {tin.noiDung}
                  </div>
                </div>
              ))}
              {dangGui ? (
                <div className="rounded-2xl border border-[#ECECE9] bg-[#f3f3f0] px-4 py-3 text-sm text-[#63635E]">
                  Trợ lý đang soạn câu trả lời...
                </div>
              ) : null}
              <div ref={khungCuoiRef} />
            </div>

            {/* Gợi ý nhanh */}
            <div className="mt-5 flex flex-wrap gap-2">
              {tab.goiY.map((goiY) => (
                <button
                  key={goiY}
                  onClick={() => guiTinNhan(goiY)}
                  className="rounded-full border border-[#ECECE9] bg-white px-3.5 py-2 text-xs text-[#4A4A46] hover:border-[#B60F0F] hover:text-[#B60F0F]"
                >
                  {goiY}
                </button>
              ))}
            </div>
          </div>

          {/* Ô nhập */}
          <div className="border-t border-[#ECECE9] bg-white p-3.5">
            {loiChat ? <p role="alert" className="mb-2 text-xs text-red-700">{loiChat}</p> : null}
            <div className="flex items-center gap-2.5">
            <input
              type="text"
              value={noiDungNhap}
              onChange={(e) => setNoiDungNhap(e.target.value)}
              onKeyDown={(e) => e.key === "Enter" && guiTinNhan(noiDungNhap)}
              disabled={dangGui}
              placeholder="Nhập câu hỏi của bạn..."
              className="flex-1 rounded-full border border-[#ECECE9] bg-[#FAFAF9] px-4 py-2.5 text-sm outline-none focus:border-[#17171A]"
            />
            <button
              onClick={() => void guiTinNhan(noiDungNhap)}
              disabled={dangGui}
              className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#B60F0F] text-white disabled:opacity-50"
            >
              <Send className="h-4 w-4" />
            </button>
            </div>
          </div>
        </div>
      )}
    </>
  );
}