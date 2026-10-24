import { useEffect, useMemo, useRef, useState } from "react";
import { useLocation, useNavigate } from "react-router-dom";
import {
  AlertCircle,
  ArrowRight,
  Check,
  Minus,
  Package,
  Plus,
  ShieldCheck,
  ShoppingBag,
  Star,
  X,
} from "lucide-react";
import { getGoiDoClientById, getPhuTungClientById } from "../../api/PublicApi";
import { useAuth } from "../../context/AuthContext";
import { useCart } from "../../context/CartContext";

const formatMoney = (value) => `${Number(value).toLocaleString("vi-VN")} ₫`;

function getValue(record, keys) {
  for (const key of keys) {
    const value = record?.[key];
    if (value !== undefined && value !== null && value !== "") return value;
  }
  return null;
}

function toLines(value) {
  if (value === null || value === undefined || value === "") return [];
  if (typeof value === "string") {
    try {
      const parsed = JSON.parse(value);
      if (parsed !== value) return toLines(parsed);
    } catch {
      return value.split(/\r?\n/).map((line) => line.trim()).filter(Boolean);
    }
    return [value];
  }
  if (Array.isArray(value)) {
    return value.flatMap((item) => {
      if (typeof item === "object" && item !== null) {
        const name = getValue(item, ["ten", "name", "label"]);
        const detail = getValue(item, ["gia_tri", "giaTri", "value", "mo_ta", "description"]);
        return [name && detail ? `${name}: ${detail}` : name || detail || JSON.stringify(item)];
      }
      return [String(item)];
    }).filter(Boolean);
  }
  if (typeof value === "object") {
    return Object.entries(value).map(([name, detail]) => (
      typeof detail === "object" && detail !== null
        ? `${name.replaceAll("_", " ")}: ${JSON.stringify(detail)}`
        : `${name.replaceAll("_", " ")}: ${detail}`
    ));
  }
  return [String(value)];
}

function DetailList({ title, value }) {
  const lines = toLines(value);
  if (!lines.length) return null;

  return (
    <section>
      <h3 className="mb-2 text-sm font-bold text-[#17171A]">{title}</h3>
      <ul className="space-y-2 text-sm leading-5 text-[#63635E]">
        {lines.map((line, index) => (
          <li key={`${line}-${index}`} className="flex gap-2">
            <Check className="mt-0.5 h-4 w-4 shrink-0 text-[#a78343]" aria-hidden="true" />
            <span>{line}</span>
          </li>
        ))}
      </ul>
    </section>
  );
}

