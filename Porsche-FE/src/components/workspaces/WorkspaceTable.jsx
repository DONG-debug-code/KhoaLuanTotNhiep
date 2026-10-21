function hienThiGiaTri(value) {
  if (value === null || value === undefined || value === "") return "—";
  if (typeof value === "boolean") return value ? "Có" : "Không";
  if (typeof value === "object") return JSON.stringify(value);
  return String(value);
}

export default function WorkspaceTable({ rows, emptyMessage = "Chưa có dữ liệu.", columns }) {
  if (!rows.length) {
    return <p className="rounded-xl bg-[#F1F1EF] px-4 py-5 text-sm text-[#63635E]">{emptyMessage}</p>;
  }

  const visibleColumns = columns ?? Object.keys(rows[0])
    .filter((key) => !/password|token|secret/i.test(key))
    .slice(0, 7)
    .map((key) => ({ key, label: key.replaceAll("_", " ") }));

  return (
    <div className="overflow-x-auto rounded-xl border border-[#ECECE9]">
      <table className="w-full min-w-[540px] border-collapse text-left text-sm">
        <thead className="bg-[#F1F1EF] text-xs uppercase tracking-wide text-[#63635E]">
          <tr>
            {visibleColumns.map((column) => (
              <th key={column.key} className="px-4 py-3 font-bold">{column.label}</th>
            ))}
            {rows.some((row) => row.__actions) ? <th className="px-4 py-3 text-right">Thao tác</th> : null}
          </tr>
        </thead>
        <tbody>
          {rows.map((row, index) => (
            <tr key={row.id ?? row.ma_dat_lich ?? index} className="border-t border-[#ECECE9] align-top">
              {visibleColumns.map((column) => (
                <td key={column.key} className="max-w-[320px] px-4 py-3 text-[#4A4A46]">
                  {column.render ? column.render(row[column.key], row) : hienThiGiaTri(row[column.key])}
                </td>
              ))}
              {row.__actions ? <td className="px-4 py-3 text-right">{row.__actions}</td> : null}
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
