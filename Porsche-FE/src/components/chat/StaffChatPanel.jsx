import { useCallback, useEffect, useRef, useState } from "react";
import { Link } from "react-router-dom";
import { Headset, Send } from "lucide-react";
import { useAuth } from "../../context/AuthContext";
import { getWorkspaceData, postWorkspaceData } from "../../api/WorkspaceApi";
import { errorMessage } from "../../pages/chuyengia/workspace/expertUi";

const POLL_INTERVAL_MS = 4000;

const STATUS_TEXT = {
  cho: "Đang chờ nhân viên tiếp nhận...",
  dong: "Cuộc trò chuyện đã kết thúc.",
};

function Bubble({ message }) {
  if (message.nguoi_gui_loai === "he_thong") {
    return (
      <div className="mx-auto max-w-[90%] rounded-lg border border-[#E8DCC4] bg-[#FAF6EF] px-3 py-2 text-center text-[12px] leading-[1.5] text-[#4A4A46]">
        {message.noi_dung}
      </div>
    );
  }
  const mine = message.nguoi_gui_loai === "khach_hang";
  return (
    <div className={`flex flex-col ${mine ? "items-end" : "items-start"}`}>
      {!mine ? <span className="mb-0.5 text-[11px] text-[#777a80]">{message.nguoi_gui_ten || "Nhân viên"}</span> : null}
      <div className={`max-w-[80%] whitespace-pre-wrap break-words rounded-2xl px-3.5 py-2.5 text-[14px] leading-[1.5] ${mine ? "bg-[#17171A] text-white" : "border border-[#ECECE9] bg-[#f3f3f0] text-[#17171A]"}`}>
        {message.noi_dung}
      </div>
    </div>
  );
}

