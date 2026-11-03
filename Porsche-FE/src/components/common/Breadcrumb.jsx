import { Fragment } from "react";
import { Link } from "react-router-dom";

// items: [{ label, to? }] - mục cuối là trang hiện tại (không cần `to`)
export default function Breadcrumb({ items }) {
  return (
    <nav
      aria-label="Breadcrumb"
      className="border-b border-[#ECECE9] bg-[#F1F1EF] px-[1.3rem] py-2 text-[13px] text-[#63635E]"
    >
      {items.map((item, index) => (
        <Fragment key={`${item.label}-${index}`}>
          {index > 0 ? <span className="mx-2">›</span> : null}
          {item.to ? (
            <Link to={item.to} className="hover:text-[#17171A]">{item.label}</Link>
          ) : (
            <span className="text-[#17171A]">{item.label}</span>
          )}
        </Fragment>
      ))}
    </nav>
  );
}
