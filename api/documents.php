<?php
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

        $allowedfileExtensions = array('jpg', 'gif', 'png', 'pdf', 'doc', 'docx');
        
        if (in_array($fileExtension, $allowedfileExtensions)) {
            if ($fileSize < 5000000) { // 5MB limit
                $newFileName = uniqid() . '_' . time() . '.' . $fileExtension;
                $uploadFileDir = '../uploads/documents/';
                $dest_path = $uploadFileDir . $newFileName;

                if(move_uploaded_file($fileTmpPath, $dest_path)) {
                    $docType = $_POST['doc_type'] ?? 'Personal';
                    $dbPath = 'uploads/documents/' . $newFileName;
                    
                    $stmt = $db->prepare("INSERT INTO documents (employee_id, doc_name, file_path, doc_type) VALUES (?, ?, ?, ?)");
                    if ($stmt->execute([$user_id, $fileName, $dbPath, $docType])) {
                        echo json_encode(['success' => true, 'message' => 'File uploaded successfully!']);
                    } else {
                        echo json_encode(['success' => false, 'message' => 'Database error.']);
                    }
                } else {
                    echo json_encode(['success' => false, 'message' => 'Error moving the file.']);
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

    // ফাইলের পাথ বের করা (ফোল্ডার থেকে ডিলিট করার জন্য)
    $stmt = $db->prepare("SELECT file_path FROM documents WHERE id = ? AND employee_id = ?");
    $stmt->execute([$id, $user_id]);
    $doc = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($doc) {
        $filePath = '../' . $doc['file_path'];
        // সার্ভার থেকে ফাইল ডিলিট
        if (file_exists($filePath) && is_file($filePath)) {
            unlink($filePath);
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
