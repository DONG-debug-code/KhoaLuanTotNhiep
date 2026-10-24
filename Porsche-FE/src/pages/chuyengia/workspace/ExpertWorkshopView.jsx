import { BayCard } from "./ExpertBoardView";
import { STAGE_META, bayShortName } from "./expertUi";

export default function ExpertWorkshopView({ data }) {
  const { bay, dang_co_xe: busy, bay_trong: free, dang_bao_tri: maintenance, xe_cho_nhan_ca: waiting } = data;

  return (
    <div className="space-y-6">
      <div>
        <p className="text-xs uppercase tracking-wider text-[#63635E]">Khu chuyên gia / Khu vực workshop</p>
        <h1 className="mt-2 text-3xl font-extrabold tracking-tight sm:text-4xl">Sơ đồ Bay · Workshop</h1>
        <p className="mt-2 text-sm text-[#63635E]">Kiểm tra Bay nào còn trống hoặc đang thi công để sắp xếp ca độ xe, tránh trùng khung giờ Dyno.</p>
      </div>

      <div className="grid gap-4 md:grid-cols-3">
        {[["Đang có xe", busy, "text-[#17171A]"], ["Bay trống", free, "text-[#17171A]"], ["Đang bảo trì", maintenance, "text-[#8B8B86]"]].map(([label, value, tone]) => (
          <div key={label} className="rounded-2xl border border-[#ECECE9] bg-white p-5">
            <p className="text-sm text-[#63635E]">{label}</p>
            <p className={`mt-3 text-3xl font-extrabold ${tone}`}>{value}</p>
          </div>
        ))}
      </div>

      <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
        {bay.map((item) => <BayCard key={item.id} bay={item} />)}
      </div>

      <section className="rounded-2xl border border-[#ECECE9] bg-white">
        <h2 className="px-5 py-4 text-lg font-extrabold tracking-tight">Xe chờ nhận ca ({waiting.length})</h2>
        {waiting.length === 0 ? (
          <p className="border-t border-[#ECECE9] px-5 py-6 text-center text-sm text-[#63635E]">Không có xe nào đang chờ nhận ca hôm nay.</p>
        ) : (
          <ul>
            {waiting.map((job) => (
              <li key={job.id} className="flex flex-wrap items-center justify-between gap-3 border-t border-[#ECECE9] px-5 py-4">
                <div className="min-w-0">
                  <p className="font-bold">{job.ten_xe} <span className="ml-1 font-mono text-xs text-[#63635E]">{job.bien_so_xe}</span></p>
                  <p className="text-sm text-[#63635E]">{job.tieu_de} · {job.ten_khach_hang}</p>
                </div>
                <div className="flex items-center gap-3">
                  <span className="font-mono text-sm">{job.gio_dat} · {job.loai_lich === "tu-van" ? "Khu tư vấn" : bayShortName(job.ten_khu_vuc) || "Chưa gán Bay"}</span>
                  <span className={`rounded-full border px-2.5 py-1 text-[11px] font-semibold ${STAGE_META.cho_nhan_xe.chip}`}>Chờ nhận xe</span>
                </div>
              </li>
            ))}
          </ul>
        )}
      </section>
    </div>
  );
}
