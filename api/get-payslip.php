<?php
header("Content-Type: application/json");
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$id = $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'] ?? '';

$db = (new Database())->getConnection();

$stmt = $db->prepare("SELECT p.*, u.first_name, u.last_name, u.employee_id as emp_code 
                       FROM payroll p 
                       JOIN users u ON p.employee_id = u.id 
                       WHERE p.id = ?");
$stmt->execute([$id]);
$payroll = $stmt->fetch(PDO::FETCH_ASSOC);

if ($payroll) {
    if ($user_type === 'employee' && $payroll['employee_id'] != $user_id) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized to view this payslip']);
        exit;
    }
    echo json_encode(['success' => true, 'data' => $payroll]);
} else {
    echo json_encode(['success' => false, 'message' => 'Payslip not found']);
}
?>
