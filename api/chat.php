<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
session_start();
require_once '../config/database.php';

if (!isset($_SESSION['user_id'])) { echo json_encode(['success' => false, 'message' => 'Unauthorized']); exit; }

$db = (new Database())->getConnection();
$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'];
$method = $_SERVER['REQUEST_METHOD'];

// ইউজার অনলাইন আপডেট
try {
    $db->prepare("UPDATE users SET last_active = NOW() WHERE id = ?")->execute([$user_id]);
} catch (Exception $e) {}

function getLastSeen($last_active) {
    if (empty($last_active) || strtotime($last_active) < strtotime('2001-01-01')) return 'Offline';
    $diff = time() - strtotime($last_active);
    if ($diff < 120) return 'Active now';
    if ($diff < 3600) return floor($diff / 60) . ' mins ago';
    if ($diff < 86400) return floor($diff / 3600) . ' hours ago';
    return date('d M', strtotime($last_active));
}

if ($method == 'GET') {
    $action = $_GET['action'] ?? '';

    if ($action == 'get_contacts') {
        $contacts = [];
        $stmtUser = $db->prepare("SELECT department FROM users WHERE id = ?");
        $stmtUser->execute([$user_id]);
        $my_dept = $stmtUser->fetchColumn();

        // 🟢 Groups
        $stmt = $db->query("SELECT id, name FROM conversations WHERE type = 'group' ORDER BY name ASC");
        $groups = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($groups as $g) {
            if ($user_type === 'hr' || $g['name'] === $my_dept) {
                
                // গ্রুপের সর্বশেষ মেসেজের সময় বের করা
                $stmt_last = $db->prepare("SELECT MAX(created_at) FROM messages WHERE conversation_id = ?");
                $stmt_last->execute([$g['id']]);
                $last_time = $stmt_last->fetchColumn() ?: '1970-01-01 00:00:00';

                $contacts[] = [
                    'id' => $g['id'], 
                    'unique_id' => 'g_' . $g['id'],
                    'first_name' => $g['name'] . ' Group', 
                    'last_name' => '',
                    'user_type' => 'group', 
                    'initials' => strtoupper(substr($g['name'],0,2)),
                    'is_online' => true, 
                    'is_group' => true, 
                    'last_seen' => 'Group Chat', 
                    'profile_picture' => null,
                    'last_message_time' => $last_time // 🟢 নতুন
                ];
            }
        }

        // 🟢 Users
        if ($user_type === 'hr') {
            $stmt = $db->prepare("SELECT id, first_name, last_name, user_type, last_active, profile_picture FROM users WHERE id != ? ORDER BY user_type DESC, first_name ASC");
            $stmt->execute([$user_id]);
        } else {
            $stmt = $db->prepare("SELECT id, first_name, last_name, user_type, last_active, profile_picture FROM users WHERE id != ? AND (user_type = 'hr' OR (user_type = 'employee' AND department = ?)) ORDER BY user_type DESC, first_name ASC");
            $stmt->execute([$user_id, $my_dept]);
        }
        
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($users as $u) {
            // ইউজারের সর্বশেষ মেসেজের সময় বের করা
            $stmt_last = $db->prepare("
                SELECT MAX(m.created_at) FROM messages m 
                JOIN conversations c ON m.conversation_id = c.id 
                JOIN conversation_members cm1 ON c.id = cm1.conversation_id AND cm1.user_id = ? 
                JOIN conversation_members cm2 ON c.id = cm2.conversation_id AND cm2.user_id = ? 
                WHERE c.type = 'individual'
            ");
            $stmt_last->execute([$user_id, $u['id']]);
            $last_time = $stmt_last->fetchColumn() ?: '1970-01-01 00:00:00';

            $is_online = (strtotime($u['last_active']) > strtotime('-2 minutes'));
            $contacts[] = [
                'id' => $u['id'], 
                'unique_id' => 'u_' . $u['id'],
                'first_name' => $u['first_name'], 
                'last_name' => $u['last_name'],
                'user_type' => $u['user_type'], 
                'initials' => strtoupper(substr($u['first_name'],0,1) . substr($u['last_name'],0,1)),
                'is_online' => $is_online, 
                'is_group' => false, 
                'last_seen' => getLastSeen($u['last_active']),
                'profile_picture' => $u['profile_picture'],
                'last_message_time' => $last_time // 🟢 নতুন
            ];
        }

        // 🟢 সময় অনুযায়ী সাজানো (সর্বশেষ মেসেজ সবার উপরে থাকবে)
        usort($contacts, function($a, $b) {
            return strtotime($b['last_message_time']) - strtotime($a['last_message_time']);
        });

        echo json_encode(['success' => true, 'data' => $contacts]);
        exit;
    }

    if ($action == 'get_messages') {
        $contact_id = $_GET['contact_id'] ?? 0;
        $is_group = $_GET['is_group'] ?? 0;

        if ($is_group == 1) {
            $conv_id = $contact_id; 
        } else {
            $stmt = $db->prepare("SELECT c.id FROM conversations c JOIN conversation_members cm1 ON c.id = cm1.conversation_id AND cm1.user_id = ? JOIN conversation_members cm2 ON c.id = cm2.conversation_id AND cm2.user_id = ? WHERE c.type = 'individual' LIMIT 1");
            $stmt->execute([$user_id, $contact_id]);
            $conv = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$conv) {
                $db->prepare("INSERT INTO conversations (type) VALUES ('individual')")->execute();
                $conv_id = $db->lastInsertId();
                $db->prepare("INSERT INTO conversation_members (conversation_id, user_id) VALUES (?, ?), (?, ?)")->execute([$conv_id, $user_id, $conv_id, $contact_id]);
            } else {
                $conv_id = $conv['id'];
            }
        }

        $stmt = $db->prepare("SELECT m.*, u.first_name, u.last_name FROM messages m JOIN users u ON m.sender_id = u.id WHERE m.conversation_id = ? ORDER BY m.created_at ASC");
        $stmt->execute([$conv_id]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $db->prepare("UPDATE messages SET is_read = 1 WHERE conversation_id = ? AND sender_id != ?")->execute([$conv_id, $user_id]);
        echo json_encode(['success' => true, 'conversation_id' => $conv_id, 'messages' => $messages]);
        exit;
    }
}

if ($method == 'POST') {
    try {
        $conv_id = $_POST['conversation_id'] ?? 0;
        $receiver_id = $_POST['receiver_id'] ?? 0;
        $is_group = $_POST['is_group'] ?? 0;
        $message = trim($_POST['message'] ?? '');
        
        $attachment = null; $file_name = null;

        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = '../uploads/chat/';
            if (!is_dir($uploadDir)) { mkdir($uploadDir, 0777, true); }
            
            $fileName = $_FILES['file']['name'];
            $fileExt = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
            $newFileName = uniqid() . '.' . $fileExt;
            
            if (move_uploaded_file($_FILES['file']['tmp_name'], $uploadDir . $newFileName)) {
                $attachment = 'uploads/chat/' . $newFileName;
                $file_name = $fileName;
            }
        }

        if (!$conv_id && $receiver_id && !$is_group) {
             $stmt = $db->prepare("SELECT c.id FROM conversations c JOIN conversation_members cm1 ON c.id = cm1.conversation_id AND cm1.user_id = ? JOIN conversation_members cm2 ON c.id = cm2.conversation_id AND cm2.user_id = ? WHERE c.type = 'individual' LIMIT 1");
            $stmt->execute([$user_id, $receiver_id]);
            $conv = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($conv) {
                $conv_id = $conv['id'];
            } else {
                $db->prepare("INSERT INTO conversations (type) VALUES ('individual')")->execute();
                $conv_id = $db->lastInsertId();
                $db->prepare("INSERT INTO conversation_members (conversation_id, user_id) VALUES (?, ?), (?, ?)")->execute([$conv_id, $user_id, $conv_id, $receiver_id]);
            }
        }

        if ($conv_id && ($message !== '' || $attachment)) {
            $stmt = $db->prepare("INSERT INTO messages (conversation_id, sender_id, message, attachment, file_name) VALUES (?, ?, ?, ?, ?)");
            if ($stmt->execute([$conv_id, $user_id, $message, $attachment, $file_name])) {
                
                $sender_name = $_SESSION['first_name'] ?? 'Someone';
                if ($is_group == 1) {
                    $g_stmt = $db->prepare("SELECT name FROM conversations WHERE id = ?");
                    $g_stmt->execute([$conv_id]);
                    $g_name = $g_stmt->fetchColumn();
                    $h_stmt = $db->query("SELECT id FROM users WHERE user_type = 'hr'");
                    $hrs = $h_stmt->fetchAll(PDO::FETCH_COLUMN);
                    foreach($hrs as $hr_id) {
                        if($hr_id != $user_id) {
                            $db->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'info')")->execute([$hr_id, "New Group Message", "$sender_name sent a message in $g_name Group"]);
                        }
                    }
                } else {
                    $db->prepare("INSERT INTO notifications (user_id, title, message, type) VALUES (?, ?, ?, 'info')")->execute([$receiver_id, "New Message", "$sender_name sent you a message."]);
                }

                echo json_encode(['success' => true, 'conversation_id' => $conv_id]);
            } else {
                echo json_encode(['success' => false, 'error' => 'Database error: ' . implode(" ", $stmt->errorInfo())]);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Empty message or invalid conversation']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}
?>
