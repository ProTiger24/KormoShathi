<?php
date_default_timezone_set('Asia/Dhaka');
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
$today = date('Y-m-d');
$now = date('H:i:s');
$office_start = '10:00:00';

$input = json_decode(file_get_contents("php://input"), true);
$type = $input['type'] ?? '';
$action = $_GET['action'] ?? '';
$date = $_GET['date'] ?? date('Y-m-d');

// GET: Today's status (for employee)
if ($_SERVER['REQUEST_METHOD'] == 'GET' && $action == 'today_status') {
    $stmt = $db->prepare("SELECT check_in, check_out, status FROM attendance WHERE employee_id = ? AND attendance_date = ?");
    $stmt->execute([$user_id, $today]);
    $attendance = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($attendance) {
        $status = $attendance['check_out'] ? 'Completed' : 'Checked In';
        echo json_encode(['success' => true, 'status' => $status, 'check_in' => $attendance['check_in'], 'check_out' => $attendance['check_out']]);
    } else {
        echo json_encode(['success' => true, 'status' => 'Not Checked In', 'check_in' => null, 'check_out' => null]);
    }
    exit;
}

// GET: Attendance records (for HR)
if ($_SERVER['REQUEST_METHOD'] == 'GET' && $_SESSION['user_type'] == 'hr') {
    $day_of_week = date('w', strtotime($date));
    $is_weekend = ($day_of_week == 5 || $day_of_week == 6);

    $h_stmt = $db->prepare("SELECT id FROM holidays WHERE ? BETWEEN start_date AND end_date");
    $h_stmt->execute([$date]);
    $is_holiday = $h_stmt->fetch() ? true : false;

    $l_stmt = $db->prepare("SELECT employee_id FROM leave_requests WHERE status = 'approved' AND ? BETWEEN start_date AND end_date");
    $l_stmt->execute([$date]);
    $on_leave_ids = $l_stmt->fetchAll(PDO::FETCH_COLUMN);

    $sql = "SELECT u.id, u.first_name, u.last_name, u.employee_id, 
                   a.check_in, a.check_out, 
                   COALESCE(a.status, 'absent') as status
            FROM users u 
            LEFT JOIN attendance a ON u.id = a.employee_id AND a.attendance_date = :date 
            WHERE u.user_type = 'employee' 
            ORDER BY u.first_name ASC";
    
    $stmt = $db->prepare($sql);
    $stmt->bindParam(':date', $date);
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $present = 0; $absent = 0; $late = 0; $leave = 0; $off = 0;
    
    foreach ($data as &$row) {
        if ($row['check_in']) {
            // 🟢 ফিক্স: চেক-ইন করলেই সে 'Present' হিসেবে গোনা হবে
            $present++; 
            if ($row['status'] == 'late') { 
                $late++; 
            } else {
                $row['status'] = 'present';
            }
        } else {
            if (in_array($row['id'], $on_leave_ids)) {
                $row['status'] = 'leave';
                $leave++;
            } elseif ($is_weekend || $is_holiday) {
                $row['status'] = 'off';
                $off++;
            } else {
                $row['status'] = 'absent';
                $absent++;
            }
        }
    }
    
    echo json_encode([
        'success' => true, 
        'data' => $data,
        'counts' => [
            'present' => $present, 'absent' => $absent, 'late' => $late, 'leave' => $leave, 'off' => $off,
            'total' => count($data)
        ]
    ]);
    exit;
}

// POST: Check In / Check Out
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($type == 'check_in') {
        $stmt = $db->prepare("SELECT id FROM attendance WHERE employee_id = ? AND attendance_date = ?");
        $stmt->execute([$user_id, $today]);
        
        if ($stmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Already checked in today']);
            exit;
        }
        
        $status = ($now > $office_start) ? 'late' : 'present';
        
        $stmt = $db->prepare("INSERT INTO attendance (employee_id, attendance_date, check_in, status) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$user_id, $today, $now, $status])) {
            echo json_encode(['success' => true, 'message' => 'Check-in successful at ' . date('h:i A') . ($status == 'late' ? ' (Late)' : '')]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Check-in failed']);
        }
    }
    elseif ($type == 'check_out') {
        $stmt = $db->prepare("UPDATE attendance SET check_out = ? WHERE employee_id = ? AND attendance_date = ? AND check_out IS NULL");
        if ($stmt->execute([$now, $user_id, $today]) && $stmt->rowCount() > 0) {
            echo json_encode(['success' => true, 'message' => 'Check-out successful at ' . date('h:i AM')]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No check-in found for today']);
        }
    }
    exit;
}
?>
