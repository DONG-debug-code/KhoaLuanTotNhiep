import { useEffect, useState } from "react";
import { Link } from "react-router-dom";
import axios from "axios";
import { BASE_URL, getApiErrorMessage } from "../../api/Authapi";
import { getWorkspaceData, postWorkspaceData } from "../../api/WorkspaceApi";
import { useAuth } from "../../context/AuthContext";

const fieldClass = "mt-1 w-full rounded-lg border border-[#DCDCD8] bg-white px-3 py-2.5 text-sm outline-none focus:border-[#17171A]";
const today = () => new Date().toISOString().slice(0, 10);

export default function ChuyenGiaDatLichForm({ chuyenGia }) {
  const { vaiTro } = useAuth();
  const [form, setForm] = useState({ loai_lich: "tu-van", ten_xe: "", bien_so_xe: "", ngay_dat: "", gio_dat: "", id_khu_vuc_workshop: "", ghi_chu: "" });
  const [khungGio, setKhungGio] = useState([]);
  const [khuVuc, setKhuVuc] = useState([]);
  const [dangLuu, setDangLuu] = useState(false);
  const [loi, setLoi] = useState("");
  const [thanhCong, setThanhCong] = useState("");
  const laKhach = vaiTro === "khach-hang";

  useEffect(() => {
    if (!laKhach) return;
    getWorkspaceData("khach-hang", "workshop/options")
      .then((data) => setKhuVuc(Array.isArray(data?.khu_vuc_workshops) ? data.khu_vuc_workshops : []))
      .catch(() => setKhuVuc([]));
  }, [laKhach]);

  const chonNgay = async (ngay) => {
    setForm((current) => ({ ...current, ngay_dat: ngay, gio_dat: "" }));
    setKhungGio([]);
    if (!ngay) return;
    try {
      const { data } = await axios.get(`${BASE_URL}/client/chuyen-gia-tuning/${chuyenGia.id}/khung-gio`, { params: { ngay } });
      setKhungGio(Array.isArray(data?.data) ? data.data : []);
    } catch (error) {
      setLoi(getApiErrorMessage(error, "Không tải được khung giờ của chuyên gia."));
    }
  };

  const setField = (event) => setForm((current) => ({ ...current, [event.target.name]: event.target.value }));

  const gui = async (event) => {
    event.preventDefault();
    setLoi("");
    setThanhCong("");
    if (!form.gio_dat) {
      setLoi("Vui lòng chọn khung giờ.");
      return;
    }
    setDangLuu(true);
    try {
      const payload = { ...form, id_chuyen_gia: chuyenGia.id, id_khu_vuc_workshop: form.id_khu_vuc_workshop || null };
      const result = await postWorkspaceData("khach-hang", "dat-lich-kham", payload);
      setThanhCong(`${result.message} Mã lịch: ${result.data?.ma_dat_lich ?? ""}. Email xác nhận đã được gửi, bạn xem lại trong mục Lịch hẹn ở trang cá nhân.`);
      setForm((current) => ({ ...current, gio_dat: "", ghi_chu: "" }));
      await chonNgay(form.ngay_dat);
    } catch (error) {
      setLoi(getApiErrorMessage(error, "Không thể đặt lịch. Vui lòng thử lại."));
    } finally {
      setDangLuu(false);
    }
  };

  return (
    <section id="dat-lich" className="mt-12 rounded-2xl border border-[#ECECE9] bg-white p-6">
      <h2 className="mb-1 text-xl font-extrabold">Đặt lịch với {chuyenGia.ten}</h2>
      <p className="mb-5 text-sm text-[#63635E]">Lịch hẹn sẽ được gửi tới chuyên gia và lưu trong lịch hẹn của bạn.</p>

      {!laKhach ? (
        <p className="text-sm text-[#63635E]">
          Vui lòng <Link to="/dang-nhap" className="font-semibold text-[#17171A] underline">đăng nhập tài khoản khách hàng</Link> để đặt lịch.
        </p>
      ) : (
        <form onSubmit={(event) => void gui(event)} className="grid gap-4 md:grid-cols-2">
          <label className="text-sm font-medium">Nhu cầu
            <select name="loai_lich" value={form.loai_lich} onChange={setField} className={fieldClass}>
              <option value="tu-van">Tư vấn độ xe</option>
              <option value="do-xe">Thi công / độ xe</option>
            </select>
          </label>
          <label className="text-sm font-medium">Dòng xe *
            <input required name="ten_xe" value={form.ten_xe} onChange={setField} placeholder="Porsche 911 Carrera S" className={fieldClass} />
          </label>
          <label className="text-sm font-medium">Biển số xe *
            <input required name="bien_so_xe" value={form.bien_so_xe} onChange={setField} placeholder="51K-123.45" className={fieldClass} />
          </label>
          <label className="text-sm font-medium">Ngày hẹn *
            <input required type="date" min={today()} value={form.ngay_dat} onChange={(event) => void chonNgay(event.target.value)} className={fieldClass} />
          </label>
          <div className="md:col-span-2">
            <div className="text-sm font-medium">Khung giờ *</div>
            {!form.ngay_dat ? <p className="mt-1 text-sm text-[#8A8A85]">Chọn ngày để xem giờ trống.</p> : (
              <div className="mt-2 flex flex-wrap gap-2">
                {khungGio.map((slot) => (
                  <button
                    key={slot.gio}
                    type="button"
                    disabled={!slot.con_trong}
                    onClick={() => setForm((current) => ({ ...current, gio_dat: slot.gio }))}
                    className={`rounded-lg border px-4 py-2 text-sm font-semibold ${form.gio_dat === slot.gio ? "border-[#17171A] bg-[#17171A] text-white" : "border-[#DCDCD8]"} disabled:cursor-not-allowed disabled:bg-[#F1F1EF] disabled:text-[#B5B5B0] disabled:line-through`}
                  >
                    {slot.gio}
                  </button>
                ))}
                {khungGio.length > 0 && khungGio.every((slot) => !slot.con_trong) ? <p className="text-sm text-[#B60F0F]">Chuyên gia không còn giờ trống trong ngày này.</p> : null}
              </div>
            )}
          </div>
          <label className="text-sm font-medium">Khu vực workshop (nếu cần thi công)
            <select name="id_khu_vuc_workshop" value={form.id_khu_vuc_workshop} onChange={setField} className={fieldClass}>
              <option value="">Không chọn</option>
              {khuVuc.map((area) => <option key={area.id} value={area.id}>{area.ten_khu_vuc}</option>)}
            </select>
          </label>
          <label className="text-sm font-medium md:col-span-2">Ghi chú
            <textarea name="ghi_chu" rows={3} maxLength={1000} value={form.ghi_chu} onChange={setField} className={fieldClass} />
          </label>

          {loi ? <p role="alert" className="rounded-lg bg-[#FBE9E9] px-4 py-3 text-sm text-red-800 md:col-span-2">{loi}</p> : null}
          {thanhCong ? <p role="status" className="rounded-lg bg-[#E9F7EE] px-4 py-3 text-sm text-green-800 md:col-span-2">{thanhCong}</p> : null}
          <div className="md:col-span-2">
            <button type="submit" disabled={dangLuu} className="rounded-lg bg-[#17171A] px-6 py-3 text-sm font-semibold text-white disabled:opacity-50">
              {dangLuu ? "Đang gửi..." : "Xác nhận đặt lịch"}
            </button>
          </div>
        </form>
      )}
    </section>
  );
}
