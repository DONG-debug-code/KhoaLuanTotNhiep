import { useState } from "react";
import { CalendarCheck, CheckCircle2, Coins, Gauge, Hammer, MapPin, PlayCircle, StickyNote, UserRound, Users, Award } from "lucide-react";
import { STAGE_META, bayShortName, formatDate, formatDuration, formatMillions, formatMoney } from "./expertUi";

function StageChip({ stage }) {
  const meta = STAGE_META[stage] || STAGE_META.cho_nhan_xe;
  return (
    <span className={`inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[11px] font-semibold ${meta.chip}`}>
      <span className="h-1.5 w-1.5 rounded-full" style={{ backgroundColor: stage === "da_ban_giao" ? "#fff" : meta.dot }} />
      {meta.label}
    </span>
  );
}

function StatCard({ label, value, hint, icon: Icon, tone }) {
  return (
    <div className="rounded-2xl border border-[#ECECE9] bg-white p-5">
      <div className="flex items-start justify-between">
        <p className="text-sm text-[#63635E]">{label}</p>
        <span className={`flex h-10 w-10 items-center justify-center rounded-xl ${tone}`}>
          <Icon className="h-[18px] w-[18px]" />
        </span>
      </div>
      <p className="mt-4 text-4xl font-extrabold tracking-tight">{value}</p>
      <p className="mt-2 text-xs text-[#8B8B86]">{hint}</p>
    </div>
  );
}

