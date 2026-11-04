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
$user_type = $_SESSION['user_type'];
$month = $_GET['month'] ?? date('Y-m');

$month_start = $month . '-01';
$month_end = date('Y-m-t', strtotime($month_start));
$today_date = date('Y-m-d');

// 🟢 ফিক্স: যদি চলতি মাস হয়, তবে শুধু আজকের তারিখ পর্যন্ত কর্মদিবস গোনা হবে
$calc_end = ($month === date('Y-m')) ? $today_date : $month_end;

// মাসে মোট কতগুলো কর্মদিবস (Working Days) আছে তা বের করা
$working_days = 0;
$current = strtotime($month_start);
$last = strtotime($calc_end);
while ($current <= $last) {
    $d_str = date('Y-m-d', $current);
    $dow = date('w', $current);
    if ($dow != 5 && $dow != 6) { // 5=Fri, 6=Sat
        $h_stmt = $db->prepare("SELECT id FROM holidays WHERE ? BETWEEN start_date AND end_date");
        $h_stmt->execute([$d_str]);
        if (!$h_stmt->fetch()) { $working_days++; }
    }
    $current = strtotime('+1 day', $current);
}

if ($user_type === 'employee') {
    $stmt = $db->prepare("SELECT attendance_date, check_in, check_out, status FROM attendance WHERE employee_id = ? AND DATE_FORMAT(attendance_date, '%Y-%m') = ? ORDER BY attendance_date DESC");
    $stmt->execute([$user_id, $month]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $present = 0; $late = 0; $absent = 0;
    foreach($records as $row) {
        if($row['status'] === 'present') {
            $present++;
        } elseif($row['status'] === 'late') { 
            $late++; 
            $present++; // লেট হলেও সে Present হিসেবে গোনা হবে
        } elseif($row['status'] === 'absent') {
            $absent++;
        }
    }
    
    // Leave এর হিসাব শুধু আজ পর্যন্ত
    $leave_stmt = $db->prepare("SELECT SUM(DATEDIFF(LEAST(end_date, ?), GREATEST(start_date, ?)) + 1) FROM leave_requests WHERE employee_id = ? AND status = 'approved' AND (start_date <= ? AND end_date >= ?)");
    $leave_stmt->execute([$calc_end, $month_start, $user_id, $calc_end, $month_start]);
    $total_leave_days = (int)$leave_stmt->fetchColumn();

    echo json_encode([
        'success' => true, 
        'data' => $records,
        'summary' => [
            'present' => $present, 
            'late' => $late, 
            'absent' => max(0, $working_days - ($present + $total_leave_days))
        ]
    ]);
} 
elseif ($user_type === 'hr') {
    $query = "SELECT u.id, u.first_name, u.last_name, u.employee_id,
              COUNT(a.id) as total_present,
              SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as total_late
              FROM users u
              LEFT JOIN attendance a ON u.id = a.employee_id AND DATE_FORMAT(a.attendance_date, '%Y-%m') = ?
              WHERE u.user_type = 'employee' AND u.status = 'active'
              GROUP BY u.id
              ORDER BY u.first_name ASC";
              
    $stmt = $db->prepare($query);
    $stmt->execute([$month]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach($records as &$emp) {
        $leave_stmt = $db->prepare("SELECT SUM(DATEDIFF(LEAST(end_date, ?), GREATEST(start_date, ?)) + 1) FROM leave_requests WHERE employee_id = ? AND status = 'approved' AND (start_date <= ? AND end_date >= ?)");
        $leave_stmt->execute([$calc_end, $month_start, $emp['id'], $calc_end, $month_start]);
        $emp_leave = (int)$leave_stmt->fetchColumn();
        
        $emp['total_leave'] = $emp_leave;
        $emp['total_absent'] = max(0, $working_days - ($emp['total_present'] + $emp_leave));
    }

    echo json_encode(['success' => true, 'data' => $records, 'working_days' => $working_days]);
}
?>
