import { useState } from "react";
import AdminDataTable, { ActionButton, Chip, StatCards } from "./AdminDataTable";
import VehicleHistoryModal from "../../components/workspaces/VehicleHistoryModal";
import { formatDate, formatMoney } from "../chuyengia/workspace/expertUi";

const APPOINTMENT_STATUS = {
  0: { label: "Chờ xác nhận", tone: "gold" },
  1: { label: "Đã xác nhận", tone: "gray" },
  2: { label: "Hoàn thành", tone: "dark" },
  3: { label: "Đã hủy", tone: "red" },
};

const ORDER_STATUS = {
  cho_xac_nhan: { label: "Chờ xác nhận", tone: "gold" },
  da_xac_nhan: { label: "Đã xác nhận", tone: "gray" },
  dang_giao: { label: "Đang xử lý", tone: "gray" },
  hoan_thanh: { label: "Hoàn thành", tone: "dark" },
  yeu_cau_huy: { label: "Yêu cầu hủy", tone: "red" },
  da_huy_hoan_kho: { label: "Đã hủy", tone: "red" },
};

const NEXT_ORDER_STEP = {
  cho_xac_nhan: { to: "da_xac_nhan", label: "Xác nhận" },
  da_xac_nhan: { to: "dang_giao", label: "Bắt đầu xử lý" },
  dang_giao: { to: "hoan_thanh", label: "Hoàn thành" },
};

const dateTime = (value) => (value ? new Date(value.replace(" ", "T") + "Z").toLocaleString("vi-VN", { timeZone: "Asia/Ho_Chi_Minh" }) : "—");

function Person({ name, sub }) {
  return <div><p className="font-semibold">{name || "—"}</p>{sub ? <p className="text-xs text-[#63635E]">{sub}</p> : null}</div>;
}

function AssignModal({ appointment, experts, bays, onClose, onSubmit }) {
  const [expertId, setExpertId] = useState(appointment.id_chuyen_gia || "");
  const [bayId, setBayId] = useState(appointment.id_khu_vuc_workshop || "");
  const [reason, setReason] = useState("");
  const changingExpert = Boolean(appointment.id_chuyen_gia) && Number(expertId) !== Number(appointment.id_chuyen_gia);
  return (
    <div className="fixed inset-0 z-[70] flex items-center justify-center bg-black/50 p-4" onClick={onClose}>
      <form onClick={(event) => event.stopPropagation()} onSubmit={(event) => { event.preventDefault(); onSubmit({ id: appointment.id, id_chuyen_gia: Number(expertId), id_khu_vuc_workshop: Number(bayId), ...(changingExpert && reason.trim() ? { ly_do: reason.trim() } : {}) }); }} className="w-full max-w-md space-y-4 rounded-2xl bg-white p-6">
        <h3 className="text-lg font-extrabold">Phân công {appointment.ma_dat_lich}</h3>
        <label className="block text-sm font-medium">Chuyên gia
          <select required value={expertId} onChange={(event) => setExpertId(event.target.value)} className="mt-1 w-full rounded-lg border border-[#E4E4E0] px-3 py-2.5">
            <option value="">Chọn chuyên gia</option>
            {experts.filter((item) => item.trang_thai === 1).map((item) => <option key={item.id} value={item.id}>{item.ho_ten}</option>)}
          </select>
        </label>
        <label className="block text-sm font-medium">Khu vực workshop
          <select required value={bayId} onChange={(event) => setBayId(event.target.value)} className="mt-1 w-full rounded-lg border border-[#E4E4E0] px-3 py-2.5">
            <option value="">Chọn khu vực</option>
            {bays.filter((item) => Number(item.trang_thai) === 1).map((item) => <option key={item.id} value={item.id}>{item.ten_khu_vuc}</option>)}
          </select>
        </label>
        {changingExpert ? (
          <label className="block text-sm font-medium">Lý do đổi chuyên gia (gửi cho khách)
            <input value={reason} onChange={(event) => setReason(event.target.value)} maxLength={500} placeholder="Ví dụ: Chuyên gia không có mặt vào giờ hẹn" className="mt-1 w-full rounded-lg border border-[#E4E4E0] px-3 py-2.5" />
          </label>
        ) : null}
        <div className="flex justify-end gap-2"><ActionButton onClick={onClose}>Đóng</ActionButton><button type="submit" className="rounded-lg bg-[#AC0021] px-4 py-2 text-xs font-semibold text-white">Lưu phân công</button></div>
      </form>
    </div>
  );
}

