import { createContext, useCallback, useContext, useEffect, useState } from "react";
import { useAuth } from "./AuthContext";

const LEGACY_STORAGE_KEY = "gioHangShowroom";
const STORAGE_KEY_PREFIX = "gioHangShowroom:";
const CartContext = createContext(null);

function storageKeyForUser(user) {
  if (!user) return null;
  const email = typeof user.email === "string" ? user.email.trim().toLocaleLowerCase() : "";
  const userId = user.id ?? email;
  return userId ? `${STORAGE_KEY_PREFIX}${user.vaiTro || "account"}:${userId}` : null;
}

function readCart(storageKey) {
  if (!storageKey) return [];
  try {
    const cart = JSON.parse(localStorage.getItem(storageKey) || "[]");
    return Array.isArray(cart) ? cart : [];
  } catch {
    return [];
  }
}

export function CartProvider({ children }) {
  const { nguoiDung } = useAuth();
  const accountKey = storageKeyForUser(nguoiDung);

  useEffect(() => {
    localStorage.removeItem(LEGACY_STORAGE_KEY);
  }, []);

  return (
    <CartSession key={accountKey || "signed-out"} accountKey={accountKey}>
      {children}
    </CartSession>
  );
}

function CartSession({ children, accountKey }) {
  const [items, setItems] = useState(() => readCart(accountKey));

  useEffect(() => {
    if (accountKey) localStorage.setItem(accountKey, JSON.stringify(items));
  }, [accountKey, items]);

  const addItem = useCallback((product) => {
    if (!accountKey || !product?.id || !Number.isFinite(Number(product.giaSo)) || Number(product.giaSo) <= 0) {
      return false;
    }

    const stock = Math.max(0, Number(product.soLuong) || 0);
    if (!stock) return false;

    setItems((current) => {
      const existing = current.find((item) => item.type === product.type && item.id === product.id);
      if (existing) {
        return current.map((item) => (
            item.type === product.type && item.id === product.id
              ? { ...item, quantity: Math.min(stock, item.quantity + 1), soLuong: stock }
              : item
        ));
      }
      return [...current, { ...product, quantity: 1 }];
    });
    return true;
  }, [accountKey]);

  const setQuantity = useCallback((type, id, quantity) => {
    if (!accountKey) return;
    setItems((current) => current.map((item) => (
        item.type === type && item.id === id
          ? { ...item, quantity: Math.max(1, Math.min(item.soLuong, Number(quantity) || 1)) }
          : item
    )));
  }, [accountKey]);

  const removeItem = useCallback((type, id) => {
    if (!accountKey) return;
    setItems((current) => current.filter((item) => item.type !== type || item.id !== id));
  }, [accountKey]);

  const clearCart = useCallback(() => {
    if (accountKey) setItems([]);
  }, [accountKey]);

  const itemCount = items.reduce((total, item) => total + item.quantity, 0);
  const subtotal = items.reduce((total, item) => total + item.giaSo * item.quantity, 0);

  return (
    <CartContext.Provider value={{ items, itemCount, subtotal, addItem, setQuantity, removeItem, clearCart }}>
      {children}
    </CartContext.Provider>
  );
}

export function useCart() {
  const cart = useContext(CartContext);
  if (!cart) throw new Error("useCart phải được dùng bên trong CartProvider.");
  return cart;
}
