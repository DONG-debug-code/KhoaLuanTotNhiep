import axios from "axios";
import { BASE_URL } from "./config";

const getValue = (record, keys, fallback = "") => {
  for (const key of keys) {
    if (record?.[key] !== undefined && record[key] !== null) return record[key];
  }
  return fallback;
};

const getImageUrl = (value) => {
  if (typeof value !== "string" || !value) return "";
  if (/^(https?:)?\/\//i.test(value) || value.startsWith("data:")) return value;
  const origin = BASE_URL.replace(/\/api\/?$/, "");
  const path = value.trim().replace(/\\/g, "/").replace(/^\/+/, "").replace(/^storage\//, "");
  return `${origin}/storage/${path}`;
};

const asArray = (value) => Array.isArray(value) ? value : value ? [value] : [];

function normalizeGoiDo(goi) {
  const id = getValue(goi, ["id", "id_san_pham", "id_goi_do", "ma"], null);
  const rawPrice = getValue(goi, ["gia", "price"], null);
  const price = rawPrice === null || rawPrice === "" ? Number.NaN : Number(rawPrice);
  const rawCategory = getValue(goi, ["hangMuc", "hang_muc", "loai_san_pham", "category"]);
  const categoryLabels = {
    phan_cung: "Phụ tùng hiệu năng",
    phan_mem: "Phần mềm",
    cong_nghe_moi: "Công nghệ mới",
  };
  const rawSpecs = getValue(goi, ["thong_so_chi_tiet", "thong_so", "specifications"], null);
  let specs = rawSpecs;

  if (typeof rawSpecs === "string") {
    try {
      specs = JSON.parse(rawSpecs);
    } catch {
      specs = rawSpecs;
    }
  }

  const rawModels = getValue(goi, [
    "dongXeTuongThich",
    "dong_xe_tuong_thich",
    "dong_xe",
    "mau_xe",
    "mau_xe_tuong_thich",
    "model",
  ], []);
  const models = asArray(rawModels).map((model) => {
    if (typeof model === "string" || typeof model === "number") return String(model);
    return getValue(model, ["ten", "ten_mau_xe", "name"], "");
  }).filter(Boolean);

  return {
    ...goi,
    id,
    slug: getValue(goi, ["slug", "slug_san_pham", "duong_dan"], String(id ?? "")),
    ten: getValue(goi, ["ten", "ten_san_pham", "name"], "Gói độ"),
    anh: getImageUrl(getValue(goi, ["anh", "hinh_anh", "image", "anh_san_pham"])),
    hangMuc: categoryLabels[rawCategory] ?? rawCategory,
    dongXeTuongThich: models,
    moTaNgan: getValue(goi, ["moTaNgan", "mo_ta_ngan", "mo_ta", "description"]),
    chiTiet: getValue(goi, ["chiTiet", "chi_tiet", "mo_ta_chi_tiet", "mo_ta", "description"]),
    thongSoChiTiet: specs,
    gia: Number.isFinite(price) ? `${price.toLocaleString("vi-VN")} đ` : rawPrice ?? "",
    giaSo: Number.isFinite(price) ? price : null,
  };
}

function normalizePhuTung(phuTung) {
  const id = getValue(phuTung, ["id", "id_phu_tung", "ma"], null);
  const rawPrice = getValue(phuTung, ["gia", "price"], null);
  const price = rawPrice === null || rawPrice === "" ? Number.NaN : Number(rawPrice);
  const categoryLabels = {
    ngoai_that: "Ngoại thất",
    noi_that: "Nội thất",
    hieu_suat: "Hiệu suất",
    cong_nghe: "Công nghệ",
    cong_nghe_moi: "Công nghệ mới",
  };
  const rawCategory = getValue(phuTung, ["loai", "loai_phu_tung", "category"]);

  return {
    ...phuTung,
    id,
    slug: String(id ?? ""),
    ten: getValue(phuTung, ["ten", "ten_phu_tung", "name"], "Phụ tùng Porsche"),
    anh: getImageUrl(getValue(phuTung, ["anh", "hinh_anh", "image"])),
    hangMuc: categoryLabels[rawCategory] ?? rawCategory,
    moTaNgan: getValue(phuTung, ["mo_ta", "moTa", "description"]),
    gia: Number.isFinite(price) ? `${price.toLocaleString("vi-VN")} đ` : rawPrice ?? "",
    giaSo: Number.isFinite(price) ? price : null,
    soLuong: Number(getValue(phuTung, ["so_luong", "so_luong_kho", "stock"], 0)),
  };
}

function getCollection(response, keys) {
  if (Array.isArray(response)) return response;
  if (!response || typeof response !== "object") return [];

  for (const key of keys) {
    const value = response[key];
    if (Array.isArray(value)) return value;
  }

  if (response.data && response.data !== response) {
    return getCollection(response.data, keys);
  }

  throw new Error("BE trả về cấu trúc dữ liệu danh sách không hợp lệ.");
}

function normalizeHang(hang) {
  const id = getValue(hang, ["id", "id_hang_phu_tung", "id_chuyen_khoa", "ma"], null);
  const goiDo = getValue(hang, [
    "goiDo",
    "goi_do",
    "goi_dos",
    "san_phams",
    "san_pham",
    "products",
    "packages",
  ], []);

  return {
    ...hang,
    id,
    slug: getValue(hang, ["slug", "duong_dan"], String(id ?? "")),
    ten: getValue(hang, ["ten", "ten_hang", "ten_hang_phu_tung", "ten_chuyen_khoa", "name"], "Chưa có tên"),
    quocGia: getValue(hang, ["quocGia", "quoc_gia", "country"]),
    tuNam: getValue(hang, ["tuNam", "tu_nam", "nam_thanh_lap"]),
    rating: getValue(hang, ["rating", "danh_gia"]),
    hangMucChinh: getValue(hang, ["hangMucChinh", "hang_muc_chinh", "chuyen_mon"]),
    chuyenMon: getValue(hang, ["chuyenMon", "chuyen_mon", "mo_ta_ngan"]),
    tags: asArray(getValue(hang, ["tags", "the"], [])),
    moTaNgan: getValue(hang, ["moTaNgan", "mo_ta_ngan", "mo_ta"]),
    moTa: getValue(hang, ["moTa", "mo_ta", "description"]),
    anhBia: getImageUrl(getValue(hang, ["anhBia", "anh_bia", "hinh_anh", "anh", "image"])),
    goiDo: Array.isArray(goiDo) ? goiDo.map(normalizeGoiDo) : [],
  };
}

function normalizeChuyenGia(chuyenGia) {
  const id = getValue(chuyenGia, ["id", "id_chuyen_gia", "id_bac_si", "ma"], null);

  return {
    ...chuyenGia,
    id,
    slug: getValue(chuyenGia, ["slug", "duong_dan"], String(id ?? "")),
    ten: getValue(chuyenGia, ["ten", "ho_ten", "ten_chuyen_gia", "name"], "Chưa có tên"),
    chucDanh: getValue(chuyenGia, ["chucDanh", "chuc_danh", "chuc_vu"]),
    trangThai: getValue(chuyenGia, ["trangThai", "trang_thai"], "san-sang"),
    hangMucChuyenMon: asArray(getValue(chuyenGia, ["hangMucChuyenMon", "hang_muc_chuyen_mon", "chuyen_mon"], [])),
    dongXeChuyenMon: asArray(getValue(chuyenGia, ["dongXeChuyenMon", "dong_xe_chuyen_mon", "dong_xe"], [])),
    anh: getImageUrl(getValue(chuyenGia, ["anh", "hinh_anh", "avatar", "image"])),
    soNamKinhNghiem: getValue(chuyenGia, ["soNamKinhNghiem", "so_nam_kinh_nghiem", "kinh_nghiem"]),
    moTa: getValue(chuyenGia, ["moTa", "mo_ta", "description"]),
  };
}

function normalizeMauXe(mauXe) {
  const id = getValue(mauXe, ["id", "id_mau_xe", "ma"], null);

  return {
    ...mauXe,
    id,
    ten: getValue(mauXe, ["ten", "ten_mau_xe", "name"], "Mẫu xe Porsche"),
    moTa: getValue(mauXe, ["mo_ta", "moTa", "description"]),
    hangXe: getValue(mauXe, ["hang_xe", "ten_hang_xe", "brand"], "Porsche"),
    hangXeSlug: getValue(mauXe, ["hang_xe_slug", "slug_hang_xe"], ""),
    logoHangXe: getImageUrl(getValue(mauXe, ["hang_xe_logo", "logo_hang_xe", "brand_logo"])),
  };
}

function normalizeSanPham(sanPham) {
  const rawPrice = getValue(sanPham, ["gia", "price"], null);
  const price = rawPrice === null || rawPrice === "" ? Number.NaN : Number(rawPrice);
  const rawImages = getValue(sanPham, ["anh_san_phams", "anhSanPhams", "images", "anh"], []);
  const images = asArray(rawImages)
    .map((image) => {
      const path = typeof image === "string"
        ? image
        : getValue(image, ["duong_dan", "url", "path", "anh"], "");
      return getImageUrl(path);
    })
    .filter(Boolean);
  const rawColors = getValue(sanPham, ["mau_sacs", "mauSacs", "colors"], []);
  const colors = asArray(rawColors).map((color) => (
    typeof color === "string"
      ? { ten: color, maMau: "" }
      : {
        ten: getValue(color, ["ten", "name"], ""),
        maMau: getValue(color, ["ma_mau", "maMau", "hex"], ""),
      }
  )).filter((color) => color.ten);
  const model = getValue(sanPham, ["mau_xe", "mauXe", "model"], null);
  let specs = getValue(sanPham, ["thong_so_chi_tiet", "thongSoChiTiet", "specifications"], null);

  if (typeof specs === "string") {
    try {
      specs = JSON.parse(specs);
    } catch {
      specs = {};
    }
  }

  const ten = getValue(sanPham, ["ten", "ten_san_pham", "name"], "Sản phẩm Porsche");
  const modelName = typeof model === "string"
    ? model
    : getValue(model, ["ten", "name"], "");

  return {
    ...sanPham,
    id: getValue(sanPham, ["id", "id_san_pham"], null),
    slug: getValue(sanPham, ["slug"], null),
    ten,
    moTa: getValue(sanPham, ["mo_ta", "moTa", "description"]),
    giaSo: Number.isFinite(price) ? price : null,
    gia: Number.isFinite(price) ? `${price.toLocaleString("vi-VN")} đ` : "Liên hệ",
    soLuong: Number(getValue(sanPham, ["so_luong", "so_luong_kho", "stock"], 0)),
    mauXeId: getValue(sanPham, ["mau_xe_id", "mauXeId"], model?.id ?? null),
    mauXe: modelName,
    mauSacs: colors,
    anhGallery: images,
    anh: images[0] ?? "",
    loaiSanPham: getValue(sanPham, ["loai_san_pham", "loaiSanPham", "category"]),
    thongSoChiTiet: specs && typeof specs === "object" ? specs : {},
  };
}

export async function getHomePageData() {
  const { data } = await axios.get(`${BASE_URL}/home-page/data`);
  return data;
}

export async function getHangPhuTungClient() {
  const { data } = await axios.get(`${BASE_URL}/client/hang-phu-tung/data`);
  return getCollection(data, ["hang_phu_tung", "hangPhuTung", "chuyen_khoa", "chuyenKhoa", "hangs", "brands", "items", "data"])
    .map(normalizeHang);
}

export async function getHangPhuTungClientById(id) {
  const { data } = await axios.get(`${BASE_URL}/client/hang-phu-tung/chi-tiet/${encodeURIComponent(id)}`);
  const detail = data?.data ?? data;
  return detail && typeof detail === "object" ? normalizeHang(detail) : null;
}

export async function getGoiDoClientById(id) {
  const { data } = await axios.get(
    `${BASE_URL}/client/goi-do/chi-tiet/${encodeURIComponent(id)}`
  );
  const detail = data?.data ?? data;
  return detail && typeof detail === "object" ? normalizeGoiDo(detail) : null;
}

export async function getGoiDoClient() {
  const { data } = await axios.get(`${BASE_URL}/client/goi-do/data`);
  return getCollection(data, ["goi_do", "san_phams", "san_pham", "products", "items", "data"])
    .map(normalizeGoiDo);
}

export async function getPhuTungClient() {
  const { data } = await axios.get(`${BASE_URL}/client/phu-tung/data`);
  return getCollection(data, ["phu_tungs", "phu_tung", "accessories", "items", "data"])
    .map(normalizePhuTung);
}

export async function getPhuTungClientById(id) {
  const { data } = await axios.get(
    `${BASE_URL}/client/phu-tung/chi-tiet/${encodeURIComponent(id)}`
  );
  const detail = data?.data ?? data;
  return detail && typeof detail === "object" ? normalizePhuTung(detail) : null;
}

export async function getMauXeClient() {
  const { data } = await axios.get(`${BASE_URL}/client/mau-xe/data`);
  return getCollection(data, ["mau_xes", "mau_xe", "models", "vehicles", "items", "data"])
    .map(normalizeMauXe);
}

export async function getSanPhamClient(filters = {}) {
  const { data } = await axios.get(`${BASE_URL}/san-pham`, { params: filters });
  const pageData = data?.data;

  if (Array.isArray(pageData)) {
    return { sanPham: pageData.map(normalizeSanPham), currentPage: 1, lastPage: 1, total: pageData.length };
  }

  if (!Array.isArray(pageData?.data)) {
    throw new Error("BE trả về cấu trúc phân trang sản phẩm không hợp lệ.");
  }

  return {
    sanPham: pageData.data.map(normalizeSanPham),
    currentPage: Number(pageData.current_page) || 1,
    lastPage: Number(pageData.last_page) || 1,
    total: Number(pageData.total) || 0,
  };
}

export async function getXeDangBanClient() {
  const xe = [];
  let page = 1;
  let lastPage = 1;

  do {
    const result = await getSanPhamClient({
      page,
      per_page: 100,
      sap_xep: "moi_nhat",
    });
    xe.push(...result.sanPham);
    lastPage = result.lastPage;
    page += 1;
  } while (page <= lastPage);

  return xe.filter((item) => !item.slug);
}

export async function getSanPhamClientById(id) {
  const { data } = await axios.get(`${BASE_URL}/san-pham/${encodeURIComponent(id)}`);
  const sanPham = data?.data;
  if (!sanPham || typeof sanPham !== "object") return null;

  const normalized = normalizeSanPham(sanPham);
  if (normalized.slug) return null;

  return {
    sanPham: normalized,
    xeLienQuan: asArray(data?.xe_lien_quan)
      .map(normalizeSanPham)
      .filter((item) => !item.slug),
  };
}

export async function getGoiDoVaPhuTungClient() {
  const [goiDoResult, phuTungResult] = await Promise.allSettled([
    getGoiDoClient(),
    getPhuTungClient(),
  ]);

  return {
    goiDo: goiDoResult.status === "fulfilled" ? goiDoResult.value : [],
    phuTung: phuTungResult.status === "fulfilled" ? phuTungResult.value : [],
    loiGoiDo: goiDoResult.status === "rejected"
      ? goiDoResult.reason.response?.data?.message || "Không tải được danh sách gói độ."
      : "",
    loiPhuTung: phuTungResult.status === "rejected"
      ? phuTungResult.reason.response?.data?.message || "Không tải được danh sách phụ tùng."
      : "",
  };
}

export async function getChuyenGiaTuningClient() {
  const { data } = await axios.get(`${BASE_URL}/client/chuyen-gia-tuning/data`);
  return getCollection(data, ["chuyen_gia", "chuyen_gias", "chuyenGia", "chuyen_gia_tuning", "bac_si", "bacSi", "experts", "items", "data"])
    .map(normalizeChuyenGia);
}

export async function getChuyenGiaTuningClientById(id) {
  const { data } = await axios.get(`${BASE_URL}/client/chuyen-gia-tuning/chi-tiet/${encodeURIComponent(id)}`);
  const detail = data?.data ?? data;
  return detail && typeof detail === "object" ? normalizeChuyenGia(detail) : null;
}
