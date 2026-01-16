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


$stmt = $pdo->query("SELECT * FROM items WHERE is_active = 1 ORDER BY item_name");
$items = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Item Catalog - KICD</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css" />
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
    <style>
        .catalog-container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .item-list {
            display: grid;
            grid-template-columns: repeat(auto-fill,minmax(250px,1fr));
            gap: 20px;
        }
        .item-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            display: flex;
            flex-direction: column;
            align-items: center;
            transition: box-shadow 0.3s ease;
        }
        .item-card:hover {
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .item-image {
            width: 100%;
            height: 150px;
            object-fit: contain;
            margin-bottom: 10px;
        }
        .item-name {
            font-weight: 600;
            font-size: 18px;
            margin-bottom: 5px;
            text-align: center;
        }
        .item-category {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }
        .item-price {
            font-weight: 700;
            color: #002855;
            margin-bottom: 10px;
        }
        .item-description {
            font-size: 14px;
            color: #333;
            text-align: center;
            flex-grow: 1;
        }
        .item-stock {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }
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
                <img src="../assets/images.jpeg" alt="KICD Logo" /> <span>KICD</span>
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
                    <li class="active">
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
                <h1>Item Catalog</h1>
                <p>Browse all available items for requisition</p>
            </section>

            <section class="catalog-container">
                <div class="item-list">
                    <?php foreach ($items as $item): ?>
                        <div class="item-card">
                            <?php
                            $image_src = $item['image_url'] ? $item['image_url'] : '../assets/Paper Ream.jpeg';

                            if (strpos(strtolower($item['item_name']), 'desktop computer') !== false) {
                                $image_src = '../assets/desktopimage.jpg';
                            } elseif (strpos(strtolower($item['item_name']), 'hp laserjet') !== false) {
                                $image_src = '../assets/catridge.jpg';
                            } elseif (strpos(strtolower($item['item_name']), 'office chair') !== false) {
                                $image_src = '../assets/officechair.jpg';
                            } elseif (strpos(strtolower($item['item_name']), 'whiteboard') !== false) {
                                $image_src = '../assets/whiteboard.jpeg';
                            }
                            ?>
                            <img src="<?php echo htmlspecialchars($image_src); ?>" alt="<?php echo htmlspecialchars($item['item_name']); ?>" class="item-image" />
                            <div class="item-name"><?php echo htmlspecialchars($item['item_name']); ?></div>
                            <div class="item-category"><?php echo htmlspecialchars($item['category']); ?></div>
                            <div class="item-price">KSh <?php echo number_format($item['unit_price'], 2); ?></div>
                            <div class="item-stock">Stock: <?php echo htmlspecialchars($item['stock_quantity']); ?></div>
                            <div class="item-description"><?php echo htmlspecialchars($item['description']); ?></div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </main>
    </div>

    <script>
    </script>
</body>
</html>
