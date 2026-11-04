<?php
session_start();
header("Content-Type: application/json");
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = (new Database())->getConnection();
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

try {
    $stmt = $db->prepare("SELECT user_id FROM user_settings WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    
    // নোটিফিকেশন ভ্যালুগুলো রেডি করা (যদি না পাঠানো হয় তবে ডিফল্ট ১)
    $notify_leave = $input['notify_leave'] ?? 1;
    $notify_attendance = $input['notify_attendance'] ?? 1;
    $notify_salary = $input['notify_salary'] ?? 1;
    $notify_announcements = $input['notify_announcements'] ?? 1;
    $notify_notices = $input['notify_notices'] ?? 1;
    
    if ($stmt->rowCount() > 0) {
        $sql = "UPDATE user_settings SET 
                email_notifications = ?, push_notifications = ?, 
                language = ?, theme = ?, two_factor = ?, profile_visibility = ?,
                notify_leave = ?, notify_attendance = ?, notify_salary = ?, notify_announcements = ?, notify_notices = ?
                WHERE user_id = ?";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $input['email_notifications'] ?? 1, 
            $input['push_notifications'] ?? 1,
            $input['language'] ?? 'English', 
            $input['theme'] ?? 'Light',
            $input['two_factor'] ?? 0, 
            $input['profile_visibility'] ?? 1,
            $notify_leave, $notify_attendance, $notify_salary, $notify_announcements, $notify_notices,
            $_SESSION['user_id']
        ]);
    } else {
        $sql = "INSERT INTO user_settings 
                (user_id, email_notifications, push_notifications, language, theme, two_factor, profile_visibility, notify_leave, notify_attendance, notify_salary, notify_announcements, notify_notices) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $db->prepare($sql);
        $stmt->execute([
            $_SESSION['user_id'], 
            $input['email_notifications'] ?? 1, 
            $input['push_notifications'] ?? 1,
            $input['language'] ?? 'English', 
            $input['theme'] ?? 'Light',
            $input['two_factor'] ?? 0, 
            $input['profile_visibility'] ?? 1,
            $notify_leave, $notify_attendance, $notify_salary, $notify_announcements, $notify_notices
        ]);
    }
    
    echo json_encode(['success' => true, 'message' => 'Settings saved successfully!']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>