function JobCard({ job, bays, busy, onProgress, onConfirm }) {
  const [note, setNote] = useState(job.ghi_chu_chuyen_gia || "");
  const freeBays = bays.filter((bay) => bay.trang_thai === "trong" || bay.cong_viec?.id === job.id);
  const [bayId, setBayId] = useState(job.id_khu_vuc_workshop && freeBays.some((bay) => bay.id === job.id_khu_vuc_workshop) ? job.id_khu_vuc_workshop : freeBays[0]?.id || "");
  const stage = job.giai_doan_hien_tai;
  const isConsult = job.loai_lich === "tu-van";
  const noteText = stage === "da_ban_giao" ? job.ghi_chu_chuyen_gia : null;

  const run = (action) => onProgress(job, action, { ghi_chu_chuyen_gia: note, id_khu_vuc_workshop: bayId || undefined });

  return (
    <article className="rounded-2xl border border-[#ECECE9] bg-white p-4 sm:p-5">
      <div className="flex flex-wrap items-start gap-4">
        <div className="w-[64px] shrink-0 rounded-xl bg-[#F4F4F2] py-2.5 text-center">
          <p className="text-base font-extrabold">{job.gio_dat}</p>
          <p className="text-[11px] text-[#8B8B86]">{formatDuration(job.thoi_luong)}</p>
        </div>
        <div className="min-w-0 flex-1">
          <div className="flex flex-wrap items-center gap-2">
            <span className="font-mono text-[11px] text-[#63635E]">{job.ma_dat_lich}</span>
            <StageChip stage={stage} />
          </div>
          <h3 className="mt-1.5 text-[17px] font-bold leading-snug">{job.tieu_de}</h3>
          <p className="mt-1 text-sm text-[#63635E]">
            {[job.ten_xe, job.bien_so_xe, isConsult ? "Khu tư vấn" : bayShortName(job.ten_khu_vuc)].filter(Boolean).join(" · ")}
          </p>
          <p className="text-sm text-[#63635E]">{[job.ten_khach_hang, job.dien_thoai].filter(Boolean).join(" · ")}</p>
        </div>
        <div className="flex flex-col items-end gap-3 max-sm:w-full max-sm:items-stretch">
          <p className="text-right text-base font-extrabold">{job.gia_tri_ca > 0 ? formatMoney(job.gia_tri_ca) : "Tư vấn"}</p>
          {stage === "cho_xac_nhan" ? (
            <div className="flex gap-2">
              <button type="button" disabled={busy} onClick={() => onConfirm(job, 1)} className="inline-flex items-center gap-2 rounded-xl bg-[#0B0B0D] px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-60">
                <CheckCircle2 className="h-4 w-4" /> Xác nhận
              </button>
              <button type="button" disabled={busy} onClick={() => onConfirm(job, 3)} className="rounded-xl border border-[#F1CACA] px-4 py-2.5 text-sm font-semibold text-[#AC0021] disabled:opacity-60">
                Từ chối
              </button>
            </div>
          ) : null}
          {stage === "cho_nhan_xe" ? (
            <div className="flex flex-wrap items-center justify-end gap-2">
              {!isConsult ? (
                <select value={bayId} onChange={(event) => setBayId(Number(event.target.value))} aria-label="Chọn bay nhận xe" className="rounded-xl border border-[#DCDCD7] bg-white px-3 py-2.5 text-sm">
                  {freeBays.length === 0 ? <option value="">Không còn bay trống</option> : null}
                  {freeBays.map((bay) => <option key={bay.id} value={bay.id}>{bayShortName(bay.ten)}</option>)}
                </select>
              ) : null}
              <button type="button" disabled={busy || (!isConsult && !bayId)} onClick={() => run("bat-dau")} className="inline-flex items-center gap-2 rounded-xl bg-[#0B0B0D] px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-60">
                <PlayCircle className="h-4 w-4" /> Bắt đầu thi công
              </button>
            </div>
          ) : null}
          {stage === "dang_thi_cong" ? (
            <div className="flex flex-wrap justify-end gap-2">
              {!isConsult ? (
                <button type="button" disabled={busy} onClick={() => run("test-dyno")} className="inline-flex items-center gap-2 rounded-xl border border-[#DCDCD7] bg-white px-4 py-2.5 text-sm font-semibold disabled:opacity-60">
                  <Gauge className="h-4 w-4" /> Chuyển sang test Dyno
                </button>
              ) : null}
              <button type="button" disabled={busy} onClick={() => run("hoan-tat")} className="inline-flex items-center gap-2 rounded-xl bg-[#0B0B0D] px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-60">
                <CheckCircle2 className="h-4 w-4" /> Hoàn tất &amp; bàn giao
              </button>
            </div>
          ) : null}
          {stage === "dang_test_dyno" ? (
            <button type="button" disabled={busy} onClick={() => run("hoan-tat")} className="inline-flex items-center gap-2 rounded-xl bg-[#0B0B0D] px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-60">
              <CheckCircle2 className="h-4 w-4" /> Hoàn tất &amp; bàn giao
            </button>
          ) : null}
          {stage === "da_ban_giao" ? (
            <span className="inline-flex items-center gap-2 rounded-xl bg-[#F4F4F2] px-4 py-2.5 text-sm font-semibold text-[#4A4A46]">
              <CheckCircle2 className="h-4 w-4" /> Đã bàn giao
            </span>
          ) : null}
        </div>
      </div>

      {noteText ? (
        <p className="mt-4 flex items-start gap-2.5 rounded-xl bg-[#F4F4F2] px-4 py-3 text-sm text-[#4A4A46]">
          <StickyNote className="mt-0.5 h-4 w-4 shrink-0 text-[#8B8B86]" /> {noteText}
        </p>
      ) : null}
      {stage !== "da_ban_giao" && stage !== "cho_xac_nhan" ? (
        <label className="mt-4 flex items-center gap-2.5 rounded-xl bg-[#F4F4F2] px-4 py-2.5 text-sm text-[#4A4A46]">
          <StickyNote className="h-4 w-4 shrink-0 text-[#8B8B86]" />
          <input
            value={note}
            onChange={(event) => setNote(event.target.value)}
            maxLength={1000}
            placeholder={job.ghi_chu ? `Khách ghi chú: ${job.ghi_chu}` : "Ghi chú tiến độ cho ca này (sẽ lưu khi bạn cập nhật trạng thái)"}
            className="w-full bg-transparent outline-none placeholder:text-[#8B8B86]"
          />
        </label>
      ) : null}
      {stage === "cho_xac_nhan" && job.ghi_chu ? (
        <p className="mt-4 flex items-start gap-2.5 rounded-xl bg-[#F4F4F2] px-4 py-3 text-sm text-[#4A4A46]">
          <StickyNote className="mt-0.5 h-4 w-4 shrink-0 text-[#8B8B86]" /> Khách ghi chú: {job.ghi_chu}
        </p>
      ) : null}
    </article>
  );
}

