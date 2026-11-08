<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = (new Database())->getConnection();
$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

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

// 🟢 Cloudinary থেকে ফাইল ডিলিট করার ফাংশন
function deleteFromCloudinary($file_url) {
    $cloud_url = getenv('CLOUDINARY_URL');
    if (!$cloud_url) return false;
    
    preg_match('/cloudinary:\/\/(.*):(.*)@(.*)/', $cloud_url, $matches);
    $api_key = $matches[1] ?? ''; $api_secret = $matches[2] ?? ''; $cloud_name = $matches[3] ?? '';

    // URL থেকে public_id বের করা (যেমন: kormoshathi/documents/6_xxx)
    $parts = explode('/upload/', $file_url);
    if (count($parts) < 2) return false;
    
    $path_with_version = $parts[1]; 
    $path_parts = explode('/', $path_with_version);
    // ভার্সন (v123456) বাদ দেওয়া
    if (strpos($path_parts[0], 'v') === 0) { array_shift($path_parts); }
    
    $path_with_ext = implode('/', $path_parts);
    // এক্সটেনশন বাদ দেওয়া
    $public_id = preg_replace('/\.[^.]+$/', '', $path_with_ext);

    $timestamp = time();
    $params_to_sign = "public_id=$public_id&timestamp=$timestamp";
    $signature = sha1($params_to_sign . $api_secret);

    $url = "https://api.cloudinary.com/v1_1/$cloud_name/image/destroy";
    $post_data = [
        'public_id' => $public_id,
        'api_key' => $api_key,
        'timestamp' => $timestamp,
        'signature' => $signature
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);
    $response = curl_exec($ch);
    curl_close($ch);
    
    return true; 
}

// 🟢 GET: ডকুমেন্ট লিস্ট
if ($method == 'GET') {
    $stmt = $db->prepare("SELECT * FROM documents WHERE employee_id = ? ORDER BY upload_date DESC");
    $stmt->execute([$user_id]);
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

// 🟢 POST: নতুন ফাইল আপলোড
if ($method == 'POST') {
    if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['file']['tmp_name'];
        $fileName = $_FILES['file']['name'];
        $fileSize = $_FILES['file']['size'];
        $fileNameCmps = explode(".", $fileName);
        $fileExtension = strtolower(end($fileNameCmps));

        $allowedfileExtensions = array('jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx');
        
        if (in_array($fileExtension, $allowedfileExtensions)) {
            if ($fileSize < 5000000) { // 5MB limit
                
                $public_id = 'kormoshathi/documents/' . $user_id . '_' . uniqid() . '_' . time();
                $upload_result = uploadToCloudinary($fileTmpPath, $public_id);

                if (isset($upload_result['success'])) {
                    $dbPath = $upload_result['url']; 
                    $docType = $_POST['doc_type'] ?? 'Personal';
                    
                    $stmt = $db->prepare("INSERT INTO documents (employee_id, doc_name, file_path, doc_type) VALUES (?, ?, ?, ?)");
                    if ($stmt->execute([$user_id, $fileName, $dbPath, $docType])) {
                        echo json_encode(['success' => true, 'message' => 'File uploaded successfully!']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Database error.']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Cloudinary Error: ' . $upload_result['error']]);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'File size must be less than 5MB.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid file type.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'No file uploaded.']);
    }
    exit;
}

// 🟢 DELETE: ফাইল ডিলিট করা
if ($method == 'DELETE') {
    $id = $_GET['id'] ?? 0;
    if(!$id) {
        echo json_encode(['success' => false, 'message' => 'Invalid ID.']);
        exit;
    }

    $stmt = $db->prepare("SELECT file_path FROM documents WHERE id = ? AND employee_id = ?");
    $stmt->execute([$id, $user_id]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($doc) {
        // 🟢 যদি Cloudinary ফাইল হয়, তবে Cloudinary থেকেও ডিলিট করবে
        if (strpos($doc['file_path'], 'http') === 0) {
            deleteFromCloudinary($doc['file_path']);
        } 
        // যদি লোকাল ফাইল হয়
        else {
            $filePath = '../' . $doc['file_path'];
            if (file_exists($filePath) && is_file($filePath)) {
                unlink($filePath);
            }
        }
        
        // ডাটাবেস থেকে ডিলিট
        $stmt = $db->prepare("DELETE FROM documents WHERE id = ? AND employee_id = ?");
        if($stmt->execute([$id, $user_id])) {
            echo json_encode(['success' => true, 'message' => 'Document deleted successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to delete from database.']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Document not found or unauthorized.']);
    }
    exit;
}
?>
