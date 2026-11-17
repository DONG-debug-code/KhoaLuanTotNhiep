import { useCallback, useEffect, useRef, useState } from "react";
import { Send } from "lucide-react";
import { getWorkspaceData, postWorkspaceData } from "../../api/WorkspaceApi";
import { errorMessage } from "../chuyengia/workspace/expertUi";

const LIST_POLL_MS = 5000;
const MESSAGE_POLL_MS = 3000;

const STATUS = {
  cho: { label: "Chờ nhận", className: "bg-[#FFF4DB] text-[#8A6D1F]" },
  dang: { label: "Đang trao đổi", className: "bg-[#E4F5EA] text-[#1F7A3D]" },
  dong: { label: "Đã kết thúc", className: "bg-[#F1F1EF] text-[#63635E]" },
};

const timeOf = (value) => (value ? new Date(String(value).replace(" ", "T") + "Z").toLocaleTimeString("vi-VN", { hour: "2-digit", minute: "2-digit", timeZone: "Asia/Ho_Chi_Minh" }) : "");

function Message({ message }) {
  if (message.nguoi_gui_loai === "he_thong") {
    return <div className="mx-auto max-w-[85%] rounded-lg border border-[#E8DCC4] bg-[#FAF6EF] px-3 py-2 text-center text-[12px] text-[#4A4A46]">{message.noi_dung}</div>;
  }
  const staff = message.nguoi_gui_loai === "nhan_vien";
  return (
    <div className={`flex flex-col ${staff ? "items-end" : "items-start"}`}>
      <span className="mb-0.5 text-[11px] text-[#777a80]">{message.nguoi_gui_ten || (staff ? "Nhân viên" : "Khách hàng")} · {timeOf(message.created_at)}</span>
      <div className={`max-w-[75%] whitespace-pre-wrap break-words rounded-2xl px-3.5 py-2.5 text-[14px] ${staff ? "bg-[#17171A] text-white" : "border border-[#ECECE9] bg-white"}`}>{message.noi_dung}</div>
    </div>
  );
}