export default function ExpertBoardView({ data, date, onDateChange, busyId, onProgress, onConfirm, onNavigate }) {
  const { chuyen_gia: expert, thong_ke: stats, lich, cho_xac_nhan: pending, bay } = data;
  const todayLabel = formatDate(date);
  const activeBays = bay.filter((item) => item.cong_viec);
  const upcomingPending = pending.filter((job) => !lich.some((item) => item.id === job.id));

  return (
    <div className="space-y-6">
      <div className="flex flex-wrap items-end justify-between gap-4">
        <div>
          <p className="text-xs uppercase tracking-wider text-[#63635E]">Khu chuyên gia / Bảng làm việc</p>
          <h1 className="mt-2 text-3xl font-extrabold tracking-tight sm:text-4xl">Ca làm việc · {todayLabel}</h1>
          <p className="mt-2 max-w-2xl text-sm text-[#63635E]">Xem lịch thi công được phân công, cập nhật trạng thái xe và kiểm tra Bay còn trống trước khi nhận ca mới.</p>
        </div>
        <div className="flex flex-wrap gap-2.5">
          <input type="date" value={date} onChange={(event) => event.target.value && onDateChange(event.target.value)} aria-label="Chọn ngày làm việc" className="rounded-xl border border-[#DCDCD7] bg-white px-3.5 py-3 text-sm font-semibold" />
          <button type="button" onClick={() => onNavigate("workshop")} className="inline-flex items-center gap-2 rounded-xl border border-[#DCDCD7] bg-white px-5 py-3 text-sm font-semibold">
            <Hammer className="h-4 w-4" /> Trạng thái Bay
          </button>
          <button type="button" onClick={() => onNavigate("customers")} className="inline-flex items-center gap-2 rounded-xl bg-[#AC0021] px-5 py-3 text-sm font-semibold text-white">
            <Users className="h-4 w-4" /> Khách phụ trách
          </button>
        </div>
      </div>

      <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <StatCard label="Ca trong ngày" value={stats.ca_hom_nay} hint={`${stats.da_hoan_tat} ca đã hoàn tất`} icon={CalendarCheck} tone="bg-[#0B0B0D] text-white" />
        <StatCard label="Đang thi công" value={stats.dang_thi_cong} hint="Theo dõi tiến độ từng Bay" icon={Hammer} tone="bg-[#FDE8E8] text-[#AC0021]" />
        <StatCard label="Chờ test Dyno" value={stats.cho_test_dyno} hint="Cần chốt công suất sau nạp map" icon={Gauge} tone="bg-[#FBF1DC] text-[#8A6414]" />
        <StatCard label="Giá trị ca làm" value={formatMillions(stats.gia_tri_ca)} hint="Tạm tính theo lịch hẹn" icon={Coins} tone="bg-[#EEEEEB] text-[#4A4A46]" />
      </div>

      <div className="grid gap-6 xl:grid-cols-[minmax(0,1fr)_380px]">
        <section className="space-y-4">
          <div className="flex items-center justify-between">
            <h2 className="text-xl font-extrabold tracking-tight">Lịch thi công được phân công</h2>
            <span className="rounded-full bg-[#EEEEEB] px-3 py-1 text-xs font-semibold text-[#4A4A46]">{lich.length} ca</span>
          </div>
          {lich.length === 0 ? (
            <div className="rounded-2xl border border-dashed border-[#DCDCD7] bg-white p-10 text-center text-sm text-[#63635E]">
              Chưa có ca nào được phân công cho ngày {todayLabel}.
            </div>
          ) : (
            lich.map((job) => (
              <JobCard key={`${job.id}-${job.giai_doan_hien_tai}`} job={job} bays={bay} busy={busyId === job.id} onProgress={onProgress} onConfirm={onConfirm} />
            ))
          )}

          {upcomingPending.length > 0 ? (
            <div className="space-y-3 pt-4">
              <div className="flex items-center justify-between">
                <h2 className="text-xl font-extrabold tracking-tight">Yêu cầu chờ xác nhận</h2>
                <span className="rounded-full bg-[#FBF1DC] px-3 py-1 text-xs font-semibold text-[#8A6414]">{upcomingPending.length} yêu cầu</span>
              </div>
              {upcomingPending.map((job) => (
                <div key={job.id} className="flex flex-wrap items-center gap-4 rounded-2xl border border-[#ECECE9] bg-white p-4">
                  <div className="w-[88px] shrink-0 rounded-xl bg-[#F4F4F2] py-2.5 text-center">
                    <p className="text-sm font-extrabold">{formatDate(job.ngay_dat).slice(0, 5)}</p>
                    <p className="text-[11px] text-[#8B8B86]">{job.gio_dat}</p>
                  </div>
                  <div className="min-w-0 flex-1">
                    <p className="font-bold">{job.tieu_de}</p>
                    <p className="flex flex-wrap items-center gap-x-3 text-sm text-[#63635E]">
                      <span className="inline-flex items-center gap-1"><UserRound className="h-3.5 w-3.5" /> {job.ten_khach_hang} · {job.dien_thoai}</span>
                      {job.ten_khu_vuc ? <span className="inline-flex items-center gap-1"><MapPin className="h-3.5 w-3.5" /> {bayShortName(job.ten_khu_vuc)}</span> : null}
                    </p>
                  </div>
                  <div className="flex gap-2">
                    <button type="button" disabled={busyId === job.id} onClick={() => onConfirm(job, 1)} className="rounded-xl bg-[#0B0B0D] px-4 py-2.5 text-sm font-semibold text-white disabled:opacity-60">Xác nhận</button>
                    <button type="button" disabled={busyId === job.id} onClick={() => onConfirm(job, 3)} className="rounded-xl border border-[#F1CACA] px-4 py-2.5 text-sm font-semibold text-[#AC0021] disabled:opacity-60">Từ chối</button>
                  </div>
                </div>
              ))}
            </div>
          ) : null}
        </section>

        <aside className="space-y-5">
          <section className="rounded-2xl border border-[#ECECE9] bg-white p-5">
            <div className="flex items-center justify-between">
              <h2 className="text-lg font-extrabold tracking-tight">Trạng thái Bay</h2>
              <button type="button" onClick={() => onNavigate("workshop")} className="text-xs font-semibold text-[#AC0021]">Xem tất cả</button>
            </div>
            <ul className="mt-4 space-y-2.5">
              {bay.map((item) => {
                const meta = STAGE_META[item.trang_thai];
                return (
                  <li key={item.id} className="flex items-center gap-3 rounded-xl bg-[#F4F4F2] px-4 py-3 text-sm">
                    <span className="font-mono font-bold">{bayShortName(item.ten)}</span>
                    <span className="min-w-0 flex-1 truncate text-xs text-[#63635E]">{item.cong_viec?.xe || meta.label}</span>
                    <span className="h-2 w-2 shrink-0 rounded-full" style={{ backgroundColor: meta.dot }} />
                  </li>
                );
              })}
            </ul>
          </section>

          <section className="rounded-2xl bg-[#0B0B0D] p-5 text-white">
            <div className="flex items-center gap-3">
              <span className="flex h-11 w-11 items-center justify-center rounded-xl bg-white/10"><Award className="h-5 w-5" /></span>
              <div>
                <p className="font-bold">{expert.ho_ten}</p>
                <p className="text-xs text-white/60">{expert.chuc_danh || "Chuyên gia Tuner"}</p>
              </div>
            </div>
            <dl className="mt-5 space-y-3 text-sm">
              <div className="flex justify-between"><dt className="text-white/60">Kinh nghiệm</dt><dd className="font-semibold">{expert.so_nam_kinh_nghiem ?? 0} năm kinh nghiệm</dd></div>
              <div className="flex justify-between"><dt className="text-white/60">Đánh giá</dt><dd className="font-semibold">{expert.so_danh_gia ? `${Number(expert.diem_danh_gia).toFixed(1)} / 5` : "Chưa có"}</dd></div>
              <div className="flex justify-between"><dt className="text-white/60">Ca đã hoàn tất</dt><dd className="font-semibold">{expert.so_ca_hoan_tat}</dd></div>
            </dl>
            {expert.chuyen_mon.length > 0 ? (
              <div className="mt-5 flex flex-wrap gap-2">
                {expert.chuyen_mon.map((item) => <span key={item} className="rounded-full border border-white/20 px-3 py-1 text-xs">{item}</span>)}
              </div>
            ) : null}
          </section>
        </aside>
      </div>

      <section className="space-y-4 pt-2">
        <div className="flex items-center justify-between">
          <h2 className="text-xl font-extrabold tracking-tight">Bay đang hoạt động</h2>
          <button type="button" onClick={() => onNavigate("workshop")} className="text-xs font-semibold text-[#AC0021]">Toàn bộ khu vực</button>
        </div>
        {activeBays.length === 0 ? (
          <p className="rounded-2xl border border-dashed border-[#DCDCD7] bg-white p-6 text-center text-sm text-[#63635E]">Hiện chưa có Bay nào đang thi công.</p>
        ) : (
          <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            {activeBays.map((item) => <BayCard key={item.id} bay={item} />)}
          </div>
        )}
      </section>
    </div>
  );
}

