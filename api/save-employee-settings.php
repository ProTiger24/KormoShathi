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
    $user_id = $_SESSION['user_id'];
    
    // ডিফল্ট ভ্যালু সেট করা (যদি না পাওয়া যায়)
    $defaults = [
        'email_notifications' => 1, 'attendance_reminders' => 1, 'leave_notifications' => 1, 
        'message_notifications' => 1, 'notice_board_updates' => 1, 'check_in_reminder' => 1, 
        'check_out_reminder' => 1, 'daily_attendance_summary' => 1, 'weekly_attendance_summary' => 0, 
        'default_leave_notification' => 1, 'leave_status_updates' => 1
    ];
    
    $cols = ['user_id'];
    $vals = [$user_id];
    $updates = [];

    foreach ($defaults as $key => $def) {
        $cols[] = $key;
        $val = isset($input[$key]) ? ($input[$key] ? 1 : 0) : $def;
        $vals[] = $val;
        $updates[] = "$key = VALUES($key)";
    }

    $strCols = ['theme', 'language', 'date_format', 'time_format'];
    foreach ($strCols as $key) {
        $cols[] = $key;
        $val = $input[$key] ?? 'Default';
        $vals[] = $val;
        $updates[] = "$key = VALUES($key)";
    }

    $placeholders = implode(',', array_fill(0, count($cols), '?'));
    $colNames = implode(',', $cols);
    $updateStr = implode(',', $updates);

    $sql = "INSERT INTO employee_settings ($colNames) VALUES ($placeholders) ON DUPLICATE KEY UPDATE $updateStr";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($vals);
    
    echo json_encode(['success' => true, 'message' => 'Settings saved successfully!']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
