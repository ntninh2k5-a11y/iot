<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../model/Database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    $n1 = isset($_GET['n1']) ? (int)$_GET['n1'] : null;
    $n2 = isset($_GET['n2']) ? (int)$_GET['n2'] : null;
    $n3 = isset($_GET['n3']) ? (int)$_GET['n3'] : null;
    $n4 = isset($_GET['n4']) ? (int)$_GET['n4'] : null;
    $uid = isset($_GET['uid']) ? trim($_GET['uid']) : '';

    $sensors = [
        1 => $n1,
        2 => $n2,
        3 => $n3,
        4 => $n4
    ];

    $response = ["status" => "success", "message" => "Đã xử lý dữ liệu."];

    if ($uid !== '') {
        $stmt_scan = $db->prepare("INSERT INTO rfid_scan_log (uid) VALUES (?)");
        $stmt_scan->execute([$uid]);
    }

    foreach ($sensors as $id_ngan => $sensor_value) {
        if ($sensor_value === null) continue;

        $stmt = $db->prepare("SELECT trang_thai_hong_ngoai, ten_sp_quy_dinh FROM trang_thai_ke WHERE id_ngan = ?");
        $stmt->execute([$id_ngan]);
        $shelf = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$shelf) continue;

        $current_status = (int)$shelf['trang_thai_hong_ngoai'];

        // ===== NGĂN 4: có quét thẻ RFID -> nhập / xuất theo từng thẻ =====
        if ($id_ngan === 4 && $uid !== '') {
            $stmt_sp = $db->prepare("SELECT * FROM san_pham WHERE uid_the = ?");
            $stmt_sp->execute([$uid]);
            $product = $stmt_sp->fetch(PDO::FETCH_ASSOC);

            $ten_sp_quy_dinh = $shelf['ten_sp_quy_dinh'];

            // Kiểm tra: lần trước thẻ này vừa bị IR tự động xuất (chưa quét) trong 10 giây?
            $id_log_chua_quet = false;
            if ($product) {
                $stmt_cq = $db->prepare("SELECT id_log FROM lich_su_kho
                    WHERE uid_the = ? AND hanh_dong = 'Xuất kho' AND ghi_chu = 'Chưa quét RFID'
                    AND thoi_gian >= (NOW() - INTERVAL 10 SECOND)
                    ORDER BY id_log DESC LIMIT 1");
                $stmt_cq->execute([$uid]);
                $id_log_chua_quet = $stmt_cq->fetchColumn();
            }

            if (!$product) {
                // 1. Thẻ chưa đăng ký
                $response = ["status" => "error", "message" => "UID $uid chưa được đăng ký trong hệ thống."];

            } else if ($ten_sp_quy_dinh && $product['ten_sp'] !== $ten_sp_quy_dinh) {
                // 2. Sai loại hàng: không tính vào ngăn, chỉ ghi lịch sử và đánh dấu sai quy định.
                //    Lần trước thẻ này "nhập" sai thì lần này là "xuất" sai.
                $stmt_last = $db->prepare("SELECT hanh_dong FROM lich_su_kho WHERE uid_the = ? AND sai_quy_dinh = 1 ORDER BY id_log DESC LIMIT 1");
                $stmt_last->execute([$uid]);
                $hanh_dong_sai = ($stmt_last->fetchColumn() === 'Nhập kho') ? 'Xuất kho' : 'Nhập kho';

                $stmt_err = $db->prepare("INSERT INTO lich_su_kho (hanh_dong, id_ngan, uid_the, sai_quy_dinh, ghi_chu) VALUES (?, ?, ?, 1, ?)");
                $stmt_err->execute([$hanh_dong_sai, $id_ngan, $uid, "Sai quy định: ngăn chỉ chứa $ten_sp_quy_dinh"]);

                $response = [
                    "status" => "error",
                    "message" => "SAI QUY ĐỊNH! Ngăn #4 chỉ chứa [{$ten_sp_quy_dinh}], sản phẩm [{$product['ten_sp']}] không được phép!"
                ];
                http_response_code(400);

            } else if ((int)$product['trang_thai_kho'] === 1) {
                // 3. Sản phẩm đang nằm trong ngăn -> quét để XUẤT
                $stmt_upd = $db->prepare("UPDATE san_pham SET trang_thai_kho = 0, id_ngan_hien_tai = NULL WHERE uid_the = ?");
                $stmt_upd->execute([$uid]);

                $stmt_log = $db->prepare("INSERT INTO lich_su_kho (hanh_dong, id_ngan, uid_the) VALUES ('Xuất kho', ?, ?)");
                $stmt_log->execute([$id_ngan, $uid]);

                $response["message"] = "Xuất kho thành công: " . $product['ten_sp'];

            } else if ($id_log_chua_quet) {
                // 4. IR đã báo trống và tự xuất trước, giờ mới quét thẻ -> chỉ xác nhận, KHÔNG nhập lại
                $stmt_xn = $db->prepare("UPDATE lich_su_kho SET ghi_chu = NULL WHERE id_log = ?");
                $stmt_xn->execute([$id_log_chua_quet]);

                $response["message"] = "Xuất kho thành công: " . $product['ten_sp'];

            } else {
                // 5. Quét để NHẬP (ngăn 4 không giới hạn số lượng)
                $stmt_upd = $db->prepare("UPDATE san_pham SET trang_thai_kho = 1, id_ngan_hien_tai = ? WHERE uid_the = ?");
                $stmt_upd->execute([$id_ngan, $uid]);

                $stmt_log = $db->prepare("INSERT INTO lich_su_kho (hanh_dong, id_ngan, uid_the) VALUES ('Nhập kho', ?, ?)");
                $stmt_log->execute([$id_ngan, $uid]);

                $stmt_count = $db->prepare("SELECT COUNT(*) FROM san_pham WHERE id_ngan_hien_tai = ? AND trang_thai_kho = 1");
                $stmt_count->execute([$id_ngan]);
                $so_luong = (int)$stmt_count->fetchColumn();

                $response["message"] = "Nhập kho thành công: " . $product['ten_sp'] . " (hiện có $so_luong sản phẩm)";
            }
        }

        // ===== Cập nhật cảm biến hồng ngoại khi trạng thái thay đổi =====
        if ($current_status !== $sensor_value) {
            $stmt_upd_ke = $db->prepare("UPDATE trang_thai_ke SET trang_thai_hong_ngoai = ?, thoi_gian_cap_nhat = CURRENT_TIMESTAMP WHERE id_ngan = ?");
            $stmt_upd_ke->execute([$sensor_value, $id_ngan]);

            if ($id_ngan !== 4) {
                // Ngăn 1-3: chỉ có IR -> 1 sang 0 là nhập, 0 sang 1 là xuất
                if ($current_status === 1 && $sensor_value === 0) {
                    $stmt_log = $db->prepare("INSERT INTO lich_su_kho (hanh_dong, id_ngan, uid_the) VALUES ('Nhập kho', ?, '')");
                    $stmt_log->execute([$id_ngan]);
                } else if ($current_status === 0 && $sensor_value === 1) {
                    $stmt_log = $db->prepare("INSERT INTO lich_su_kho (hanh_dong, id_ngan, uid_the) VALUES ('Xuất kho', ?, '')");
                    $stmt_log->execute([$id_ngan]);
                }
            } else if ($sensor_value === 1) {
                // Ngăn 4 báo TRỐNG: sản phẩm nào còn ghi trong ngăn thì chắc chắn đã bị lấy ra
                // mà chưa quét thẻ -> trừ số lượng và ghi chú "Chưa quét RFID"
                $stmt_con = $db->prepare("SELECT uid_the FROM san_pham WHERE id_ngan_hien_tai = ? AND trang_thai_kho = 1");
                $stmt_con->execute([$id_ngan]);
                $con_lai = $stmt_con->fetchAll(PDO::FETCH_COLUMN);

                foreach ($con_lai as $uid_con) {
                    $stmt_upd = $db->prepare("UPDATE san_pham SET trang_thai_kho = 0, id_ngan_hien_tai = NULL WHERE uid_the = ?");
                    $stmt_upd->execute([$uid_con]);

                    $stmt_log = $db->prepare("INSERT INTO lich_su_kho (hanh_dong, id_ngan, uid_the, ghi_chu) VALUES ('Xuất kho', ?, ?, 'Chưa quét RFID')");
                    $stmt_log->execute([$id_ngan, $uid_con]);
                }

                if (count($con_lai) > 0 && $uid === '') {
                    $response["message"] = "Ngăn #4 trống: đã tự động xuất " . count($con_lai) . " sản phẩm (chưa quét RFID).";
                }
            }
        }
    }

    echo json_encode($response);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>