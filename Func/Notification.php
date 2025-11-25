<?php
// Thư viện hiển thị thông báo SweetAlert2
function Notification_SweetAlert2($status, $message_success, $message_failure) {
    if (!isset($_GET["$status"])) return;

    $isSuccess = $_GET["$status"] === 'success';
    $icon = $isSuccess ? 'success' : 'error';
    $title = $isSuccess ? "$message_success" : "$message_failure";

    echo "
    <script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script>
    <script>
        Swal.fire({
            icon: '$icon',
            title: '$title',
            toast: true,
            position: 'top',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            customClass: {
                popup: 'swal2-toast-custom'
            }
        });
    </script>
    <style>
        /* Màu nền nổi bật cho toast success/error */
        .swal2-toast-custom {
            background-color: " . ($isSuccess ? "#fff" : "#F44336") . " !important;
            color: black !important;
        }
    </style>
    ";
}

// Thư viện hiển thị thông báo IziToast
function Notification_IziToast($status, $message_success, $message_failure) {
    if (!isset($_GET["$status"])) return;

    $isSuccess = $_GET["$status"] === 'success';
    $title = $isSuccess ? 'Thành công' : 'Lỗi';
    $message = $isSuccess ? "$message_success" : "$message_failure";

    echo "
    <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/izitoast/dist/css/iziToast.min.css'>
    <script src='https://cdn.jsdelivr.net/npm/izitoast/dist/js/iziToast.min.js'></script>
    <script>
        iziToast." . ($isSuccess ? "success" : "error") . "({
            title: '$title',
            message: '$message',
            position: 'topCenter',
            timeout: 3000,
            progressBar: true
        });
    </script>
    ";
}

// Thư viện hiển thị thông báo Notyf
function Notification_Notyf($status, $message_success, $message_failure) { 
    if (!isset($_GET["$status"])) return;

    $isSuccess = $_GET["$status"] === 'success';
    $message = $isSuccess ? "$message_success" : "$message_failure";

    echo "
    <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.css'>
    <script src='https://cdn.jsdelivr.net/npm/notyf@3/notyf.min.js'></script>
    <script>
        const notyf = new Notyf({ duration: 3000, position: { x: 'center', y: 'top' } });
        notyf." . ($isSuccess ? "success" : "error") . "('$message');
    </script>
    ";
}
?>
