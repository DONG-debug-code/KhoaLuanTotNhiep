import { ArrowRight, CalendarCheck, CheckCheck, Clock3, CircleDollarSign, ShoppingBag } from "lucide-react";
import { formatDate, formatMoney, initials, STAGE_META } from "../chuyengia/workspace/expertUi";
import { getWorkspaceAssetUrl } from "../../api/WorkspaceApi";

function Card({ title, subtitle, right, children, className = "" }) {
  return (
    <section className={`rounded-2xl border border-[#ECECE9] bg-white p-6 ${className}`}>
      <div className="mb-5 flex items-start justify-between gap-3">
        <div>
          <h2 className="text-base font-extrabold tracking-tight">{title}</h2>
          {subtitle ? <p className="mt-1 text-xs text-[#63635E]">{subtitle}</p> : null}
        </div>
        {right}
      </div>
      {children}
    </section>
  );
}

function Kpi({ label, value, hint, icon: Icon, tone }) {
  return (
    <div className="rounded-2xl border border-[#ECECE9] bg-white p-5">
      <div className="flex items-start justify-between">
        <p className="text-sm text-[#63635E]">{label}</p>
        <span className={`flex h-10 w-10 items-center justify-center rounded-xl ${tone}`}><Icon className="h-[18px] w-[18px]" /></span>
      </div>
      <p className="mt-4 text-3xl font-extrabold tracking-tight">{value}</p>
      <p className="mt-1 text-xs text-[#63635E]">{hint}</p>
    </div>
  );
}

function compactMoney(value) {
  const amount = Number(value) || 0;
  if (amount >= 1e9) return `${(amount / 1e9).toLocaleString("vi-VN", { maximumFractionDigits: 1 })} tỷ`;
  if (amount >= 1e6) return `${(amount / 1e6).toLocaleString("vi-VN", { maximumFractionDigits: 1 })} tr`;
  return formatMoney(amount);
}

function Bars({ data, valueKey, labelKey, color, formatter }) {
  const max = Math.max(1, ...data.map((item) => item[valueKey]));
  return (
    <div className="flex h-[210px] items-end gap-3">
      {data.map((item) => (
        <div key={item[labelKey]} className="flex h-full flex-1 flex-col justify-end text-center">
          <span className="mb-1 text-[11px] font-semibold text-[#63635E]">{item[valueKey] ? formatter(item[valueKey]) : ""}</span>
          <div className="rounded-t-lg" style={{ height: `${Math.max(3, (item[valueKey] / max) * 100)}%`, background: color }} />
          <span className="mt-2 text-[11px] text-[#63635E]">{item[labelKey]}</span>
        </div>
      ))}
    </div>
  );
}

