import { useCallback, useEffect, useState } from "react";
import { ArrowRight, Plus, Trash2, X } from "lucide-react";
import { getWorkspaceData, postWorkspaceData } from "../../api/WorkspaceApi";
import { errorMessage, formatDate } from "../../pages/chuyengia/workspace/expertUi";

const TYPE_LABEL = {
  nang_cap: "Nâng cấp",
  thay_doi: "Thay đổi",
  bao_duong: "Bảo dưỡng",
  cap_nhat_thong_tin: "Cập nhật thông tin",
  ghi_nhan: "Ghi nhận",
};

const SOURCE_LABEL = { he_thong: "Hệ thống", nhan_vien: "Nhân viên", khach_hang: "Khách hàng" };
const inputClass = "mt-1 w-full rounded-lg border border-[#E4E4E0] px-3 py-2 text-[13px] outline-none focus:border-[#17171A]";
const EMPTY_FORM = { loai: "nang_cap", tieu_de: "", mo_ta: "", ngay_thuc_hien: "" };

function Entry({ entry }) {
  return (
    <li className="relative border-l-2 border-[#E4E4E0] pb-5 pl-4 last:pb-0">
      <span className="absolute -left-[5px] top-1.5 h-2 w-2 rounded-full bg-[#AC0021]" />
      <div className="flex flex-wrap items-center gap-2">
        <span className="rounded-full bg-[#F1F1EF] px-2.5 py-0.5 text-[11px] font-semibold">{TYPE_LABEL[entry.loai] || entry.loai}</span>
        <span className="text-[12px] text-[#777a80]">{formatDate(entry.ngay_thuc_hien)}</span>
        <span className="text-[11px] text-[#9a9da2]">· {entry.nguoi_tao_ten || SOURCE_LABEL[entry.nguon] || ""}</span>
      </div>
      <p className="mt-1 text-[14px] font-semibold">{entry.tieu_de}</p>
      {entry.mo_ta ? <p className="mt-0.5 text-[12px] text-[#63635E]">{entry.mo_ta}</p> : null}
      {entry.thay_doi?.length ? (
        <ul className="mt-2 space-y-1">
          {entry.thay_doi.map((change, index) => (
            <li key={index} className="flex flex-wrap items-center gap-1.5 text-[12px] text-[#4A4A46]">
              <strong>{change.truong}:</strong>
              {change.truoc ? <span className="rounded bg-[#FBE9E9] px-1.5 py-0.5 line-through">{change.truoc}</span> : <span className="text-[#9a9da2]">—</span>}
              <ArrowRight className="h-3 w-3" />
              <span className="rounded bg-[#E8F6EC] px-1.5 py-0.5">{change.sau || "—"}</span>
            </li>
          ))}
        </ul>
      ) : null}
    </li>
  );
}

