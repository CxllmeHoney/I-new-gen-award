<?php
// admin_dashboard.php

// เปิดการแสดงข้อผิดพลาด (สำหรับการ Debug) ควรปิดในสภาพแวดล้อมการผลิต
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

// ตรวจสอบสิทธิ์การเข้าถึง (ต้องเป็น Admin เท่านั้น)
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: admin_login.php");
    exit;
}

header('Content-Type: text/html; charset=utf-8');

// รวมไฟล์เชื่อมต่อฐานข้อมูลโดยใช้ require_once และ __DIR__
include '../elephant_api/db.php';

// ตรวจสอบว่า $conn ถูกกำหนดและเป็น instance ของ mysqli
if (!isset($conn) || !$conn instanceof mysqli) {
    die(json_encode([
        "status" => "error",
        "message" => "Database connection is not established."
    ]));
}

// ฟังก์ชันช่วยเหลือเพื่อป้องกันการส่งค่า null ให้กับ htmlspecialchars
function safe_htmlspecialchars($string) {
    return htmlspecialchars($string ?? '', ENT_QUOTES, 'UTF-8');
}

// ตั้งค่าการแสดงข้อมูลในตาราง
$perPage = 5;  // จำนวนข้อมูลที่แสดงต่อหน้า
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? intval($_GET['page']) : 1;  // หน้าเริ่มต้น
$start = ($page - 1) * $perPage;  // จุดเริ่มต้นของการดึงข้อมูล

// คิวรีดึงข้อมูลจากทั้งสองตารางสำหรับตารางข้อมูล (มีการแบ่งหน้า)
$sql_detections = "SELECT detections.id, detections.lat_cam, detections.long_cam, detections.elephant, 
                        detections.lat_ele, detections.long_ele, detections.distance_ele, detections.alert,
                        images.timestamp, images.image_path
                   FROM detections
                   LEFT JOIN images ON detections.image_id = images.id
                   ORDER BY detections.id DESC LIMIT ?, ?";
$stmt_detections = $conn->prepare($sql_detections);
if (!$stmt_detections) {
    error_log("Prepare failed: " . $conn->error);
    die("Prepare failed for detections: " . $conn->error);
}
$stmt_detections->bind_param("ii", $start, $perPage);
$stmt_detections->execute();
$result_detections = $stmt_detections->get_result();

$markers = [];
$missing_coordinates = [];  // สำหรับเก็บการตรวจจับที่มีพิกัดเป็น null

// ตรวจสอบว่ามีผลลัพธ์หรือไม่
if ($result_detections && $result_detections->num_rows > 0) {
    while ($row = $result_detections->fetch_assoc()) {
        // ตรวจสอบว่า image_path มีค่าและไม่เป็น null
        if (!empty($row['image_path'])) {
            if (strpos($row['image_path'], 'uploads/') === 0) {
                $full_image_path = 'https://aprlabtop.com/elephant_api/' . $row['image_path'];
            } else {
                $full_image_path = 'https://aprlabtop.com/elephant_api/uploads/' . $row['image_path'];
            }
        } else {
            $full_image_path = ''; // ตั้งค่าเป็นค่าว่างถ้า image_path เป็น null หรือไม่มีค่า
        }

        // ตรวจสอบพิกัด
        $has_null_coords = false;
        if (is_null($row['lat_cam']) || is_null($row['long_cam']) || is_null($row['lat_ele']) || is_null($row['long_ele'])) {
            $has_null_coords = true;
            $missing_coordinates[] = [
                'id' => $row['id'],
                'timestamp' => $row['timestamp'],
                'lat_cam' => $row['lat_cam'],
                'long_cam' => $row['long_cam'],
                'lat_ele' => $row['lat_ele'],
                'long_ele' => $row['long_ele'],
                'distance_ele' => $row['distance_ele'],
                'image_path' => $full_image_path,
                'alert' => filter_var($row['alert'], FILTER_VALIDATE_BOOLEAN)
            ];
        }

        if (!$has_null_coords) {
            $markers[] = [
                'id' => $row['id'],
                'lat_cam' => $row['lat_cam'],
                'long_cam' => $row['long_cam'],
                'lat_ele' => $row['lat_ele'],
                'long_ele' => $row['long_ele'],
                'distance_ele' => $row['distance_ele'],
                'timestamp' => $row['timestamp'],
                'elephant' => filter_var($row['elephant'], FILTER_VALIDATE_BOOLEAN),
                'image_path' => $full_image_path,
                'alert' => filter_var($row['alert'], FILTER_VALIDATE_BOOLEAN)
            ];
        }
    }
}

// คำนวณจำนวนหน้าทั้งหมด
$sql_count = "SELECT COUNT(detections.id) AS total FROM detections";
$count_result = $conn->query($sql_count);
if ($count_result) {
    $total_rows = $count_result->fetch_assoc()['total'];
    $total_pages = ceil($total_rows / $perPage);
} else {
    $total_rows = 0;
    $total_pages = 1;
}

$stmt_detections->close();

// ข้อมูลที่จะแสดงบนแผนที่ (ทั้งหมดที่ไม่มีพิกัดเป็น null)
$markers_all = $markers;

// ตรวจสอบว่ามีผลลัพธ์หรือไม่
$sql_all = "SELECT detections.id, detections.lat_cam, detections.long_cam, detections.elephant, 
                detections.lat_ele, detections.long_ele, detections.distance_ele, detections.alert,
                images.timestamp, images.image_path
        FROM detections
        LEFT JOIN images ON detections.image_id = images.id
        ORDER BY detections.id DESC";
$result_all = $conn->query($sql_all);