function RescheduleModal({ appointment, experts, onClose, onSubmit }) {
  const [date, setDate] = useState(String(appointment.ngay_dat).slice(0, 10));
  const [time, setTime] = useState(String(appointment.gio_dat).slice(0, 5));
  const [expertId, setExpertId] = useState(appointment.id_chuyen_gia || "");
  const [reason, setReason] = useState("");
  return (
    <div className="fixed inset-0 z-[70] flex items-center justify-center bg-black/50 p-4" onClick={onClose}>
      <form onClick={(event) => event.stopPropagation()} onSubmit={(event) => { event.preventDefault(); onSubmit({ id: appointment.id, ngay_dat: date, gio_dat: time, ...(expertId ? { id_chuyen_gia: Number(expertId) } : {}), ...(reason.trim() ? { ly_do: reason.trim() } : {}) }); }} className="w-full max-w-md space-y-4 rounded-2xl bg-white p-6">
        <h3 className="text-lg font-extrabold">Đổi lịch {appointment.ma_dat_lich}</h3>
        <div className="grid grid-cols-2 gap-3">
          <label className="block text-sm font-medium">Ngày mới
            <input required type="date" value={date} onChange={(event) => setDate(event.target.value)} className="mt-1 w-full rounded-lg border border-[#E4E4E0] px-3 py-2.5" />
          </label>
          <label className="block text-sm font-medium">Giờ mới
            <input required type="time" value={time} onChange={(event) => setTime(event.target.value)} className="mt-1 w-full rounded-lg border border-[#E4E4E0] px-3 py-2.5" />
          </label>
        </div>
        <label className="block text-sm font-medium">Chuyên gia phụ trách
          <select value={expertId} onChange={(event) => setExpertId(event.target.value)} className="mt-1 w-full rounded-lg border border-[#E4E4E0] px-3 py-2.5">
            <option value="">Giữ nguyên</option>
            {experts.filter((item) => item.trang_thai === 1).map((item) => <option key={item.id} value={item.id}>{item.ho_ten}</option>)}
          </select>
        </label>
        <label className="block text-sm font-medium">Lý do (gửi cho khách)
          <input value={reason} onChange={(event) => setReason(event.target.value)} maxLength={500} className="mt-1 w-full rounded-lg border border-[#E4E4E0] px-3 py-2.5" />
        </label>
        <p className="text-xs text-[#63635E]">Hệ thống kiểm tra chuyên gia và khu vực còn trống, sau đó thông báo cho khách qua chat, email và lịch hẹn của khách.</p>
        <div className="flex justify-end gap-2"><ActionButton onClick={onClose}>Đóng</ActionButton><button type="submit" className="rounded-lg bg-[#AC0021] px-4 py-2 text-xs font-semibold text-white">Đổi lịch và thông báo</button></div>
      </form>
    </div>
  );
}

