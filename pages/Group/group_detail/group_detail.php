<?php
$data = include 'group_detail_controller.php';

// dùng extract để chuyển mảng thành biến (ví dụ: $data['group_name'] thành $group_name)
extract($data);
include 'group_detail_view.php';

