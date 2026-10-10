<?php
class TrangThaiKe {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll() {
        $query = "SELECT t.* FROM trang_thai_ke t ORDER BY t.id_ngan ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function getById($id) {
        $query = "SELECT t.* FROM trang_thai_ke t WHERE t.id_ngan = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id);
        $stmt->execute();
        return $stmt;
    }

    public function getProductsInShelf($id_ngan) {
        $query = "SELECT * FROM san_pham WHERE id_ngan_hien_tai = ? AND trang_thai_kho = 1 ORDER BY ngay_tao DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id_ngan);
        $stmt->execute();
        return $stmt;
    }

    public function updateTenSpQuyDinh($id_ngan, $ten_sp) {
        $query = "UPDATE trang_thai_ke SET ten_sp_quy_dinh = ? WHERE id_ngan = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $ten_sp);
        $stmt->bindParam(2, $id_ngan);
        return $stmt->execute();
    }

    public function clearTenSpQuyDinh($id_ngan) {
        $query = "UPDATE trang_thai_ke SET ten_sp_quy_dinh = NULL WHERE id_ngan = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $id_ngan);
        return $stmt->execute();
    }
}
?>
