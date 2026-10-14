let prevShelfData = null;

// Ngăn có hàng hay không:
// - Ngăn 4 (RFID): dựa vào số lượng sản phẩm đã quét thẻ
// - Ngăn 1-3: dựa vào cảm biến hồng ngoại (0 = có hàng)
function coHang(s) {
  if (s.id_ngan == 4) return parseInt(s.so_luong || 0) > 0;
  return s.trang_thai_hong_ngoai == 0;
}

// Câu thông báo hết hàng: "Ngăn #4 đang hết sản phẩm hộp giấy"
function textHetHang(s) {
  return 'Ngăn #' + s.id_ngan + ' đang hết sản phẩm ' + s.ten_sp_quy_dinh;
}
let isLoadingDashboard = false;

async function loadDashboard() {
  if (isLoadingDashboard) return;
  isLoadingDashboard = true;

  try {
    const shelfRes = await fetch(API + 'ApiTrangThai.php').then(r => r.json()).catch(() => null);
    const summaryRes = await fetch(API + 'ApiLichSu.php?action=summary').then(r => r.json()).catch(() => null);

    if (!shelfRes || shelfRes.status !== 'success') {
      document.getElementById('sysStatus').className = 'dot off';
      document.getElementById('lastUpdate').textContent = 'Mất kết nối...';
      isLoadingDashboard = false;
      return;
    }

    const data = shelfRes.data;
    
    const occupied = data.filter(coHang).length;
    const empty = data.length - occupied;

    document.getElementById('statOccupied').textContent = occupied;
    document.getElementById('statEmpty').textContent = empty;

    if (prevShelfData) {
      for (var i = 0; i < data.length; i++) {
        var prev = prevShelfData[i];
        var curr = data[i];
        if (prev && curr && coHang(prev) != coHang(curr)) {
          if (!coHang(curr)) {
            showToast(curr.ten_sp_quy_dinh ? textHetHang(curr) : 'Ngăn kệ #' + curr.id_ngan + ' vừa HẾT HÀNG!', 'warn');
          } else {
            showToast('Ngăn kệ #' + curr.id_ngan + ' đã NHẬN HÀNG!', 'success');
          }
        }
      }
    }
    prevShelfData = JSON.parse(JSON.stringify(data));

    renderAlerts(data);
    renderShelves(data);

    if (summaryRes && summaryRes.status === 'success') {
      var s = summaryRes.data;
      var today = (parseInt(s.today_imports || 0) + parseInt(s.today_exports || 0));
      document.getElementById('statToday').textContent = today;
    }

    document.getElementById('sysStatus').className = 'dot on';
    document.getElementById('lastUpdate').textContent = 'Cập nhật: ' + new Date().toLocaleTimeString('vi-VN');
  } catch (e) {
    console.error('Dashboard error:', e);
    document.getElementById('sysStatus').className = 'dot off';
    document.getElementById('lastUpdate').textContent = 'Lỗi: ' + e.message;
  }

  isLoadingDashboard = false;
}

// Banner cảnh báo: ngăn nào đang hết hàng mà có quy định loại hàng
function renderAlerts(data) {
  var box = document.getElementById('alertBox');
  if (!box) return;
  var html = '';
  for (var i = 0; i < data.length; i++) {
    if (!coHang(data[i]) && data[i].ten_sp_quy_dinh) {
      html += '<div class="alert-item"><i class="bi bi-exclamation-triangle-fill"></i> ' + textHetHang(data[i]) + '</div>';
    }
  }
  box.innerHTML = html;
}

function renderShelves(data) {
  var grid = document.getElementById('shelfGrid');
  if (!grid || !data) return;

  var html = '';
  for (var i = 0; i < data.length; i++) {
    var s = data[i];
    var isRfid = s.id_ngan == 4;
    var isOccupied = coHang(s);
    var cls = isOccupied ? 'occupied' : 'empty';
    var statusText = isOccupied ? 'Có hàng' : 'Trống';
    var assignedName = s.ten_sp_quy_dinh || 'Chưa quy định';

    html += '<div class="shelf-card ' + cls + '" onclick="openShelfDetail(' + s.id_ngan + ')">' +
      (isRfid ? '<div class="rfid-tag"><i class="bi bi-broadcast"></i> RFID</div>' : '') +
      '<div class="shelf-header">' +
        '<div class="shelf-title"><span class="shelf-status-dot"></span>Ngăn Kệ #' + s.id_ngan + '</div>' +
        '<span class="shelf-badge">' + statusText + '</span>' +
      '</div>' +
      '<div class="shelf-info">' +
        '<div class="info-row"><span class="label">Quy định:</span><span class="value">' + assignedName + '</span></div>' +
        (isRfid ? '<div class="info-row"><span class="label">Số lượng:</span><span class="value">' + (s.so_luong || 0) + '</span></div>' : '') +
        '<div class="info-row"><span class="label">Cập nhật:</span><span class="value" style="font-size:11px">' + formatTime(s.thoi_gian_cap_nhat) + '</span></div>' +
      '</div>' +
    '</div>';
  }
  grid.innerHTML = html;
}