export function AppointmentsView({ rows, experts, bays, search, run, busy }) {
  const [assigning, setAssigning] = useState(null);
  const [rescheduling, setRescheduling] = useState(null);
  const count = (status) => rows.filter((row) => Number(row.trang_thai) === status).length;
  const tabs = [
    { key: "all", label: "Tất cả" },
    ...[0, 1, 2, 3].map((status) => ({ key: String(status), label: APPOINTMENT_STATUS[status].label, test: (row) => Number(row.trang_thai) === status })),
  ];

  return (
    <div className="space-y-6">
      <StatCards items={[
        { label: "Chờ xác nhận", value: count(0), dot: "#B8862B" },
        { label: "Đã xác nhận", value: count(1) },
        { label: "Hoàn thành", value: count(2) },
        { label: "Đã hủy", value: count(3), dot: "#8B8B86" },
      ]} />
      <AdminDataTable
        rows={rows}
        search={search}
        tabs={tabs}
        searchText={(row) => `${row.ma_dat_lich} ${row.ten_khach_hang} ${row.dien_thoai} ${row.bien_so_xe} ${row.thong_tin_xe} ${row.ten_chuyen_gia}`}
        emptyMessage="Chưa có lịch hẹn nào."
        columns={[
          { label: "Mã lịch", render: (row) => <Person name={<span className="font-mono">{row.ma_dat_lich}</span>} sub={`Tạo ${formatDate(row.ngay_tao)}`} /> },
          { label: "Khách hàng", render: (row) => <Person name={row.ten_khach_hang} sub={row.dien_thoai} /> },
          { label: "Dịch vụ", render: (row) => <Person name={row.dich_vu} sub={row.thong_tin_xe || row.id_don_hang && `Đơn DH-${String(row.id_don_hang).padStart(6, "0")}`} /> },
          { label: "Chuyên gia", render: (row) => <Person name={row.ten_chuyen_gia || "Chưa phân công"} sub={row.ten_khu_vuc} /> },
          { label: "Thời gian", render: (row) => <Person name={formatDate(row.ngay_dat)} sub={row.gio_dat} /> },
          { label: "Trạng thái", render: (row) => { const meta = APPOINTMENT_STATUS[Number(row.trang_thai)]; return <Chip tone={meta.tone}>{meta.label}</Chip>; } },
          { label: "Giá trị", align: "right", render: (row) => (row.gia_tri ? formatMoney(row.gia_tri) : "—") },
        ]}
        actions={(row) => {
          const status = Number(row.trang_thai);
          if (status > 1) return null;
          return (
            <>
              {status === 0 ? <ActionButton tone="primary" disabled={busy} onClick={() => run("lich-hen/xac-nhan", { id: row.id }, "Đã xác nhận lịch hẹn.")}>Xác nhận</ActionButton> : null}
              <ActionButton disabled={busy} onClick={() => setRescheduling(row)}>Đổi lịch</ActionButton>
              <ActionButton disabled={busy} onClick={() => setAssigning(row)}>{row.id_chuyen_gia ? "Đổi chuyên gia" : "Phân công"}</ActionButton>
              <ActionButton tone="danger" disabled={busy} onClick={() => window.confirm(`Hủy lịch ${row.ma_dat_lich}? Khách hàng sẽ nhận email thông báo.`) && run("lich-hen/huy", { id: row.id }, "Đã hủy lịch hẹn.")}>Hủy</ActionButton>
            </>
          );
        }}
      />
      {assigning ? <AssignModal appointment={assigning} experts={experts} bays={bays} onClose={() => setAssigning(null)} onSubmit={async (payload) => { if (await run("lich-hen/phan-cong", payload, "Đã phân công lịch hẹn.")) setAssigning(null); }} /> : null}
      {rescheduling ? <RescheduleModal appointment={rescheduling} experts={experts} onClose={() => setRescheduling(null)} onSubmit={async (payload) => { if (await run("lich-hen/doi-lich", payload, "Đã đổi lịch và thông báo cho khách hàng.")) setRescheduling(null); }} /> : null}
    </div>
  );
}

const TEST_DRIVE_STATUS = {
  0: { label: "Mới đăng ký", tone: "gold" },
  1: { label: "Đã xác nhận", tone: "gray" },
  2: { label: "Đã lái thử", tone: "dark" },
  3: { label: "Đã hủy", tone: "red" },
};

function TestDriveModal({ registration, onClose, onSubmit }) {
  const [form, setForm] = useState({
    ngay_hen: String(registration.ngay_hen).slice(0, 10),
    gio_hen: registration.gio_hen,
    dia_diem: registration.dia_diem,
    ghi_chu_nhan_vien: registration.ghi_chu_nhan_vien || "",
  });
  const set = (key) => (event) => setForm((current) => ({ ...current, [key]: event.target.value }));
  return (
    <div className="fixed inset-0 z-[70] flex items-center justify-center bg-black/50 p-4" onClick={onClose}>
      <form onClick={(event) => event.stopPropagation()} onSubmit={(event) => { event.preventDefault(); onSubmit({ id: registration.id, ...form, trang_thai: 1 }); }} className="w-full max-w-md space-y-4 rounded-2xl bg-white p-6">
        <h3 className="text-lg font-extrabold">Đổi lịch lái thử {registration.ma_lai_thu}</h3>
        <div className="grid grid-cols-2 gap-3">
          <label className="block text-sm font-medium">Ngày
            <input required type="date" value={form.ngay_hen} onChange={set("ngay_hen")} className="mt-1 w-full rounded-lg border border-[#E4E4E0] px-3 py-2.5" />
          </label>
          <label className="block text-sm font-medium">Giờ
            <input required type="time" value={form.gio_hen} onChange={set("gio_hen")} className="mt-1 w-full rounded-lg border border-[#E4E4E0] px-3 py-2.5" />
          </label>
        </div>
        <label className="block text-sm font-medium">Địa điểm
          <input required value={form.dia_diem} onChange={set("dia_diem")} className="mt-1 w-full rounded-lg border border-[#E4E4E0] px-3 py-2.5" />
        </label>
        <label className="block text-sm font-medium">Ghi chú gửi khách
          <textarea rows={3} value={form.ghi_chu_nhan_vien} onChange={set("ghi_chu_nhan_vien")} className="mt-1 w-full resize-none rounded-lg border border-[#E4E4E0] px-3 py-2.5" />
        </label>
        <p className="text-xs text-[#63635E]">Khách hàng sẽ nhận email thông báo lịch mới và có thể trả lời trực tiếp email đó.</p>
        <div className="flex justify-end gap-2"><ActionButton onClick={onClose}>Đóng</ActionButton><button type="submit" className="rounded-lg bg-[#AC0021] px-4 py-2 text-xs font-semibold text-white">Lưu và gửi email</button></div>
      </form>
    </div>
  );
}

