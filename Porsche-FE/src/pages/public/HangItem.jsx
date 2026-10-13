import { Star } from 'lucide-react'
import React from 'react'
import { Link } from 'react-router-dom'

export const HangItem = ({ HANG_DO }) => {
  return (
    <>
      {HANG_DO.map((hang) => (
        <div
          key={hang.id ?? hang.slug}
          className="flex flex-col rounded-2xl border border-[#ECECE9] bg-white p-6"
        >
          <div className="mb-4 flex items-start justify-between">
            <div className="flex items-center gap-3">
              <span className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-[#17171A] text-sm font-bold text-white">
                {(hang.ten || "?").charAt(0)}
              </span>
              <div>
                <div className="font-bold">{hang.ten}</div>
                <div className="text-xs text-[#8A8A85]">{hang.quocGia}{hang.quocGia && hang.tuNam ? " · " : ""}{hang.tuNam ? `từ ${hang.tuNam}` : ""}</div>
              </div>
            </div>

            <span className="flex items-center gap-1 rounded-full bg-[#FBF3E4] px-2.5 py-1 text-xs font-semibold text-[#8A6D1F]">
              <Star className="h-3 w-3 fill-[#8A6D1F]" />
              {hang.rating}
            </span>
          </div>

          <div className="mb-1.5 text-sm font-bold">{hang.hangMucChinh}</div>
          <p className="mb-4 flex-1 text-sm leading-[1.6] text-[#63635E]">{hang.moTaNgan}</p>

          <div className="mb-5 flex flex-wrap gap-1.5">
            {(Array.isArray(hang.tags) ? hang.tags : []).map((tag) => (
              <span key={tag} className="rounded-full bg-[#F1F1EF] px-2.5 py-1 text-xs font-medium text-[#4A4A46]">
                {tag}
              </span>
            ))}
          </div>

          <div className="flex items-center justify-between border-t border-[#ECECE9] pt-4">
            <span className="text-sm text-[#8A8A85]">{hang.goiDo.length} gói độ sẵn có</span>
            <Link
              to={`/hang-phu-tung/${hang.id ?? hang.slug}`}
              className="text-sm font-semibold text-[#c71430] hover:underline"
            >
              Xem gói độ →
            </Link>
          </div>
        </div>
      ))}</>
  )
}
