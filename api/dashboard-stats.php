<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
session_start();

require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'hr') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    // KPI Data
    $totalEmployees = $db->query("SELECT COUNT(*) as total FROM users WHERE user_type = 'employee' AND status = 'active'")->fetch(PDO::FETCH_ASSOC)['total'] ?? 0;
    $today = date('Y-m-d');
    $presentToday = $db->query("SELECT COUNT(DISTINCT employee_id) as present FROM attendance WHERE attendance_date = '$today'")->fetch(PDO::FETCH_ASSOC)['present'] ?? 0;
    $onLeave = $db->query("SELECT COUNT(DISTINCT employee_id) as on_leave FROM leave_requests WHERE '$today' BETWEEN start_date AND end_date AND status = 'approved'")->fetch(PDO::FETCH_ASSOC)['on_leave'] ?? 0;
    $pendingLeaves = $db->query("SELECT COUNT(*) as pending FROM leave_requests WHERE status = 'pending'")->fetch(PDO::FETCH_ASSOC)['pending'] ?? 0;
    $unreadNotifications = $db->query("SELECT COUNT(*) as unread FROM notifications WHERE user_id = {$_SESSION['user_id']} AND is_read = 0")->fetch(PDO::FETCH_ASSOC)['unread'] ?? 0;

    // Line Chart Data (Removed strict filter to ensure data shows)
    $chartLabels = [];
    $chartValues = [];
    $chartStmt = $db->query("SELECT DATE_FORMAT(created_at, '%b') as month_name, COUNT(*) as count 
                             FROM users 
                             GROUP BY YEAR(created_at), MONTH(created_at), month_name 
                             ORDER BY YEAR(created_at), MONTH(created_at)");
    while ($row = $chartStmt->fetch(PDO::FETCH_ASSOC)) {
        $chartLabels[] = $row['month_name'];
        $chartValues[] = (int)$row['count'];
    }

    // Doughnut Chart Data
    $deptLabels = [];
    $deptValues = [];
    $deptStmt = $db->query("SELECT department, COUNT(*) as count FROM users WHERE department IS NOT NULL AND department != '' GROUP BY department");
    while ($row = $deptStmt->fetch(PDO::FETCH_ASSOC)) {
        $deptLabels[] = $row['department'];
        $deptValues[] = (int)$row['count'];
    }

    echo json_encode([
        'success' => true,
        'stats' => [
            'total_employees' => (int)$totalEmployees,
            'present_today' => (int)$presentToday,
            'on_leave' => (int)$onLeave,
            'pending_leaves' => (int)$pendingLeaves,
            'unread_notifications' => (int)$unreadNotifications
        ],
        'chart_data' => ['labels' => $chartLabels, 'values' => $chartValues],
        'dept_data' => ['labels' => $deptLabels, 'values' => $deptValues]
    ]);
} catch(PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
