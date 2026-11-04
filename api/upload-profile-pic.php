<?php
session_start();
header("Content-Type: application/json");
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if (!isset($_FILES['profile_pic']) || $_FILES['profile_pic']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error.']);
    exit;
}

$file = $_FILES['profile_pic'];

// ফাইল ফরম্যাট চেক
$allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
if (!in_array($file['type'], $allowed_types)) {
    echo json_encode(['success' => false, 'message' => 'Only JPG, PNG, and GIF are allowed.']);
    exit;
}

// ফাইল সাইজ চেক (2MB)
if ($file['size'] > 2 * 1024 * 1024) {
    echo json_encode(['success' => false, 'message' => 'File size must be less than 2MB.']);
    exit;
}

$upload_dir = __DIR__ . '/../uploads/profiles/';
if (!is_dir($upload_dir)) { mkdir($upload_dir, 0755, true); }

$ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$filename = 'hr_' . uniqid() . '.' . $ext; 
$target_file = $upload_dir . $filename;

if (move_uploaded_file($file['tmp_name'], $target_file)) {
    $db_path = 'uploads/profiles/' . $filename; 
    $db = (new Database())->getConnection();
    $stmt = $db->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
    $stmt->execute([$db_path, $_SESSION['user_id']]);

    echo json_encode(['success' => true, 'message' => 'Profile picture updated successfully!']);
} else {
    echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file.']);
}
?>