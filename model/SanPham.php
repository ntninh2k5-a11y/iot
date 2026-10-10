<?php
class SanPham {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll() {
        $query = "SELECT * FROM san_pham ORDER BY ngay_tao DESC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function getByUid($uid) {
        $query = "SELECT * FROM san_pham WHERE uid_the = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $uid);
        $stmt->execute();
        return $stmt;
    }

    public function create($uid, $ten_sp) {
        $query = "INSERT INTO san_pham (uid_the, ten_sp, trang_thai_kho) VALUES (?, ?, 0)";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $uid);
        $stmt->bindParam(2, $ten_sp);
        return $stmt->execute();
    }

    public function createBatch($uids, $ten_sp) {
        $query = "INSERT INTO san_pham (uid_the, ten_sp, trang_thai_kho) VALUES (?, ?, 0)";
        $stmt = $this->conn->prepare($query);
        $count = 0;
        foreach ($uids as $uid) {
            $uid = trim($uid);
            if ($uid === '') continue;
            $check = $this->conn->prepare("SELECT uid_the FROM san_pham WHERE uid_the = ?");
            $check->execute([$uid]);
            if ($check->rowCount() > 0) continue;
            $stmt->execute([$uid, $ten_sp]);
            $count++;
        }
        return $count;
    }

    public function update($uid, $ten_sp) {
        $query = "UPDATE san_pham SET ten_sp = ? WHERE uid_the = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $ten_sp);
        $stmt->bindParam(2, $uid);
        return $stmt->execute();
    }

    public function delete($uid) {
        $query = "DELETE FROM san_pham WHERE uid_the = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(1, $uid);
        return $stmt->execute();
    }

    public function countInWarehouse() {
        $query = "SELECT COUNT(*) as count FROM san_pham WHERE trang_thai_kho = 1";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row['count'];
    }

    public function getProductGroups() {
        $query = "SELECT ten_sp, COUNT(*) as total, 
                  SUM(CASE WHEN trang_thai_kho = 1 THEN 1 ELSE 0 END) as in_stock
                  FROM san_pham GROUP BY ten_sp ORDER BY ten_sp ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }

    public function getDistinctNames() {
        $query = "SELECT DISTINCT ten_sp FROM san_pham ORDER BY ten_sp ASC";
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        return $stmt;
    }
}
?>
