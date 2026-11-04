<?php
session_start();
require_once 'config/database.php'; 


if (isset($_SESSION['user_id'])) {
    try {
        $db = (new Database())->getConnection();
        $stmt = $db->prepare("UPDATE users SET last_active = '2000-01-01 00:00:00' WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
    } catch (Exception $e) {
       
    }
}

session_destroy();
header('Location: login.php');
exit;
?>