export function TestDrivesView({ rows, search, run, busy }) {
  const [editing, setEditing] = useState(null);
  const count = (status) => rows.filter((row) => Number(row.trang_thai) === status).length;
  const tabs = [
    { key: "all", label: "Tất cả" },
    ...[0, 1, 2, 3].map((status) => ({ key: String(status), label: TEST_DRIVE_STATUS[status].label, test: (row) => Number(row.trang_thai) === status })),
  ];

  return (
    <div className="space-y-6">
      <StatCards items={[
        { label: "Mới đăng ký", value: count(0), dot: "#B8862B" },
        { label: "Đã xác nhận", value: count(1) },
        { label: "Đã lái thử", value: count(2) },
        { label: "Đã hủy", value: count(3), dot: "#8B8B86" },
      ]} />
      <AdminDataTable
        rows={rows}
        search={search}
        tabs={tabs}
        searchText={(row) => `${row.ma_lai_thu} ${row.ho_ten} ${row.email} ${row.so_dien_thoai} ${row.dong_xe}`}
        emptyMessage="Chưa có đăng ký lái thử nào."
        columns={[
          { label: "Mã", render: (row) => <Person name={<span className="font-mono">{row.ma_lai_thu}</span>} sub={dateTime(row.created_at)} /> },
          { label: "Khách hàng", render: (row) => <Person name={row.ho_ten} sub={`${row.so_dien_thoai} · ${row.email}`} /> },
          { label: "Dòng xe", render: (row) => <Person name={row.dong_xe || "—"} sub={row.ghi_chu} /> },
          { label: "Lịch hẹn", render: (row) => <Person name={`${formatDate(row.ngay_hen)} ${row.gio_hen}`} sub={row.dia_diem} /> },
          { label: "Trạng thái", render: (row) => { const meta = TEST_DRIVE_STATUS[Number(row.trang_thai)]; return <Chip tone={meta.tone}>{meta.label}</Chip>; } },
        ]}
        actions={(row) => {
          const status = Number(row.trang_thai);
          if (status > 1) return null;
          return (
            <>
              <ActionButton disabled={busy} onClick={() => setEditing(row)}>Đổi lịch</ActionButton>
              <ActionButton tone="primary" disabled={busy} onClick={() => run("lai-thu/cap-nhat", { id: row.id, trang_thai: 2 }, "Đã ghi nhận khách đã lái thử.")}>Hoàn tất</ActionButton>
              <ActionButton tone="danger" disabled={busy} onClick={() => window.confirm(`Hủy lịch lái thử ${row.ma_lai_thu}? Khách hàng sẽ nhận email thông báo.`) && run("lai-thu/cap-nhat", { id: row.id, trang_thai: 3 }, "Đã hủy lịch lái thử.")}>Hủy</ActionButton>
            </>
          );
        }}
      />
      {editing ? <TestDriveModal registration={editing} onClose={() => setEditing(null)} onSubmit={async (payload) => { if (await run("lai-thu/cap-nhat", payload, "Đã cập nhật lịch lái thử và gửi email cho khách.")) setEditing(null); }} /> : null}
    </div>
  );
}

