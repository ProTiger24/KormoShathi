<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = (new Database())->getConnection();
$user_id = $_SESSION['user_id'];

function uploadToCloudinary($file_tmp_path, $public_id) {
    $cloud_url = getenv('CLOUDINARY_URL');
    
    if (!$cloud_url) {
        $env_path = __DIR__ . '/../.env';
        if (file_exists($env_path)) {
            $lines = file($env_path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos(trim($line), 'CLOUDINARY_URL=') === 0) {
                    $cloud_url = trim(explode('=', $line, 2)[1] ?? '', " \"'");
                    break;
                }
            }
        }
    }
    
    if (!$cloud_url) return ['error' => 'CLOUDINARY_URL not configured'];
    
    preg_match('/cloudinary:\/\/(.*):(.*)@(.*)/', $cloud_url, $matches);
    $api_key = $matches[1] ?? ''; $api_secret = $matches[2] ?? ''; $cloud_name = $matches[3] ?? '';

    if (!$api_key || !$api_secret || !$cloud_name) return ['error' => 'Invalid CLOUDINARY_URL format'];

    $timestamp = time();
    $params_to_sign = "public_id=$public_id&timestamp=$timestamp";
    $signature = sha1($params_to_sign . $api_secret);

    $url = "https://api.cloudinary.com/v1_1/$cloud_name/image/upload";
    $post_data = [
        'file' => new CURLFile($file_tmp_path),
        'api_key' => $api_key,
        'timestamp' => $timestamp,
        'public_id' => $public_id,
        'signature' => $signature
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $result = json_decode($response, true);

    if ($httpCode == 200 && isset($result['secure_url'])) {
        return ['success' => true, 'url' => $result['secure_url']];
    } else {
        return ['error' => $result['error']['message'] ?? 'Upload failed'];
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit;
}

if (empty($_FILES) && empty($_POST) && isset($_SERVER['CONTENT_LENGTH'])) {
    echo json_encode(['success' => false, 'message' => 'File size exceeds server limit.']);
    exit;
}

$file_key = isset($_FILES['profile_picture']) ? 'profile_picture' : (isset($_FILES['profile_pic']) ? 'profile_pic' : null);

if ($file_key) {
    $file = $_FILES[$file_key];
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'File upload error code: ' . $file['error']]);
        exit;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (!in_array($ext, $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Only JPG, PNG, GIF, WEBP files are allowed']);
        exit;
    }

    $public_id = 'kormoshathi/profile_' . $user_id . '_' . time();
    $upload_result = uploadToCloudinary($file['tmp_name'], $public_id);

    if (isset($upload_result['success'])) {
        $image_url = $upload_result['url'];

        $stmt = $db->prepare("UPDATE users SET profile_picture = :profile_picture WHERE id = :user_id");
        $stmt->bindParam(':profile_picture', $image_url);
        $stmt->bindParam(':user_id', $user_id);
        $stmt->execute();

        $_SESSION['profile_picture'] = $image_url;

        echo json_encode(['success' => true, 'message' => 'Profile picture updated', 'image_path' => $image_url]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Cloudinary Error: ' . ($upload_result['error'] ?? 'Unknown error')]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'No file uploaded.']);
}
?>
