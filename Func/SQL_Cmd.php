<?php
    // Hàm dùng để thực hiện câu lệnh SELECT
    function SQL_Select($conn, $sql, $types = null, $params = []) {
        $stmt = $conn->prepare($sql);
        if ($types && !empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $data = $result->fetch_all(MYSQLI_ASSOC);
        // $data = [];
        // while ($row = $result->fetch_assoc()) {
        //     $data[] = $row;
        // }
        return $data;
    }
    // $types: chuỗi định dạng kiểu dữ liệu (vd: "is" - integer, string)
    // $params: mảng các giá trị tương ứng với kiểu dữ liệu trong $types


    // Hàm dùng để thực hiện câu lệnh INSERT, UPDATE, DELETE
    function SQL_Execute($conn, $sql, $types = null, $params = []) {
        $stmt = $conn->prepare($sql);
        if ($types && !empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        return $stmt->execute();
    }
?>