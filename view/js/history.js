let historyData = [];
let currentHistoryPage = 1;
const historyItemsPerPage = 10;

async function loadHistorySummary() {
  try {
    const res = await fetch(API + 'ApiLichSu.php?action=summary').then(r => r.json());
    if (res.status === 'success') {
      const s = res.data;
      document.getElementById('historyStats').innerHTML = `
        <div class="stat-card"><div class="stat-icon green"><i class="bi bi-box-arrow-in-down"></i></div><div><div class="stat-val">${s.total_imports}</div><div class="stat-label">Tổng nhập kho</div></div></div>
        <div class="stat-card"><div class="stat-icon red"><i class="bi bi-box-arrow-up"></i></div><div><div class="stat-val">${s.total_exports}</div><div class="stat-label">Tổng xuất kho</div></div></div>
        <div class="stat-card"><div class="stat-icon green"><i class="bi bi-box-arrow-in-down"></i></div><div><div class="stat-val">${s.today_imports}</div><div class="stat-label">Nhập hôm nay</div></div></div>
        <div class="stat-card"><div class="stat-icon red"><i class="bi bi-box-arrow-up"></i></div><div><div class="stat-val">${s.today_exports}</div><div class="stat-label">Xuất hôm nay</div></div></div>`;
    }
  } catch (e) {}
}

async function loadHistory() {
  const filter = document.getElementById('historyFilter').value;
  const url = filter ? API + 'ApiLichSu.php?id_ngan=' + filter : API + 'ApiLichSu.php';
  try {
    const res = await fetch(url).then(r => r.json());
    if (res.status === 'success') {
      historyData = res.data;
      currentHistoryPage = 1;
      renderHistoryTable();
    } else {
      historyData = [];
      renderHistoryTable();
    }
  } catch (e) {
    showToast('Lỗi tải lịch sử', 'warn');
  }
}

function renderHistoryTable() {
  const tb = document.getElementById('historyTable');
  const p = document.getElementById('historyPagination');
  
  if (!historyData || historyData.length === 0) {
    tb.innerHTML = '<tr><td colspan="6"><div class="empty-state"><div class="icon"><i class="bi bi-journal-x"></i></div><p>Chưa có lịch sử</p></div></td></tr>';
    if(p) p.innerHTML = '';
    return;
  }

  const totalPages = Math.ceil(historyData.length / historyItemsPerPage);
  if (currentHistoryPage > totalPages) currentHistoryPage = totalPages;
  if (currentHistoryPage < 1) currentHistoryPage = 1;

  const start = (currentHistoryPage - 1) * historyItemsPerPage;
  const end = start + historyItemsPerPage;
  const pageData = historyData.slice(start, end);

  tb.innerHTML = pageData.map((h, i) => `
    <tr>
      <td>${start + i + 1}</td>
      <td><span class="tag ${h.hanh_dong === 'Nhập kho' ? 'nhap' : 'xuat'}">${h.hanh_dong}</span></td>
      <td>Ngăn #${h.id_ngan}</td>
      <td><code style="font-size:11px">${h.uid_the || '—'}</code></td>
      <td>${h.ten_sp || '—'}</td>
      <td style="font-size:12px;color:var(--text2)">${formatTime(h.thoi_gian)}</td>
    </tr>`).join('');

  if (totalPages > 1) {
    let html = `<button class="btn btn-sm btn-primary" ${currentHistoryPage === 1 ? 'disabled' : ''} onclick="changeHistoryPage(${currentHistoryPage - 1})"><i class="bi bi-chevron-left"></i> Trước</button>`;
    html += `<span style="font-size:13px;color:var(--text2);font-weight:600;">Trang ${currentHistoryPage} / ${totalPages}</span>`;
    html += `<button class="btn btn-sm btn-primary" ${currentHistoryPage === totalPages ? 'disabled' : ''} onclick="changeHistoryPage(${currentHistoryPage + 1})">Sau <i class="bi bi-chevron-right"></i></button>`;
    if(p) p.innerHTML = html;
  } else {
    if(p) p.innerHTML = '';
  }
}

function changeHistoryPage(page) {
  currentHistoryPage = page;
  renderHistoryTable();
}
