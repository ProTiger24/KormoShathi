<?php
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

if ($method == 'GET') {
    $stmt = $db->prepare("SELECT * FROM tasks WHERE employee_id = ? ORDER BY created_at DESC");
    $stmt->execute([$user_id]);
    echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

if ($method == 'POST') {
    $input = json_decode(file_get_contents("php://input"), true);
    
    // 🟢 নতুন টাস্ক তৈরি করা
    if (isset($input['action']) && $input['action'] === 'create') {
        $task_name = trim($input['task_name'] ?? '');
        $description = trim($input['description'] ?? '');
        $deadline = !empty($input['deadline']) ? $input['deadline'] : null;

        if (empty($task_name)) {
            echo json_encode(['success' => false, 'message' => 'Task name is required.']);
            exit;
        }

        $stmt = $db->prepare("INSERT INTO tasks (employee_id, task_name, description, deadline, status, progress) VALUES (?, ?, ?, ?, 'pending', 0)");
        if ($stmt->execute([$user_id, $task_name, $description, $deadline])) {
            echo json_encode(['success' => true, 'message' => 'Task added successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add task.']);
        }
        exit;
    }

    // 🟢 টাস্ক আপডেট করা
    if (isset($input['action']) && $input['action'] === 'update') {
        $id = $input['id'];
        $progress = (int)$input['progress'];
        $status = $input['status'];
        
        if ($progress >= 100) { $status = 'completed'; $progress = 100; }
        elseif ($progress > 0 && $status == 'pending') { $status = 'in_progress'; }

        $stmt = $db->prepare("UPDATE tasks SET progress = ?, status = ? WHERE id = ? AND employee_id = ?");
        if ($stmt->execute([$progress, $status, $id, $user_id])) {
            echo json_encode(['success' => true, 'message' => 'Task updated successfully!']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to update task.']);
        }
        exit;
    }
}
?>