export function VehiclesView({ rows, search }) {
  const [selected, setSelected] = useState(null);
  return (
    <div className="space-y-6">
      <AdminDataTable
        rows={rows}
        search={search}
        searchText={(row) => `${row.ten_xe} ${row.bien_so_xe} ${row.chu_xe} ${row.so_dien_thoai} ${row.dong_xe}`}
        emptyMessage="Chưa có xe nào trong garage của khách hàng."
        columns={[
          { label: "Xe", render: (row) => <Person name={row.ten_xe} sub={<span className="font-mono">{row.bien_so_xe}</span>} /> },
          { label: "Chủ xe", render: (row) => <Person name={row.chu_xe} sub={row.so_dien_thoai} /> },
          { label: "Thông tin", render: (row) => <Person name={[row.dong_xe, row.nam_san_xuat, row.mau_sac].filter(Boolean).join(" · ") || "—"} sub={row.so_km != null ? `${Number(row.so_km).toLocaleString("vi-VN")} km` : null} /> },
          { label: "Lịch sử", render: (row) => <Person name={`${row.so_ban_ghi} bản ghi`} sub={row.lan_cuoi ? `Gần nhất ${formatDate(row.lan_cuoi)}` : "Chưa có"} /> },
        ]}
        actions={(row) => <ActionButton tone="primary" onClick={() => setSelected(row)}>Lịch sử / Ghi nhận</ActionButton>}
      />
      {selected ? <VehicleHistoryModal vehicle={selected} staff onClose={() => setSelected(null)} /> : null}
    </div>
  );
}

export function OrdersView({ rows, search, run, busy, openOrder }) {
  const count = (test) => rows.filter(test).length;
  const tabs = [
    { key: "all", label: "Tất cả" },
    { key: "cho", label: "Chờ xác nhận", test: (row) => row.trang_thai === "cho_xac_nhan" },
    { key: "xl", label: "Đang xử lý", test: (row) => ["da_xac_nhan", "dang_giao"].includes(row.trang_thai) },
    { key: "ht", label: "Hoàn thành", test: (row) => row.trang_thai === "hoan_thanh" },
    { key: "huy", label: "Hủy", test: (row) => ["yeu_cau_huy", "da_huy_hoan_kho"].includes(row.trang_thai) },
  ];

  return (
    <div className="space-y-6">
      <StatCards items={[
        { label: "Chờ xác nhận", value: count((row) => row.trang_thai === "cho_xac_nhan"), dot: "#B8862B" },
        { label: "Đang xử lý", value: count((row) => ["da_xac_nhan", "dang_giao"].includes(row.trang_thai)) },
        { label: "Hoàn thành", value: count((row) => row.trang_thai === "hoan_thanh") },
        { label: "Yêu cầu hủy / đã hủy", value: count((row) => ["yeu_cau_huy", "da_huy_hoan_kho"].includes(row.trang_thai)), dot: "#8B8B86" },
      ]} />
      <AdminDataTable
        rows={rows}
        search={search}
        tabs={tabs}
        searchText={(row) => `${row.ma_don_hang} ${row.ten_khach_hang} ${row.ten_nguoi_nhan} ${row.so_dien_thoai_nguoi_nhan} ${row.email_nguoi_nhan}`}
        emptyMessage="Chưa có đơn hàng nào."
        columns={[
          { label: "Mã đơn", render: (row) => <Person name={<span className="font-mono">{row.ma_don_hang}</span>} sub={dateTime(row.created_at)} /> },
          { label: "Khách hàng", render: (row) => <Person name={row.ten_nguoi_nhan || row.ten_khach_hang} sub={row.so_dien_thoai_nguoi_nhan} /> },
          { label: "Nhận hàng", render: (row) => (row.phuong_thuc_nhan === "nhan_tai_showroom" ? "Tại showroom" : "Giao tận nơi") },
          { label: "Thanh toán", render: (row) => <Person name={row.phuong_thuc_tt === "the" ? "Thẻ / VNPay" : "Tiền mặt (COD)"} sub={row.trang_thai_thanh_toan === "da_thanh_toan" ? "Đã thanh toán" : "Chưa thanh toán"} /> },
          { label: "Trạng thái", render: (row) => { const meta = ORDER_STATUS[row.trang_thai] || { label: row.trang_thai, tone: "gray" }; return <Chip tone={meta.tone}>{meta.label}</Chip>; } },
          { label: "Xác nhận bởi", render: (row) => row.nhan_vien_xac_nhan || "—" },
          { label: "Thành tiền", align: "right", render: (row) => formatMoney(row.thanh_tien) },
        ]}
        actions={(row) => {
          const next = NEXT_ORDER_STEP[row.trang_thai];
          return (
            <>
              <ActionButton onClick={() => openOrder(row.id)}>Chi tiết</ActionButton>
              {next ? <ActionButton tone="primary" disabled={busy} onClick={() => run("don-hang/cap-nhat-trang-thai", { don_hang_id: row.id, trang_thai: next.to }, `Đơn ${row.ma_don_hang}: ${next.label.toLowerCase()} thành công.`)}>{next.label}</ActionButton> : null}
              {row.trang_thai === "yeu_cau_huy" ? (
                <>
                  <ActionButton tone="danger" disabled={busy} onClick={() => run("don-hang/duyet-huy-hoan-kho", { don_hang_id: row.id, dong_y_huy: true }, "Đã duyệt hủy và hoàn kho.")}>Duyệt hủy</ActionButton>
                  <ActionButton disabled={busy} onClick={() => run("don-hang/duyet-huy-hoan-kho", { don_hang_id: row.id, dong_y_huy: false }, "Đã từ chối yêu cầu hủy.")}>Từ chối</ActionButton>
                </>
              ) : null}
            </>
          );
        }}
      />
    </div>
  );
}

