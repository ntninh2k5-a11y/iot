<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../model/Database.php';
require_once '../model/SanPham.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $sanPham = new SanPham($db);
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'GET') {
        $action = isset($_GET['action']) ? $_GET['action'] : '';

        if ($action === 'groups') {
            $stmt = $sanPham->getProductGroups();
            $data = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $data[] = $row;
            }
            echo json_encode(["status" => "success", "data" => $data]);
        } else if ($action === 'names') {
            $stmt = $sanPham->getDistinctNames();
            $data = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $data[] = $row['ten_sp'];
            }
            echo json_encode(["status" => "success", "data" => $data]);
        } else {
            $stmt = $sanPham->getAll();
            $data = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $data[] = $row;
            }
            echo json_encode(["status" => "success", "data" => $data]);
        }
    } else if ($method === 'POST') {
        $action = isset($_POST['action']) ? $_POST['action'] : '';
        $uid = isset($_POST['uid_the']) ? $_POST['uid_the'] : '';
        $ten_sp = isset($_POST['ten_sp']) ? $_POST['ten_sp'] : '';
        
        if ($action === 'create') {
            if ($sanPham->create($uid, $ten_sp)) {
                echo json_encode(["status" => "success", "message" => "Tạo sản phẩm thành công."]);
            } else {
                echo json_encode(["status" => "error", "message" => "Tạo thất bại."]);
            }
        } else if ($action === 'create_batch') {
            $uids_raw = isset($_POST['uids']) ? $_POST['uids'] : '';
            $uids = array_filter(array_map('trim', explode(',', $uids_raw)));
            if (empty($uids) || $ten_sp === '') {
                echo json_encode(["status" => "error", "message" => "Thiếu dữ liệu."]);
            } else {
                $count = $sanPham->createBatch($uids, $ten_sp);
                echo json_encode(["status" => "success", "message" => "Đã thêm $count sản phẩm."]);
            }
        } else if ($action === 'update') {
            if ($sanPham->update($uid, $ten_sp)) {
                echo json_encode(["status" => "success", "message" => "Cập nhật thành công."]);
            } else {
                echo json_encode(["status" => "error", "message" => "Cập nhật thất bại."]);
            }
        } else if ($action === 'delete') {
            if ($sanPham->delete($uid)) {
                echo json_encode(["status" => "success", "message" => "Xóa thành công."]);
            } else {
                echo json_encode(["status" => "error", "message" => "Xóa thất bại."]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Hành động không hợp lệ."]);
        }
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
