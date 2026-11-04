<?php
session_start();
header("Content-Type: application/json");
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'hr') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = (new Database())->getConnection();
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['section'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid data']);
    exit;
}

try {
    $section = $input['section'];

    if ($section === 'company') {
        $stmt = $db->prepare("UPDATE company_settings SET company_name=?, address=?, email=?, phone=?, timezone=?, currency=? WHERE id=1");
        $stmt->execute([$input['company_name'], $input['address'], $input['email'], $input['phone'], $input['timezone'], $input['currency']]);
    } 
    elseif ($section === 'attendance') {
        $stmt = $db->prepare("UPDATE attendance_settings SET start_time=?, end_time=?, late_threshold=?, early_departure=?, break_time=?, weekly_days=?, allow_correction=? WHERE id=1");
        $stmt->execute([$input['start_time'], $input['end_time'], $input['late_threshold'], $input['early_departure'], $input['break_time'], $input['weekly_days'], $input['allow_correction']]);
    }
    elseif ($section === 'leave') {
        $stmt = $db->prepare("UPDATE leave_settings SET annual_leave=?, sick_leave=?, casual_leave=?, maternity_leave=?, paternity_leave=?, carry_forward=?, approval_rules=? WHERE id=1");
        $stmt->execute([$input['annual_leave'], $input['sick_leave'], $input['casual_leave'], $input['maternity_leave'], $input['paternity_leave'], $input['carry_forward'], $input['approval_rules']]);
    }
    elseif ($section === 'system') {
        $stmt = $db->prepare("UPDATE user_settings SET language=?, theme=? WHERE user_id=?");
        $stmt->execute([$input['language'], $input['theme'], $_SESSION['user_id']]);
    }

    echo json_encode(['success' => true, 'message' => 'Settings saved successfully!']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
