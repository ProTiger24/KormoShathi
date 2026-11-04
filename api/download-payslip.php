<?php
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(403); echo "Unauthorized"; exit;
}

$id = $_GET['id'] ?? 0;
$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'] ?? '';

$db = (new Database())->getConnection();

// 🟢 u.employee_id এর পরিবর্তে as emp_code ব্যবহার করা হয়েছে যাতে p.employee_id ওভাররাইট না হয়
$stmt = $db->prepare("SELECT p.*, u.first_name, u.last_name, u.employee_id as emp_code 
                       FROM payroll p 
                       JOIN users u ON p.employee_id = u.id 
                       WHERE p.id = ?");
$stmt->execute([$id]);
$payroll = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$payroll) {
    http_response_code(404); echo "Payslip not found"; exit;
}

// 🟢 সিকিউরিটি চেক (এখন এটি ঠিকমতো কাজ করবে)
if ($user_type === 'employee' && $payroll['employee_id'] != $user_id) {
    http_response_code(403); echo "You do not have permission to download this payslip."; exit;
}

$filename = 'payslip_' . $payroll['emp_code'] . '_' . date('Ymd') . '.html';

$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Payslip</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        .header { background: #2c5aa0; color: white; padding: 20px; text-align: center; }
        .body { padding: 20px; border: 1px solid #ddd; }
        .row { display: flex; justify-content: space-between; padding: 8px 0; border-bottom: 1px solid #eee; }
        .total { font-weight: bold; font-size: 18px; border-bottom: 2px solid #2c5aa0; }
        .footer { text-align: center; padding: 15px; background: #f8f9fa; }
        .label { color: #6c757d; }
    </style>
</head>
<body>
    <div class="header">
        <h2>KormoShathi HR Solutions</h2>
        <p>Payslip</p>
    </div>
    <div class="body">
        <div style="display:flex;justify-content:space-between;">
            <div>
                <p><strong>Employee:</strong> ' . $payroll['first_name'] . ' ' . $payroll['last_name'] . '</p>
                <p><strong>Employee ID:</strong> ' . $payroll['emp_code'] . '</p>
            </div>
            <div>
                <p><strong>Payment Date:</strong> ' . ($payroll['payment_date'] ?? 'N/A') . '</p>
                <p><strong>Status:</strong> ' . ($payroll['status'] ?? 'Processed') . '</p>
            </div>
        </div>
        <hr>
        <div class="row"><span class="label">Basic Salary</span><span>৳ ' . number_format($payroll['basic_salary'] ?? 0, 2) . '</span></div>
        <div class="row"><span class="label">House Rent</span><span>৳ ' . number_format($payroll['house_rent'] ?? 0, 2) . '</span></div>
        <div class="row"><span class="label">Medical Allowance</span><span>৳ ' . number_format($payroll['medical_allowance'] ?? 0, 2) . '</span></div>
        <div class="row"><span class="label">Travel Allowance</span><span>৳ ' . number_format($payroll['travel_allowance'] ?? 0, 2) . '</span></div>
        <div class="row"><span class="label">Total Allowance</span><span>৳ ' . number_format($payroll['total_allowance'] ?? 0, 2) . '</span></div>
        <hr>
        <div class="row"><span class="label">Provident Fund</span><span>৳ ' . number_format($payroll['provident_fund'] ?? 0, 2) . '</span></div>
        <div class="row"><span class="label">Tax</span><span>৳ ' . number_format($payroll['tax'] ?? 0, 2) . '</span></div>
        <div class="row"><span class="label">Total Deductions</span><span>৳ ' . number_format($payroll['total_deductions'] ?? 0, 2) . '</span></div>
        <hr>
        <div class="row total"><span>Net Salary</span><span style="color:#2c5aa0;">৳ ' . number_format($payroll['net_salary'] ?? 0, 2) . '</span></div>
    </div>
    <div class="footer">
        <p class="text-muted">This is a computer-generated payslip.</p>
    </div>
</body>
</html>';

header("Content-Type: text/html; charset=UTF-8");
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($html));
echo $html;
exit;
?>
