import { useCallback, useEffect, useState } from "react";
import { RefreshCw } from "lucide-react";
import { getWorkspaceData, postWorkspaceData } from "../../api/WorkspaceApi";
import ExpertWorkspaceLayout from "../../components/workspaces/ExpertWorkspaceLayout";
import ExpertBoardView from "./workspace/ExpertBoardView";
import ExpertCustomersView from "./workspace/ExpertCustomersView";
import ExpertRosterView from "./workspace/ExpertRosterView";
import ExpertWorkshopView from "./workspace/ExpertWorkshopView";
import { errorMessage } from "./workspace/expertUi";

const ROLE = "chuyen-gia";

function todayInVietnam() {
  return new Intl.DateTimeFormat("en-CA", { timeZone: "Asia/Ho_Chi_Minh" }).format(new Date());
}

export default function ChuyenGiaWorkspacePage() {
  const [activeKey, setActiveKey] = useState("board");
  const [date, setDate] = useState(todayInVietnam);
  const [board, setBoard] = useState(null);
  const [workshop, setWorkshop] = useState(null);
  const [customers, setCustomers] = useState(null);
  const [shifts, setShifts] = useState(null);
  const [dangTai, setDangTai] = useState(true);
  const [dangLuu, setDangLuu] = useState(false);
  const [busyId, setBusyId] = useState(null);
  const [loi, setLoi] = useState("");
  const [thongBao, setThongBao] = useState("");

  const taiDuLieu = useCallback(async () => {
    setDangTai(true);
    setLoi("");
    try {
      const [boardData, workshopData, customerData, shiftData] = await Promise.all([
        getWorkspaceData(ROLE, "bang-lam-viec", { ngay: date }),
        getWorkspaceData(ROLE, "khu-vuc-workshop/tong-quan"),
        getWorkspaceData(ROLE, "khach-hang-phu-trach"),
        getWorkspaceData(ROLE, "lich-lam-viec/data"),
      ]);
      setBoard(boardData);
      setWorkshop(workshopData);
      setCustomers(Array.isArray(customerData) ? customerData : []);
      setShifts(Array.isArray(shiftData) ? shiftData : []);
    } catch (error) {
      setLoi(errorMessage(error, "Không tải được dữ liệu khu chuyên gia."));
    } finally {
      setDangTai(false);
    }
  }, [date]);

  useEffect(() => {
    void taiDuLieu();
  }, [taiDuLieu]);

  const thucHien = async (path, payload, { id = null, success } = {}) => {
    setThongBao("");
    setLoi("");
    setBusyId(id);
    setDangLuu(true);
    try {
      const ketQua = await postWorkspaceData(ROLE, path, payload);
      setThongBao(ketQua?.message || success || "Đã cập nhật.");
      await taiDuLieu();
      return true;
    } catch (error) {
      setLoi(errorMessage(error, "Không thể thực hiện thao tác."));
      return false;
    } finally {
      setBusyId(null);
      setDangLuu(false);
    }
  };

  const capNhatTienDo = (job, hanhDong, extra = {}) =>
    thucHien("lich-dat-do/tien-do", { id: job.id, hanh_dong: hanhDong, ...extra }, { id: job.id });

  const xacNhanLich = (job, trangThai) =>
    thucHien("lich-dat-do/cap-nhat", { id: job.id, trang_thai: trangThai }, { id: job.id });

  const luuThongSo = (khachKey, bienSo, rows) =>
    thucHien("khach-hang-phu-trach/thong-so", {
      khach_key: khachKey,
      bien_so_xe: bienSo,
      thong_so: rows.map((row) => ({ hang_muc: row.hang_muc, truoc: row.truoc, hien_tai: row.hien_tai })),
    });

  const luuGhiChu = (khachKey, noiDung) => thucHien("khach-hang-phu-trach/ghi-chu", { khach_key: khachKey, noi_dung: noiDung });

  const dangKyCa = (ngay, ca) => thucHien("lich-lam-viec/dang-ky", { ngay_lam_viec: ngay, ca_lam_viec: ca });

  const xoaCa = (id) => thucHien("lich-lam-viec/xoa", { id });

  const sanSang = board && workshop && customers && shifts;

  return (
    <ExpertWorkspaceLayout activeKey={activeKey} onNavigate={setActiveKey} expert={board?.chuyen_gia} dynoBusy={Boolean(board?.dyno_ban)}>
      {loi ? <p role="alert" className="mb-4 rounded-xl border border-[#F1CACA] bg-[#FBE9E9] px-4 py-3 text-sm text-[#B60F0F]">{loi}</p> : null}
      {thongBao ? <p role="status" className="mb-4 rounded-xl border border-[#CFE6D4] bg-[#EAF6ED] px-4 py-3 text-sm text-[#256C3A]">{thongBao}</p> : null}

      {!sanSang ? (
        <div className="flex items-center justify-center gap-3 py-24 text-sm text-[#63635E]">
          {dangTai ? <><RefreshCw className="h-4 w-4 animate-spin" /> Đang tải dữ liệu khu chuyên gia…</> : (
            <button type="button" onClick={() => void taiDuLieu()} className="rounded-xl bg-[#0B0B0D] px-5 py-3 font-semibold text-white">Thử tải lại</button>
          )}
        </div>
      ) : (
        <>
          {activeKey === "board" ? (
            <ExpertBoardView data={board} date={board.ngay} onDateChange={setDate} busyId={busyId} onProgress={capNhatTienDo} onConfirm={xacNhanLich} onNavigate={setActiveKey} />
          ) : null}
          {activeKey === "customers" ? (
            <ExpertCustomersView customers={customers} onSaveTuning={luuThongSo} onSaveNote={luuGhiChu} saving={dangLuu} />
          ) : null}
          {activeKey === "workshop" ? <ExpertWorkshopView data={workshop} /> : null}
          {activeKey === "roster" ? (
            <ExpertRosterView shifts={shifts} today={todayInVietnam()} saving={dangLuu} onRegister={dangKyCa} onRemove={xoaCa} />
          ) : null}
        </>
      )}
    </ExpertWorkspaceLayout>
  );
}