function AddForm({ vehicleId, onSaved }) {
  const [form, setForm] = useState(EMPTY_FORM);
  const [today] = useState(() => new Date(Date.now() - new Date().getTimezoneOffset() * 60000).toISOString().slice(0, 10));
  const [changes, setChanges] = useState([]);
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState("");
  const set = (key) => (event) => setForm((current) => ({ ...current, [key]: event.target.value }));
  const setChange = (index, key) => (event) => setChanges((current) => current.map((item, i) => (i === index ? { ...item, [key]: event.target.value } : item)));

  const submit = async (event) => {
    event.preventDefault();
    setSaving(true);
    setError("");
    try {
      const payload = { ...form, xe_khach_hang_id: vehicleId, thay_doi: changes.filter((item) => item.truong.trim()) };
      if (!payload.ngay_thuc_hien) delete payload.ngay_thuc_hien;
      await postWorkspaceData("admin", "xe-khach-hang/lich-su/them", payload);
      setForm(EMPTY_FORM);
      setChanges([]);
      await onSaved();
    } catch (err) {
      setError(errorMessage(err, "Không thể ghi nhận."));
    } finally {
      setSaving(false);
    }
  };

  return (
    <form onSubmit={submit} className="mb-5 space-y-3 rounded-xl border border-[#E4E4E0] bg-[#FAFAF8] p-4">
      <p className="text-[13px] font-bold">Ghi nhận nâng cấp / thay đổi tại showroom</p>
      <div className="grid grid-cols-2 gap-3">
        <label className="text-[12px] font-medium">Loại
          <select value={form.loai} onChange={set("loai")} className={inputClass}>
            <option value="nang_cap">Nâng cấp</option>
            <option value="thay_doi">Thay đổi</option>
            <option value="bao_duong">Bảo dưỡng</option>
            <option value="ghi_nhan">Ghi nhận khác</option>
          </select>
        </label>
        <label className="text-[12px] font-medium">Ngày thực hiện
          <input type="date" max={today} value={form.ngay_thuc_hien} onChange={set("ngay_thuc_hien")} className={inputClass} />
        </label>
      </div>
      <label className="block text-[12px] font-medium">Nội dung
        <input required maxLength={200} value={form.tieu_de} onChange={set("tieu_de")} placeholder="Ví dụ: Thay hệ thống xả Akrapovič" className={inputClass} />
      </label>
      <label className="block text-[12px] font-medium">Mô tả
        <textarea rows={2} maxLength={2000} value={form.mo_ta} onChange={set("mo_ta")} className={`${inputClass} resize-none`} />
      </label>
      <div className="space-y-2">
        {changes.map((change, index) => (
          <div key={index} className="grid grid-cols-[1fr_1fr_1fr_auto] items-end gap-2">
            <input aria-label="Hạng mục" placeholder="Hạng mục" value={change.truong} onChange={setChange(index, "truong")} className={inputClass} />
            <input aria-label="Trước" placeholder="Trước" value={change.truoc} onChange={setChange(index, "truoc")} className={inputClass} />
            <input aria-label="Sau" placeholder="Sau" value={change.sau} onChange={setChange(index, "sau")} className={inputClass} />
            <button type="button" aria-label="Xóa dòng" onClick={() => setChanges((current) => current.filter((_, i) => i !== index))} className="mb-1 rounded-lg p-2 text-[#8C001B] hover:bg-[#FBE9E9]"><Trash2 className="h-4 w-4" /></button>
          </div>
        ))}
        <button type="button" onClick={() => setChanges((current) => [...current, { truong: "", truoc: "", sau: "" }])} className="flex items-center gap-1 text-[12px] font-semibold text-[#AC0021]"><Plus className="h-3.5 w-3.5" />Thêm thay đổi (trước → sau)</button>
      </div>
      {error ? <p role="alert" className="text-[12px] text-[#AC0021]">{error}</p> : null}
      <button type="submit" disabled={saving} className="rounded-lg bg-[#AC0021] px-4 py-2 text-[12px] font-semibold text-white disabled:opacity-60">{saving ? "Đang lưu..." : "Lưu vào lịch sử"}</button>
    </form>
  );
}

/** Lịch sử thay đổi/nâng cấp của một xe. `staff` bật form ghi nhận dành cho nhân viên. */
export default function VehicleHistoryModal({ vehicle, staff = false, onClose }) {
  const [entries, setEntries] = useState(null);
  const [error, setError] = useState("");

  const load = useCallback(async () => {
    try {
      const data = await getWorkspaceData(staff ? "admin" : "khach-hang", staff ? `xe-khach-hang/${vehicle.id}/lich-su` : `xe/${vehicle.id}/lich-su`);
      setEntries(Array.isArray(data?.lich_su) ? data.lich_su : []);
      setError("");
    } catch (err) {
      setError(errorMessage(err, "Không thể tải lịch sử xe."));
    }
  }, [staff, vehicle.id]);

  useEffect(() => {
    let active = true;
    Promise.resolve().then(() => { if (active) void load(); });
    return () => { active = false; };
  }, [load]);

  return (
    <div className="fixed inset-0 z-[70] flex items-center justify-center bg-black/50 p-4" onClick={onClose}>
      <div className="max-h-[90vh] w-full max-w-xl overflow-y-auto rounded-2xl bg-white p-6" onClick={(event) => event.stopPropagation()}>
        <div className="mb-4 flex items-start justify-between gap-3">
          <div>
            <h3 className="text-lg font-extrabold">Lịch sử xe</h3>
            <p className="text-[13px] text-[#63635E]">{vehicle.ten_xe} · <span className="font-mono">{vehicle.bien_so_xe}</span></p>
          </div>
          <button type="button" aria-label="Đóng" onClick={onClose} className="rounded-lg p-1.5 hover:bg-[#F1F1EF]"><X className="h-5 w-5" /></button>
        </div>
        {staff ? <AddForm vehicleId={vehicle.id} onSaved={load} /> : null}
        {error ? <p role="alert" className="text-sm text-[#AC0021]">{error}</p> : null}
        {entries === null && !error ? <p className="py-6 text-center text-sm text-[#63635E]">Đang tải...</p> : null}
        {entries?.length === 0 ? <p className="py-6 text-center text-sm text-[#63635E]">Chưa có lịch sử nâng cấp hoặc thay đổi nào.</p> : null}
        {entries?.length ? <ul>{entries.map((entry) => <Entry key={entry.id} entry={entry} />)}</ul> : null}
      </div>
    </div>
  );
}
