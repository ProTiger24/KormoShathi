<?php
// এরর ডিসপ্লে বন্ধ করা হলো যাতে JSON ফরম্যাট নষ্ট না হয়
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();
header("Content-Type: application/json");
require_once '../config/database.php';

// Authorization Check
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = (new Database())->getConnection();
$input = json_decode(file_get_contents('php://input'), true);

if (!$input || !isset($input['action'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$user_id = $_SESSION['user_id'];
$action = $input['action'];

try {
    if ($action === 'update_profile') {
        // টেক্সট ফিল্ডগুলো নেওয়া
        $first_name = trim($input['first_name'] ?? '');
        $last_name  = trim($input['last_name'] ?? '');
        $email      = trim($input['email'] ?? '');
        $phone      = trim($input['phone'] ?? '');
        
        $address = trim($input['address'] ?? '');
        $emergency_contact = trim($input['emergency_contact'] ?? '');
        $blood_group = trim($input['blood_group'] ?? '');
        
        // 🟢 ডেট অফ বার্থ হ্যান্ডেল করা (খালি থাকলে NULL হবে, নাহলে সঠিক ডেট)
        $dob_input = trim($input['date_of_birth'] ?? '');
        $date_of_birth = !empty($dob_input) ? $dob_input : null;

        if (empty($first_name) || empty($email)) {
            echo json_encode(['success' => false, 'message' => 'First Name and Email are required']);
            exit;
        }

        // ডাটাবেসে আপডেট
        $stmt = $db->prepare("UPDATE users SET first_name=?, last_name=?, email=?, phone=?, address=?, emergency_contact=?, blood_group=?, date_of_birth=? WHERE id=?");
        $stmt->execute([$first_name, $last_name, $email, $phone, $address, $emergency_contact, $blood_group, $date_of_birth, $user_id]);

        // সেশন আপডেট
        $_SESSION['first_name'] = $first_name;
        $_SESSION['last_name'] = $last_name;

        echo json_encode(['success' => true, 'message' => 'Profile updated successfully!']);
    } 
    elseif ($action === 'update_password') {
        $current_password = $input['current_password'] ?? '';
        $new_password     = $input['new_password'] ?? '';

        if (empty($current_password) || empty($new_password)) {
            echo json_encode(['success' => false, 'message' => 'Both current and new passwords are required']);
            exit;
        }

        // বর্তমান পাসওয়ার্ড চেক
        $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($current_password, $user['password'])) {
            echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
            exit;
        }

        // নতুন পাসওয়ার্ড আপডেট
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        $stmt = $db->prepare("UPDATE users SET password = ? WHERE id = ?");
        $stmt->execute([$hashed_password, $user_id]);

        echo json_encode(['success' => true, 'message' => 'Password updated successfully!']);
    } 
    else {
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
    }
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>
