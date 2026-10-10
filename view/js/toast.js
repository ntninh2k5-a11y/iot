function showToast(msg, type = 'info') {
  const toggle = document.getElementById('notifToggle');
  if (toggle && !toggle.checked) return;
  const icons = { 
    warn: '<i class="bi bi-exclamation-triangle-fill"></i>', 
    info: '<i class="bi bi-info-circle-fill"></i>', 
    success: '<i class="bi bi-check-circle-fill"></i>' 
  };
  const c = document.getElementById('toastContainer');
  if (!c) return;
  if (c.children.length > 5) c.firstChild.remove();
  const t = document.createElement('div');
  t.className = 'toast ' + type;
  t.innerHTML = '<span class="toast-icon">' + (icons[type] || icons['info']) + '</span><span class="toast-msg">' + msg + '</span><span class="toast-close" onclick="this.parentElement.remove()">&times;</span>';
  c.appendChild(t);
  setTimeout(() => t.remove(), 5000);
}
