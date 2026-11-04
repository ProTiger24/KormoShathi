<?php
header("Content-Type: application/json; charset=UTF-8");
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) { echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit; }

$db = (new Database())->getConnection();
$user_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

if ($method == 'POST') {
    $input = json_decode(file_get_contents("php://input"), true);
    
    // 🟢 Profile Update
    if (isset($input['action']) && $input['action'] === 'update_profile') {
        try {
            $stmt = $db->prepare("UPDATE users SET first_name=?, last_name=?, phone=?, email=?, address=?, emergency_contact=?, blood_group=?, date_of_birth=? WHERE id=?");
            $stmt->execute([
                $input['first_name'], $input['last_name'], $input['phone'], 
                $input['email'], $input['address'], $input['emergency_contact'],
                $input['blood_group'], $input['date_of_birth'], $user_id
            ]);
            
            // সেশন আপডেট
            $_SESSION['first_name'] = $input['first_name'];
            $_SESSION['last_name'] = $input['last_name'];
            
            echo json_encode(['success' => true, 'message' => 'Profile updated successfully!']);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
        }
        exit;
    }

    // 🟢 Password Update
    if (isset($input['action']) && $input['action'] === 'update_password') {
        $current_pass = $input['current_password'];
        $new_pass = $input['new_password'];
        
        $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $hash = $stmt->fetchColumn();
        
        if (password_verify($current_pass, $hash)) {
            $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
            $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            if ($stmt->execute([$new_hash, $user_id])) {
                echo json_encode(['success' => true, 'message' => 'Password changed successfully!']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to update password.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Current password is incorrect!']);
        }
        exit;
    }
}
?>
