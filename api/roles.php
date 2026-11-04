<?php
session_start();
header("Content-Type: application/json");
require_once '../config/database.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'hr') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$db = (new Database())->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $stmt = $db->query("SELECT * FROM roles ORDER BY id ASC");
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
}
elseif ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (empty($input['role_name']) || empty($input['access_level'])) {
        echo json_encode(['success' => false, 'message' => 'Role name and access level are required.']);
        exit;
    }
    try {
        if (isset($input['id']) && $input['id'] > 0) {
            $stmt = $db->prepare("UPDATE roles SET role_name = ?, access_level = ? WHERE id = ?");
            $stmt->execute([$input['role_name'], $input['access_level'], $input['id']]);
            echo json_encode(['success' => true, 'message' => 'Role updated successfully!']);
        } else {
            $stmt = $db->prepare("INSERT INTO roles (role_name, access_level) VALUES (?, ?)");
            $stmt->execute([$input['role_name'], $input['access_level']]);
            echo json_encode(['success' => true, 'message' => 'Role created successfully!']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
}
elseif ($method === 'DELETE') {
    $input = json_decode(file_get_contents('php://input'), true);
    if (isset($input['id'])) {
        $stmt = $db->prepare("DELETE FROM roles WHERE id = ?");
        $stmt->execute([$input['id']]);
        echo json_encode(['success' => true, 'message' => 'Role deleted successfully!']);
    }
}
?>
