async function loadAssignPage() {
  try {
    const [shelfRes, namesRes] = await Promise.all([
      fetch(API + 'ApiTrangThai.php').then(r => r.json()),
      fetch(API + 'ApiSanPham.php?action=names').then(r => r.json())
    ]);

    if (shelfRes.status !== 'success') return;
    const shelves = shelfRes.data;
    const names = namesRes.status === 'success' ? namesRes.data : [];

    const grid = document.getElementById('assignGrid');
    grid.innerHTML = shelves.map(s => {
      const opts = names.map(n =>
        `<option value="${n}" ${s.ten_sp_quy_dinh === n ? 'selected' : ''}>${n}</option>`
      ).join('');
      return `
        <div class="assign-card">
          <h3>${s.id_ngan == 4 ? '<i class="bi bi-broadcast"></i>' : '<i class="bi bi-box"></i>'} Ngăn Kệ #${s.id_ngan} ${s.id_ngan == 4 ? '(RFID)' : ''}</h3>
          <div class="current">
            <div class="label">Nhóm SP quy định hiện tại:</div>
            <div style="font-weight:600;color:${s.ten_sp_quy_dinh ? 'var(--green)' : 'var(--text2)'}">${s.ten_sp_quy_dinh || 'Chưa quy định'}</div>
          </div>
          <div class="form-group" style="margin-bottom:12px">
            <label>Chọn nhóm sản phẩm</label>
            <select id="assignSelect${s.id_ngan}">
              <option value="">-- Chọn nhóm SP --</option>
              ${opts}
            </select>
          </div>
          <div class="actions">
            <button class="btn btn-sm btn-primary" onclick="assignShelf(${s.id_ngan})"><i class="bi bi-check-lg"></i> Gán</button>
            <button class="btn btn-sm btn-danger" onclick="clearShelf(${s.id_ngan})"><i class="bi bi-x-lg"></i> Xóa quy định</button>
          </div>
        </div>`;
    }).join('');
  } catch (e) {
    showToast('Lỗi tải dữ liệu', 'warn');
  }
}

async function assignShelf(id) {
  const ten_sp = document.getElementById('assignSelect' + id).value;
  if (!ten_sp) {
    showToast('Vui lòng chọn nhóm sản phẩm', 'warn');
    return;
  }
  const fd = new FormData();
  fd.append('action', 'assign');
  fd.append('id_ngan', id);
  fd.append('ten_sp', ten_sp);
  try {
    const res = await fetch(API + 'ApiQuyDinh.php', { method: 'POST', body: fd }).then(r => r.json());
    if (res.status === 'success') {
      showToast(`Đã gán nhóm [${ten_sp}] cho Ngăn #${id}`, 'success');
      loadAssignPage();
    } else {
      showToast(res.message, 'warn');
    }
  } catch (e) {
    showToast('Lỗi kết nối', 'warn');
  }
}

async function clearShelf(id) {
  if (!confirm('Xóa quy định sản phẩm cho Ngăn kệ #' + id + '?')) return;
  const fd = new FormData();
  fd.append('action', 'clear');
  fd.append('id_ngan', id);
  try {
    const res = await fetch(API + 'ApiQuyDinh.php', { method: 'POST', body: fd }).then(r => r.json());
    if (res.status === 'success') {
      showToast(`Đã xóa quy định Ngăn #${id}`, 'success');
      loadAssignPage();
    } else {
      showToast(res.message, 'warn');
    }
  } catch (e) {
    showToast('Lỗi kết nối', 'warn');
  }
}
