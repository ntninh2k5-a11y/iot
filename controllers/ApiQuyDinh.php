<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../model/Database.php';
require_once '../model/TrangThaiKe.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $trangThaiKe = new TrangThaiKe($db);
    $method = $_SERVER['REQUEST_METHOD'];
    
    if ($method === 'POST') {
        $action = isset($_POST['action']) ? $_POST['action'] : '';
        $id_ngan = isset($_POST['id_ngan']) ? $_POST['id_ngan'] : '';
        $ten_sp = isset($_POST['ten_sp']) ? $_POST['ten_sp'] : '';
        
        if ($action === 'assign') {
            if ($trangThaiKe->updateTenSpQuyDinh($id_ngan, $ten_sp)) {
                echo json_encode(["status" => "success", "message" => "Chỉ định thành công."]);
            } else {
                echo json_encode(["status" => "error", "message" => "Chỉ định thất bại."]);
            }
        } else if ($action === 'clear') {
            if ($trangThaiKe->clearTenSpQuyDinh($id_ngan)) {
                echo json_encode(["status" => "success", "message" => "Bỏ chỉ định thành công."]);
            } else {
                echo json_encode(["status" => "error", "message" => "Bỏ chỉ định thất bại."]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Hành động không hợp lệ."]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Phương thức không hợp lệ."]);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
