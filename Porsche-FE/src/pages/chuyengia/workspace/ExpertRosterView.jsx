import { useState } from "react";
import { CalendarPlus, Trash2 } from "lucide-react";
import { formatDate } from "./expertUi";

const SHIFTS = ["Ca sáng (08:00 - 12:00)", "Ca chiều (13:00 - 17:00)"];

export default function ExpertRosterView({ shifts, today, saving, onRegister, onRemove }) {
  const [date, setDate] = useState("");
  const [shift, setShift] = useState("");

  const submit = async (event) => {
    event.preventDefault();
    if (await onRegister(date, shift)) {
      setDate("");
      setShift("");
    }
  };

  const grouped = shifts.reduce((acc, item) => {
    const key = String(item.ngay_lam_viec).slice(0, 10);
    (acc[key] = acc[key] || []).push(item);
    return acc;
  }, {});

  return (
    <div className="space-y-6">
      <div>
        <p className="text-xs uppercase tracking-wider text-[#63635E]">Khu chuyên gia / Lịch làm việc</p>
        <h1 className="mt-2 text-3xl font-extrabold tracking-tight sm:text-4xl">Lịch làm việc cá nhân</h1>
        <p className="mt-2 text-sm text-[#63635E]">Khách hàng chỉ đặt lịch được vào những ca bạn đã đăng ký. Ca đã có lịch hẹn sẽ không thể xóa.</p>
      </div>

      <form onSubmit={submit} className="grid gap-3 rounded-2xl border border-[#ECECE9] bg-white p-5 md:grid-cols-[1fr_1fr_auto]">
        <input required type="date" min={today} value={date} onChange={(event) => setDate(event.target.value)} aria-label="Ngày làm việc" className="rounded-xl border border-[#DCDCD7] px-3.5 py-3 text-sm" />
        <select required value={shift} onChange={(event) => setShift(event.target.value)} aria-label="Ca làm việc" className="rounded-xl border border-[#DCDCD7] bg-white px-3.5 py-3 text-sm">
          <option value="" disabled>Chọn ca</option>
          {SHIFTS.map((item) => <option key={item} value={item}>{item}</option>)}
        </select>
        <button disabled={saving} className="inline-flex items-center justify-center gap-2 rounded-xl bg-[#0B0B0D] px-5 py-3 text-sm font-semibold text-white disabled:opacity-50">
          <CalendarPlus className="h-4 w-4" /> Đăng ký ca
        </button>
      </form>

      <section className="rounded-2xl border border-[#ECECE9] bg-white">
        <h2 className="px-5 py-4 text-lg font-extrabold tracking-tight">Ca đã đăng ký ({shifts.length})</h2>
        {shifts.length === 0 ? (
          <p className="border-t border-[#ECECE9] px-5 py-8 text-center text-sm text-[#63635E]">Bạn chưa đăng ký ca nào từ hôm nay trở đi.</p>
        ) : (
          Object.entries(grouped).map(([day, items]) => (
            <div key={day} className="flex flex-wrap items-center gap-4 border-t border-[#ECECE9] px-5 py-3.5">
              <p className="w-28 font-bold">{formatDate(day)}</p>
              <div className="flex flex-1 flex-wrap gap-2">
                {items.map((item) => (
                  <span key={item.id} className="inline-flex items-center gap-2 rounded-lg bg-[#F4F4F2] py-1.5 pl-3 pr-1.5 text-sm">
                    {item.ca_lam_viec}
                    <button type="button" disabled={saving} onClick={() => onRemove(item.id)} aria-label={`Xóa ${item.ca_lam_viec} ngày ${formatDate(day)}`} className="rounded-md p-1.5 text-[#AC0021] hover:bg-[#FBE9E9] disabled:opacity-50">
                      <Trash2 className="h-3.5 w-3.5" />
                    </button>
                  </span>
                ))}
              </div>
            </div>
          ))
        )}
      </section>
    </div>
  );
}