if ($result_all && $result_all->num_rows > 0) {
    while ($row = $result_all->fetch_assoc()) {
        // ตรวจสอบว่า image_path มีค่าและไม่เป็น null
        if (!empty($row['image_path'])) {
            if (strpos($row['image_path'], 'uploads/') === 0) {
                $full_image_path = 'https://aprlabtop.com/elephant_api/' . $row['image_path'];
            } else {
                $full_image_path = 'https://aprlabtop.com/elephant_api/uploads/' . $row['image_path'];
            }
        } else {
            $full_image_path = '';
        }

        // ตรวจสอบพิกัด
        $has_null_coords = false;
        if (is_null($row['lat_cam']) || is_null($row['long_cam']) || is_null($row['lat_ele']) || is_null($row['long_ele'])) {
            $has_null_coords = true;
            // คุณสามารถเพิ่มข้อมูลนี้ไปยัง $missing_coordinates ได้เช่นกัน หากต้องการแสดงทั้งหมด
        }

        if (!$has_null_coords) {
            $markers_all[] = [
                'id' => $row['id'],
                'lat_cam' => $row['lat_cam'],
                'long_cam' => $row['long_cam'],
                'lat_ele' => $row['lat_ele'],
                'long_ele' => $row['long_ele'],
                'distance_ele' => $row['distance_ele'],
                'timestamp' => $row['timestamp'],
                'elephant' => filter_var($row['elephant'], FILTER_VALIDATE_BOOLEAN),
                'image_path' => !empty($row['image_path']) ? $full_image_path : '',
                'alert' => filter_var($row['alert'], FILTER_VALIDATE_BOOLEAN)
            ];
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - AI ตรวจจับช้างป่า</title>
    <!-- รวม Tailwind CSS ผ่าน CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <!-- Leaflet Control Geocoder CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.css" />
    <!-- Leaflet Routing Machine CSS -->
    <link rel="stylesheet" href="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.css" />
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet" />
    <style>
        /* ปรับปรุงการใช้ Tailwind กับ Leaflet */
        .leaflet-popup-content-wrapper {
            border-radius: 0.75rem; /* 12px */
            padding: 0.3125rem; /* 5px */
        }

        .leaflet-popup-content {
            padding: 0.625rem; /* 10px */
        }

        /* ซ่อน Leaflet Routing Machine Control */
        .leaflet-routing-container.leaflet-bar.leaflet-control {
            display: none !important;
        }

        /* ปรับแต่งไอคอนมาร์กเกอร์ */
        .custom-popup-content button {
            cursor: pointer;
        }

        /* Popup Styles */
        .popup {
            display: none;
            position: fixed;
            top: 80px; /* เพิ่มขึ้นจาก 60px เป็น 80px */
            right: 20px;
            padding: 15px 20px;
            border-radius: 0.5rem; /* 8px */
            color: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            z-index: 9999;
            min-width: 300px;
            opacity: 0;
            transform: translateY(-20px);
            transition: all 0.3s ease;
        }


        .popup.show {
            display: block;
            opacity: 1;
            transform: translateY(0);
        }

        .popup.bg-red-500 {
            background-color: #ef4444;  /* สีแดง */
        }

        .popup.bg-green-500 {
            background-color: #10b981;  /* สีเขียว */
        }

        /* การแจ้งเตือนที่หัวหน้าเว็บ */
        .alert-header {
            color: white;
            padding: 20px; /* เพิ่ม padding เพื่อให้พื้นที่มากขึ้น */
            font-size: 24px; /* เพิ่มขนาดตัวอักษร */
            font-weight: bold;
            text-align: center;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 9999;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, opacity 0.3s ease;
            height: 80px; /* กำหนดความสูงให้เพิ่มขึ้น */
            display: none; /* ซ่อนเริ่มต้น */
        }

        #headerAlertMessage {
            font-size: 28px; /* ขนาดข้อความภายใน */
        }

        #closeHeaderAlert {
            font-size: 32px; /* ปรับขนาดปุ่มปิด */
            padding: 10px 15px; /* เพิ่ม padding ให้ปุ่มปิด */
        }


        .alert-header.bg-red-500 {
            background-color: #ef4444;  /* สีแดง */
        }

        .alert-header.bg-green-500 {
            background-color: #10b981;  /* สีเขียว */
        }

        /* สีแถวที่เปลี่ยนเมื่อเจอช้าง */
        .highlighted-row {
            background-color: #ef4444;
            color: white;
        }

        /* Modal Styles */
        .modal {
            display: none; /* Hidden by default */
            position: fixed; /* Stay in place */
            z-index: 10000; /* Sit on top */
            left: 0;
            top: 0;
            width: 100%; /* Full width */
            height: 100%; /* Full height */
            overflow: auto; /* Enable scroll if needed */
            background-color: rgba(0,0,0,0.5); /* Black w/ opacity */
        }

        .modal-content {
            background-color: #fefefe;
            margin: 10% auto; /* 10% จากด้านบนและอยู่กึ่งกลาง */
            padding: 20px;
            border: 1px solid #888;
            width: 80%; /* สามารถปรับขนาดได้ตามต้องการ */
            max-width: 800px;
            border-radius: 0.5rem; /* 8px */
        }

        .modal-content img {
            width: 100%;
            height: auto;
            border-radius: 0.5rem; /* 8px */
        }

        .close-modal {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close-modal:hover,
        .close-modal:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

        /* Responsive Table */
        @media (max-width: 768px) {
            table, thead, tbody, th, td, tr {
                display: block;
            }

            thead tr {
                position: absolute;
                top: -9999px;
                left: -9999px;
            }

            tr {
                margin-bottom: 1rem;
            }

            td {
                border: none;
                position: relative;
                padding-left: 50%;
            }

            td::before {
                position: absolute;
                top: 0;
                left: 0;
                width: 45%;
                padding-left: 0.5rem;
                font-weight: bold;
                white-space: nowrap;
            }

            td:nth-of-type(1)::before { content: "ID"; }
            td:nth-of-type(2)::before { content: "Timestamp"; }
            td:nth-of-type(3)::before { content: "Camera Location"; }
            td:nth-of-type(4)::before { content: "Elephant Location"; }
            td:nth-of-type(5)::before { content: "Distance"; }
            td:nth-of-type(6)::before { content: "Image"; }
            td:nth-of-type(7)::before { content: "Actions"; }
        }

        /* ปรับปรุงการใช้ Tailwind กับ Leaflet */
        .leaflet-popup-content-wrapper {
            border-radius: 0.75rem; /* 12px */
            padding: 0.3125rem; /* 5px */
        }

        .leaflet-popup-content {
            padding: 0.625rem; /* 10px */
        }

        /* ซ่อน Leaflet Routing Machine Control */
        .leaflet-routing-container.leaflet-bar.leaflet-control {
            display: none !important;
        }

        /* ปรับแต่งไอคอนมาร์กเกอร์ */
        .custom-popup-content button {
            cursor: pointer;
        }

        /* Popup Styles */
        .popup {
            @apply hidden fixed top-20 right-5 px-5 py-4 rounded-lg text-white shadow-lg z-50 min-w-[300px] transition-transform duration-300;
            opacity: 0;
            transform: translateY(-20px);
        }

        .popup.show {
            @apply block opacity-100 transform translateY(0);
        }

        /* การแจ้งเตือนที่มุมขวาบน */
        .notification {
            @apply hidden fixed top-20 right-5 px-5 py-4 rounded-lg text-white shadow-lg z-50 min-w-[300px] transition-opacity duration-300;
            opacity: 0;
            transform: translateY(-20px);
        }

        .notification.show {
            @apply block opacity-100 transform translateY(0);
        }

        /* การแจ้งเตือนที่หัวหน้าเว็บ */
        .alert-header {
            @apply hidden bg-red-500 text-white py-2 px-4 text-lg font-bold text-center fixed top-0 left-0 right-0 z-50 shadow;
            transition: transform 0.3s ease, opacity 0.3s ease;
        }

        /* สีแถวที่เปลี่ยนเมื่อเจอช้าง */
        .highlighted-row {
            @apply bg-red-500 text-white;
        }

        /* Modal Styles */
        .modal {
            @apply hidden fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 z-50;
        }

        .modal-content {
            @apply bg-white rounded-lg shadow-lg p-6 w-11/12 max-w-3xl relative;
        }

        .modal-content img {
            @apply w-full h-auto rounded-lg;
        }

        .close-modal {
            @apply absolute top-3 right-3 text-gray-500 hover:text-gray-700 text-2xl cursor-pointer;
        }

        /* Responsive Table */
        @media (max-width: 768px) {
            table, thead, tbody, th, td, tr {
                display: block;
            }

            thead tr {
                @apply absolute -top-full -left-full;
            }

            tr {
                @apply mb-4;
            }

            td {
                @apply border-none relative px-4 py-2;
                padding-left: 50%;
            }

            td::before {
                @apply absolute left-0 top-0 bg-gray-200 text-gray-700 font-semibold px-2 py-1 rounded-l;
                width: 45%;
                content: attr(data-label);
            }
        }
    </style>
</head>
<body class="bg-white font-sans antialiased text-gray-800">

<div class="flex h-screen">
    <!-- Sidebar -->
    <div class="w-64 bg-white border-r border-gray-200 fixed inset-y-0 left-0 transform -translate-x-full md:translate-x-0 transition-transform duration-200 ease-in-out z-50 sidebar">
        <div class="p-6">
            <h2 class="text-2xl font-semibold mb-6 text-gray-700">Admin Menu</h2>
            <ul class="space-y-4">
                <li><a href="admin_dashboard.php" class="flex items-center p-2 rounded hover:bg-gray-100 transition-colors"><i class="fas fa-tachometer-alt mr-3 text-gray-600"></i> Dashboard</a></li>
                <li><a href="manage_images.php" class="flex items-center p-2 rounded hover:bg-gray-100 transition-colors"><i class="fas fa-images mr-3 text-gray-600"></i> Manage Images</a></li>
                <li><a href="mapLocation.php" class="flex items-center p-2 rounded hover:bg-gray-100 transition-colors"><i class="fas fa-map-marked-alt mr-3 text-gray-600"></i> Map</a></li>
                <li><a href="settings.php" class="flex items-center p-2 rounded hover:bg-gray-100 transition-colors"><i class="fas fa-cogs mr-3 text-gray-600"></i> Settings</a></li>
                <li><a href="admin_logout.php" class="flex items-center p-2 rounded hover:bg-gray-100 transition-colors"><i class="fas fa-sign-out-alt mr-3 text-gray-600"></i> Logout</a></li>
            </ul>
        </div>
    </div>

    <!-- Main Content -->
    <div class="flex-1 ml-0 md:ml-64">
        <div class="container mx-auto py-6 px-4">
            <!-- Header Section -->
            <header class="flex justify-between items-center mb-6">
                <h1 class="text-4xl font-bold text-gray-800">Admin Dashboard</h1>
                <!-- Toggle Button for Sidebar (visible on small screens) -->
                <button id="sidebarToggle" class="md:hidden text-gray-700 focus:outline-none">
                    <i class="fas fa-bars fa-2x"></i>
                </button>
            </header>

            <!-- Alert Header (เพิ่ม HTML นี้เพื่อแสดงการแจ้งเตือน) -->
            <div id="headerAlert" class="alert-header">
                <span id="headerAlertMessage"></span>
                <button id="closeHeaderAlert" class="ml-4 px-2 py-1 rounded bg-opacity-50">✕</button>
            </div>

            <!-- Notification for Alerts (Top-Right) -->
            <div id="notification" class="notification">
                <span id="notificationMessage"></span>
                <button id="closeNotification" class="ml-4 px-2 py-1 rounded bg-opacity-50">✕</button>
            </div>

            <!-- Popup for Notifications -->
            <div id="animalPopup" class="popup">
                <span id="popupMessage"></span>
                <button id="closePopup" class="ml-4 px-2 py-1 rounded bg-opacity-50">✕</button>
            </div>

            <!-- Modal for Viewing Images -->
            <div id="imageModal" class="modal">
                <div class="modal-content">
                    <span class="close-modal">&times;</span>
                    <img id="modalImage" src="" alt="Detection Image">
                </div>
            </div>

            <!-- Detections Table -->
            <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
                <h2 class="text-2xl font-semibold text-gray-700 mb-4">Detection Data</h2>

                <!-- Table for Detections -->
                <div class="overflow-x-auto">
                    <table class="min-w-full table-auto border border-gray-200">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-2 text-left text-gray-600 font-semibold">ID</th>
                                <th class="px-4 py-2 text-left text-gray-600 font-semibold">Timestamp</th>
                                <th class="px-4 py-2 text-left text-gray-600 font-semibold">Camera Location</th>
                                <th class="px-4 py-2 text-left text-gray-600 font-semibold">Elephant Location</th>
                                <th class="px-4 py-2 text-left text-gray-600 font-semibold">Distance</th>
                                <th class="px-4 py-2 text-left text-gray-600 font-semibold">Image</th>
                                <th class="px-4 py-2 text-left text-gray-600 font-semibold">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="detection-table-body">
                            <?php if (count($markers) > 0 || count($missing_coordinates) > 0): ?>
                                <?php foreach ($markers as $marker): ?>
                                    <tr id="row-<?= safe_htmlspecialchars($marker['id']) ?>" class="<?= $marker['elephant'] ? 'highlighted-row' : ($marker['alert'] ? 'bg-green-100' : 'bg-white') ?>">
                                        <td class="border px-4 py-2" data-label="ID"><?= safe_htmlspecialchars($marker['id']) ?></td>
                                        <td class="border px-4 py-2" data-label="Timestamp"><?= safe_htmlspecialchars($marker['timestamp']) ?></td>
                                        <td class="border px-4 py-2" data-label="Camera Location"><?= safe_htmlspecialchars($marker['lat_cam']) ?>, <?= safe_htmlspecialchars($marker['long_cam']) ?></td>
                                        <td class="border px-4 py-2" data-label="Elephant Location"><?= safe_htmlspecialchars($marker['lat_ele']) ?>, <?= safe_htmlspecialchars($marker['long_ele']) ?></td>
                                        <td class="border px-4 py-2" data-label="Distance"><?= safe_htmlspecialchars($marker['distance_ele']) ?> m</td>
                                        <td class="border px-4 py-2" data-label="Image">
                                            <?php if (!empty($marker['image_path'])): ?>
                                                <button onclick="openImageModal('<?= safe_htmlspecialchars($marker['image_path']) ?>')" class="bg-purple-500 text-white px-3 py-1 rounded hover:bg-purple-600 transition-colors">View Image</button>
                                            <?php else: ?>
                                                <span class="text-gray-500">No Image</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="border px-4 py-2" data-label="Actions">
                                            <button onclick="focusOnMarker(<?= safe_htmlspecialchars($marker['id']) ?>, 'cam')" class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600 transition-colors">Focus Camera</button>
                                            <?php if ($marker['elephant']): ?>
                                                <button onclick="focusOnMarker(<?= safe_htmlspecialchars($marker['id']) ?>, 'ele')" class="bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600 transition-colors">Focus Elephant</button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>

                                <?php foreach ($missing_coordinates as $marker): ?>
                                    <tr id="row-<?= safe_htmlspecialchars($marker['id']) ?>" class="bg-yellow-100 text-yellow-800">
                                        <td class="border px-4 py-2" data-label="ID"><?= safe_htmlspecialchars($marker['id']) ?></td>
                                        <td class="border px-4 py-2" data-label="Timestamp"><?= safe_htmlspecialchars($marker['timestamp']) ?></td>
                                        <td class="border px-4 py-2" data-label="Camera Location">
                                            <?php if (is_null($marker['lat_cam']) || is_null($marker['long_cam'])): ?>
                                                <span class="text-red-800">Camera coordinates missing</span>
                                            <?php else: ?>
                                                <?= safe_htmlspecialchars($marker['lat_cam']) ?>, <?= safe_htmlspecialchars($marker['long_cam']) ?>
                                            <?php endif; ?>
                                        </td>
                                        <td class="border px-4 py-2" data-label="Elephant Location">
                                            <?php if (is_null($marker['lat_ele']) || is_null($marker['long_ele'])): ?>
                                                <span class="text-red-800">Elephant coordinates missing</span>
                                            <?php else: ?>
                                                <?= safe_htmlspecialchars($marker['lat_ele']) ?>, <?= safe_htmlspecialchars($marker['long_ele']) ?>
                                            <?php endif; ?>
                                        </td>
                                        <td class="border px-4 py-2" data-label="Distance"><?= safe_htmlspecialchars($marker['distance_ele']) ?> m</td>
                                        <td class="border px-4 py-2" data-label="Image">
                                            <?php if (!empty($marker['image_path'])): ?>
                                                <button onclick="openImageModal('<?= safe_htmlspecialchars($marker['image_path']) ?>')" class="bg-purple-500 text-white px-3 py-1 rounded hover:bg-purple-600 transition-colors">View Image</button>
                                            <?php else: ?>
                                                <span class="text-gray-500">No Image</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="border px-4 py-2" data-label="Actions">
                                            <button onclick="focusOnMarker(<?= safe_htmlspecialchars($marker['id']) ?>, 'cam')" class="bg-blue-500 text-white px-3 py-1 rounded hover:bg-blue-600 transition-colors">Focus Camera</button>
                                            <?php if ($marker['elephant']): ?>
                                                <button onclick="focusOnMarker(<?= safe_htmlspecialchars($marker['id']) ?>, 'ele')" class="bg-green-500 text-white px-3 py-1 rounded hover:bg-green-600 transition-colors">Focus Elephant</button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="border px-4 py-2 text-center">No data available.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="flex justify-between items-center mt-6">
                    <div>
                        <span class="text-gray-600">Page <?= safe_htmlspecialchars($page) ?> of <?= safe_htmlspecialchars($total_pages) ?></span>
                    </div>
                    <div class="flex space-x-2">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?= safe_htmlspecialchars($page - 1) ?>" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 transition-colors">Prev</a>
                        <?php endif; ?>
                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?= safe_htmlspecialchars($page + 1) ?>" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600 transition-colors">Next</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Map Section -->
            <div class="bg-white rounded-lg shadow-lg p-6">
                <h2 class="text-2xl font-semibold text-gray-700 mb-4">Live Map</h2>
                <div id="mapid" class="w-full h-96 rounded-lg shadow-md"></div>
            </div>
        </div>
    </div>
</div>

<!-- Leaflet JS -->
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<!-- Leaflet Control Geocoder JS -->
<script src="https://unpkg.com/leaflet-control-geocoder/dist/Control.Geocoder.js"></script>
<!-- Leaflet Routing Machine JS -->
<script src="https://unpkg.com/leaflet-routing-machine/dist/leaflet-routing-machine.js"></script>

<script>
    const cameraIcon = L.icon({
        iconUrl: 'https://cdn-icons-png.flaticon.com/512/45/45010.png', // ไอคอนกล้อง
        iconSize: [30, 30], // ขนาดไอคอน
        iconAnchor: [15, 30], // จุดที่เชื่อมต่อกับตำแหน่ง
        popupAnchor: [0, -30], // จุดที่เปิด popup
    });

    const elephantIcon = L.icon({
        iconUrl: 'https://cdn-icons-png.flaticon.com/512/107/107831.png', // ไอคอนช้าง
        iconSize: [30, 30],
        iconAnchor: [15, 30],
        popupAnchor: [0, -30],
    });

    // Markers data from PHP
    let markersData = <?php echo json_encode($markers, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    let missingCoordinatesData = <?php echo json_encode($missing_coordinates, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
    const markersObject = {}; // Object สำหรับเก็บมาร์กเกอร์ด้วย ID

    let mymap;

    // ฟังก์ชันช่วยเหลือเพื่อ escape HTML ใน JavaScript
    function safe_htmlspecialchars(str) {
        if (typeof str !== 'string') {
            return '';
        }
        return str.replace(/&/g, "&amp;")
                  .replace(/</g, "&lt;")
                  .replace(/>/g, "&gt;")
                  .replace(/"/g, "&quot;")
                  .replace(/'/g, "&#039;");
    }

    // Initialize Map
    function initializeMap() {
        const initialLat = 14.439606;
        const initialLong = 101.372359;
        const initialView = [initialLat, initialLong];

        mymap = L.map("mapid").setView(initialView, 13);
        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            maxZoom: 19,
            attribution: "© OpenStreetMap contributors",
        }).addTo(mymap);

        // เพิ่มมาร์กเกอร์จากข้อมูลเริ่มต้น
        markersData.forEach(marker => addMarkerToMap(marker));
    }

    // ฟังก์ชันเพิ่มมาร์กเกอร์ลงแผนที่
    function addMarkerToMap(marker) {
        // ตรวจสอบว่าพิกัดไม่เป็น null หรือไม่
        if (marker.lat_cam !== null && marker.long_cam !== null) {
            // มาร์กเกอร์กล้อง
            const camMarker = L.marker([marker.lat_cam, marker.long_cam], { icon: cameraIcon }).addTo(mymap);
            camMarker.bindPopup(`
                <div class="popup-content">
                    <h3 class="text-blue-500">กล้อง CCTV #${safe_htmlspecialchars(marker.id)}</h3>
                    <p>ละติจูด: ${safe_htmlspecialchars(marker.lat_cam)}</p>
                    <p>ลองจิจูด: ${safe_htmlspecialchars(marker.long_cam)}</p>
                    <p>สถานะ: ออนไลน์</p>
                    ${marker.image_path ? `<button class="bg-purple-500 hover:bg-purple-600 text-white px-3 py-1 rounded mt-2" onclick="openImageModal('${safe_htmlspecialchars(marker.image_path)}')">ดูภาพจากกล้อง</button>` : `<span class="text-gray-500">No Image</span>`}
                </div>
            `);
            markersObject[marker.id + '_cam'] = camMarker;
        } else {
            console.warn(`Camera coordinates missing for detection ID: ${marker.id}`);
        }

        // มาร์กเกอร์ช้าง (ถ้ามีและพิกัดไม่เป็น null)
        if (marker.elephant && marker.lat_ele !== null && marker.long_ele !== null) {
            const eleMarker = L.marker([marker.lat_ele, marker.long_ele], { icon: elephantIcon }).addTo(mymap);
            eleMarker.bindPopup(`
                <div class="popup-content">
                    <h3 class="text-green-500">ช้าง #${safe_htmlspecialchars(marker.id)}</h3>
                    <p>ละติจูด: ${safe_htmlspecialchars(marker.lat_ele)}</p>
                    <p>ลองจิจูด: ${safe_htmlspecialchars(marker.long_ele)}</p>
                    <p>ระยะห่าง: ${safe_htmlspecialchars(marker.distance_ele)} ม.</p>
                </div>
            `);
            markersObject[marker.id + '_ele'] = eleMarker;
        } else if (marker.elephant) {
            console.warn(`Elephant coordinates missing for detection ID: ${marker.id}`);
        }
    }

    // ฟังก์ชันแสดงข้อความแจ้งเตือน
    function showNotification(message, type = "info") {
        const notification = document.createElement("div");
        notification.className = `notification fixed top-5 left-1/2 transform -translate-x-1/2 px-4 py-2 rounded-lg shadow-lg text-white font-medium ${
            type === "info"
                ? "bg-blue-500"
                : type === "success"
                ? "bg-green-500"
                : type === "warning"
                ? "bg-yellow-500"
                : "bg-red-500"
        } transition-opacity duration-300`;
        notification.textContent = message;
        document.body.appendChild(notification);

        setTimeout(() => {
            notification.classList.add("opacity-0");
            setTimeout(() => {
                notification.remove();
            }, 300);
        }, 3000);
    }

    // ฟังก์ชันเปิด Modal เพื่อดูรูปภาพ
    function openImageModal(imagePath) {
        if (imagePath) {
            const modal = document.getElementById("imageModal");
            const modalImage = document.getElementById("modalImage");
            modalImage.src = imagePath;
            modal.style.display = "block";
        }
    }

    // ฟังก์ชันปิด Modal
    function closeImageModal() {
        const modal = document.getElementById("imageModal");
        const modalImage = document.getElementById("modalImage");
        modal.style.display = "none";
        modalImage.src = "";
    }

    // Event Listener สำหรับปิด Modal เมื่อคลิกที่ปุ่มปิด
    document.querySelector(".close-modal").addEventListener("click", closeImageModal);

    // Event Listener สำหรับปิด Modal เมื่อคลิกนอก Modal Content
    window.addEventListener("click", function(event) {
        const modal = document.getElementById("imageModal");
        if (event.target == modal) {
            closeImageModal();
        }
    });

    // ฟังก์ชันโฟกัสที่มาร์กเกอร์
    function focusOnMarker(id, type) {
        const key = id + '_' + type;
        const marker = markersObject[key];
        
        if (marker) {
            mymap.setView(marker.getLatLng(), 15);  // Zoom ไปที่มาร์กเกอร์
            marker.openPopup();  // เปิด popup ที่เกี่ยวข้อง
            
            // แสดงข้อความใน notification
            if (type === 'ele') {
                showNotification('เจอช้างที่ตำแหน่งนี้!', "warning");
            } else if (type === 'cam') {
                showNotification('โฟกัสกล้องแล้ว!', "info");
            } else {
                showNotification('ประเภทที่ไม่รู้จัก!', "error");
            }
        }
    }

    // ฟังก์ชันซ่อนการแจ้งเตือนจาก Header
    function hideHeaderAlert() {
        const alertDiv = document.getElementById("headerAlert");
        if (alertDiv) {
            alertDiv.style.opacity = '0';  // ทำให้จางลง
            setTimeout(() => {
                alertDiv.style.display = 'none';  // ซ่อนการแจ้งเตือน
            }, 300); // เวลาตาม transition
        }
    }

    // ฟังก์ชันซ่อน Popup
    function hidePopup() {
        const popup = document.getElementById('animalPopup');
        if (popup) {
            popup.classList.remove('show');
            popup.classList.remove('bg-red-500', 'bg-green-500', 'bg-yellow-500');
            document.getElementById('popupMessage').textContent = '';
        }

        // ล้างตัวนับเวลา
        clearTimeout(alertTimeout);
    }

    // ฟังก์ชันแสดง Header Alert ให้คงอยู่จนกว่าจะได้รับค่าใหม่
    function showHeaderAlert(message, popupColorClass) {
        const alertDiv = document.getElementById("headerAlert");
        if (alertDiv) {
            alertDiv.style.display = 'block';  // แสดงการแจ้งเตือน
            alertDiv.style.opacity = '1';       // ทำให้ปรากฏขึ้นเต็มที่
            const alertMessage = document.getElementById("headerAlertMessage");
            if (alertMessage) {
                alertMessage.textContent = message;
            }

            // ลบคลาสสีเก่า
            alertDiv.classList.remove('bg-red-500', 'bg-green-500', 'bg-yellow-500');

            // เพิ่มคลาสสีใหม่
            alertDiv.classList.add(popupColorClass);
        }
    }

    let alertTimeout; // ตัวนับเวลาสำหรับการซ่อน alert

    let lastDetectionTime = Date.now();  // เวลาปัจจุบันที่ใช้ในการเช็คว่าไม่มีข้อมูลมานานแค่ไหน

    // ฟังก์ชันตรวจสอบการหมดเวลาของการแจ้งเตือน
    function checkAlertTimeout() {
        setInterval(() => {
            // ถ้าเวลาเกิน 1 นาทีหลังจากได้รับข้อมูลล่าสุด (60,000 ms)
            if (Date.now() - lastDetectionTime > 60000) {
                hideHeaderAlert();  // ซ่อน Header Alert
                hidePopup();        // ซ่อน Popup Alert
            }
        }, 1000);  // เช็คทุกๆ 1 วินาที
    }

    // ฟังก์ชันจัดการการตรวจจับใหม่
    function handleNewDetection(detection) {
        let message = '';
        let popupColorClass = '';
        let shouldShowAlert = false;

        // ตรวจสอบเงื่อนไขการแจ้งเตือน
        if (detection.elephant && detection.alert) {
            // เจอช้างและรถ
            message = `⚠️ เจอช้างและรถอยู่ด้วยกันที่ตำแหน่ง: ละติจูด ${safe_htmlspecialchars(detection.lat_ele)}, ลองจิจูด ${safe_htmlspecialchars(detection.long_ele)}`;
            popupColorClass = 'bg-red-500';  // สีแดง
            shouldShowAlert = true;
        } else if (detection.elephant && !detection.alert) {
            // มีช้างแต่ไม่มีการแจ้งเตือน
            message = `⚠️ มีช้างที่ตำแหน่ง: ละติจูด ${safe_htmlspecialchars(detection.lat_ele)}, ลองจิจูด ${safe_htmlspecialchars(detection.long_ele)}`;
            popupColorClass = 'bg-green-500';  // สีเขียว
            shouldShowAlert = true;
        } else if (!detection.elephant && detection.alert) {
            // มีการแจ้งเตือนแต่ไม่มีช้าง
            message = `⚠️ การแจ้งเตือนที่ตำแหน่ง: ละติจูด ${safe_htmlspecialchars(detection.lat_ele)}, ลองจิจูด ${safe_htmlspecialchars(detection.long_ele)}`;
            popupColorClass = 'bg-yellow-500';  // สีเหลือง
            shouldShowAlert = true;
        }

        if (shouldShowAlert) {
            showPopup(message, popupColorClass);  // แสดง popup พร้อมสี
            showHeaderAlert("แจ้งเตือน: " + message, popupColorClass);  // แสดงการแจ้งเตือนที่ header พร้อมสี
            lastDetectionTime = Date.now();  // รีเซ็ตเวลาของการตรวจจับล่าสุด
        } else {
            // เมื่อไม่มีเงื่อนไขการแจ้งเตือน, ซ่อน header alert หากมีการแสดงอยู่
            hideHeaderAlert();
        }
    }

    // ฟังก์ชันแสดง Popup
    function showPopup(message, popupColorClass) {
        const popup = document.getElementById('animalPopup');
        const popupMessage = document.getElementById('popupMessage');

        if (!popup || !popupMessage) {
            console.error('ไม่พบองค์ประกอบ popup');
            return;
        }

        // ตั้งข้อความ
        popupMessage.textContent = message;

        // ลบคลาสสีเก่า
        popup.classList.remove('bg-red-500', 'bg-green-500', 'bg-yellow-500');

        // เพิ่มคลาสสีใหม่
        popup.classList.add(popupColorClass);

        // แสดง popup
        popup.classList.add('show');

        // รีเซ็ตตัวนับเวลา
        resetAlertTimeout();
    }

    // ฟังก์ชันรีเซ็ตตัวนับเวลาเพื่อซ่อน Popup หลังจาก 1 นาที
    function resetAlertTimeout() {
        // ซ่อน popup หลังจาก 1 นาที (60,000 มิลลิวินาที)
        clearTimeout(alertTimeout);
        alertTimeout = setTimeout(() => {
            hidePopup();
        }, 60000);
    }

    // ฟังก์ชันดึงข้อมูลใหม่จาก API (ปรับปรุงแล้ว)
    function fetchNewData() {
        fetch('https://aprlabtop.com/elephant_api/get_detections.php')  // เปลี่ยน URL ให้ตรงกับตำแหน่งไฟล์
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok ' + response.statusText);
            }
            return response.json();
        })
        .then(data => {
            console.log('Data fetched from API:', data);  // เพิ่มบรรทัดนี้
            // สมมติว่าโครงสร้างของ API คือ { status: 'success', data: [{...}, ...] }
            if (data && data.status === 'success' && Array.isArray(data.data)) {
                let newIdsFound = false;  // ตัวแปรตรวจสอบ ID ใหม่

                if (data.data.length > 0) {
                    data.data.forEach(detection => {
                        if (!markersObject[detection.id + '_cam']) { // ตรวจสอบว่า ID ยังไม่ถูกเพิ่ม
                            addMarkerToMap(detection);
                            addDetectionToTable(detection);
                            handleNewDetection(detection); // เรียกใช้สำหรับ ID ใหม่เท่านั้น
                            newIdsFound = true; // พบ ID ใหม่
                        }
                    });

                    if (newIdsFound) {
                        lastDetectionTime = Date.now();  // รีเซ็ตเวลาของการตรวจจับล่าสุด
                    }
                }

                if (!newIdsFound) {
                    // หากไม่พบ ID ใหม่ ให้ซ่อน popup และ header alert หลังจาก 1 นาที
                    // อย่างไรก็ตาม ฟังก์ชัน checkAlertTimeout จะดูแลการซ่อนนี้อยู่แล้ว
                }
            }
        })
        .catch(error => {
            console.error('Error fetching data:', error);
            showNotification('เกิดข้อผิดพลาดในการดึงข้อมูลใหม่: ' + error.message, 'error');
        });
    }

    // ฟังก์ชันเพิ่มข้อมูลการตรวจจับลงในตาราง
    function addDetectionToTable(detection) {
        const tableBody = document.getElementById('detection-table-body');
        const newRow = document.createElement('tr');

        // กำหนดสีพื้นหลังแถวตามเงื่อนไข
        let rowClass = '';
        if (detection.elephant && detection.alert) {
            rowClass = 'bg-red-500 text-white';  // สีแดง
        } else if (detection.elephant && !detection.alert) {
            rowClass = 'bg-green-500 text-white';  // สีเขียว
        } else if (!detection.elephant && detection.alert) {
            rowClass = 'bg-yellow-500 text-white';  // สีเหลือง
        }
        // ถ้าไม่มีเงื่อนไขใด ๆ ให้แถวเป็นสีขาวโดยไม่กำหนดคลาส

        newRow.id = `row-${safe_htmlspecialchars(detection.id)}`;
        newRow.className = rowClass;

        newRow.innerHTML = `
            <td class="border px-4 py-2">${safe_htmlspecialchars(detection.id)}</td>
            <td class="border px-4 py-2">${safe_htmlspecialchars(detection.timestamp)}</td>
            <td class="border px-4 py-2">
                ${detection.lat_cam !== null && detection.long_cam !== null 
                    ? `${safe_htmlspecialchars(detection.lat_cam)}, ${safe_htmlspecialchars(detection.long_cam)}`
                    : `<span class="text-red-800">Camera coordinates missing</span>`}
            </td>
            <td class="border px-4 py-2">
                ${detection.lat_ele !== null && detection.long_ele !== null 
                    ? `${safe_htmlspecialchars(detection.lat_ele)}, ${safe_htmlspecialchars(detection.long_ele)}`
                    : `<span class="text-red-800">Elephant coordinates missing</span>`}
            </td>
            <td class="border px-4 py-2">
                ${detection.distance_ele !== null && detection.distance_ele !== undefined 
                    ? `${safe_htmlspecialchars(detection.distance_ele)} m` 
                    : 'No Distance'}
            </td>
            <td class="border px-4 py-2">
                ${detection.image_path 
                    ? `<button onclick="openImageModal('${safe_htmlspecialchars(detection.image_path)}')" class="bg-purple-500 text-white px-3 py-1 rounded">View Image</button>` 
                    : `<span class="text-gray-500">No Image</span>`}
            </td>
            <td class="border px-4 py-2">
                <button onclick="focusOnMarker(${safe_htmlspecialchars(detection.id)}, 'cam')" class="bg-blue-500 text-white px-3 py-1 rounded">Focus Camera</button>
                ${detection.elephant 
                    ? `<button onclick="focusOnMarker(${safe_htmlspecialchars(detection.id)}, 'ele')" class="bg-green-500 text-white px-3 py-1 rounded">Focus Elephant</button>` 
                    : ''}
            </td>
        `;

        // เพิ่มแถวใหม่ที่ด้านบนสุดของตาราง
        tableBody.prepend(newRow);

        // หากเกินจำนวนข้อมูลต่อหน้า ลบแถวด้านล่าง
        const currentRows = tableBody.querySelectorAll('tr');
        if (currentRows.length > <?= $perPage ?>) {
            tableBody.removeChild(currentRows[currentRows.length - 1]);
        }
    }

    // ฟังก์ชันเพิ่มข้อมูลการตรวจจับที่มีพิกัดเป็น null ลงในตาราง
    function addMissingDetectionToTable(detection) {
        const tableBody = document.getElementById('detection-table-body');
        const newRow = document.createElement('tr');
        newRow.id = `row-${safe_htmlspecialchars(detection.id)}`;
        
        // กำหนดคลาสตามเงื่อนไข
        if (detection.elephant && detection.alert) {
            newRow.className = 'bg-red-500 text-white';
        } else if (detection.elephant && !detection.alert) {
            newRow.className = 'bg-green-500 text-white';
        } else if (!detection.elephant && detection.alert) {
            newRow.className = 'bg-yellow-500 text-white';
        } else {
            newRow.className = ''; // สีขาว
        }

        newRow.innerHTML = `
            <td class="border px-4 py-2">${safe_htmlspecialchars(detection.id)}</td>
            <td class="border px-4 py-2">${safe_htmlspecialchars(detection.timestamp)}</td>
            <td class="border px-4 py-2">
                ${detection.lat_cam !== null && detection.long_cam !== null ? `${safe_htmlspecialchars(detection.lat_cam)}, ${safe_htmlspecialchars(detection.long_cam)}` : `<span class="text-red-800">Camera coordinates missing</span>`}
            </td>
            <td class="border px-4 py-2">
                ${detection.lat_ele !== null && detection.long_ele !== null ? `${safe_htmlspecialchars(detection.lat_ele)}, ${safe_htmlspecialchars(detection.long_ele)}` : `<span class="text-red-800">Elephant coordinates missing</span>`}
            </td>
            <td class="border px-4 py-2">${safe_htmlspecialchars(detection.distance_ele)} m</td>
            <td class="border px-4 py-2">
                ${detection.image_path ? `<button onclick="openImageModal('${safe_htmlspecialchars(detection.image_path)}')" class="bg-purple-500 text-white px-3 py-1 rounded">View Image</button>` : `<span class="text-gray-500">No Image</span>`}
            </td>
            <td class="border px-4 py-2">
                <button onclick="focusOnMarker(${safe_htmlspecialchars(detection.id)}, 'cam')" class="bg-blue-500 text-white px-3 py-1 rounded">Focus Camera</button>
                ${detection.elephant ? `<button onclick="focusOnMarker(${safe_htmlspecialchars(detection.id)}, 'ele')" class="bg-green-500 text-white px-3 py-1 rounded">Focus Elephant</button>` : ''}
            </td>
        `;

        // เพิ่มแถวใหม่ที่ด้านบนสุดของตาราง
        tableBody.prepend(newRow);

        // หากเกินจำนวนข้อมูลต่อหน้า ลบแถวด้านล่าง
        const currentRows = tableBody.querySelectorAll('tr');
        if (currentRows.length > <?= $perPage ?>) {
            tableBody.removeChild(currentRows[currentRows.length - 1]);
        }
    }

    // เริ่มต้นแผนที่และตั้งค่าการดึงข้อมูลใหม่ทุก 5 วินาที
    document.addEventListener("DOMContentLoaded", () => {
        initializeMap();
        setInterval(fetchNewData, 5000);  // ดึงข้อมูลทุก 5 วินาที
        checkAlertTimeout();  // เริ่มต้นการตรวจสอบการหมดเวลาของการแจ้งเตือน

        // เพิ่มข้อมูลที่มีพิกัดเป็น null ลงในตาราง
        missingCoordinatesData.forEach(marker => {
            addMissingDetectionToTable(marker);
        });

        // Event Listener สำหรับปิด Header Alert
        document.getElementById("closeHeaderAlert").addEventListener("click", hideHeaderAlert);
        
        // Event Listener สำหรับปิด Popup Alert
        document.getElementById("closePopup").addEventListener("click", hidePopup);
    });
</script>

<!-- หน้าจอโหลด -->
<div class="loading fixed inset-0 flex items-center justify-center bg-white bg-opacity-95 dark:bg-gray-800 dark:bg-opacity-95 z-50 rounded-lg hidden">
    <div class="flex flex-col items-center">
        <div class="w-8 h-8 border-4 border-blue-500 border-t-transparent rounded-full animate-spin"></div>
        <span class="mt-2 text-gray-700 dark:text-gray-200">กำลังโหลด...</span>
    </div>
</div>

</body>
</html>
