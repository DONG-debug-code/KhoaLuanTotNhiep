import { useEffect, useState } from "react";
import { ChevronRight, Lightbulb, Save, Tag, CarFront } from "lucide-react";
import { formatDate, formatMoney, initials } from "./expertUi";

const TIER_STYLE = {
  Platinum: "bg-[#EDEFF5] text-[#3C4670]",
  Gold: "bg-[#FBF1DC] text-[#8A6414]",
  Silver: "bg-[#EEEEEB] text-[#4A4A46]",
  "Thành viên": "bg-[#F4F4F2] text-[#63635E]",
};

function TuningTable({ customer, vehicle, onSave, saving }) {
  const [rows, setRows] = useState(vehicle.thong_so);
  useEffect(() => setRows(vehicle.thong_so), [vehicle]);

  const update = (index, field, value) => setRows((current) => current.map((row, i) => (i === index ? { ...row, [field]: value } : row)));

  return (
    <section className="overflow-hidden rounded-2xl border border-[#ECECE9] bg-white">
      <div className="flex flex-wrap items-center justify-between gap-3 px-5 py-4">
        <h3 className="text-lg font-extrabold tracking-tight">Thông số Tuning / Dyno / Remap</h3>
        <button type="button" disabled={saving} onClick={() => onSave(customer.key, vehicle.bien_so, rows)} className="inline-flex items-center gap-2 rounded-xl bg-[#0B0B0D] px-4 py-2 text-xs font-semibold text-white disabled:opacity-60">
          <Save className="h-3.5 w-3.5" /> Lưu thông số
        </button>
      </div>
      <div className="grid grid-cols-[1.2fr_1fr_1.2fr] bg-[#F1F1EE] px-5 py-3 text-[11px] font-semibold uppercase tracking-wider text-[#63635E] max-sm:grid-cols-1">
        <span>Hạng mục</span><span className="max-sm:hidden">Trước</span><span className="max-sm:hidden">Hiện tại</span>
      </div>
      {rows.map((row, index) => (
        <div key={row.hang_muc} className="grid grid-cols-[1.2fr_1fr_1.2fr] items-center gap-3 border-t border-[#ECECE9] px-5 py-2.5 text-sm max-sm:grid-cols-1">
          <span>{row.hang_muc}</span>
          <input value={row.truoc} onChange={(event) => update(index, "truoc", event.target.value)} aria-label={`${row.hang_muc} trước`} placeholder="—" className="rounded-lg border border-transparent bg-transparent px-2 py-1.5 text-[#63635E] outline-none hover:border-[#DCDCD7] focus:border-[#AC0021] focus:bg-white" />
          <input value={row.hien_tai} onChange={(event) => update(index, "hien_tai", event.target.value)} aria-label={`${row.hang_muc} hiện tại`} placeholder="—" className="rounded-lg border border-transparent bg-transparent px-2 py-1.5 font-semibold outline-none hover:border-[#DCDCD7] focus:border-[#AC0021] focus:bg-white" />
        </div>
      ))}
    </section>
  );
}

