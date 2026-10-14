<?php
class LichSuKho {
    private $conn;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function getAll($limit = 50) {
        $query = "SELECT l.*, s.ten_sp 
                  FROM lich_su_kho l 
                  LEFT JOIN san_pham s ON l.uid_the = s.uid_the 
                  ORDER BY l.thoi_gian DESC LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    public function getByNgan($id_ngan, $limit = 20) {
        $query = "SELECT l.*, s.ten_sp 
                  FROM lich_su_kho l 
                  LEFT JOIN san_pham s ON l.uid_the = s.uid_the 
                  WHERE l.id_ngan = :id_ngan 
                  ORDER BY l.thoi_gian DESC LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':id_ngan', $id_ngan);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    public function getByUid($uid, $limit = 20) {
        $query = "SELECT l.*, s.ten_sp 
                  FROM lich_su_kho l 
                  LEFT JOIN san_pham s ON l.uid_the = s.uid_the 
                  WHERE l.uid_the = :uid 
                  ORDER BY l.thoi_gian DESC LIMIT :limit";
        $stmt = $this->conn->prepare($query);
        $stmt->bindValue(':uid', $uid);
        $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt;
    }

    public function getSummary() {
        $queries = [
            "total_imports" => "SELECT COUNT(*) as count FROM lich_su_kho WHERE hanh_dong = 'Nhập kho' AND sai_quy_dinh = 0",
            "total_exports" => "SELECT COUNT(*) as count FROM lich_su_kho WHERE hanh_dong = 'Xuất kho' AND sai_quy_dinh = 0",
            "today_imports" => "SELECT COUNT(*) as count FROM lich_su_kho WHERE hanh_dong = 'Nhập kho' AND sai_quy_dinh = 0 AND DATE(thoi_gian) = CURDATE()",
            "today_exports" => "SELECT COUNT(*) as count FROM lich_su_kho WHERE hanh_dong = 'Xuất kho' AND sai_quy_dinh = 0 AND DATE(thoi_gian) = CURDATE()"
        ];
        
        $summary = [];
        foreach($queries as $key => $query) {
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $summary[$key] = $row['count'];
        }
        return $summary;
    }
}
?>