export default function ProductDetailModal({ product, onClose }) {
  const navigate = useNavigate();
  const location = useLocation();
  const { addItem } = useCart();
  const { daDangNhap, vaiTro } = useAuth();
  const canShop = daDangNhap && vaiTro === "khach-hang";
  const [detail, setDetail] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [quantity, setQuantity] = useState(1);
  const [notice, setNotice] = useState("");
  const [retryKey, setRetryKey] = useState(0);
  const closeButtonRef = useRef(null);

  useEffect(() => {
    let active = true;
    setLoading(true);
    setError("");
    setDetail(null);
    setQuantity(1);
    setNotice("");

    const loadDetail = product.type === "phu_tung"
      ? getPhuTungClientById(product.id)
      : getGoiDoClientById(product.slug ?? product.id);

    loadDetail
      .then((result) => {
        if (!active) return;
        if (!result) {
          setError("Không tìm thấy thông tin sản phẩm.");
          return;
        }
        setDetail({ ...product, ...result, type: product.type });
      })
      .catch((requestError) => {
        if (active) {
          setError(requestError.response?.data?.message || requestError.message || "Không tải được thông tin sản phẩm.");
        }
      })
      .finally(() => {
        if (active) setLoading(false);
      });

    return () => { active = false; };
  }, [product, retryKey]);

  useEffect(() => {
    const previousOverflow = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    closeButtonRef.current?.focus();
    const handleKeyDown = (event) => {
      if (event.key === "Escape") onClose();
    };
    window.addEventListener("keydown", handleKeyDown);
    return () => {
      document.body.style.overflow = previousOverflow;
      window.removeEventListener("keydown", handleKeyDown);
    };
  }, [onClose]);

  const stock = Math.max(0, Number(detail?.soLuong) || 0);
  const price = Number(detail?.giaSo);
  const included = getValue(detail, ["baoGom", "bao_gom", "goi_bao_gom", "thanh_phan"]);
  const compatibleModels = getValue(detail, ["dongXeTuongThich", "dong_xe_tuong_thich", "dong_xe", "mau_xe_tuong_thich"]);
  const specifications = getValue(detail, ["thongSoChiTiet", "thong_so_chi_tiet", "thong_so", "specifications"]);
  const warranty = getValue(detail, ["baoHanh", "bao_hanh", "thoi_gian_bao_hanh", "warranty"]);
  const rating = getValue(detail, ["danhGia", "danh_gia", "rating", "diem_danh_gia"]);
  const sold = getValue(detail, ["daBan", "da_ban", "luot_ban", "so_luong_da_ban"]);
  const description = getValue(detail, ["chiTiet", "chi_tiet", "mo_ta_chi_tiet", "moTaNgan", "mo_ta", "description"]);
  const isInStock = stock > 0;
  const formattedRating = useMemo(() => (
    typeof rating === "number" ? rating.toLocaleString("vi-VN") : rating
  ), [rating]);

  const addToCart = (buyNow = false) => {
    if (!canShop) {
      if (!daDangNhap && !buyNow) {
        onClose();
        navigate("/dang-nhap", {
          state: {
            from: { pathname: location.pathname },
            pendingCartItem: { type: product.type, id: product.id, quantity },
          },
        });
        return;
      }
      setNotice("Vui lòng đăng nhập bằng tài khoản khách hàng để mua sản phẩm.");
      return;
    }

    const added = addItem({
      ...detail,
      type: product.type,
      giaSo: price,
      soLuong: stock,
    }, quantity);
    if (!added) {
      setNotice("Sản phẩm hiện không thể thêm vào giỏ hàng.");
      return;
    }

    if (buyNow) {
      onClose();
      navigate("/thanh-toan");
      return;
    }
    setNotice("Đã thêm sản phẩm vào giỏ hàng.");
  };

  return (
    <div
      className="fixed inset-0 z-[70] flex items-center justify-center bg-black/60 p-4 backdrop-blur-sm sm:p-6"
      onMouseDown={(event) => {
        if (event.target === event.currentTarget) onClose();
      }}
    >
      <section
        role="dialog"
        aria-modal="true"
        aria-labelledby="product-detail-title"
        className="flex max-h-[92vh] w-full max-w-[925px] flex-col overflow-hidden rounded-3xl bg-[#FAFAF9] shadow-2xl"
      >
        <header className="flex shrink-0 items-start justify-between gap-4 border-b border-[#ECECE9] px-6 py-4 sm:px-7">
          <div className="min-w-0">
            <p className="mb-1 text-[11px] font-semibold uppercase tracking-[0.12em] text-[#B60F0F]">
              Porsche Equipment · {product.type === "phu_tung" ? "Phụ tùng" : "Gói độ"}
            </p>
            <h2 id="product-detail-title" className="text-xl font-extrabold text-[#17171A] sm:text-2xl">
              {detail?.ten || product.ten}
            </h2>
          </div>
          <button
            ref={closeButtonRef}
            type="button"
            onClick={onClose}
            aria-label="Đóng thông tin chi tiết"
            className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl border border-[#ECECE9] bg-white text-[#555a60] hover:bg-[#f3f3f1]"
          >
            <X className="h-5 w-5" />
          </button>
        </header>

        {loading ? (
          <div className="flex min-h-72 items-center justify-center px-6 text-sm text-[#63635E]">
            Đang tải đầy đủ thông tin từ showroom...
          </div>
        ) : error ? (
          <div className="flex min-h-72 flex-col items-center justify-center gap-4 px-6 text-center">
            <p role="alert" className="flex items-center gap-2 text-sm text-red-700">
              <AlertCircle className="h-4 w-4 shrink-0" />{error}
            </p>
            <button
              type="button"
              onClick={() => setRetryKey((current) => current + 1)}
              className="rounded-lg border border-[#dededb] bg-white px-4 py-2 text-sm font-semibold text-[#17171A]"
            >
              Thử tải lại
            </button>
          </div>
        ) : detail ? (
          <div className="grid min-h-0 gap-6 overflow-y-auto p-5 sm:p-6 lg:grid-cols-[1.08fr_1fr] lg:gap-6">
            <div className="space-y-3">
              <div className="flex aspect-[4/3] items-center justify-center overflow-hidden rounded-2xl bg-[#E7E8E5]">
                {detail.anh ? (
                  <img src={detail.anh} alt={detail.ten} className="h-full w-full object-cover" />
                ) : (
                  <Package className="h-16 w-16 text-[#9da1a4]" strokeWidth={1} aria-hidden="true" />
                )}
              </div>
              <div className="grid grid-cols-3 gap-2">
                <div className="flex min-h-16 flex-col items-center justify-center rounded-xl border border-[#ECECE9] bg-[#F3F3F1] px-2 text-center">
                  <Star className="mb-1 h-4 w-4 text-[#a78343]" aria-hidden="true" />
                  <span className="text-xs text-[#555a60]">{rating ? `${formattedRating} đánh giá` : "Chính hãng Porsche"}</span>
                </div>
                <div className="flex min-h-16 flex-col items-center justify-center rounded-xl border border-[#ECECE9] bg-[#F3F3F1] px-2 text-center">
                  <ShoppingBag className="mb-1 h-4 w-4 text-[#a78343]" aria-hidden="true" />
                  <span className="text-xs text-[#555a60]">{sold ? `${sold} đã bán` : `Còn ${stock} sản phẩm`}</span>
                </div>
                <div className="flex min-h-16 flex-col items-center justify-center rounded-xl border border-[#ECECE9] bg-[#F3F3F1] px-2 text-center">
                  <ShieldCheck className="mb-1 h-4 w-4 text-[#a78343]" aria-hidden="true" />
                  <span className="text-xs text-[#555a60]">{warranty || "Bảo hành chính hãng"}</span>
                </div>
              </div>
            </div>

            <div className="flex flex-col">
              <p className="mb-4 text-sm leading-6 text-[#63635E]">
                {description || "Chưa có mô tả chi tiết cho sản phẩm này."}
              </p>
              <div className="mb-4 rounded-xl border border-[#ECECE9] bg-[#F3F3F1] p-4">
                <span className="block text-xs text-[#777a80]">
                  {product.type === "phu_tung" ? "Giá trọn gói (đã gồm công lắp đặt)" : "Giá bán"}
                </span>
                <strong className="mt-1 block text-2xl font-extrabold text-[#B60F0F]">
                  {Number.isFinite(price) ? formatMoney(price) : detail.gia || "Liên hệ"}
                </strong>
                <span className={`mt-1 block text-xs ${isInStock ? "text-[#63635E]" : "text-[#B60F0F]"}`}>
                  {isInStock ? `Còn ${stock} sản phẩm trong kho` : "Tạm hết hàng"}
                  {warranty ? ` · Bảo hành ${warranty}` : ""}
                </span>
              </div>

              <div className="space-y-4">
                <DetailList title="Gói bao gồm" value={included} />
                <DetailList title="Thông số chi tiết" value={specifications} />
                <DetailList title="Tương thích dòng xe" value={compatibleModels} />
              </div>

              <div className="mt-auto pt-5">
                {notice ? <p role="status" className="mb-3 text-xs text-[#63635E]">{notice}</p> : null}
                <div className="mb-3 flex items-center justify-between border-t border-[#ECECE9] pt-4">
                  <span className="text-sm text-[#63635E]">Số lượng</span>
                  <div className="flex items-center rounded-lg border border-[#dededb] bg-white">
                    <button
                      type="button"
                      aria-label="Giảm số lượng"
                      disabled={quantity <= 1}
                      onClick={() => setQuantity((current) => Math.max(1, current - 1))}
                      className="p-2.5 text-[#777a80] disabled:opacity-40"
                    >
                      <Minus className="h-4 w-4" />
                    </button>
                    <span className="min-w-10 text-center text-sm font-semibold">{quantity}</span>
                    <button
                      type="button"
                      aria-label="Tăng số lượng"
                      disabled={quantity >= stock}
                      onClick={() => setQuantity((current) => Math.min(stock, current + 1))}
                      className="p-2.5 text-[#777a80] disabled:opacity-40"
                    >
                      <Plus className="h-4 w-4" />
                    </button>
                  </div>
                </div>
                <div className="grid grid-cols-1 gap-2 sm:grid-cols-2">
                  <button
                    type="button"
                    onClick={() => addToCart()}
                    disabled={!isInStock}
                    className="inline-flex items-center justify-center gap-2 rounded-lg border border-[#c6c7c8] bg-white px-4 py-3 text-sm font-semibold text-[#17171A] hover:bg-[#f3f3f1] disabled:cursor-not-allowed disabled:opacity-50"
                  >
                    <ShoppingBag className="h-4 w-4" /> Thêm vào giỏ
                  </button>
                  <button
                    type="button"
                    onClick={() => addToCart(true)}
                    disabled={!isInStock}
                    className="inline-flex items-center justify-center gap-2 rounded-lg bg-[#C8102E] px-4 py-3 text-sm font-bold text-white hover:bg-[#B10E27] disabled:cursor-not-allowed disabled:opacity-50"
                  >
                    Mua ngay <ArrowRight className="h-4 w-4" />
                  </button>
                </div>
                {!canShop ? (
                  <p className="mt-2 text-center text-xs text-[#777a80]">Đăng nhập bằng tài khoản khách hàng để đặt mua.</p>
                ) : null}
              </div>
            </div>
          </div>
        ) : null}
      </section>
    </div>
  );
}
