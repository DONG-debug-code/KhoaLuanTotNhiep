import { useEffect, useMemo, useRef, useState } from "react";
import { Link, useLocation, useNavigate } from "react-router-dom";
import { AlertCircle, Check, Package, Search, ShoppingBag, ShoppingCart } from "lucide-react";
import Navbar from "../../components/layouts/partials/Navbar";
import Footer from "../../components/layouts/partials/Footer";
import { getGoiDoVaPhuTungClient } from "../../api/PublicApi";
import { useCart } from "../../context/CartContext";
import { useAuth } from "../../context/AuthContext";
import ProductDetailModal from "../../components/product/ProductDetailModal";

const formatMoney = (value) => `${Number(value).toLocaleString("vi-VN")} ₫`;

function toStoreItem(item, type) {
  return {
    ...item,
    type,
    soLuong: Number(item.soLuong) || 0,
    giaSo: Number(item.giaSo),
  };
}

function StoreProductCard({ product, onAdd, justAdded, onSelect }) {
  return (
    <article className="overflow-hidden rounded-2xl border border-[#e9e9e6] bg-white transition-shadow hover:shadow-lg">
      <button type="button" onClick={() => onSelect(product)} className="group block w-full text-left">
        <div className="relative flex aspect-[4/3] items-center justify-center overflow-hidden bg-[#e7e8e5]">
          {product.anh ? (
            <img
              src={product.anh}
              alt={product.ten}
              className="h-full w-full object-cover transition-transform duration-500 group-hover:scale-105"
              onError={(event) => { event.currentTarget.style.display = "none"; }}
            />
          ) : (
            <Package className="h-14 w-14 text-[#9da1a4]" strokeWidth={1} />
          )}
          <span className="absolute left-3 top-3 rounded-full bg-[#080a0d] px-3 py-1.5 text-xs font-semibold text-white">
            {product.type === "phu_tung" ? "Phụ tùng" : "Gói độ"}
          </span>
          <span className="absolute right-3 top-3 rounded-full bg-white/90 px-3 py-1.5 text-xs font-medium text-[#555a60]">
            Còn {product.soLuong}
          </span>
        </div>
        <div className="p-4">
          <p className="text-xs font-semibold uppercase tracking-wide text-[#85888d]">
            {product.hangMuc || "Phụ kiện Porsche"}
          </p>
          <h2 className="mt-2 line-clamp-2 min-h-12 font-bold text-[#17191c]">{product.ten}</h2>
          {product.moTaNgan ? (
            <p className="mt-2 line-clamp-2 min-h-10 text-sm leading-5 text-[#777a80]">{product.moTaNgan}</p>
          ) : null}
        </div>
      </button>
      <div className="flex items-center justify-between gap-3 border-t border-[#ecece9] px-4 py-4">
        <div>
          <span className="block text-xs text-[#85888d]">
            {product.type === "phu_tung" ? "Giá trọn gói (đã gồm công lắp đặt)" : "Giá bán"}
          </span>
          <strong className="text-lg font-extrabold text-[#b10e27]">{formatMoney(product.giaSo)}</strong>
        </div>
        <button
          type="button"
          onClick={() => onAdd(product)}
          disabled={product.soLuong < 1}
          className="inline-flex items-center gap-2 rounded-lg bg-[#080a0d] px-3.5 py-2.5 text-xs font-semibold text-white transition hover:bg-[#292b2f] disabled:cursor-not-allowed disabled:bg-[#a9aaab]"
        >
          {justAdded ? <Check className="h-4 w-4" /> : <ShoppingCart className="h-4 w-4" />}
          {product.soLuong < 1 ? "Hết hàng" : justAdded ? "Đã thêm" : "Thêm vào giỏ"}
        </button>
      </div>
    </article>
  );
}