export function InvoicesView({ rows, search, openOrder }) {
  return (
    <AdminDataTable
      rows={rows}
      search={search}
      searchText={(row) => `${row.ma_hoa_don} ${row.ten_khach_hang}`}
      emptyMessage="Chưa phát sinh hóa đơn. Hóa đơn được tạo khi đơn hàng hoàn thành."
      columns={[
        { label: "Số hóa đơn", render: (row) => <span className="font-mono font-semibold">{row.ma_hoa_don}</span> },
        { label: "Khách hàng", render: (row) => row.ten_khach_hang || "—" },
        { label: "Đơn hàng", render: (row) => (row.don_hang_id ? `DH-${String(row.don_hang_id).padStart(6, "0")}` : "—") },
        { label: "Ngày xuất", render: (row) => dateTime(row.ngay_xuat) },
        { label: "Thanh toán", render: (row) => <Chip tone={row.trang_thai_tt === "da_thanh_toan" ? "green" : "gold"}>{row.trang_thai_tt === "da_thanh_toan" ? "Đã thanh toán" : "Chưa thanh toán"}</Chip> },
        { label: "Tổng tiền", align: "right", render: (row) => formatMoney(row.tong_tien) },
      ]}
      actions={(row) => (row.don_hang_id ? <ActionButton onClick={() => openOrder(row.don_hang_id)}>Chi tiết / In PDF</ActionButton> : null)}
    />
  );
}

export function CustomersView({ rows, search }) {
  return (
    <AdminDataTable
      rows={rows}
      search={search}
      searchText={(row) => `${row.ho_ten} ${row.email} ${row.so_dien_thoai}`}
      emptyMessage="Chưa có khách hàng."
      columns={[
        { label: "Khách hàng", render: (row) => <Person name={row.ho_ten} sub={row.email} /> },
        { label: "Điện thoại", render: (row) => row.so_dien_thoai || "—" },
        { label: "Ngày đăng ký", render: (row) => row.ngay_dang_ky || "—" },
        { label: "Đơn hàng", render: (row) => row.so_don_hang },
        { label: "Lịch hẹn", render: (row) => row.so_lich_hen },
        { label: "Trạng thái", render: (row) => <Chip tone={row.trang_thai === 1 ? "green" : "gray"}>{row.trang_thai === 1 ? "Hoạt động" : "Bị khóa"}</Chip> },
        { label: "Đã chi tiêu", align: "right", render: (row) => formatMoney(row.tong_chi_tieu) },
      ]}
    />
  );
}

