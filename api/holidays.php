<?php
header("Content-Type: application/json; charset=UTF-8");
session_start();
require_once '../config/database.php';

$db = (new Database())->getConnection();
$method = $_SERVER['REQUEST_METHOD'];

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($method == 'GET') {
    try {
        $stmt = $db->query("SELECT * FROM holidays ORDER BY start_date ASC");
        $holidays = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $holidays]);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

if (($method == 'POST' || $method == 'PUT') && $_SESSION['user_type'] === 'hr') {
    $input = json_decode(file_get_contents("php://input"), true);
    
    $id = $input['id'] ?? 0;
    $name = trim($input['name'] ?? '');
    $start = trim($input['start_date'] ?? '');
    $end = trim($input['end_date'] ?? '');
    $type = trim($input['type'] ?? '');

    // 🟢 End date খালি থাকলে Start date ই End date হিসেবে সেভ হবে
    if (empty($end)) {
        $end = $start;
    }

    if (empty($name) || empty($start) || empty($type)) {
        echo json_encode(['success' => false, 'message' => 'Name, Start Date and Type are required.']);
        exit;
    }

    try {
        if ($method == 'POST') {
            $stmt = $db->prepare("INSERT INTO holidays (holiday_name, start_date, end_date, holiday_type) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$name, $start, $end, $type])) {
                echo json_encode(['success' => true, 'message' => 'Holiday added successfully!']);
            }
        } else {
            $stmt = $db->prepare("UPDATE holidays SET holiday_name=?, start_date=?, end_date=?, holiday_type=? WHERE id=?");
            if ($stmt->execute([$name, $start, $end, $type, $id])) {
                echo json_encode(['success' => true, 'message' => 'Holiday updated successfully!']);
            }
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}

if ($method == 'DELETE' && $_SESSION['user_type'] === 'hr') {
    $id = $_GET['id'] ?? 0;
    try {
        $stmt = $db->prepare("DELETE FROM holidays WHERE id = ?");
        if ($stmt->execute([$id])) {
            echo json_encode(['success' => true, 'message' => 'Holiday deleted.']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
    }
    exit;
}
?>
