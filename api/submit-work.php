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

// 🟢 Cloudinary আপলোড ফাংশন
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

    $url = "https://api.cloudinary.com/v1_1/$cloud_name/auto/upload";
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    
    if (empty($title)) {
        echo json_encode(['success' => false, 'message' => 'Work title is required.']);
        exit;
    }

    $file_path = null;
    $file_name = null;

    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $fileName = $_FILES['file']['name'];
        $fileTmpPath = $_FILES['file']['tmp_name'];
        
        $public_id = 'kormoshathi/submissions/sub_' . $user_id . '_' . uniqid() . '_' . time();
        $upload_result = uploadToCloudinary($fileTmpPath, $public_id);

        if (isset($upload_result['success'])) {
            $file_path = $upload_result['url']; // Cloudinary URL
            $file_name = $fileName;
        } else {
            echo json_encode(['success' => false, 'message' => 'Cloudinary Error: ' . $upload_result['error']]);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Please attach a file.']);
        exit;
    }

    try {
        $stmt = $db->prepare("INSERT INTO employee_submissions (employee_id, title, description, file_path, file_name, status) VALUES (?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([$user_id, $title, $description, $file_path, $file_name]);
        
        echo json_encode(['success' => true, 'message' => 'Work submitted successfully!']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
?>
