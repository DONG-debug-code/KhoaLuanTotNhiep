import { Package } from "lucide-react";

export function GoiDoItem({ goiDo, onSelect }) {
  return goiDo.map((goi) => (
    <button
      type="button"
      key={goi.id ?? goi.slug}
      onClick={() => onSelect?.({ ...goi, type: "goi_do" })}
      className="group overflow-hidden rounded-2xl border border-[#ECECE9] bg-white text-left shadow-sm transition-shadow hover:shadow-lg"
    >
      <div className="flex aspect-[16/9] items-center justify-center overflow-hidden bg-[#F1F1EF]">
        {goi.anh ? (
          <img
            src={goi.anh}
            alt={goi.ten}
            className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
            onError={(event) => { event.currentTarget.style.display = "none"; }}
          />
        ) : (
          <Package className="h-12 w-12 text-[#C9C8C3]" aria-hidden="true" />
        )}
      </div>
      <div className="p-5">
        <div className="mb-2 text-xs font-semibold uppercase tracking-wide text-[#8A8A85]">
          {goi.hangMuc || "Gói độ Porsche"}
        </div>
        <h3 className="mb-2 text-lg font-bold text-[#17171A]">{goi.ten}</h3>
        {goi.moTaNgan ? (
          <p className="mb-4 line-clamp-3 text-sm leading-[1.6] text-[#63635E]">{goi.moTaNgan}</p>
        ) : null}
        <div className="flex items-center justify-between border-t border-[#ECECE9] pt-4">
          <span className="text-base font-extrabold text-[#B60F0F]">{goi.gia || "Liên hệ"}</span>
          <span className="text-sm font-semibold text-[#17171A]">Xem chi tiết →</span>
        </div>
      </div>
    </button>
  ));
}
