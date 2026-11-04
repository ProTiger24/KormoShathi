<?php
session_start();
header("Content-Type: application/json");
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = (new Database())->getConnection();
$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $db->prepare("SELECT * FROM employee_documents WHERE user_id = ? ORDER BY uploaded_at DESC");
    $stmt->execute([$user_id]);
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} 
elseif ($method === 'POST') {
    if (!isset($_FILES['document']) || $_FILES['document']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'message' => 'No file uploaded or upload error.']);
        exit;
    }
    $file = $_FILES['document'];
    $doc_type = $_POST['document_type'] ?? 'Personal';
    
    $allowed = ['pdf', 'doc', 'docx', 'jpg', 'jpeg', 'png'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    
    if (!in_array($ext, $allowed)) {
        echo json_encode(['success' => false, 'message' => 'Invalid file type.']);
        exit;
    }

    $upload_dir = __DIR__ . '/../uploads/documents/';
    if (!is_dir($upload_dir)) { mkdir($upload_dir, 0777, true); }

    $filename = uniqid('doc_') . '.' . $ext;
    $target_file = $upload_dir . $filename;
    $db_path = 'uploads/documents/' . $filename;

    if (move_uploaded_file($file['tmp_name'], $target_file)) {
        $stmt = $db->prepare("INSERT INTO employee_documents (user_id, document_name, file_path, document_type) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user_id, $file['name'], $db_path, $doc_type]);
        echo json_encode(['success' => true, 'message' => 'Document uploaded successfully!']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to move file. Check folder permissions.']);
    }
}
elseif ($method === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (isset($input['id'])) {
        $stmt = $db->prepare("SELECT file_path FROM employee_documents WHERE id = ? AND user_id = ?");
        $stmt->execute([$input['id'], $user_id]);
        $doc = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($doc) {
            $file_path = __DIR__ . '/../' . $doc['file_path'];
            if (file_exists($file_path)) { unlink($file_path); }
            
            $stmt = $db->prepare("DELETE FROM employee_documents WHERE id = ? AND user_id = ?");
            $stmt->execute([$input['id'], $user_id]);
            echo json_encode(['success' => true, 'message' => 'Document deleted.']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Document not found.']);
        }
    }
}
?>