export default function AdminChatView() {
  const [conversations, setConversations] = useState([]);
  const [selectedId, setSelectedId] = useState(null);
  const [messages, setMessages] = useState([]);
  const [text, setText] = useState("");
  const [sending, setSending] = useState(false);
  const [error, setError] = useState("");
  const lastIdRef = useRef(0);
  const endRef = useRef(null);

  const loadList = useCallback(async () => {
    try {
      const rows = await getWorkspaceData("admin", "workspace/chat");
      setConversations(Array.isArray(rows) ? rows : []);
    } catch (err) {
      setError(errorMessage(err, "Không thể tải danh sách cuộc trò chuyện."));
    }
  }, []);

  useEffect(() => {
    Promise.resolve().then(loadList);
    const timer = setInterval(() => { if (!document.hidden) void loadList(); }, LIST_POLL_MS);
    return () => clearInterval(timer);
  }, [loadList]);

  const poll = useCallback(async () => {
    if (!selectedId) return;
    try {
      const data = await getWorkspaceData("admin", `chat/${selectedId}/tin-nhan`, { sau_id: lastIdRef.current });
      const incoming = Array.isArray(data?.tin_nhan) ? data.tin_nhan : [];
      if (incoming.length) {
        lastIdRef.current = incoming[incoming.length - 1].id;
        setMessages((current) => [...current, ...incoming]);
      }
    } catch {
      // Lỗi mạng tạm thời: lần hỏi tiếp theo sẽ thử lại.
    }
  }, [selectedId]);

  useEffect(() => {
    if (!selectedId) return undefined;
    void poll();
    const timer = setInterval(() => { if (!document.hidden) void poll(); }, MESSAGE_POLL_MS);
    return () => clearInterval(timer);
  }, [selectedId, poll]);

  useEffect(() => {
    endRef.current?.scrollIntoView({ behavior: "smooth" });
  }, [messages]);

  const select = (id) => {
    lastIdRef.current = 0;
    setMessages([]);
    setSelectedId(id);
    setError("");
  };

  const selected = conversations.find((item) => item.id === selectedId);
  const ended = selected?.trang_thai === "dong";

  const send = async (event) => {
    event.preventDefault();
    const content = text.trim();
    if (!content || sending || !selectedId) return;
    setSending(true);
    setError("");
    try {
      await postWorkspaceData("admin", `chat/${selectedId}/gui`, { noi_dung: content });
      setText("");
      await Promise.all([poll(), loadList()]);
    } catch (err) {
      setError(errorMessage(err, "Không gửi được tin nhắn."));
    } finally {
      setSending(false);
    }
  };

  const close = async () => {
    if (!selectedId || !window.confirm("Kết thúc cuộc trò chuyện này?")) return;
    try {
      await postWorkspaceData("admin", `chat/${selectedId}/dong`, {});
      await loadList();
    } catch (err) {
      setError(errorMessage(err, "Không thể kết thúc cuộc trò chuyện."));
    }
  };

  return (
    <div className="grid min-h-[520px] gap-4 lg:grid-cols-[320px_1fr]">
      <aside className="max-h-[70vh] overflow-y-auto rounded-2xl border border-[#E4E4E0] bg-white">
        {conversations.length === 0 ? <p className="p-6 text-center text-sm text-[#63635E]">Chưa có cuộc trò chuyện nào.</p> : null}
        {conversations.map((item) => {
          const status = STATUS[item.trang_thai] || STATUS.dong;
          return (
            <button key={item.id} type="button" onClick={() => select(item.id)} className={`block w-full border-b border-[#F1F1EF] px-4 py-3 text-left hover:bg-[#FAFAF8] ${item.id === selectedId ? "bg-[#F4F4F1]" : ""}`}>
              <div className="flex items-center justify-between gap-2">
                <strong className="truncate text-[14px]">{item.ten_khach_hang}</strong>
                {item.so_chua_doc ? <span className="rounded-full bg-[#AC0021] px-2 py-0.5 text-[11px] font-bold text-white">{item.so_chua_doc}</span> : null}
              </div>
              <p className="mt-0.5 truncate text-[12px] text-[#63635E]">{item.tin_cuoi || "Chưa có tin nhắn"}</p>
              <div className="mt-1.5 flex flex-wrap items-center gap-2 text-[11px]">
                <span className={`rounded-full px-2 py-0.5 font-semibold ${status.className}`}>{status.label}</span>
                {item.ma_dat_lich ? <span className="font-mono text-[#777a80]">{item.ma_dat_lich}</span> : null}
                {item.ten_nhan_vien ? <span className="text-[#777a80]">· {item.ten_nhan_vien}</span> : null}
              </div>
            </button>
          );
        })}
      </aside>

      <section className="flex max-h-[70vh] flex-col overflow-hidden rounded-2xl border border-[#E4E4E0] bg-[#FAFAF8]">
        {!selected ? (
          <p className="m-auto p-6 text-sm text-[#63635E]">Chọn một cuộc trò chuyện để xem và phản hồi.</p>
        ) : (
          <>
            <div className="flex items-center justify-between border-b border-[#E4E4E0] bg-white px-4 py-3">
              <div>
                <p className="font-bold">{selected.ten_khach_hang}</p>
                <p className="text-[12px] text-[#63635E]">{selected.so_dien_thoai || "—"}{selected.ma_dat_lich ? ` · Lịch ${selected.ma_dat_lich}` : ""}</p>
              </div>
              {!ended ? <button type="button" onClick={close} className="rounded-lg border border-[#F0C9CE] px-3 py-1.5 text-xs font-semibold text-[#8C001B]">Kết thúc</button> : null}
            </div>
            <div className="flex-1 overflow-y-auto px-4 py-4">
              <div className="flex flex-col gap-3">
                {messages.map((message) => <Message key={message.id} message={message} />)}
                <div ref={endRef} />
              </div>
            </div>
            <div className="border-t border-[#E4E4E0] bg-white p-3">
              {error ? <p role="alert" className="mb-2 text-xs text-[#AC0021]">{error}</p> : null}
              {ended ? (
                <p className="text-center text-xs text-[#63635E]">Cuộc trò chuyện đã kết thúc.</p>
              ) : (
                <form onSubmit={send} className="flex items-center gap-2.5">
                  <input value={text} onChange={(event) => setText(event.target.value)} maxLength={2000} placeholder="Nhập phản hồi cho khách hàng..." className="flex-1 rounded-full border border-[#E4E4E0] bg-[#FAFAF8] px-4 py-2.5 text-sm outline-none focus:border-[#17171A]" />
                  <button type="submit" disabled={sending || !text.trim()} aria-label="Gửi" className="flex h-10 w-10 items-center justify-center rounded-full bg-[#AC0021] text-white disabled:opacity-50"><Send className="h-4 w-4" /></button>
                </form>
              )}
            </div>
          </>
        )}
      </section>
      {error && !selected ? <p role="alert" className="text-sm text-[#AC0021] lg:col-span-2">{error}</p> : null}
    </div>
  );
}
