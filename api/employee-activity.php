<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
session_start();

require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

date_default_timezone_set('Asia/Dhaka');

try {
    $database = new Database();
    $db = $database->getConnection();
    $user_id = $_SESSION['user_id'];
    $user_type = $_SESSION['user_type'];

    $activities = [];

    if ($user_type === 'employee') {
        // ১. অ্যাটেনডেন্স
        $stmt = $db->prepare("SELECT check_in, created_at FROM attendance WHERE employee_id = ? ORDER BY attendance_date DESC LIMIT 1");
        $stmt->execute([$user_id]);
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $activities[] = ['description' => 'You marked attendance', 'created_at' => $row['created_at']];
        }

        // ২. লিভ রিকোয়েস্ট
        $stmt = $db->prepare("SELECT leave_type, total_days, applied_on FROM leave_requests WHERE employee_id = ? ORDER BY applied_on DESC LIMIT 1");
        $stmt->execute([$user_id]);
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $activities[] = ['description' => "Applied for {$row['total_days']} days {$row['leave_type']} leave", 'created_at' => $row['applied_on']];
        }

        // ৩. পে-স্লিপ
        $stmt = $db->prepare("SELECT month_year, created_at FROM payroll WHERE employee_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$user_id]);
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $activities[] = ['description' => "Payslip generated for {$row['month_year']}", 'created_at' => $row['created_at']];
        }

        // ৪. কাজ সাবমিট
        $stmt = $db->prepare("SELECT title, created_at FROM employee_submissions WHERE employee_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$user_id]);
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $activities[] = ['description' => "Submitted work: {$row['title']}", 'created_at' => $row['created_at']];
        }
    } else {
        // HR অ্যাক্টিভিটি
        // ১. নতুন এমপ্লয়ী
        $stmt = $db->query("SELECT first_name, created_at FROM users WHERE user_type = 'employee' ORDER BY created_at DESC LIMIT 1");
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $activities[] = ['description' => "New employee {$row['first_name']} added to the system", 'created_at' => $row['created_at']];
        }

        // ২. পে-রোল
        $stmt = $db->query("SELECT month_year, created_at FROM payroll ORDER BY created_at DESC LIMIT 1");
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $activities[] = ['description' => "Payroll processed for {$row['month_year']}", 'created_at' => $row['created_at']];
        }

        // ৩. লিভ অ্যাপ্রুভাল
        $stmt = $db->prepare("SELECT status, approved_on FROM leave_requests WHERE approved_by = ? AND status != 'pending' ORDER BY approved_on DESC LIMIT 1");
        $stmt->execute([$user_id]);
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $activities[] = ['description' => "Leave request {$row['status']} by HR", 'created_at' => $row['approved_on']];
        }

        // ৪. নোটিশ
        $stmt = $db->query("SELECT title, created_at FROM notices ORDER BY created_at DESC LIMIT 1");
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $activities[] = ['description' => "Notice published: {$row['title']}", 'created_at' => $row['created_at']];
        }
    }

    // তারিখ অনুযায়ী সর্ট করা (সর্বশেষটি আগে)
    usort($activities, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });

    // সর্বোচ্চ ৪টি রিটার্ন করা
    echo json_encode([
        'success' => true,
        'data' => array_slice($activities, 0, 4)
    ], JSON_UNESCAPED_UNICODE);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Unable to load recent activities.',
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