export function BayCard({ bay }) {
  const meta = STAGE_META[bay.trang_thai];
  const job = bay.cong_viec;
  const barColor = bay.trang_thai === "dang_test_dyno" ? "#B08B45" : "#AC0021";
  return (
    <article className="rounded-2xl border border-[#ECECE9] bg-white p-5">
      <div className="flex items-center justify-between">
        <h3 className="text-lg font-extrabold">{bayShortName(bay.ten)}</h3>
        <span className={`inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[11px] font-semibold ${meta.chip}`}>
          <span className="h-1.5 w-1.5 rounded-full" style={{ backgroundColor: meta.dot }} /> {meta.label}
        </span>
      </div>
      {job ? (
        <>
          <p className="mt-4 font-bold">{job.xe || "Xe khách"}</p>
          <p className="font-mono text-xs text-[#63635E]">{job.bien_so}</p>
          <p className="mt-2 text-sm text-[#4A4A46]">{job.tieu_de}</p>
          <div className="mt-4 h-1.5 overflow-hidden rounded-full bg-[#E8E8E5]">
            <div className="h-full rounded-full" style={{ width: `${job.tien_do}%`, backgroundColor: barColor }} />
          </div>
          <div className="mt-3 flex justify-between text-xs text-[#63635E]">
            <span>{job.chuyen_gia}</span>
            <span className="font-mono">{job.gio_bat_dau} → {job.gio_ket_thuc}</span>
          </div>
        </>
      ) : (
        <>
          <p className="mt-4 text-[#8B8B86]">—</p>
          <p className="mt-2 text-sm text-[#4A4A46]">{bay.ghi_chu || (bay.trang_thai === "bao_tri" ? "Đang bảo trì" : "Trống, sẵn sàng nhận xe")}</p>
          <p className="mt-4 flex items-center gap-2 text-xs text-[#63635E]">
            <span className="flex h-4 w-4 items-center justify-center rounded-full border border-current text-[10px]">i</span>
            {bay.trang_thai === "bao_tri" ? "Không nhận xe trong hôm nay" : "Có thể nhận xe mới ngay"}
          </p>
        </>
      )}
    </article>
  );
}
