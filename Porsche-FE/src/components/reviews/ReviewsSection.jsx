import { useCallback, useEffect, useState } from "react";
import { Link } from "react-router-dom";
import { Star } from "lucide-react";
import { useAuth } from "../../context/AuthContext";
import { apiClient, getApiErrorMessage } from "../../api/Authapi";

const dateText = (value) => (value ? new Date(String(value).replace(" ", "T") + "Z").toLocaleDateString("vi-VN", { timeZone: "Asia/Ho_Chi_Minh" }) : "");

function Stars({ value, size = "h-4 w-4" }) {
  return (
    <span className="inline-flex" aria-label={`${value} trên 5 sao`}>
      {[1, 2, 3, 4, 5].map((n) => (
        <Star key={n} className={`${size} ${n <= Math.round(value) ? "fill-[#E0A100] text-[#E0A100]" : "text-[#D5D5D0]"}`} />
      ))}
    </span>
  );
}

function ReviewForm({ loai, id, initial, onSaved }) {
  const [stars, setStars] = useState(initial?.so_sao || 0);
  const [content, setContent] = useState(initial?.noi_dung || "");
  const [saving, setSaving] = useState(false);
  const [message, setMessage] = useState(null);

  const submit = async (event) => {
    event.preventDefault();
    if (!stars) {
      setMessage({ ok: false, text: "Vui lòng chọn số sao." });
      return;
    }
    setSaving(true);
    setMessage(null);
    try {
      const request = loai === "chuyen_gia"
        ? apiClient.post("/khach-hang/danh-gia/chuyen-gia", { id_chuyen_gia: id, so_sao: stars, noi_dung: content })
        : apiClient.post("/khach-hang/danh-gia/san-pham", { loai, id, so_sao: stars, noi_dung: content });
      const { data } = await request;
      setMessage({ ok: true, text: data.message });
      await onSaved();
    } catch (error) {
      setMessage({ ok: false, text: getApiErrorMessage(error, "Không thể gửi đánh giá.") });
    } finally {
      setSaving(false);
    }
  };

  return (
    <form onSubmit={submit} className="mb-6 rounded-xl border border-[#ECECE9] bg-[#FAFAF8] p-4">
      <p className="mb-2 text-sm font-bold">{initial ? "Cập nhật đánh giá của bạn" : "Viết đánh giá"}</p>
      <div className="mb-3 flex gap-1" role="radiogroup" aria-label="Số sao">
        {[1, 2, 3, 4, 5].map((n) => (
          <button key={n} type="button" role="radio" aria-checked={stars === n} aria-label={`${n} sao`} onClick={() => setStars(n)}>
            <Star className={`h-6 w-6 ${n <= stars ? "fill-[#E0A100] text-[#E0A100]" : "text-[#D5D5D0]"}`} />
          </button>
        ))}
      </div>
      <textarea
        rows={3}
        maxLength={1000}
        value={content}
        onChange={(event) => setContent(event.target.value)}
        placeholder="Chia sẻ trải nghiệm của bạn (không bắt buộc)"
        className="w-full resize-none rounded-lg border border-[#ECECE9] bg-white px-3 py-2 text-sm outline-none focus:border-[#17171A]"
      />
      {message ? <p role="status" className={`mt-2 text-xs ${message.ok ? "text-[#1F7A3A]" : "text-[#AC0021]"}`}>{message.text}</p> : null}
      <button type="submit" disabled={saving} className="mt-3 rounded-lg bg-[#AC0021] px-4 py-2 text-xs font-semibold text-white disabled:opacity-60">{saving ? "Đang gửi..." : "Gửi đánh giá"}</button>
    </form>
  );
}

/**
 * Đánh giá của một đối tượng: loai = san_pham | phu_tung | chuyen_gia.
 * Ai cũng xem được; form chỉ hiện với khách đã mua sản phẩm / đã hoàn thành lịch với chuyên gia.
 */
