<?php

define('DB_HOST', 'localhost');
define('DB_NAME', 'kicd_requisitions');
define('DB_USER', 'root');
define('DB_PASS', ''); 
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();


try {
    $pdo = new PDO(
        'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4',
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    );
} catch (PDOException $e) {
    die('Database connection failed: ' . $e->getMessage());
}


function sanitize_input($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function generate_requisition_number() {
    global $pdo;
    $prefix = 'REQ';
    $year = date('Y');
    $month = date('m');
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM requisitions WHERE YEAR(created_at) = YEAR(CURDATE())");
    $stmt->execute();
    $result = $stmt->fetch();
    
    $count = $result['count'] + 1;
    return $prefix . '/' . $year . '/' . str_pad($count, 4, '0', STR_PAD_LEFT);
}

function send_notification($user_id, $type, $title, $message, $requisition_id = null) {
    global $pdo;
    
    $stmt = $pdo->prepare("
        INSERT INTO notifications (user_id, type, title, message, related_requisition_id) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$user_id, $type, $title, $message, $requisition_id]);
}

function get_user_by_id($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    return $stmt->fetch();
}

function check_permission($required_role) {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }

    $user = get_user_by_id($_SESSION['user_id']);
    if (!$user || $user['role'] != $required_role) {
        header('Location: dashboard.php');
        exit();
    }
}


?>
