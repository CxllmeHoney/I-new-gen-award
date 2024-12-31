<?php
// admin_login.php

session_start();

// ตรวจสอบถ้าผู้ใช้ล็อกอินแล้ว, รีไดเรกไปยัง Dashboard
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: admin_dashboard.php");
    exit;
}

// กำหนดค่าผู้ใช้และรหัสผ่าน (ในตัวอย่างนี้เป็นแบบง่าย, ควรใช้ระบบจัดการรหัสผ่านที่ปลอดภัยมากขึ้น)
$admin_username = "admin";
$admin_password = "admin123"; // เปลี่ยนเป็นรหัสผ่านที่ปลอดภัย

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = isset($_POST['username']) ? trim($_POST['username']) : '';
    $password = isset($_POST['password']) ? $_POST['password'] : '';

    if ($username === $admin_username && $password === $admin_password) {
        // ตั้งค่าการล็อกอินในเซสชัน
        $_SESSION['admin_logged_in'] = true;
        header("Location: admin_dashboard.php");
        exit;
    } else {
        $message = "ชื่อผู้ใช้หรือรหัสผ่านไม่ถูกต้อง.";
    }
}
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login - AI ตรวจจับช้างป่า</title>
    <!-- รวม Tailwind CSS ผ่าน CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <style>
        body {
            font-family: 'Prompt', sans-serif;
        }
    </style>
</head>
<body class="bg-gray-100 flex items-center justify-center h-screen">
    <div class="bg-white p-8 rounded-lg shadow-lg w-96">
        <h2 class="text-2xl font-semibold text-gray-700 mb-6 text-center">Admin Login</h2>
        <?php if (!empty($message)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="admin_login.php">
            <div class="mb-4">
                <label class="block text-gray-700 mb-2" for="username">Username</label>
                <input type="text" id="username" name="username" required class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-300">
            </div>
            <div class="mb-6">
                <label class="block text-gray-700 mb-2" for="password">Password</label>
                <input type="password" id="password" name="password" required class="w-full px-3 py-2 border rounded focus:outline-none focus:ring focus:border-blue-300">
            </div>
            <button type="submit" class="w-full bg-blue-500 text-white py-2 rounded hover:bg-blue-600">Login</button>
        </form>
    </div>
</body>
</html>
