<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../../module/config.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: login.php");
  exit;
}

$user_id = $_SESSION['user_id'];
$group_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$error_message = '';
$memberTotals = [];

$stmt = $conn->prepare("SELECT id, username, email, is_placeholder FROM Users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$current_user_data = $result->fetch_assoc();
$stmt->close();

// Lấy vai trò của người đang đăng nhập trong nhóm
$current_user_role = null;
$stmt = $conn->prepare("SELECT role FROM group_members WHERE group_id = ? AND user_id = ?");
$stmt->bind_param("ii", $group_id, $user_id);
$stmt->execute();
$stmt->bind_result($current_user_role);
$stmt->fetch();
$stmt->close();

if (!$current_user_role) {
  header("Location: /pages/Group/group.php");
  exit;
}

if ($group_id <= 0) {
  die("ID nhóm không hợp lệ.");
}

// Lấy thông tin nhóm
$stmt = $conn->prepare("SELECT g.name, g.group_code, g.created_by, u.username AS creator_name, g.created_at
                       FROM Groupss g
                       JOIN Users u ON g.created_by = u.id
                       WHERE g.id = ?");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$result = $stmt->get_result();
$group = $result->fetch_assoc();
$creator_id = $group['created_by'];
$group_code = $group['group_code'];
$stmt->close();

if (!$group) {
  die("Không tìm thấy nhóm.");
}


// Lấy thành viên
$stmt = $conn->prepare("SELECT u.id AS user_id, u.username, gm.role, gm.joined_at, u.fake_user_code
                       FROM group_members gm
                       JOIN Users u ON gm.user_id = u.id
                       WHERE gm.group_id = ?");

$stmt->bind_param("i", $group_id);
$stmt->execute();
$members_result = $stmt->get_result();
$members = $members_result->fetch_all(MYSQLI_ASSOC);
// Thứ tự ưu tiên vai trò: càng nhỏ càng ưu tiên trên
$rolePriority = [
  'owner' => 1,
  'owner_full' => 2,
  'manager' => 3,
  'member' => 4
];

usort($members, function ($a, $b) use ($rolePriority) {
  $roleA = $rolePriority[$a['role']] ?? 999;
  $roleB = $rolePriority[$b['role']] ?? 999;
  return $roleA - $roleB;
});

$stmt->close();

// Lấy giao dịch chia sẻ
$stmt = $conn->prepare("SELECT st.id, st.amount, st.description, st.date, u.username AS creator
                       FROM Shared_Transactions st
                       JOIN Users u ON st.created_by = u.id
                       WHERE st.group_id = ?
                       ORDER BY st.date DESC");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$transactions_result = $stmt->get_result();
$transactions = $transactions_result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
// Tính tổng số tiền mỗi thành viên đã chia
foreach ($members as $m) {
  $memberTotals[$m['user_id']] = 0;
}

$stmt = $conn->prepare("
  SELECT st.split_type, st.amount AS total_amount, sp.user_id, sp.share_amount
  FROM Shared_Transactions st
  JOIN Shared_Transaction_Participants sp ON st.id = sp.shared_transaction_id
  WHERE st.group_id = ?
");
$stmt->bind_param("i", $group_id);
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
  $uid = $row['user_id'];
  $amount = $row['share_amount'];
  $memberTotals[$uid] = ($memberTotals[$uid] ?? 0) + $amount;
}
$stmt->close();


// Xử lý cập nhật vai trò
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
  $edit_user_id = intval($_POST['user_id']);
  $new_role = $_POST['new_role'];

  // Kiểm tra quyền
  if (!in_array($current_user_role, ['owner', 'owner_full'])) {
    $error_message = " Bạn không có quyền thay đổi vai trò.";
  } else {
    // Không cho chỉnh sửa nếu user là placeholder
    $stmt = $conn->prepare("SELECT gm.role, u.is_placeholder FROM Group_Members gm
                            JOIN Users u ON gm.user_id = u.id
                            WHERE gm.group_id = ? AND gm.user_id = ?");
    $stmt->bind_param("ii", $group_id, $edit_user_id);
    $stmt->execute();
    $stmt->bind_result($target_role, $is_placeholder);
    if ($stmt->fetch()) {
      if ($is_placeholder) {
        $error_message = " Không thể thay đổi vai trò của người dùng ảo. Vui lòng yêu cầu họ tạo tài khoản.";
      } elseif ($current_user_role === 'owner_full' && $target_role === 'owner') {
        $error_message = " Bạn không thể chỉnh sửa người tạo nhóm.";
      }
    } else {
      $error_message = " Không tìm thấy thành viên.";
    }
    $stmt->close();
  }

  // Cập nhật nếu không có lỗi
  if (empty($error_message)) {
    $stmt = $conn->prepare("UPDATE Group_Members SET role = ? WHERE group_id = ? AND user_id = ?");
    $stmt->bind_param("sii", $new_role, $group_id, $edit_user_id);
    $stmt->execute();
    $stmt->close();

    header("Location: group_detail_view.php?id=$group_id");
    exit;
  }
}


// Xử lý thêm thành viên mới
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_member_inline'])) {
  $email = trim($_POST['email']);
  $role = $_POST['role'];

  // Tìm user theo email
  $stmt = $conn->prepare("SELECT id FROM Users WHERE email = ?");
  $stmt->bind_param("s", $email);
  $stmt->execute();
  $stmt->bind_result($new_user_id);
  $stmt->fetch();
  $stmt->close();

  // Nếu không tồn tại thì tạo user ảo
 if (!$new_user_id) {
    if ($role !== 'member') {
      $error_message = ' Người dùng chưa tạo, vui lòng tạo người dùng ảo bằng thành viên hoặc tạo tài khoản.';
    } else {
      $username_from_email = strstr($email, '@', true);
      $placeholderPassword = password_hash('1', PASSWORD_DEFAULT);

      // Tạo mã 6 số ngẫu nhiên và đảm bảo không trùng
      do {
        $fake_code = random_int(100000, 999999);
        $check = $conn->prepare("SELECT 1 FROM Users WHERE fake_user_code = ?");
        $check->bind_param("i", $fake_code);
        $check->execute();
        $check->store_result();
        $exists = $check->num_rows > 0;
        $check->close();
      } while ($exists);

      // Chèn user ảo
      $stmt = $conn->prepare("INSERT INTO Users (username, email, password, is_placeholder, fake_user_code) VALUES (?, ?, ?, TRUE, ?)");
      $stmt->bind_param("sssi", $username_from_email, $email, $placeholderPassword, $fake_code);
      $stmt->execute();
      $new_user_id = $conn->insert_id;
      $stmt->close();

    }
}


  // Thêm thành viên nếu chưa tồn tại
  if (empty($error_message)) {
    $stmt = $conn->prepare("SELECT 1 FROM Group_Members WHERE group_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $group_id, $new_user_id);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 0) {
      $stmt->close();

      $stmt = $conn->prepare("INSERT INTO Group_Members (group_id, user_id, role, joined_at) VALUES (?, ?, ?, NOW())");
      $stmt->bind_param("iis", $group_id, $new_user_id, $role);
      $stmt->execute();
      $stmt->close();
      // Cập nhật lại tất cả giao dịch chia đều
      $stmt = $conn->prepare("SELECT id, amount FROM Shared_Transactions WHERE group_id = ? AND split_type = 'equal'");
      $stmt->bind_param("i", $group_id);
      $stmt->execute();
      $result = $stmt->get_result();
      $equalTransactions = $result->fetch_all(MYSQLI_ASSOC);
      $stmt->close();

      // Đếm lại tổng thành viên mới
      $totalMembers = count($members) + 1; // vì $members chưa có người mới

      foreach ($equalTransactions as $tx) {
        $shareAmount = $tx['amount'] / $totalMembers;
        $transaction_id = $tx['id'];

        // Xóa chia cũ
        $conn->query("DELETE FROM Shared_Transaction_Participants WHERE shared_transaction_id = $transaction_id");

        // Chèn lại cho tất cả thành viên (kể cả người mới)
        $stmt = $conn->prepare("SELECT user_id FROM Group_Members WHERE group_id = ?");
        $stmt->bind_param("i", $group_id);
        $stmt->execute();
        $res = $stmt->get_result();
        $group_users = $res->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        foreach ($group_users as $gu) {
          $uid = $gu['user_id'];
          $stmt = $conn->prepare("INSERT INTO Shared_Transaction_Participants (shared_transaction_id, user_id, share_amount) VALUES (?, ?, ?)");
          $stmt->bind_param("iid", $transaction_id, $uid, $shareAmount);
          $stmt->execute();
          $stmt->close();
        }
      }

      header("Location: group_detail_view.php?id=$group_id");
      exit;
    } else {
      $stmt->close();
      $error_message = ' Email đã tồn tại trong nhóm.';
    }
  }
}

// Xử lý đổi tên nhóm
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['rename_group'])) {
  $new_name = trim($_POST['new_group_name']);
  if ($new_name !== '') {
    $stmt = $conn->prepare("UPDATE Groupss SET name = ? WHERE id = ?");
    $stmt->bind_param("si", $new_name, $group_id);
    $stmt->execute();
    $stmt->close();

    header("Location: group_detail.php?id=$group_id");
    exit;
  }
}

// Xử lý xoá nhóm
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_group'])) {
  // Xóa liên kết trong Group_Members
  $stmt = $conn->prepare("DELETE FROM Group_Members WHERE group_id = ?");
  $stmt->bind_param("i", $group_id);
  $stmt->execute();
  $stmt->close();

  // Xóa các bản ghi liên quan đến giao dịch chia sẻ
  $stmt = $conn->prepare("SELECT id FROM Shared_Transactions WHERE group_id = ?");
  $stmt->bind_param("i", $group_id);
  $stmt->execute();
  $result = $stmt->get_result();
  $tx_ids = $result->fetch_all(MYSQLI_ASSOC);
  $stmt->close();

  foreach ($tx_ids as $tx) {
    $conn->query("DELETE FROM Shared_Transaction_Participants WHERE shared_transaction_id = " . $tx['id']);
  }

  $stmt = $conn->prepare("DELETE FROM Shared_Transactions WHERE group_id = ?");
  $stmt->bind_param("i", $group_id);
  $stmt->execute();
  $stmt->close();

  // Xóa tin nhắn (nếu có)
  $conn->query("DELETE FROM Group_Chat WHERE group_id = $group_id");

  // Cuối cùng xoá nhóm
  $stmt = $conn->prepare("DELETE FROM Groupss WHERE id = ?");
  $stmt->bind_param("i", $group_id);
  $stmt->execute();
  $stmt->close();

  header("Location: ../group.php");
  exit;
}
return [
  'group' => $group,
  'members' => $members,
  'transactions' => $transactions,
  'current_user_role' => $current_user_role,
  'group_id' => $group_id,
  'error_message' => $error_message,
  'memberTotals' => $memberTotals,
  'user' => $current_user_data,
];
?>
