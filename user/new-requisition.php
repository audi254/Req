<?php
require_once '../config/database.php';

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

$user = get_user_by_id($_SESSION['user_id']);

$today = date('Y-m-d');

$stmt = $pdo->query("SELECT * FROM items WHERE is_active = 1 ORDER BY item_name");
$items = $stmt->fetchAll();


$message = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $title = sanitize_input($_POST['title']);
        $priority = sanitize_input($_POST['priority']);
        $required_date = sanitize_input($_POST['required_date']);

        $errors = [];
        if (empty($title)) {
            $errors[] = "Requisition Title is required.";
        } elseif (strlen($title) < 5 || strlen($title) > 100) {
            $errors[] = "Requisition Title must be between 5 and 100 characters.";
        } elseif (!preg_match('/[a-zA-Z]/', $title)) {
            $errors[] = "Requisition Title must contain at least one letter.";
        } elseif (preg_match('/[^a-zA-Z0-9 ,.&-]/', $title)) {
            $errors[] = "Requisition Title can only contain letters, numbers, spaces, commas, periods, ampersands, and hyphens.";
        }

        if (!empty($errors)) {
            $message = implode('<br>', $errors);
        } else {
            if ($required_date < $today) {
            $message = 'Required date cannot be before today.';
        } else {
           
            $stock_errors = array();
            if (isset($_POST['items']) && is_array($_POST['items'])) {
                foreach ($_POST['items'] as $item) {
                    $item_id = $item['item_id'];
                    $quantity = $item['quantity'];

                   
                    $stmt = $pdo->prepare("SELECT item_name, stock_quantity FROM items WHERE id = ?");
                    $stmt->execute([$item_id]);
                    $item_data = $stmt->fetch();

                    if ($item_data && $quantity > $item_data['stock_quantity']) {
                        $stock_errors[] = "Requested quantity for {$item_data['item_name']} exceeds available stock ({$item_data['stock_quantity']}).";
                    }
                }
            }

            if (!empty($stock_errors)) {
                $message = implode('<br>', $stock_errors);
            } else {
                $requisition_number = generate_requisition_number();

                $stmt = $pdo->prepare("
                    INSERT INTO requisitions (requisition_number, user_id, title, priority, required_date, status)
                    VALUES (?, ?, ?, ?, ?, 'pending')
                ");

                if ($stmt->execute([$requisition_number, $user['id'], $title, $priority, $required_date])) {
                    $requisition_id = $pdo->lastInsertId();

                    if (isset($_POST['items']) && is_array($_POST['items'])) {
                        foreach ($_POST['items'] as $item) {
                            $item_id = $item['item_id'];
                            $quantity = $item['quantity'];

                            $stmt = $pdo->prepare("
                                INSERT INTO requisition_items (requisition_id, item_id, quantity)
                                VALUES (?, ?, ?)
                            ");
                            $stmt->execute([$requisition_id, $item_id, $quantity]);
                        }
                    }

                    $message = 'Requisition created successfully!';
                    $download_link = '<a href="generate-pdf.php?id=' . $requisition_id . '" class="download-pdf-btn">Download PDF</a>';
                } else {
                    $message = 'Error creating requisition. Please try again.';
                    $download_link = '';
                }
            }
        }
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>New Requisition - KICD</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link rel="stylesheet" href="../assets/css/new-requisition-enhanced.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        .username {
            cursor: pointer;
            position: relative;
        }
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="logo">
                <img src="../assets/images.jpeg" alt="KICD Logo"> <span>KICD</span>
            </div>
            <div class="system-name">Requisition Management System</div>
            <nav class="navigation">
                <h3>Navigation</h3>
                <ul>
                    <li>
                        <span class="material-icons">dashboard</span>
                        <a href="dashboard.php">Dashboard</a>
                    </li>
                    <li>
                        <span class="material-icons">add_box</span>
                        <a href="new-requisition.php">New Requisition</a>
                    </li>
                    <li>
                        <span class="material-icons">assignment</span>
                        <a href="my-requisitions.php">My Requisitions</a>
                    </li>
                    <li>
                        <span class="material-icons">list_alt</span>
                        <a href="item-catalog.php">Item Catalog</a>
                    </li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
<header class="navbar" style="display: flex; align-items: center; gap: 10px; padding: 10px 20px; background-color: #f5f5f5; border-bottom: 1px solid #ddd;">
    <div class="user-info" style="display: flex; align-items: center; gap: 10px;">
        <span class="material-icons" style="font-size: 36px; color: #555;">account_circle</span>
        <div class="user-details" style="display: flex; flex-direction: column;">
            <span class="username" style="font-weight: 600; font-size: 16px;"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></span>
            <span class="role" style="font-size: 14px; color: #777;"><?php echo htmlspecialchars($user['role']); ?></span>
        </div>
    </div>
</header>

            <section class="welcome-section">
                <h1>Create New Requisition</h1>
                <p>Fill in the details below to create a new requisition</p>
            </section>

            <div class="form-container">
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo strpos($message, 'success') !== false ? 'success' : 'error'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <?php if (isset($download_link) && $download_link): ?>
                    <div class="download-section">
                        <?php echo $download_link; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="title">Requisition Title</label>
                        <input type="text" id="title" name="title" required placeholder="e.g., Office Supplies for Q1">
                    </div>

                    <div class="form-group">
                        <label for="priority">Priority</label>
                        <select id="priority" name="priority" required>
                            <option value="low">Low</option>
                            <option value="medium" selected>Medium</option>
                            <option value="high">High</option>
                            <option value="urgent">Urgent</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="required_date">Required Date</label>
                        <input type="date" id="required_date" name="required_date" required min="<?php echo $today; ?>">
                    </div>

                    <div class="items-section">
                        <h3>Add Items</h3>
                        <div id="items-container">
                            <div class="item-row">
                                <select name="items[0][item_id]" required>
                                    <option value="">Select Item</option>
                                    <?php foreach ($items as $item): ?>
                                        <option value="<?php echo $item['id']; ?>">
                                            <?php echo htmlspecialchars($item['item_name']); ?> - KSh <?php echo number_format($item['unit_price']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <input type="number" name="items[0][quantity]" placeholder="Qty" min="1" required>
                                <button type="button" class="remove-item-btn" onclick="removeItem(this)">
                                    <span class="material-icons">delete</span>
                                </button>
                            </div>
                        </div>
                        <button type="button" class="add-item-btn" onclick="addItem()">
                            <span class="material-icons">add_circle</span>
                            Add Another Item
                        </button>
                    </div>

                    <button type="submit" class="new-requisition-btn">Create Requisition</button>
                </form>
            </div>
        </main>
    </div>

    <script>
        let itemCount = 1;

        function addItem() {
            const container = document.getElementById('items-container');
            const newRow = document.createElement('div');
            newRow.className = 'item-row';
            newRow.innerHTML = `
                <select name="items[${itemCount}][item_id]" required>
                    <option value="">Select Item</option>
                    <?php foreach ($items as $item): ?>
                        <option value="<?php echo $item['id']; ?>">
                            <?php echo htmlspecialchars($item['item_name']); ?> - KSh <?php echo number_format($item['unit_price']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <input type="number" name="items[${itemCount}][quantity]" placeholder="Qty" min="1" required>
<button type="button" class="remove-item-btn" onclick="removeItem(this)">
    <span class="material-icons">delete</span>
</button>
            `;
            container.appendChild(newRow);
            itemCount++;
        }

        function removeItem(button) {
            button.parentElement.remove();
        }
    </script>
</body>
</html>
