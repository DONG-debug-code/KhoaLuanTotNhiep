import { useState } from "react";
import { Package } from "lucide-react";

function PhuTungCard({ item, onSelect }) {
  const [imageError, setImageError] = useState(false);

  return (
    <button
      type="button"
      key={item.id}
      onClick={() => onSelect?.({ ...item, type: "phu_tung" })}
      className="group overflow-hidden rounded-2xl border border-[#ECECE9] bg-white text-left shadow-sm transition-shadow hover:shadow-lg"
    >
      <div className="flex aspect-[16/9] items-center justify-center overflow-hidden bg-[#F1F1EF]">
        {item.anh && !imageError ? (
          <img
            src={item.anh}
            alt={item.ten}
            className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
            onError={() => setImageError(true)}
          />
        ) : (
          <div className="flex flex-col items-center gap-2 text-[#A7A69F]">
            <Package className="h-12 w-12" aria-hidden="true" />
            <span className="text-xs">Chưa có hình ảnh</span>
          </div>
        )}
      </div>
      <div className="p-5">
        <div className="mb-2 text-xs font-semibold uppercase tracking-wide text-[#8A8A85]">
          {item.hangMuc || "Phụ tùng Porsche"}
        </div>
        <h3 className="mb-2 text-lg font-bold text-[#17171A]">{item.ten}</h3>
        {item.moTaNgan ? (
          <p className="mb-4 line-clamp-3 text-sm leading-[1.6] text-[#63635E]">{item.moTaNgan}</p>
        ) : null}
        <div className="flex items-center justify-between border-t border-[#ECECE9] pt-4">
          <span className="text-base font-extrabold text-[#B60F0F]">{item.gia || "Liên hệ"}</span>
          <span className={`text-xs font-semibold ${item.soLuong > 0 ? "text-[#2FA84F]" : "text-[#8A8A85]"}`}>
            {item.soLuong > 0 ? `Còn ${item.soLuong}` : "Tạm hết hàng"}
          </span>
        </div>
      </div>
    </button>
  );
}

export function PhuTungItem({ phuTung, onSelect }) {
  return phuTung.map((item) => <PhuTungCard key={item.id} item={item} onSelect={onSelect} />);
}
