<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../model/Database.php';
require_once '../model/LichSuKho.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $lichSuKho = new LichSuKho($db);
    
    $action = isset($_GET['action']) ? $_GET['action'] : '';
    
    if ($action === 'summary') {
        $summary = $lichSuKho->getSummary();
        echo json_encode(["status" => "success", "data" => $summary]);
        exit;
    }
    
    $id_ngan = isset($_GET['id_ngan']) ? $_GET['id_ngan'] : '';
    $uid = isset($_GET['uid']) ? $_GET['uid'] : '';
    
    if ($id_ngan !== '') {
        $stmt = $lichSuKho->getByNgan($id_ngan);
    } else if ($uid !== '') {
        $stmt = $lichSuKho->getByUid($uid);
    } else {
        $stmt = $lichSuKho->getAll();
    }
    
    $data = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $data[] = $row;
    }
    
    echo json_encode(["status" => "success", "data" => $data]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
