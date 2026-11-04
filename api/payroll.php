<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
session_start();

require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = (new Database())->getConnection();
$method = $_SERVER['REQUEST_METHOD'];
$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'] ?? '';

function getPayrollConfig($db) {
    $query = "SELECT payroll_config FROM company_settings WHERE id = 1";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if($result && $result['payroll_config']) { return json_decode($result['payroll_config'], true); }
    return ['house_rent_percent' => 50, 'medical_percent' => 10, 'travel_percent' => 5, 'pf_percent' => 10, 'tax_percent' => 5];
}

if ($method == 'GET') {
    // 🟢 স্মার্ট চেক: Employee হলে শুধু নিজের, HR হলে সবার ডাটা
    if ($user_type === 'employee') {
        $query = "SELECT p.*, u.first_name, u.last_name, u.employee_id 
                  FROM payroll p JOIN users u ON p.employee_id = u.id 
                  WHERE p.employee_id = :emp_id ORDER BY p.created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->execute([':emp_id' => $user_id]);
    } else {
        $query = "SELECT p.*, u.first_name, u.last_name, u.employee_id 
                  FROM payroll p JOIN users u ON p.employee_id = u.id 
                  ORDER BY p.created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->execute();
    }
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

// HR: এক ক্লিকে সবাইকে বেতন পাঠানো
if ($method == 'POST' && $user_type === 'hr' && isset($_GET['action']) && $_GET['action'] === 'pay_all') {
    try {
        $db->beginTransaction();
        $stmt = $db->prepare("SELECT id, employee_id, net_salary FROM payroll WHERE status = 'processed'");
        $stmt->execute();
        $payrolls = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (count($payrolls) === 0) {
            echo json_encode(['success' => false, 'message' => 'No processed payrolls found to pay.']); exit;
        }
        foreach($payrolls as $p) {
            $db->prepare("UPDATE users SET wallet_balance = wallet_balance + ? WHERE id = ?")->execute([$p['net_salary'], $p['employee_id']]);
            $db->prepare("UPDATE payroll SET status = 'paid' WHERE id = ?")->execute([$p['id']]);
        }
        $db->commit();
        echo json_encode(['success' => true, 'message' => 'All salaries successfully transferred to employee wallets!']);
    } catch (Exception $e) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Transaction failed: ' . $e->getMessage()]);
    }
    exit;
}

// HR: নতুন বেতন তৈরি করা
if ($method == 'POST' && $user_type === 'hr') {
    $input = json_decode(file_get_contents("php://input"), true);
    $period_start = $input['period_start']; $period_end = $input['period_end']; $payment_date = $input['payment_date'];
    $month_year = date('F Y', strtotime($period_start));
    
    $config = getPayrollConfig($db);
    $empStmt = $db->prepare("SELECT id, salary FROM users WHERE user_type = 'employee' AND status = 'active'");
    $empStmt->execute();
    $employees = $empStmt->fetchAll(PDO::FETCH_ASSOC);
    
    $successCount = 0;
    foreach($employees as $emp) {
        $basic_salary = $emp['salary'] ?: 30000;
        $house_rent = $basic_salary * ($config['house_rent_percent'] / 100);
        $medical = $basic_salary * ($config['medical_percent'] / 100);
        $travel = $basic_salary * ($config['travel_percent'] / 100);
        $total_allowance = $house_rent + $medical + $travel;
        $provident_fund = $basic_salary * ($config['pf_percent'] / 100);
        $tax = $basic_salary * ($config['tax_percent'] / 100);
        $total_deductions = $provident_fund + $tax;
        $net_salary = $basic_salary + $total_allowance - $total_deductions;
        
        $query = "INSERT INTO payroll (employee_id, period_start, period_end, month_year, basic_salary, house_rent, medical_allowance, travel_allowance, total_allowance, provident_fund, tax, total_deductions, net_salary, payment_date, status) 
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'processed')";
        if($db->prepare($query)->execute([$emp['id'], $period_start, $period_end, $month_year, $basic_salary, $house_rent, $medical, $travel, $total_allowance, $provident_fund, $tax, $total_deductions, $net_salary, $payment_date])) {
            $successCount++;
        }
    }
    echo json_encode(['success' => true, 'message' => "Payroll processed for $successCount employees"]);
}
?>
