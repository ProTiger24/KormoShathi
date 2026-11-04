<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
session_start();

require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        if (isset($_GET['count'])) {
            // বেল আইকনের জন্য শুধু আনরিড নাম্বার আনা হচ্ছে
            $stmt = $db->prepare("SELECT COUNT(*) as unread FROM notifications WHERE user_id = :user_id AND is_read = 0");
            $stmt->execute([':user_id' => $user_id]);
            $unread = $stmt->fetch(PDO::FETCH_ASSOC)['unread'] ?? 0;
            echo json_encode(['success' => true, 'unread' => (int)$unread]);
        } else {
            // Recent Notifications প্যানেলের জন্য সম্পূর্ণ লিস্ট আনা হচ্ছে
            $stmt = $db->prepare("SELECT * FROM notifications WHERE user_id = :user_id ORDER BY created_at DESC LIMIT 10");
            $stmt->execute([':user_id' => $user_id]);
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'data' => $data]);
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        // নোটিফিকেশন রিড (Read) মার্ক করা হচ্ছে
        $input = json_decode(file_get_contents('php://input'), true);
        $id = $input['id'] ?? 0;

        if ($id == 0) {
            $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = :user_id");
            $stmt->execute([':user_id' => $user_id]);
        } else {
            $stmt = $db->prepare("UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :user_id");
            $stmt->execute([':id' => $id, ':user_id' => $user_id]);
        }
        echo json_encode(['success' => true]);
    }
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
