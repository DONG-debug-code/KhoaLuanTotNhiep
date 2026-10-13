import { useEffect } from "react";
import { useLocation } from "react-router-dom";

export default function ScrollToTop() {
  const { pathname, hash } = useLocation();

  useEffect(() => {
    if (hash) {
      // Đợi DOM render xong (đặc biệt khi vừa chuyển trang từ nơi khác về)
      const id = hash.replace("#", "");
      const timeout = setTimeout(() => {
        const el = document.getElementById(id);
        if (el) {
          el.scrollIntoView({ behavior: "smooth" });
        }
      }, 100);
      return () => clearTimeout(timeout);
    } else {
      window.scrollTo(0, 0);
    }
  }, [pathname, hash]);

  return null;
}