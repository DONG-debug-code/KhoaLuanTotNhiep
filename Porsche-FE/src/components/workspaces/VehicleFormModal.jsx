import { useState } from "react";
import { AlertCircle, X } from "lucide-react";
import { createCustomerVehicle, updateCustomerVehicle } from "../../api/WorkspaceApi";

const EMPTY_FORM = {
  ten_xe: "",
  bien_so_xe: "",
  dong_xe: "",
  nam_san_xuat: "",
  mau_sac: "",
  so_khung: "",
  so_km: "",
  ghi_chu: "",
};

const inputClass = "mt-1.5 w-full rounded-lg border border-[#dededb] px-3.5 py-2.5 text-[14px] font-normal outline-none focus:border-[#17171A]";

function toForm(vehicle) {
  if (!vehicle) return EMPTY_FORM;
  return Object.fromEntries(
    Object.keys(EMPTY_FORM).map((key) => [key, vehicle[key] ?? ""])
  );
}

export default function VehicleFormModal({ vehicle, onClose, onSaved }) {
  const isEdit = Boolean(vehicle?.id);
  const [form, setForm] = useState(() => toForm(vehicle));
  const [saving, setSaving] = useState(false);
  const [error, setError] = useState("");

  const setField = (event) => {
    const { name, value } = event.target;
    setForm((current) => ({ ...current, [name]: value }));
  };

  const submit = async (event) => {
    event.preventDefault();
    setSaving(true);
    setError("");
    try {
      const payload = {
        ...form,
        nam_san_xuat: form.nam_san_xuat === "" ? null : Number(form.nam_san_xuat),
        so_km: form.so_km === "" ? null : Number(form.so_km),
      };
      const response = isEdit
        ? await updateCustomerVehicle(vehicle.id, payload)
        : await createCustomerVehicle(payload);
      onSaved(response?.message || (isEdit ? "Đã cập nhật thông tin xe." : "Đã thêm xe vào garage."));
    } catch (requestError) {
      setError(requestError.response?.data?.message || requestError.message || "Không thể lưu thông tin xe.");
    } finally {
      setSaving(false);
    }
  };

  return (
    <div
      className="fixed inset-0 z-[70] flex items-center justify-center bg-black/50 p-4"
      role="dialog"
      aria-modal="true"
      aria-label={isEdit ? "Sửa thông tin xe" : "Thêm xe"}
      onMouseDown={(event) => { if (event.target === event.currentTarget && !saving) onClose(); }}
    >
      <form onSubmit={submit} className="max-h-[92vh] w-full max-w-[560px] overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl">
        <div className="mb-5 flex items-start justify-between gap-3">
          <div>
            <h2 className="text-[18px] font-bold">{isEdit ? "Sửa thông tin xe" : "Thêm xe vào garage"}</h2>
            <p className="mt-1 text-[13px] text-[#777a80]">Thông tin xe giúp showroom và Trợ lý AI tư vấn chính xác hơn.</p>
          </div>
          <button type="button" onClick={onClose} disabled={saving} aria-label="Đóng" className="rounded-full p-1.5 hover:bg-[#f1f1ef]">
            <X className="h-5 w-5" />
          </button>
        </div>

        {error ? (
          <p role="alert" className="mb-4 flex items-start gap-2 rounded-xl bg-[#fae9eb] px-4 py-3 text-[13px] text-[#8C001B]">
            <AlertCircle className="mt-0.5 h-4 w-4 shrink-0" />{error}
          </p>
        ) : null}

        <div className="grid gap-4 sm:grid-cols-2">
          <label className="text-[13px] font-medium sm:col-span-2">Tên / mẫu xe *
            <input required name="ten_xe" value={form.ten_xe} onChange={setField} maxLength={150} placeholder="VD: Porsche 911 Carrera" className={inputClass} />
          </label>
          <label className="text-[13px] font-medium">Biển số xe *
            <input required name="bien_so_xe" value={form.bien_so_xe} onChange={setField} maxLength={20} placeholder="VD: 51A-12345" className={`${inputClass} uppercase`} />
          </label>
          <label className="text-[13px] font-medium">Dòng xe
            <input name="dong_xe" value={form.dong_xe} onChange={setField} maxLength={60} placeholder="911 / 718 / Cayenne..." className={inputClass} />
          </label>
          <label className="text-[13px] font-medium">Năm sản xuất
            <input type="number" name="nam_san_xuat" value={form.nam_san_xuat} onChange={setField} min={1950} max={new Date().getFullYear() + 1} className={inputClass} />
          </label>
          <label className="text-[13px] font-medium">Màu sắc
            <input name="mau_sac" value={form.mau_sac} onChange={setField} maxLength={50} className={inputClass} />
          </label>
          <label className="text-[13px] font-medium">Số khung (VIN)
            <input name="so_khung" value={form.so_khung} onChange={setField} maxLength={30} className={`${inputClass} uppercase`} />
          </label>
          <label className="text-[13px] font-medium">Số km đã đi
            <input type="number" name="so_km" value={form.so_km} onChange={setField} min={0} max={2000000} className={inputClass} />
          </label>
          <label className="text-[13px] font-medium sm:col-span-2">Ghi chú
            <textarea name="ghi_chu" value={form.ghi_chu} onChange={setField} maxLength={500} rows={3} className={inputClass} />
          </label>
        </div>

        <div className="mt-6 flex justify-end gap-3">
          <button type="button" onClick={onClose} disabled={saving} className="rounded-lg border border-[#d7d7d3] px-5 py-2.5 text-[13px] font-semibold">Hủy</button>
          <button type="submit" disabled={saving} className="rounded-lg bg-[#080a0d] px-5 py-2.5 text-[13px] font-semibold text-white disabled:opacity-60">
            {saving ? "Đang lưu..." : isEdit ? "Lưu thay đổi" : "Thêm xe"}
          </button>
        </div>
      </form>
    </div>
  );
}