export default function ReviewsSection({ loai, id, title = "Đánh giá" }) {
  const { vaiTro } = useAuth();
  const isCustomer = vaiTro === "khach-hang";
  const [summary, setSummary] = useState(null);
  const [items, setItems] = useState([]);
  const [page, setPage] = useState(1);
  const [eligibility, setEligibility] = useState(null);
  const [error, setError] = useState("");

  const load = useCallback(async (pageNumber = 1) => {
    try {
      const { data } = await apiClient.get(`/danh-gia/${loai}/${id}`, { params: { page: pageNumber } });
      setSummary(data.data);
      setItems((current) => (pageNumber === 1 ? data.data.danh_gia : [...current, ...data.data.danh_gia]));
      setPage(pageNumber);
      setError("");
    } catch (err) {
      setError(getApiErrorMessage(err, "Không thể tải đánh giá."));
    }
  }, [loai, id]);

  const loadEligibility = useCallback(async () => {
    if (!isCustomer) return;
    try {
      const { data } = await apiClient.get("/khach-hang/danh-gia/dieu-kien", { params: { loai, id } });
      setEligibility(data.data);
    } catch {
      setEligibility(null);
    }
  }, [isCustomer, loai, id]);

  useEffect(() => {
    let active = true;
    Promise.resolve().then(() => { if (active) { void load(1); void loadEligibility(); } });
    return () => { active = false; };
  }, [load, loadEligibility]);

  const refresh = async () => { await Promise.all([load(1), loadEligibility()]); };

  return (
    <section className="rounded-2xl border border-[#ECECE9] bg-white p-5" aria-label={title}>
      <h2 className="mb-4 text-lg font-extrabold">{title}</h2>
      {error ? <p role="alert" className="text-sm text-[#AC0021]">{error}</p> : null}

      {summary ? (
        <div className="mb-5 flex flex-wrap items-center gap-6">
          <div className="text-center">
            <div className="text-3xl font-extrabold">{summary.trung_binh ?? "—"}</div>
            <Stars value={summary.trung_binh || 0} />
            <div className="mt-1 text-xs text-[#63635E]">{summary.so_luong} đánh giá</div>
          </div>
          <div className="min-w-[180px] flex-1 space-y-1">
            {[5, 4, 3, 2, 1].map((n) => {
              const count = summary.phan_bo?.[n] || 0;
              return (
                <div key={n} className="flex items-center gap-2 text-xs">
                  <span className="w-3">{n}</span>
                  <div className="h-2 flex-1 overflow-hidden rounded-full bg-[#F1F1EF]"><div className="h-full bg-[#E0A100]" style={{ width: `${summary.so_luong ? (count / summary.so_luong) * 100 : 0}%` }} /></div>
                  <span className="w-6 text-right text-[#63635E]">{count}</span>
                </div>
              );
            })}
          </div>
        </div>
      ) : null}

      {!isCustomer ? (
        <p className="mb-5 text-sm text-[#63635E]">
          <Link to="/dang-nhap" className="font-semibold text-[#AC0021] hover:underline">Đăng nhập</Link> để đánh giá (chỉ khách đã mua sản phẩm hoặc đã hoàn thành lịch với chuyên gia).
        </p>
      ) : null}
      {isCustomer && eligibility?.co_the_danh_gia ? <ReviewForm key={eligibility.danh_gia_cua_toi?.so_sao ?? "new"} loai={loai} id={id} initial={eligibility.danh_gia_cua_toi} onSaved={refresh} /> : null}
      {isCustomer && eligibility && !eligibility.co_the_danh_gia ? <p className="mb-5 text-sm text-[#63635E]">{eligibility.ly_do}</p> : null}

      {summary && items.length === 0 ? <p className="text-sm text-[#63635E]">Chưa có đánh giá nào.</p> : null}
      <ul className="space-y-4">
        {items.map((review) => (
          <li key={review.id} className="border-b border-[#F1F1EF] pb-4 last:border-0">
            <div className="flex flex-wrap items-center gap-2">
              <strong className="text-sm">{review.ten_khach_hang}</strong>
              <Stars value={review.so_sao} size="h-3.5 w-3.5" />
              <span className="text-xs text-[#9a9da2]">{dateText(review.created_at)}</span>
            </div>
            {review.noi_dung ? <p className="mt-1 whitespace-pre-wrap text-sm text-[#4A4A46]">{review.noi_dung}</p> : null}
          </li>
        ))}
      </ul>
      {summary && page < summary.so_trang ? (
        <button type="button" onClick={() => void load(page + 1)} className="mt-4 text-sm font-semibold text-[#AC0021] hover:underline">Xem thêm đánh giá</button>
      ) : null}
    </section>
  );
}