export default function ExpertCustomersView({ customers, onSaveTuning, onSaveNote, saving }) {
  const [selectedKey, setSelectedKey] = useState(customers[0]?.key || "");
  const [vehicleIndex, setVehicleIndex] = useState(0);
  const [note, setNote] = useState("");
  const customer = customers.find((item) => item.key === selectedKey) || customers[0];

  useEffect(() => {
    setNote(customer?.ghi_chu_chuyen_gia ?? "");
    setVehicleIndex(0);
  }, [customer?.key, customer?.ghi_chu_chuyen_gia]);

  if (!customer) {
    return (
      <div className="space-y-4">
        <h1 className="text-3xl font-extrabold tracking-tight">Khách hàng phụ trách</h1>
        <p className="rounded-2xl border border-dashed border-[#DCDCD7] bg-white p-10 text-center text-sm text-[#63635E]">
          Chưa có khách hàng nào đặt lịch với bạn. Khi khách đặt lịch, hồ sơ sẽ xuất hiện tại đây.
        </p>
      </div>
    );
  }

  const vehicle = customer.xe[vehicleIndex] || customer.xe[0];

  return (
    <div className="space-y-6">
      <div>
        <p className="text-xs uppercase tracking-wider text-[#63635E]">Khu chuyên gia / Khách hàng phụ trách</p>
        <h1 className="mt-2 text-3xl font-extrabold tracking-tight sm:text-4xl">Chủ xe chọn tôi làm cố vấn</h1>
        <p className="mt-2 text-sm text-[#63635E]">Xem lịch sử độ, thông số Dyno/Remap cũ và ghi chú sở thích của từng chủ xe trước khi nhận ca mới.</p>
      </div>

      <div className="grid gap-6 lg:grid-cols-[380px_minmax(0,1fr)]">
        <section className="h-fit overflow-hidden rounded-2xl border border-[#ECECE9] bg-white">
          <h2 className="px-5 py-4 font-bold">Danh sách chủ xe ({customers.length})</h2>
          <ul>
            {customers.map((item) => {
              const active = item.key === customer.key;
              return (
                <li key={item.key}>
                  <button type="button" onClick={() => setSelectedKey(item.key)} className={`flex w-full items-center gap-3 border-t border-[#ECECE9] px-5 py-4 text-left transition-colors ${active ? "bg-[#F4F4F2]" : "hover:bg-[#FAFAF9]"}`}>
                    <span className={`flex h-11 w-11 shrink-0 items-center justify-center rounded-full text-xs font-bold ${active ? "bg-[#AC0021] text-white" : "bg-[#E8E8E5] text-[#4A4A46]"}`}>{initials(item.ho_ten)}</span>
                    <span className="min-w-0 flex-1">
                      <span className="flex items-center gap-2">
                        <span className="truncate font-semibold">{item.ho_ten}</span>
                        <span className={`rounded-full px-2 py-0.5 text-[10px] font-semibold ${TIER_STYLE[item.hang]}`}>{item.hang}</span>
                      </span>
                      <span className="block truncate text-xs text-[#63635E]">{item.xe.map((car) => [car.ten_xe, car.bien_so].filter(Boolean).join(" · ")).join(" | ") || item.so_dien_thoai}</span>
                    </span>
                    <ChevronRight className="h-4 w-4 shrink-0 text-[#8B8B86]" />
                  </button>
                </li>
              );
            })}
          </ul>
        </section>

        <div className="min-w-0 space-y-5">
          <section className="rounded-2xl border border-[#ECECE9] bg-white p-5 sm:p-6">
            <div className="flex flex-wrap items-center gap-4">
              <span className="flex h-14 w-14 items-center justify-center rounded-full bg-[#0B0B0D] text-sm font-bold text-white">{initials(customer.ho_ten)}</span>
              <div className="min-w-0 flex-1">
                <h2 className="text-xl font-extrabold tracking-tight">{customer.ho_ten}</h2>
                <p className="text-sm text-[#63635E]">{[customer.so_dien_thoai, customer.email, `Hạng ${customer.hang}`].filter(Boolean).join(" · ")}</p>
              </div>
              <div className="flex flex-wrap gap-2">
                {customer.xe.map((car, index) => (
                  <button key={`${car.bien_so}-${index}`} type="button" onClick={() => setVehicleIndex(index)} className={`inline-flex items-center gap-1.5 rounded-lg border px-3 py-2 text-xs font-semibold ${index === vehicleIndex ? "border-[#17171A] bg-[#17171A] text-white" : "border-[#DCDCD7] bg-white"}`}>
                    <CarFront className="h-3.5 w-3.5" /> {car.ten_xe || "Xe"}{car.bien_so ? ` · ${car.bien_so}` : ""}
                  </button>
                ))}
              </div>
            </div>

            <div className="mt-5 grid gap-3 sm:grid-cols-3">
              {[
                ["Lần gần nhất", formatDate(customer.lan_gan_nhat)],
                ["Tổng chi tiêu", formatMoney(customer.tong_chi_tieu)],
                ["Số ca đã làm", `${customer.so_ca_da_lam} ca`],
              ].map(([label, value]) => (
                <div key={label} className="rounded-xl bg-[#F4F4F2] p-4">
                  <p className="text-[11px] uppercase tracking-wider text-[#63635E]">{label}</p>
                  <p className="mt-1.5 text-lg font-bold">{value}</p>
                </div>
              ))}
            </div>

            <div className="mt-5 rounded-xl border border-[#F0DDB0] bg-[#FDF8EC] p-4">
              <label className="flex items-start gap-3 text-sm text-[#5C4513]">
                <Lightbulb className="mt-1 h-4 w-4 shrink-0" />
                <span className="w-full">
                  <span className="mb-1.5 block text-xs font-semibold uppercase tracking-wider">Ghi chú sở thích của chủ xe</span>
                  <textarea value={note} onChange={(event) => setNote(event.target.value)} rows={2} maxLength={1000} placeholder={customer.ghi_chu_khach ? `Khách ghi khi đặt lịch: ${customer.ghi_chu_khach}` : "Ví dụ: ưu tiên âm thanh xả thể thao, không muốn hạ gầm quá thấp…"} className="w-full resize-none rounded-lg border border-[#F0DDB0] bg-white/70 px-3 py-2 text-sm outline-none focus:border-[#B8862B]" />
                </span>
              </label>
              <div className="mt-2 flex justify-end">
                <button type="button" disabled={saving || note === (customer.ghi_chu_chuyen_gia ?? "")} onClick={() => onSaveNote(customer.key, note)} className="inline-flex items-center gap-2 rounded-lg bg-[#0B0B0D] px-3.5 py-2 text-xs font-semibold text-white disabled:opacity-50">
                  <Tag className="h-3.5 w-3.5" /> Lưu ghi chú
                </button>
              </div>
            </div>
          </section>

          {vehicle ? <TuningTable customer={customer} vehicle={vehicle} onSave={onSaveTuning} saving={saving} /> : null}

          <section className="overflow-hidden rounded-2xl border border-[#ECECE9] bg-white">
            <h3 className="px-5 py-4 text-lg font-extrabold tracking-tight">Lịch sử nâng cấp</h3>
            {customer.lich_su.length === 0 ? (
              <p className="border-t border-[#ECECE9] px-5 py-6 text-center text-sm text-[#63635E]">Khách hàng chưa có ca nào hoàn thành.</p>
            ) : (
              customer.lich_su.map((item) => (
                <div key={item.id} className="flex items-start justify-between gap-4 border-t border-[#ECECE9] px-5 py-4">
                  <div className="min-w-0">
                    <p className="font-semibold">{item.tieu_de}</p>
                    <p className="text-xs text-[#63635E]">{[formatDate(item.ngay), item.bay, "Hoàn thành", item.ghi_chu].filter(Boolean).join(" · ")}</p>
                  </div>
                  <p className="shrink-0 font-bold">{item.gia_tri > 0 ? formatMoney(item.gia_tri) : "Tư vấn"}</p>
                </div>
              ))
            )}
          </section>
        </div>
      </div>
    </div>
  );
}