/** Chat với nhân viên: khách chuyển từ trợ lý AI sang trao đổi trực tiếp (cập nhật bằng cách hỏi lại định kỳ). */
export default function StaffChatPanel({ appointmentId = null }) {
  const { vaiTro } = useAuth();
  const isCustomer = vaiTro === "khach-hang";
  const [conversation, setConversation] = useState(null);
  const [messages, setMessages] = useState([]);
  const [text, setText] = useState("");
  const [sending, setSending] = useState(false);
  const [error, setError] = useState("");
  const lastIdRef = useRef(0);
  const endRef = useRef(null);

  const connect = useCallback(async () => {
    setError("");
    try {
      const response = await postWorkspaceData("khach-hang", "chat/ket-noi", appointmentId ? { id_phieu_dat_lich: appointmentId } : {});
      lastIdRef.current = 0;
      setMessages([]);
      setConversation(response.data);
    } catch (err) {
      setError(errorMessage(err, "Không thể kết nối với nhân viên."));
    }
  }, [appointmentId]);

  useEffect(() => {
    if (!isCustomer) return undefined;
    let active = true;
    Promise.resolve().then(() => { if (active) void connect(); });
    return () => { active = false; };
  }, [isCustomer, connect]);

  const conversationId = conversation?.id;
  const poll = useCallback(async () => {
    if (!conversationId) return;
    try {
      const data = await getWorkspaceData("khach-hang", `chat/${conversationId}/tin-nhan`, { sau_id: lastIdRef.current });
      const incoming = Array.isArray(data?.tin_nhan) ? data.tin_nhan : [];
      if (incoming.length) {
        lastIdRef.current = incoming[incoming.length - 1].id;
        setMessages((current) => [...current, ...incoming]);
      }
      if (data?.cuoc_tro_chuyen) setConversation(data.cuoc_tro_chuyen);
    } catch {
      // Lỗi mạng tạm thời: lần hỏi tiếp theo sẽ thử lại.
    }
  }, [conversationId]);

  useEffect(() => {
    if (!conversationId) return undefined;
    void poll();
    const timer = setInterval(() => { if (!document.hidden) void poll(); }, POLL_INTERVAL_MS);
    return () => clearInterval(timer);
  }, [conversationId, poll]);

  useEffect(() => {
    endRef.current?.scrollIntoView({ behavior: "smooth" });
  }, [messages]);

  const send = async (event) => {
    event.preventDefault();
    const content = text.trim();
    if (!content || sending || !conversationId) return;
    setSending(true);
    setError("");
    try {
      await postWorkspaceData("khach-hang", `chat/${conversationId}/gui`, { noi_dung: content });
      setText("");
      await poll();
    } catch (err) {
      setError(errorMessage(err, "Không gửi được tin nhắn."));
    } finally {
      setSending(false);
    }
  };

  const endChat = async () => {
    if (!conversationId || !window.confirm("Kết thúc cuộc trò chuyện với nhân viên?")) return;
    try {
      await postWorkspaceData("khach-hang", `chat/${conversationId}/dong`, {});
      await poll();
    } catch (err) {
      setError(errorMessage(err, "Không thể kết thúc cuộc trò chuyện."));
    }
  };

  if (!isCustomer) {
    return (
      <div className="flex flex-1 flex-col items-center justify-center gap-3 bg-[#fafaf9] px-6 text-center">
        <Headset className="h-9 w-9 text-[#9da1a4]" />
        <p className="text-sm font-semibold">Đăng nhập để trao đổi với nhân viên</p>
        <p className="text-xs text-[#63635E]">Nhân viên sẽ hỗ trợ bạn về lịch hẹn, đổi lịch, đổi chuyên gia và các thắc mắc khác.</p>
        <Link to="/dang-nhap" className="rounded-lg bg-[#AC0021] px-4 py-2 text-xs font-semibold text-white">Đăng nhập</Link>
      </div>
    );
  }

  const status = conversation?.trang_thai === "dang"
    ? `${conversation.ten_nhan_vien || "Nhân viên"} đang hỗ trợ bạn`
    : STATUS_TEXT[conversation?.trang_thai] || "Đang kết nối...";
  const ended = conversation?.trang_thai === "dong";

  return (
    <>
      <div className="flex items-center justify-between border-b border-[#ECECE9] bg-white px-4 py-2 text-[12px]">
        <span className="text-[#4A4A46]">{status}</span>
        {conversationId && !ended ? <button type="button" onClick={endChat} className="font-semibold text-[#AC0021]">Kết thúc</button> : null}
      </div>
      <div className="flex-1 overflow-y-auto bg-[#fafaf9] px-4 py-4">
        <div className="flex flex-col gap-3">
          {messages.length === 0 && conversationId ? (
            <p className="py-6 text-center text-[13px] text-[#63635E]">Hãy gửi câu hỏi của bạn, nhân viên sẽ phản hồi sớm nhất có thể.</p>
          ) : null}
          {messages.map((message) => <Bubble key={message.id} message={message} />)}
          <div ref={endRef} />
        </div>
      </div>
      <div className="border-t border-[#ECECE9] bg-white p-3.5">
        {error ? <p role="alert" className="mb-2 text-xs text-red-700">{error}</p> : null}
        {ended ? (
          <button type="button" onClick={() => void connect()} className="w-full rounded-lg bg-[#AC0021] py-2.5 text-sm font-semibold text-white">Bắt đầu cuộc trò chuyện mới</button>
        ) : (
          <form onSubmit={send} className="flex items-center gap-2.5">
            <input
              value={text}
              onChange={(event) => setText(event.target.value)}
              maxLength={2000}
              disabled={!conversationId}
              placeholder="Nhập tin nhắn cho nhân viên..."
              className="flex-1 rounded-full border border-[#ECECE9] bg-[#FAFAF9] px-4 py-2.5 text-sm outline-none focus:border-[#17171A]"
            />
            <button type="submit" disabled={sending || !text.trim()} aria-label="Gửi" className="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-[#AC0021] text-white disabled:opacity-50">
              <Send className="h-4 w-4" />
            </button>
          </form>
        )}
      </div>
    </>
  );
}
