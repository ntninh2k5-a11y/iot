document.querySelectorAll('.sidebar nav a').forEach(a => {
  a.addEventListener('click', e => {
    e.preventDefault();
    document.querySelectorAll('.sidebar nav a').forEach(x => x.classList.remove('active'));
    a.classList.add('active');
    
    const page = a.dataset.page;
    document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
    document.getElementById('page-' + page).classList.add('active');
    
    if (page === 'products') loadProducts();
    if (page === 'assign') loadAssignPage();
    if (page === 'history') { 
      loadHistorySummary(); 
      loadHistory(); 
    }
    if (page === 'settings') checkDbStatus();
  });
});

async function checkDbStatus() {
  try {
    const res = await fetch(API + 'ApiTrangThai.php').then(r => r.json());
    const dbElem = document.getElementById('settingsDb');
    dbElem.textContent = res.status === 'success' ? 'Kết nối OK' : 'Lỗi';
    dbElem.style.color = res.status === 'success' ? 'var(--green)' : 'var(--red)';
  } catch(e) {
    const dbElem = document.getElementById('settingsDb');
    dbElem.textContent = 'Mất kết nối';
    dbElem.style.color = 'var(--red)';
  }
}

loadDashboard();
setInterval(loadDashboard, 3000);
