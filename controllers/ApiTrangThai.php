<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once '../model/Database.php';
require_once '../model/TrangThaiKe.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $trangThaiKe = new TrangThaiKe($db);
    $stmt = $trangThaiKe->getAll();
    $data = [];
    
 while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {

    if ($row['id_ngan'] == 4) {

        $stmtProducts = $trangThaiKe->getProductsInShelf(4);

        $product = $stmtProducts->fetch(PDO::FETCH_ASSOC);

        $row['san_pham'] = $product;
    }

    $data[] = $row;
}
    
    echo json_encode([
        "status" => "success",
        "data" => $data
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
}
?>