export function ExpertsView({ rows, search }) {
  return (
    <AdminDataTable
      rows={rows}
      search={search}
      searchText={(row) => `${row.ho_ten} ${row.email} ${row.so_dien_thoai} ${row.chuc_danh} ${row.chuyen_mon.join(" ")}`}
      emptyMessage="Chưa có chuyên gia."
      columns={[
        { label: "Chuyên gia", render: (row) => <Person name={row.ho_ten} sub={row.chuc_danh} /> },
        { label: "Liên hệ", render: (row) => <Person name={row.so_dien_thoai || "—"} sub={row.email} /> },
        { label: "Kinh nghiệm", render: (row) => (row.so_nam_kinh_nghiem != null ? `${row.so_nam_kinh_nghiem} năm` : "—") },
        { label: "Chuyên môn", render: (row) => <div className="flex max-w-[260px] flex-wrap gap-1">{row.chuyen_mon.map((item) => <Chip key={item} tone="gray">{item}</Chip>)}</div> },
        { label: "Đánh giá", render: (row) => (row.diem ? `★ ${row.diem} (${row.so_danh_gia})` : "Chưa có") },
        { label: "Lịch đang giữ", render: (row) => `${row.so_lich} lịch · ${row.so_ngay_lam_viec} ngày làm` },
        { label: "Trạng thái", render: (row) => <Chip tone={row.trang_thai === 1 ? "green" : "gray"}>{row.trang_thai === 1 ? "Hoạt động" : "Tạm dừng"}</Chip> },
      ]}
    />
  );
}

export function RosterView({ rows, search }) {
  return (
    <AdminDataTable
      rows={rows}
      search={search}
      pageSize={10}
      searchText={(row) => `${row.ten_chuyen_gia} ${row.ngay_lam_viec} ${row.ca_lam_viec}`}
      emptyMessage="Chưa có lịch làm việc sắp tới."
      columns={[
        { label: "Ngày", render: (row) => formatDate(row.ngay_lam_viec) },
        { label: "Chuyên gia", render: (row) => <span className="font-semibold">{row.ten_chuyen_gia}</span> },
        { label: "Ca làm việc", render: (row) => row.ca_lam_viec || "—" },
        { label: "Lịch hẹn trong ngày", render: (row) => (row.so_lich ? <Chip tone="red">{row.so_lich} lịch</Chip> : <Chip tone="gray">Trống</Chip>) },
      ]}
    />
  );
}

export function StaffView({ rows, search }) {
  return (
    <AdminDataTable
      rows={rows}
      search={search}
      searchText={(row) => `${row.ho_ten} ${row.email} ${row.vai_tro}`}
      emptyMessage="Chưa có nhân viên."
      columns={[
        { label: "Nhân viên", render: (row) => <Person name={row.ho_ten} sub={row.email} /> },
        { label: "Điện thoại", render: (row) => row.so_dien_thoai || "—" },
        { label: "Vai trò", render: (row) => <Chip tone="gray">{row.vai_tro || "—"}</Chip> },
        { label: "Đơn đã xác nhận", render: (row) => row.so_don_xac_nhan },
        { label: "Trạng thái", render: (row) => <Chip tone={Number(row.trang_thai) === 1 ? "green" : "gray"}>{Number(row.trang_thai) === 1 ? "Hoạt động" : "Bị khóa"}</Chip> },
      ]}
    />
  );
}

export function BaysView({ rows, search }) {
  const label = { 1: ["Hoạt động", "green"], 2: ["Bảo trì", "gray"], 0: ["Ngừng", "gray"] };
  return (
    <AdminDataTable
      rows={rows}
      search={search}
      searchText={(row) => `${row.ten_khu_vuc} ${row.ghi_chu}`}
      emptyMessage="Chưa có khu vực workshop."
      columns={[
        { label: "Khu vực", render: (row) => <Person name={row.ten_khu_vuc} sub={row.ghi_chu} /> },
        { label: "Trạng thái", render: (row) => { const [text, tone] = label[Number(row.trang_thai)] || ["—", "gray"]; return <Chip tone={tone}>{text}</Chip>; } },
        { label: "Lịch hôm nay", render: (row) => row.lich_hom_nay },
        { label: "Lịch sắp tới", render: (row) => row.lich_sap_toi },
      ]}
    />
  );
}

export function ProductsView({ rows, search }) {
  return (
    <AdminDataTable
      rows={rows}
      search={search}
      pageSize={10}
      searchText={(row) => `${row.ten} ${row.ma} ${row.loai}`}
      emptyMessage="Chưa có sản phẩm."
      columns={[
        { label: "Sản phẩm", render: (row) => <Person name={row.ten} sub={row.ma} /> },
        { label: "Loại", render: (row) => <Chip tone="gray">{row.loai}</Chip> },
        { label: "Tồn kho", render: (row) => <Chip tone={row.ton_kho > 0 ? "green" : "red"}>{row.ton_kho > 0 ? `${row.ton_kho} sản phẩm` : "Hết hàng"}</Chip> },
        { label: "Giá bán", align: "right", render: (row) => formatMoney(row.gia) },
      ]}
    />
  );
}