export default function AdminDashboardView({ data, onNavigate }) {
  const stat = data.thong_ke;
  const week = data.bieu_do_7_ngay.map((day) => ({ ...day, tong: day.lich_hen + day.don_hang }));
  const weekTotal = week.reduce((sum, day) => sum + day.tong, 0);
  const mixTotal = data.co_cau_dich_vu.reduce((sum, item) => sum + item.so_luong, 0);

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-end justify-between gap-4">
        <div>
          <p className="text-xs font-semibold uppercase tracking-wide text-[#63635E]">Tổng quan / Dashboard</p>
          <h1 className="mt-2 text-3xl font-extrabold tracking-tight">Dashboard</h1>
          <p className="mt-2 max-w-2xl text-sm text-[#63635E]">Bức tranh toàn cảnh hoạt động của showroom và workshop: lịch hẹn, đơn hàng, doanh thu, chuyên gia và các việc cần xử lý.</p>
        </div>
        <button type="button" onClick={() => onNavigate("appointments")} className="inline-flex items-center gap-2 rounded-xl bg-[#C9161F] px-5 py-3 text-sm font-semibold text-white"><ArrowRight className="h-4 w-4" /> Xử lý lịch hẹn</button>
      </div>

      <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <Kpi label="Tổng lịch hẹn" value={stat.tong_lich} hint="Toàn bộ lịch lắp đặt & tư vấn" icon={CalendarCheck} tone="bg-[#FDE8E8] text-[#B60F0F]" />
        <Kpi label="Lịch cần xử lý" value={stat.can_xu_ly} hint="Đang chờ admin xác nhận" icon={Clock3} tone="bg-[#FBF1DC] text-[#8A6414]" />
        <Kpi label="Đơn hàng cần xử lý" value={stat.don_can_xu_ly} hint={`Trên tổng ${stat.tong_don_hang} đơn hàng`} icon={ShoppingBag} tone="bg-[#EEEEEB] text-[#4A4A46]" />
        <Kpi label="Doanh thu đã thu" value={compactMoney(stat.doanh_thu)} hint="Đơn đã thanh toán + lịch hoàn thành" icon={CircleDollarSign} tone="bg-[#0B0B0D] text-white" />
      </div>

      <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_380px]">
        <Card title="Hoạt động 7 ngày gần nhất" subtitle="Lịch hẹn và đơn hàng phát sinh theo từng ngày" right={<span className="rounded-full bg-[#FDE8E8] px-3 py-1 text-xs font-semibold text-[#B60F0F]">{weekTotal} lượt</span>}>
          <Bars data={week} valueKey="tong" labelKey="nhan" color="#C9161F" formatter={String} />
        </Card>
        <Card title="Cơ cấu dịch vụ" subtitle="Tỉ lệ giữa các loại lịch hẹn">
          <div className="space-y-5">
            {data.co_cau_dich_vu.length ? data.co_cau_dich_vu.map((item, index) => {
              const percent = mixTotal ? Math.round((item.so_luong / mixTotal) * 100) : 0;
              return (
                <div key={item.ten}>
                  <div className="flex justify-between text-sm"><span className="font-medium">{item.ten}</span><span className="font-semibold">{percent}%</span></div>
                  <div className="mt-2 h-2 rounded-full bg-[#EEEEEB]"><div className="h-2 rounded-full" style={{ width: `${percent}%`, background: index ? "#B8862B" : "#C9161F" }} /></div>
                  <p className="mt-1 text-xs text-[#63635E]">{item.so_luong} lịch</p>
                </div>
              );
            }) : <p className="text-sm text-[#63635E]">Chưa có lịch hẹn.</p>}
            <div className="rounded-xl bg-[#F6F5F2] p-4">
              <p className="text-xs text-[#63635E]">Giá trị trung bình mỗi lịch</p>
              <p className="mt-1 text-lg font-extrabold">{stat.gia_tri_trung_binh_lich ? formatMoney(stat.gia_tri_trung_binh_lich) : "Chưa có dữ liệu"}</p>
            </div>
          </div>
        </Card>
      </div>

      <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_380px]">
        <Card title="Doanh thu 6 tháng gần nhất" subtitle="Đơn hàng đã thanh toán và lịch hoàn thành">
          <Bars data={data.doanh_thu_6_thang} valueKey="doanh_thu" labelKey="thang" color="#B8862B" formatter={compactMoney} />
        </Card>
        <Card title="Hoạt động gần đây" subtitle="Cập nhật mới nhất từ hệ thống">
          <ul className="space-y-4">
            {data.hoat_dong.length ? data.hoat_dong.map((item, index) => (
              <li key={index} className="flex items-start gap-3">
                <span className="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#F1F1EF]">
                  {item.loai === "thanh_toan" ? <CheckCheck className="h-4 w-4" /> : item.loai === "don_hang" ? <ShoppingBag className="h-4 w-4" /> : <CalendarCheck className="h-4 w-4" />}
                </span>
                <div><p className="text-sm font-medium">{item.noi_dung}</p><p className="text-xs text-[#63635E]">{item.thoi_gian}</p></div>
              </li>
            )) : <p className="text-sm text-[#63635E]">Chưa có hoạt động.</p>}
          </ul>
        </Card>
      </div>

      <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_380px]">
        <Card title="Lịch hẹn sắp tới" subtitle="Các lịch đang chờ xác nhận hoặc đã xác nhận" right={<button type="button" onClick={() => onNavigate("appointments")} className="text-sm font-semibold text-[#C9161F]">Xem tất cả →</button>}>
          {data.lich_sap_toi.length ? (
            <div className="divide-y divide-[#ECECE9]">
              {data.lich_sap_toi.map((item) => {
                const meta = Number(item.trang_thai) === 1 ? STAGE_META.dang_thi_cong : STAGE_META.cho_xac_nhan;
                return (
                  <div key={item.id} className="flex flex-wrap items-center justify-between gap-3 py-4">
                    <div className="flex items-center gap-4">
                      <div className="w-16 rounded-lg bg-[#F6F5F2] p-2 text-center"><p className="text-sm font-extrabold">{formatDate(item.ngay_dat).slice(0, 5)}</p><p className="text-[11px] text-[#63635E]">{item.gio_dat}</p></div>
                      <div>
                        <p className="text-xs"><span className="font-mono text-[#63635E]">{item.ma_dat_lich}</span> <span className={`ml-2 rounded-full border px-2 py-0.5 text-[11px] font-semibold ${meta.chip}`}>{Number(item.trang_thai) === 1 ? "Đã xác nhận" : "Chờ xác nhận"}</span></p>
                        <p className="mt-1 font-semibold">{item.ten_khach_hang}</p>
                        <p className="text-xs text-[#63635E]">{item.thong_tin_xe || item.dich_vu}</p>
                      </div>
                    </div>
                    <p className="text-right text-xs text-[#63635E]">{item.ten_chuyen_gia || "Chưa phân công"}<br />{item.ten_khu_vuc || ""}</p>
                  </div>
                );
              })}
            </div>
          ) : <p className="text-sm text-[#63635E]">Không có lịch hẹn sắp tới.</p>}
        </Card>
        <Card title="Chuyên gia nổi bật" subtitle="Xếp theo điểm đánh giá và số ca hoàn tất">
          <div className="space-y-5">
            {data.chuyen_gia_noi_bat.map((expert) => {
              const avatar = getWorkspaceAssetUrl(expert.anh);
              return (
                <div key={expert.id} className="flex items-center gap-3">
                  {avatar ? <img src={avatar} alt={expert.ho_ten} className="h-11 w-11 rounded-full object-cover" /> : <span className="flex h-11 w-11 items-center justify-center rounded-full bg-[#0B0B0D] text-xs font-bold text-white">{initials(expert.ho_ten)}</span>}
                  <div className="min-w-0 flex-1">
                    <p className="truncate font-semibold">{expert.ho_ten}</p>
                    <p className="truncate text-xs text-[#63635E]">{expert.chuc_danh || "Chuyên gia Tuner"} · {expert.so_ca_hoan_tat} ca hoàn tất</p>
                  </div>
                  <span className="text-xs font-semibold text-[#8A6414]">★ {expert.diem ?? "—"}</span>
                </div>
              );
            })}
          </div>
        </Card>
      </div>
    </div>
  );
}