async function openShelfDetail(id) {
  try {
    var shelfRes = await fetch(API + 'ApiTrangThai.php').then(r => r.json()).catch(() => null);

    var shelf = null;
    if (shelfRes && shelfRes.status === 'success') {
      shelf = shelfRes.data.find(function(s) { return s.id_ngan == id; });
    }

    var isRfid = id == 4;
    var isOccupied = shelf ? coHang(shelf) : false;

    var html = '';

    html += '<div style="display:flex;gap:12px;margin-bottom:20px">' +
      '<div style="flex:1;background:var(--bg);border-radius:10px;padding:16px;text-align:center">' +
        '<div style="font-size:32px;margin-bottom:8px">' + (isOccupied ? '<i class="bi bi-box-fill text-success"></i>' : '<i class="bi bi-box text-danger"></i>') + '</div>' +
        '<div style="font-size:14px;font-weight:600;color:' + (isOccupied ? 'var(--green)' : 'var(--red)') + '">' + (isOccupied ? 'Có hàng' : 'Trống') + '</div>' +
      '</div>' +
      '<div style="flex:1;background:var(--bg);border-radius:10px;padding:16px;text-align:center">' +
        '<div style="font-size:32px;margin-bottom:8px">' + (isRfid ? '<i class="bi bi-broadcast"></i>' : '<i class="bi bi-record-circle text-danger"></i>') + '</div>' +
        '<div style="font-size:14px;font-weight:600">' + (isRfid ? 'Có RFID' : 'Hồng ngoại') + '</div>' +
      '</div>' +
    '</div>';

    if (isRfid && shelf) {
      html += '<div style="background:var(--bg);border-radius:10px;padding:16px;margin-bottom:20px">' +
        '<h3 style="font-size:14px;margin-bottom:12px;color:var(--accent)"><i class="bi bi-info-square"></i> Thông tin ngăn kệ</h3>' +
        '<div style="display:grid;gap:8px;font-size:13px">' +
          '<div style="display:flex;justify-content:space-between"><span style="color:var(--text2)">Quy định nhóm SP:</span><span style="font-weight:600;color:var(--green)">' + (shelf.ten_sp_quy_dinh || 'Chưa quy định') + '</span></div>' +
          '<div style="display:flex;justify-content:space-between"><span style="color:var(--text2)">Số lượng hiện tại:</span><span style="font-weight:600">' + (shelf.so_luong || 0) + '</span></div>' +
        '</div>' +
      '</div>';

      if (shelf.san_pham_trong_ke && shelf.san_pham_trong_ke.length > 0) {
        html += '<div style="background:var(--bg);border-radius:10px;padding:16px;margin-bottom:20px">' +
          '<h3 style="font-size:14px;margin-bottom:12px;color:var(--accent)"><i class="bi bi-tags"></i> Sản phẩm trong ngăn</h3>' +
          '<div style="display:grid;gap:6px">';
        for (var j = 0; j < shelf.san_pham_trong_ke.length; j++) {
          var p = shelf.san_pham_trong_ke[j];
          html += '<div style="display:flex;justify-content:space-between;font-size:13px;padding:6px 0;border-bottom:1px solid var(--border)">' +
            '<span>' + p.ten_sp + '</span>' +
            '<code style="font-size:11px;color:var(--text2)">' + p.uid_the + '</code>' +
          '</div>';
        }
        html += '</div></div>';
      } else {
        html += '<div style="background:var(--bg);border-radius:10px;padding:16px;margin-bottom:20px;font-size:13px;color:var(--red)">' +
          (shelf.ten_sp_quy_dinh ? textHetHang(shelf) : 'Ngăn đang trống') + '</div>';
      }
    } else if (shelf) {
      html += '<div style="background:var(--bg);border-radius:10px;padding:16px;margin-bottom:20px">' +
        '<h3 style="font-size:14px;margin-bottom:12px;color:var(--accent)"><i class="bi bi-info-square"></i> Thông tin ngăn kệ</h3>' +
        '<div style="display:grid;gap:8px;font-size:13px">' +
          '<div style="display:flex;justify-content:space-between"><span style="color:var(--text2)">Quy định SP:</span><span>' + (shelf.ten_sp_quy_dinh || 'Chưa quy định') + '</span></div>' +
          '<div style="display:flex;justify-content:space-between"><span style="color:var(--text2)">Cảm biến:</span><span>Hồng ngoại</span></div>' +
          '<div style="display:flex;justify-content:space-between"><span style="color:var(--text2)">Cập nhật:</span><span>' + formatTime(shelf.thoi_gian_cap_nhat) + '</span></div>' +
        '</div>' +
      '</div>';
    }

    openModal('Ngăn Kệ #' + id + (id == 4 ? ' (RFID)' : ''), html);
  } catch (e) {
    console.error('Shelf detail error:', e);
  }
}
