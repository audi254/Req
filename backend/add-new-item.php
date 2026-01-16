<?php
require_once '../config/database.php';

header('Content-Type: application/json');


session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}


$image_url = null;
if (isset($_FILES['item_image']) && $_FILES['item_image']['error'] === UPLOAD_ERR_OK) {
    $upload_dir = '../uploads/items/';
    
    
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    $file_extension = pathinfo($_FILES['item_image']['name'], PATHINFO_EXTENSION);
    $filename = uniqid('item_') . '.' . $file_extension;
    $target_path = $upload_dir . $filename;
    
   
    $allowed_types = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    if (!in_array(strtolower($file_extension), $allowed_types)) {
        echo json_encode(['error' => 'Invalid file type. Only JPG, PNG, GIF, and WebP files are allowed.']);
        exit();
    }
    
    
    if ($_FILES['item_image']['size'] > 5 * 1024 * 1024) {
        echo json_encode(['error' => 'File size too large. Maximum 5MB allowed.']);
        exit();
    }
    
    if (move_uploaded_file($_FILES['item_image']['tmp_name'], $target_path)) {
        $image_url = 'uploads/items/' . $filename;
    }
}


$item_name = $_POST['item_name'] ?? '';
$category = $_POST['category'] ?? '';
$unit_price = $_POST['unit_price'] ?? 0;
$description = $_POST['description'] ?? '';


if (empty($item_name) || empty($category) || empty($unit_price)) {
    echo json_encode(['error' => 'Please fill in all required fields']);
    exit();
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("
        INSERT INTO items (item_name, category, unit_price, description, image_url, is_active, created_at) 
        VALUES (?, ?, ?, ?, ?, 1, NOW())
    ");
    
    $stmt->execute([
        $item_name,
        $category,
        $unit_price,
        $description,
        $image_url
    ]);
    
    echo json_encode(['success' => true, 'item_id' => $pdo->lastInsertId()]);
    
} catch (PDOException $e) {
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>
