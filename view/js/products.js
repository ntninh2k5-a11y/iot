  let allProducts = [];
  let scannedUIDs = [];
  let rfidPollInterval = null;

  async function loadProducts() {
    try {
      const res = await fetch(API + 'ApiSanPham.php').then(r => r.json());
      if (res.status === 'success') {
        allProducts = res.data;
        renderProducts(allProducts);
      }
      loadProductGroups();
    } catch (e) {
      showToast('Lỗi tải sản phẩm', 'warn');
    }
  }

  async function loadProductGroups() {
    try {
      const res = await fetch(API + 'ApiSanPham.php?action=groups').then(r => r.json());
      if (res.status === 'success') {
        const container = document.getElementById('productGroups');
        if (!container) return;
        if (res.data.length === 0) {
          container.innerHTML = '<div style="color:var(--text2);font-size:13px">Chưa có nhóm sản phẩm nào</div>';
          return;
        }
        container.innerHTML = res.data.map(g => `
          <div style="display:flex;justify-content:space-between;align-items:center;padding:10px 14px;background:var(--bg);border-radius:8px;font-size:13px">
            <span style="font-weight:600">${g.ten_sp}</span>
            <div style="display:flex;gap:12px">
              <span style="color:var(--green)"><i class="bi bi-box-arrow-in-down"></i> ${g.in_stock} trong kho</span>
              <span style="color:var(--text2)"><i class="bi bi-boxes"></i> ${g.total} tổng</span>
            </div>
          </div>`).join('');
      }
    } catch (e) {}
  }

  function renderProducts(data) {
    const tb = document.getElementById('productTable');
    if (!data.length) {
      tb.innerHTML = '<tr><td colspan="6"><div class="empty-state"><div class="icon"><i class="bi bi-tags"></i></div><p>Chưa có sản phẩm nào</p></div></td></tr>';
      return;
    }
    tb.innerHTML = data.map(p => `
      <tr>
        <td><code style="background:var(--bg);padding:3px 8px;border-radius:4px;font-size:12px">${p.uid_the}</code></td>
        <td>${p.ten_sp}</td>
        <td style="font-size:12px;color:var(--text2)">${p.id_ngan_hien_tai ? 'Ngăn #' + p.id_ngan_hien_tai : '—'}</td>
        <td style="font-size:12px;color:var(--text2)">${formatTime(p.ngay_tao)}</td>
        <td><span class="tag ${p.trang_thai_kho == 1 ? 'in' : 'out'}">${p.trang_thai_kho == 1 ? 'Trong kho' : 'Ngoài kho'}</span></td>
        <td>
          <button class="btn btn-sm btn-success" onclick="editProduct('${p.uid_the}','${escapeHtml(p.ten_sp)}')"><i class="bi bi-pencil-square"></i></button>
          <button class="btn btn-sm btn-danger" onclick="deleteProduct('${p.uid_the}')"><i class="bi bi-trash"></i></button>
        </td>
      </tr>`).join('');
  }

  function filterProducts() {
    const q = document.getElementById('spSearch').value.toLowerCase();
    const filtered = allProducts.filter(p =>
      p.uid_the.toLowerCase().includes(q) || p.ten_sp.toLowerCase().includes(q)
    );
    renderProducts(filtered);
  }

  async function createProduct() {
    const uid = document.getElementById('spUid').value.trim();
    const name = document.getElementById('spName').value.trim();
    if (!uid || !name) {
      showToast('Vui lòng nhập đầy đủ UID và Tên SP', 'warn');
      return;
    }

    const fd = new FormData();
    fd.append('action', 'create');
    fd.append('uid_the', uid);
    fd.append('ten_sp', name);

    try {
      const res = await fetch(API + 'ApiSanPham.php', { method: 'POST', body: fd }).then(r => r.json());
      if (res.status === 'success') {
        showToast('Thêm sản phẩm thành công!', 'success');
        document.getElementById('spUid').value = '';
        document.getElementById('spName').value = '';
        loadProducts();
      } else {
        showToast(res.message || 'Lỗi thêm SP', 'warn');
      }
    } catch (e) {
      showToast('Lỗi kết nối', 'warn');
    }
  }

  function startRfidPolling() {
    if (rfidPollInterval) return;
    rfidPollInterval = setInterval(pollRfidScans, 1500);
    document.getElementById('rfidScanBtn').innerHTML = '<i class="bi bi-stop-circle"></i> Dừng quét';
    document.getElementById('rfidScanBtn').onclick = stopRfidPolling;
    document.getElementById('rfidScanStatus').innerHTML = '<span style="color:var(--green)"><i class="bi bi-broadcast"></i> Đang lắng nghe RFID... Hãy quét thẻ!</span>';
    showToast('Bắt đầu lắng nghe RFID. Hãy quét thẻ qua đầu đọc!', 'info');
  }

  function stopRfidPolling() {
    if (rfidPollInterval) {
      clearInterval(rfidPollInterval);
      rfidPollInterval = null;
    }
    document.getElementById('rfidScanBtn').innerHTML = '<i class="bi bi-broadcast"></i> Bắt đầu quét RFID';
    document.getElementById('rfidScanBtn').onclick = startRfidPolling;
    document.getElementById('rfidScanStatus').innerHTML = '<span style="color:var(--text2)">Nhấn "Bắt đầu quét" rồi quẹt thẻ qua đầu đọc</span>';
  }

  async function pollRfidScans() {
    try {
      const res = await fetch(API + 'ApiRfidScan.php?action=latest').then(r => r.json());
      if (res.status === 'success' && res.data.length > 0) {
        res.data.forEach(scan => {
          if (!scannedUIDs.includes(scan.uid)) {
            scannedUIDs.push(scan.uid);
            showToast('Đã nhận thẻ RFID: ' + scan.uid, 'success');
          }
        });
        renderScannedList();
      }
    } catch (e) {}
  }

  async function saveBatchProducts() {
    const name = document.getElementById('batchName').value.trim();
    if (!name) {
      showToast('Vui lòng nhập tên sản phẩm', 'warn');
      return;
    }
    if (scannedUIDs.length === 0) {
      showToast('Chưa có UID nào được quét', 'warn');
      return;
    }

    const fd = new FormData();
    fd.append('action', 'create_batch');
    fd.append('uids', scannedUIDs.join(','));
    fd.append('ten_sp', name);

    try {
      const res = await fetch(API + 'ApiSanPham.php', { method: 'POST', body: fd }).then(r => r.json());
      if (res.status === 'success') {
        showToast(res.message, 'success');
        scannedUIDs = [];
        renderScannedList();
        document.getElementById('batchName').value = '';
        stopRfidPolling();
        loadProducts();
      } else {
        showToast(res.message, 'warn');
      }
    } catch (e) {
      showToast('Lỗi kết nối', 'warn');
    }
  }

  function addManualUID() {
    const uid = document.getElementById('batchUidInput').value.trim().toUpperCase();
    if (!uid) return;
    if (scannedUIDs.includes(uid)) {
      showToast('UID này đã có trong danh sách', 'warn');
      return;
    }
    scannedUIDs.push(uid);
    document.getElementById('batchUidInput').value = '';
    renderScannedList();
  }

  function removeScannedUID(index) {
    scannedUIDs.splice(index, 1);
    renderScannedList();
  }

  function clearScannedList() {
    scannedUIDs = [];
    renderScannedList();
  }

  function renderScannedList() {
    const container = document.getElementById('scannedList');
    const countEl = document.getElementById('scannedCount');
    if (countEl) countEl.textContent = scannedUIDs.length;
    
    if (scannedUIDs.length === 0) {
      container.innerHTML = '<div style="color:var(--text2);font-size:13px;text-align:center;padding:20px">Chưa có UID nào.</div>';
      return;
    }
    container.innerHTML = scannedUIDs.map((uid, i) => `
      <div style="display:flex;justify-content:space-between;align-items:center;padding:8px 12px;background:var(--bg);border-radius:6px;font-size:13px">
        <div><span style="color:var(--text2);margin-right:8px">#${i+1}</span><code>${uid}</code></div>
        <button class="btn btn-sm btn-danger" onclick="removeScannedUID(${i})" style="padding:2px 8px"><i class="bi bi-x-lg"></i></button>
      </div>`).join('');
  }

  function editProduct(uid, name) {
    const html = `
      <div class="form-group">
        <label>UID Thẻ</label>
        <input type="text" value="${uid}" disabled style="opacity:.6">
      </div>
      <div class="form-group">
        <label>Tên sản phẩm</label>
        <input type="text" id="editSpName" value="${name}">
      </div>
      <button class="btn btn-primary" onclick="saveProduct('${uid}')"><i class="bi bi-floppy"></i> Lưu thay đổi</button>`;
    openModal('Sửa sản phẩm', html);
  }

  async function saveProduct(uid) {
    const name = document.getElementById('editSpName').value.trim();
    if (!name) return;
    const fd = new FormData();
    fd.append('action', 'update');
    fd.append('uid_the', uid);
    fd.append('ten_sp', name);
    try {
      const res = await fetch(API + 'ApiSanPham.php', { method: 'POST', body: fd }).then(r => r.json());
      if (res.status === 'success') {
        showToast('Cập nhật thành công!', 'success');
        closeModal();
        loadProducts();
      } else {
        showToast(res.message, 'warn');
      }
    } catch (e) {
      showToast('Lỗi kết nối', 'warn');
    }
  }

  async function deleteProduct(uid) {
    if (!confirm('Xác nhận xóa sản phẩm UID: ' + uid + '?')) return;
    const fd = new FormData();
    fd.append('action', 'delete');
    fd.append('uid_the', uid);
    try {
      const res = await fetch(API + 'ApiSanPham.php', { method: 'POST', body: fd }).then(r => r.json());
      if (res.status === 'success') {
        showToast('Đã xóa sản phẩm', 'success');
        loadProducts();
      } else {
        showToast(res.message, 'warn');
      }
    } catch (e) {
      showToast('Lỗi kết nối', 'warn');
    }
  }
