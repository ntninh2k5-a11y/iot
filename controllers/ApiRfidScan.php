<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../model/Database.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $action = isset($_GET['action']) ? $_GET['action'] : 'latest';

        if ($action === 'latest') {
            $stmt = $db->prepare("SELECT uid, thoi_gian FROM rfid_scan_log WHERE da_doc = 0 ORDER BY thoi_gian DESC");
            $stmt->execute();
            $data = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $data[] = $row;
            }

            $stmt2 = $db->prepare("UPDATE rfid_scan_log SET da_doc = 1 WHERE da_doc = 0");
            $stmt2->execute();

            echo json_encode(["status" => "success", "data" => $data]);
        } else if ($action === 'clear') {
            $db->prepare("DELETE FROM rfid_scan_log")->execute();
            echo json_encode(["status" => "success", "message" => "Đã xóa log."]);
        }
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
