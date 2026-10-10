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

        if ($id_ngan === 4 && $uid !== '') {
            $stmt_sp = $db->prepare("SELECT * FROM san_pham WHERE uid_the = ?");
            $stmt_sp->execute([$uid]);
            $product = $stmt_sp->fetch(PDO::FETCH_ASSOC);

            if (!$product) {
                $response = ["status" => "error", "message" => "UID $uid chưa được đăng ký trong hệ thống."];
                echo json_encode($response);
                exit;
            }

            $ten_sp_quy_dinh = $shelf['ten_sp_quy_dinh'];
            if ($ten_sp_quy_dinh && $product['ten_sp'] !== $ten_sp_quy_dinh) {
                $stmt_err = $db->prepare("INSERT INTO lich_su_kho (hanh_dong, id_ngan, uid_the) VALUES ('Sai quy định', ?, ?)");
                $stmt_err->execute([$id_ngan, $uid]);

                $response = [
                    "status" => "error", 
                    "message" => "SAI QUY ĐỊNH! Ngăn #4 chỉ chứa [{$ten_sp_quy_dinh}], sản phẩm [{$product['ten_sp']}] không được phép!"
                ];
                http_response_code(400);
                echo json_encode($response);
                exit;
            }

            $trang_thai_hien_tai = (int)$product['trang_thai_kho'];

            if ($trang_thai_hien_tai === 0) {
                $stmt_count = $db->prepare("SELECT COUNT(*) FROM san_pham WHERE id_ngan_hien_tai = ? AND trang_thai_kho = 1");
                $stmt_count->execute([$id_ngan]);
                $so_luong = (int)$stmt_count->fetchColumn();

                if ($so_luong >= 1) {
                    $response = [
                        "status" => "error",
                        "message" => "Ngăn #$id_ngan đã đầy! Chỉ chứa tối đa 1 sản phẩm."
                    ];
                    http_response_code(400);
                    echo json_encode($response);
                    exit;
                }

                $stmt_upd = $db->prepare("UPDATE san_pham SET trang_thai_kho = 1, id_ngan_hien_tai = ? WHERE uid_the = ?");
                $stmt_upd->execute([$id_ngan, $uid]);

                $stmt_log = $db->prepare("INSERT INTO lich_su_kho (hanh_dong, id_ngan, uid_the) VALUES ('Nhập kho', ?, ?)");
                $stmt_log->execute([$id_ngan, $uid]);

                $response["message"] = "Nhập kho thành công: " . $product['ten_sp'];
            } else {
                $stmt_upd = $db->prepare("UPDATE san_pham SET trang_thai_kho = 0, id_ngan_hien_tai = NULL WHERE uid_the = ?");
                $stmt_upd->execute([$uid]);

                $stmt_log = $db->prepare("INSERT INTO lich_su_kho (hanh_dong, id_ngan, uid_the) VALUES ('Xuất kho', ?, ?)");
                $stmt_log->execute([$id_ngan, $uid]);

                $response["message"] = "Xuất kho thành công: " . $product['ten_sp'];
            }
        }

        if ($current_status !== $sensor_value) {
            $stmt_upd_ke = $db->prepare("UPDATE trang_thai_ke SET trang_thai_hong_ngoai = ?, thoi_gian_cap_nhat = CURRENT_TIMESTAMP WHERE id_ngan = ?");
            $stmt_upd_ke->execute([$sensor_value, $id_ngan]);

            if ($id_ngan !== 4) {
                if ($current_status === 1 && $sensor_value === 0) {
                    $stmt_log = $db->prepare("INSERT INTO lich_su_kho (hanh_dong, id_ngan, uid_the) VALUES ('Nhập kho', ?, '')");
                    $stmt_log->execute([$id_ngan]);
                } else if ($current_status === 0 && $sensor_value === 1) {
                    $stmt_log = $db->prepare("INSERT INTO lich_su_kho (hanh_dong, id_ngan, uid_the) VALUES ('Xuất kho', ?, '')");
                    $stmt_log->execute([$id_ngan]);
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
