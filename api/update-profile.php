<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);

session_start();
header("Content-Type: application/json");
require_once '../config/database.php';

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
        // বর্তমান ডেটা বের করা (যাতে যেগুলো ফর্মে নেই, সেগুলো ডিলিট না হয়ে আগেরটাই থাকে)
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $current_user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$current_user) {
            echo json_encode(['success' => false, 'message' => 'User not found']);
            exit;
        }

        // 🟢 যদি ইনপুটে ফিল্ড থাকে, তবেই আপডেট হবে। না থাকলে আগের ডেটাই থাকবে।
        $first_name = isset($input['first_name']) ? trim($input['first_name']) : ($current_user['first_name'] ?? '');
        $last_name  = isset($input['last_name']) ? trim($input['last_name']) : ($current_user['last_name'] ?? '');
        $email      = isset($input['email']) ? trim($input['email']) : ($current_user['email'] ?? '');
        $phone      = isset($input['phone']) ? trim($input['phone']) : ($current_user['phone'] ?? '');
        
        $address = isset($input['address']) ? trim($input['address']) : ($current_user['address'] ?? '');
        $emergency_contact = isset($input['emergency_contact']) ? trim($input['emergency_contact']) : ($current_user['emergency_contact'] ?? '');
        $blood_group = isset($input['blood_group']) ? trim($input['blood_group']) : ($current_user['blood_group'] ?? '');
        
        $dob_input = isset($input['date_of_birth']) ? trim($input['date_of_birth']) : ($current_user['date_of_birth'] ?? '');
        $date_of_birth = !empty($dob_input) ? $dob_input : null;

        if (empty($first_name) || empty($email)) {
            echo json_encode(['success' => false, 'message' => 'First Name and Email are required']);
            exit;
        }

        $updateStmt = $db->prepare("UPDATE users SET first_name=?, last_name=?, email=?, phone=?, address=?, emergency_contact=?, blood_group=?, date_of_birth=? WHERE id=?");
        $updateStmt->execute([$first_name, $last_name, $email, $phone, $address, $emergency_contact, $blood_group, $date_of_birth, $user_id]);

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

        $stmt = $db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !password_verify($current_password, $user['password'])) {
            echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
            exit;
        }

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