export default function HangPhuTungPage() {
  const location = useLocation();
  const navigate = useNavigate();
  const handledPendingItem = useRef("");
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [search, setSearch] = useState("");
  const [category, setCategory] = useState("Tất cả");
  const [sort, setSort] = useState("popular");
  const [addedKey, setAddedKey] = useState("");
  const [selectedProduct, setSelectedProduct] = useState(null);
  const { addItem, itemCount, subtotal } = useCart();
  const { daDangNhap, vaiTro } = useAuth();
  const canShop = daDangNhap && vaiTro === "khach-hang";

  useEffect(() => {
    let active = true;
    getGoiDoVaPhuTungClient()
      .then((data) => {
        if (!active) return;
        const items = [
          ...data.goiDo.map((item) => toStoreItem(item, "goi_do")),
          ...data.phuTung.map((item) => toStoreItem(item, "phu_tung")),
        ].filter((item) => Number.isFinite(item.giaSo));
        setProducts(items);
        if (!items.length) {
          setError([data.loiGoiDo, data.loiPhuTung].filter(Boolean).join(" ") || "Showroom hiện chưa có sản phẩm.");
        }
      })
      .catch((requestError) => {
        if (active) setError(requestError.message || "Không thể tải danh sách sản phẩm.");
      })
      .finally(() => {
        if (active) setLoading(false);
      });
    return () => { active = false; };
  }, []);

  const categories = useMemo(
    () => ["Tất cả", ...new Set(products.map((item) => item.hangMuc).filter(Boolean))],
    [products]
  );

  const visibleProducts = useMemo(() => {
    const keyword = search.trim().toLocaleLowerCase();
    const matching = products.filter((item) => {
      const matchesCategory = category === "Tất cả" || item.hangMuc === category;
      const searchableText = `${item.ten} ${item.hangMuc} ${item.moTaNgan}`.toLocaleLowerCase();
      return matchesCategory && (!keyword || searchableText.includes(keyword));
    });

    if (sort === "price-asc") return [...matching].sort((a, b) => a.giaSo - b.giaSo);
    if (sort === "price-desc") return [...matching].sort((a, b) => b.giaSo - a.giaSo);
    return matching;
  }, [category, products, search, sort]);

  const addProduct = (product) => {
    if (!canShop) {
      if (!daDangNhap) {
        navigate("/dang-nhap", {
          state: {
            from: { pathname: location.pathname },
            pendingCartItem: { type: product.type, id: product.id, quantity: 1 },
          },
        });
        return;
      }
      setError("Vui lòng đăng nhập bằng tài khoản khách hàng để lưu giỏ hàng theo tài khoản.");
      return;
    }

    const added = addItem(product);
    if (!added) {
      setError("Sản phẩm hiện không thể thêm vào giỏ hàng.");
      return;
    }
    setError("");
    const key = `${product.type}:${product.id}`;
    setAddedKey(key);
    window.setTimeout(() => setAddedKey(""), 1200);
  };

  useEffect(() => {
    const pendingItem = location.state?.pendingCartItem;
    if (!pendingItem || loading || !canShop) return;

    const pendingKey = `${pendingItem.type}:${pendingItem.id}:${pendingItem.quantity}`;
    if (handledPendingItem.current === pendingKey) return;
    handledPendingItem.current = pendingKey;

    const product = products.find(
      (item) => item.type === pendingItem.type && String(item.id) === String(pendingItem.id)
    );
    if (product) {
      const added = addItem(product, pendingItem.quantity);
      if (added) {
        setError("");
        const key = `${product.type}:${product.id}`;
        setAddedKey(key);
        setSelectedProduct(product);
        window.setTimeout(() => setAddedKey(""), 1200);
      } else {
        setError("Không thể thêm sản phẩm vào giỏ hàng. Vui lòng kiểm tra tồn kho và thử lại.");
      }
    } else {
      setError("Không tìm thấy sản phẩm đã chọn. Vui lòng chọn lại sản phẩm.");
    }

    navigate(`${location.pathname}${location.search}${location.hash}`, {
      replace: true,
      state: null,
    });
  }, [addItem, canShop, loading, location, navigate, products]);

  return (
    <>
      <Navbar luonSang />
      <main className="min-h-screen bg-[#fafaf9] pt-[84px]">
        <section className="border-b border-[#e6e6e3] bg-[#f3f3f1] px-5 py-12 sm:px-8 lg:px-12">
          <div className="mx-auto flex max-w-[1440px] flex-wrap items-center justify-between gap-6">
            <div>
              <p className="mb-3 text-sm text-[#777a80]">Trang chủ　›　Gói độ &amp; phụ kiện</p>
              <h1 className="text-3xl font-extrabold tracking-tight sm:text-4xl">Gói độ &amp; phụ kiện chính hãng</h1>
              <p className="mt-3 max-w-2xl text-sm leading-6 text-[#63666b]">
                Sản phẩm, giá bán và số lượng tồn được tải trực tiếp từ hệ thống showroom.
              </p>
            </div>
            <Link to="/gio-hang" className="flex items-center gap-3 rounded-xl border border-[#e5e5e1] bg-white p-3.5">
              <span className="relative flex h-11 w-11 items-center justify-center rounded-lg bg-[#080a0d] text-white">
                <ShoppingBag className="h-5 w-5" />
                {itemCount > 0 ? <span className="absolute -right-2 -top-2 flex h-5 min-w-5 items-center justify-center rounded-full bg-[#c8102e] px-1 text-[10px] font-bold">{itemCount}</span> : null}
              </span>
              <span><strong className="block text-sm">Giỏ hàng của bạn</strong><small className="text-xs text-[#777a80]">{formatMoney(subtotal)} · {itemCount} món</small></span>
            </Link>
          </div>
        </section>

        <section className="mx-auto max-w-[1440px] px-5 py-8 sm:px-8 lg:px-12">
          {error ? <p role="alert" className="mb-5 flex items-center gap-2 rounded-xl bg-[#fae9eb] px-4 py-3 text-sm text-[#9d1026]"><AlertCircle className="h-4 w-4 shrink-0" />{error}</p> : null}
          <div className="flex flex-wrap items-center justify-between gap-4">
            <label className="flex min-w-[260px] flex-1 items-center gap-2 rounded-lg border border-[#dededb] bg-white px-3.5 py-3 sm:max-w-[520px]">
              <Search className="h-4 w-4 text-[#85888d]" />
              <input value={search} onChange={(event) => setSearch(event.target.value)} placeholder="Tìm theo tên sản phẩm, hãng hoặc dòng xe..." className="w-full bg-transparent text-sm outline-none" />
            </label>
            <label className="flex items-center gap-3 text-sm text-[#777a80]">
              Sắp xếp
              <select value={sort} onChange={(event) => setSort(event.target.value)} className="rounded-lg border border-[#dededb] bg-white px-3 py-2.5 text-sm text-[#17191c]">
                <option value="popular">Phổ biến</option>
                <option value="price-asc">Giá tăng dần</option>
                <option value="price-desc">Giá giảm dần</option>
              </select>
            </label>
          </div>

          <div className="my-6 flex gap-2 overflow-x-auto rounded-full border border-[#e9e9e6] bg-[#f3f3f1] p-1.5">
            {categories.map((item) => (
              <button key={item} type="button" onClick={() => setCategory(item)} className={`shrink-0 rounded-full px-4 py-2 text-sm font-medium ${category === item ? "bg-[#080a0d] text-white" : "text-[#555a60] hover:bg-white"}`}>
                {item}
              </button>
            ))}
          </div>
          <p className="mb-4 text-sm text-[#777a80]">Hiển thị {visibleProducts.length}/{products.length} sản phẩm</p>

          {loading ? <p className="py-12 text-center text-sm text-[#777a80]">Đang tải sản phẩm từ showroom...</p> : null}
          {!loading && visibleProducts.length ? (
            <div className="grid gap-5 sm:grid-cols-2 xl:grid-cols-4">
              {visibleProducts.map((product) => (
                <StoreProductCard
                  key={`${product.type}:${product.id}`}
                  product={product}
                  onAdd={addProduct}
                  onSelect={setSelectedProduct}
                  justAdded={addedKey === `${product.type}:${product.id}`}
                />
              ))}
            </div>
          ) : null}
          {!loading && !visibleProducts.length && !error ? <p className="rounded-xl border border-dashed border-[#d9d9d5] bg-white p-10 text-center text-sm text-[#777a80]">Không tìm thấy sản phẩm phù hợp.</p> : null}
        </section>
      </main>
      <Footer />
      {selectedProduct ? (
        <ProductDetailModal product={selectedProduct} onClose={() => setSelectedProduct(null)} />
      ) : null}
    </>
  );
}
