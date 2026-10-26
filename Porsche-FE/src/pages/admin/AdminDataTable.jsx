import { useMemo, useState } from "react";
import { ChevronLeft, ChevronRight } from "lucide-react";

export function Chip({ children, tone = "gold" }) {
  const tones = {
    gold: "bg-[#FBF1DC] text-[#8A6414] border-[#F0DDB0]",
    red: "bg-[#FDE8E8] text-[#B60F0F] border-[#F6C9C9]",
    dark: "bg-[#17171A] text-white border-[#17171A]",
    gray: "bg-[#F1F1EF] text-[#63635E] border-[#E4E4E0]",
    green: "bg-[#E4F5EA] text-[#1F7A3D] border-[#BFE5CD]",
  };
  return <span className={`inline-flex items-center gap-1.5 whitespace-nowrap rounded-full border px-2.5 py-1 text-xs font-semibold ${tones[tone]}`}>{children}</span>;
}

export function StatCards({ items }) {
  return (
    <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
      {items.map((item) => (
        <div key={item.label} className="rounded-2xl border border-[#ECECE9] bg-white p-5">
          <div className="flex items-center justify-between"><p className="text-sm text-[#63635E]">{item.label}</p><span className="h-2 w-2 rounded-full" style={{ background: item.dot || "#17171A" }} /></div>
          <p className="mt-3 text-3xl font-extrabold tracking-tight">{item.value}</p>
        </div>
      ))}
    </div>
  );
}

export function ActionButton({ children, onClick, tone = "default", disabled }) {
  const tones = {
    default: "border border-[#E4E4E0] text-[#17171A] hover:bg-[#F1F1EF]",
    primary: "bg-[#17171A] text-white hover:bg-black",
    danger: "border border-[#F1CACA] text-[#B60F0F] hover:bg-[#FBE9E9]",
  };
  return <button type="button" disabled={disabled} onClick={onClick} className={`rounded-lg px-3 py-1.5 text-xs font-semibold disabled:opacity-50 ${tones[tone]}`}>{children}</button>;
}

export default function AdminDataTable({ columns, rows, search = "", tabs, searchText, pageSize = 8, emptyMessage = "Không có dữ liệu.", actions, toolbar }) {
  const [tab, setTab] = useState(tabs?.[0]?.key ?? "all");
  const [page, setPage] = useState(1);

  const filtered = useMemo(() => {
    const activeTab = tabs?.find((item) => item.key === tab);
    const keyword = search.trim().toLowerCase();
    return rows.filter((row) => (!activeTab?.test || activeTab.test(row))
      && (!keyword || String(searchText ? searchText(row) : JSON.stringify(row)).toLowerCase().includes(keyword)));
  }, [rows, tab, tabs, search, searchText]);

  const pages = Math.max(1, Math.ceil(filtered.length / pageSize));
  const current = Math.min(page, pages);
  const visible = filtered.slice((current - 1) * pageSize, current * pageSize);

  return (
    <section className="rounded-2xl border border-[#ECECE9] bg-white">
      {tabs || toolbar ? (
        <div className="flex flex-wrap items-center justify-between gap-3 border-b border-[#ECECE9] p-4">
          {tabs ? (
            <div className="flex flex-wrap gap-1 rounded-full bg-[#F6F5F2] p-1">
              {tabs.map((item) => (
                <button key={item.key} type="button" onClick={() => { setTab(item.key); setPage(1); }} className={`rounded-full px-4 py-2 text-xs font-semibold ${tab === item.key ? "bg-[#0B0B0D] text-white" : "text-[#3D3D39]"}`}>{item.label}</button>
              ))}
            </div>
          ) : <span />}
          {toolbar}
        </div>
      ) : null}

      <div className="overflow-x-auto">
        <table className="w-full text-left text-sm">
          <thead className="bg-[#FAFAF9] text-[11px] uppercase tracking-wide text-[#63635E]">
            <tr>
              {columns.map((column) => <th key={column.label} className={`px-4 py-3.5 font-semibold ${column.align === "right" ? "text-right" : ""}`}>{column.label}</th>)}
              {actions ? <th className="px-4 py-3.5 text-right font-semibold">Thao tác</th> : null}
            </tr>
          </thead>
          <tbody>
            {visible.map((row, index) => (
              <tr key={row.id ?? index} className="border-t border-[#ECECE9] align-middle hover:bg-[#FAFAF9]">
                {columns.map((column) => <td key={column.label} className={`px-4 py-4 ${column.align === "right" ? "text-right font-semibold" : ""}`}>{column.render ? column.render(row) : (row[column.key] ?? "—")}</td>)}
                {actions ? <td className="px-4 py-4"><div className="flex flex-wrap justify-end gap-2">{actions(row)}</div></td> : null}
              </tr>
            ))}
            {!visible.length ? <tr><td colSpan={columns.length + (actions ? 1 : 0)} className="px-4 py-12 text-center text-sm text-[#63635E]">{emptyMessage}</td></tr> : null}
          </tbody>
        </table>
      </div>

      <div className="flex flex-wrap items-center justify-between gap-3 border-t border-[#ECECE9] p-4 text-sm text-[#63635E]">
        <span>Hiển thị <b className="text-[#17171A]">{visible.length}</b> trên {filtered.length} bản ghi</span>
        <div className="flex items-center gap-1">
          <button type="button" disabled={current <= 1} onClick={() => setPage(current - 1)} aria-label="Trang trước" className="rounded-lg border border-[#E4E4E0] p-2 disabled:opacity-40"><ChevronLeft className="h-4 w-4" /></button>
          {Array.from({ length: pages }, (_, i) => i + 1).slice(0, 7).map((n) => (
            <button key={n} type="button" onClick={() => setPage(n)} className={`h-9 w-9 rounded-lg text-xs font-semibold ${n === current ? "bg-[#0B0B0D] text-white" : "border border-[#E4E4E0]"}`}>{n}</button>
          ))}
          <button type="button" disabled={current >= pages} onClick={() => setPage(current + 1)} aria-label="Trang sau" className="rounded-lg border border-[#E4E4E0] p-2 disabled:opacity-40"><ChevronRight className="h-4 w-4" /></button>
        </div>
      </div>
    </section>
  );
}
