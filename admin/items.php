<?php
require_once '../config/database.php';

header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/login.php');
    exit();
}

check_permission('admin');

$user = get_user_by_id($_SESSION['user_id']);

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['product_name']) && isset($_POST['product_price']) && isset($_POST['product_category']) && isset($_POST['stock_quantity'])) {
        $product_code = "#" . substr(rand(), 0, 4);
        $errors = array();
        $product_name = sanitize_input($_POST['product_name']);
        $product_price = sanitize_input($_POST['product_price']);
        $product_category = sanitize_input($_POST['product_category']);
        $stock_quantity = sanitize_input($_POST['stock_quantity']);
        $description = sanitize_input($_POST['description']);
        $name_img = $_FILES['item_image']['name'];
        $size = $_FILES['item_image']['size'];
        $type = $_FILES['item_image']['type'];
        $location = "../assets/";
        if (empty($product_name) || empty($product_price) || empty($product_category) || empty($stock_quantity)) {
            $errors[] = "You must Provide All Fields First";
        } else {
            if (strlen($product_name) < 3) {
                $errors[] = "Product Name Too Short";
            }
            if (!is_numeric($product_price)) {
                $errors[] = "Invalid Price";
            }
            if (!is_numeric($stock_quantity) || $stock_quantity < 0) {
                $errors[] = "Invalid Stock Quantity";
            }
            if (is_uploaded_file($_FILES['item_image']['tmp_name'])) {
                $valid_formats = array("jpeg", "jpg", "png", "bmp", "JPG", "PNG", "JPEG", "BMP", "docx", "doc", "pdf", "odt");
                list($txt, $ext) = explode(".", $name_img);
                if (in_array($ext, $valid_formats)) {
                    if ($size < (1024 * 1024)) {
                        $actual_img_nam = rand();
                        $tmp_name = $_FILES['item_image']['tmp_name'];
                        if (move_uploaded_file($tmp_name, $location . $actual_img_nam . ".png")) {
                            $product_photo = $actual_img_nam . ".png";
                           
                            $stmt = $pdo->prepare("
                                INSERT INTO items (item_code, item_name, category, unit_price, stock_quantity, description, image_url, is_active, created_at)
                                VALUES (?, ?, ?, ?, ?, ?, ?, 1, NOW())
                            ");
                            if ($stmt->execute([$product_code, $product_name, $product_category, $product_price, $stock_quantity, $description, '../assets/' . $product_photo])) {
                                $message = "Item added to catalog successfully!";
                            } else {
                                $message = "Error Adding Product Try Again Please";
                            }
                        } else {
                            $errors[] = "Error uploading file";
                        }
                    } else {
                        $errors[] = "Photo Size Too Large";
                    }
                } else {
                    $errors[] = "Invalid Document Format";
                }
            } else {
                $errors[] = "Error Adding Product Try Again Please";
            }
        }
        if (!empty($errors)) {
            $message = implode('<br>', $errors);
        }
    } elseif (isset($_POST['remove_item'])) {
        $item_id = $_POST['item_id'];
        $stmt = $pdo->prepare("UPDATE items SET is_active = FALSE WHERE id = ?");
        if ($stmt->execute([$item_id])) {
            $message = 'Item removed successfully!';
        } else {
            $message = 'Error removing item.';
        }
    }
}

$stmt = $pdo->query("SELECT * FROM items WHERE is_active = 1 ORDER BY item_name");
$items = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Items - KICD Admin</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        .items-container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .add-item-form {
            margin-bottom: 30px;
            padding: 20px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        .form-group input, .form-group textarea, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .items-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        .item-card {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 15px;
            background: white;
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
        }
        .item-stock {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }
        .image-upload-container {
            border: 2px dashed #ddd;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            margin-bottom: 15px;
            position: relative;
        }
        .upload-icon {
            font-size: 48px;
            color: #666;
            margin-bottom: 10px;
        }
        .upload-text {
            color: #666;
            margin-bottom: 10px;
        }
        .upload-input {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            cursor: pointer;
        }
        .upload-label {
            background: #007bff;
            color: white;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            display: inline-block;
        }
        .image-preview {
            max-width: 100%;
            max-height: 200px;
            margin-top: 10px;
            display: none;
        }
        .logout-dropdown {
            display: none;
            position: absolute;
            top: 60px;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            z-index: 1000;
        }
        .logout-dropdown.show {
            display: block;
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
                        <span class="material-icons">people</span>
                        <a href="users.php">Manage Users</a>
                    </li>
                    <li class="active">
                        <span class="material-icons">inventory</span>
                        <a href="items.php">Manage Items</a>
                    </li>
                    <li>
                        <span class="material-icons">assignment</span>
                        <a href="requisitions.php">All Requisitions</a>
                    </li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <header class="navbar" style="display: flex; align-items: center; padding: 10px 20px; background-color: #f5f5f5; border-bottom: 1px solid #ddd;">
                <div class="user-info" style="display: flex; align-items: center; gap: 10px;">
                    <span class="material-icons" style="font-size: 36px; color: #555;">account_circle</span>
                    <div class="user-details" style="display: flex; flex-direction: column;">
                        <span class="username" style="font-weight: 600; font-size: 16px;"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></span>
                        <span class="role" style="font-size: 14px; color: #777;">Administrator</span>
                    </div>
                    <div class="logout-dropdown" id="logoutDropdown" style="margin-left: auto;">
                        <a href="../auth/logout.php" class="logout-btn" style="display: flex; align-items: center; gap: 5px; color: #333; text-decoration: none; font-weight: 600;">
                            <span class="material-icons">logout</span>
                            Logout
                        </a>
                    </div>
                </div>
            </header>

            <section class="welcome-section">
                <h1>Manage Items</h1>
                <p>Add new items to the catalog and manage existing ones</p>
            </section>

            <section class="items-container">
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo strpos($message, 'success') !== false ? 'success' : 'error'; ?>" style="margin-bottom: 20px; padding: 15px; border-radius: 4px; <?php echo strpos($message, 'success') !== false ? 'background: #d4edda; color: #155724; border: 1px solid #c3e6cb;' : 'background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <div class="add-item-form">
                    <h3>Add New Item to Catalog</h3>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="product_name">Item Name</label>
                            <input type="text" id="product_name" name="product_name" required placeholder="Enter item name">
                        </div>
                        <div class="form-group">
                            <label for="product_category">Category</label>
                            <input type="text" id="product_category" name="product_category" required placeholder="Enter category">
                        </div>
                        <div class="form-group">
                            <label for="product_price">Unit Price</label>
                            <input type="number" id="product_price" name="product_price" min="0" step="0.01" required placeholder="Enter price">
                        </div>
                        <div class="form-group">
                            <label for="stock_quantity">Stock Quantity</label>
                            <input type="number" id="stock_quantity" name="stock_quantity" min="0" required placeholder="Enter stock quantity">
                        </div>
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" placeholder="Enter description"></textarea>
                        </div>

                        <div class="image-upload-container">
                            <div class="upload-icon material-icons">cloud_upload</div>
                            <div class="upload-text">Drag & drop or click to upload item image</div>
                            <input type="file" id="item_image" name="item_image" accept="image/*" class="upload-input">
                            <label for="item_image" class="upload-label">Choose File</label>
                            <img id="image-preview" class="image-preview">
                        </div>

                        <button type="submit" class="new-requisition-btn" style="background: #28a745; border: none; padding: 12px 24px; border-radius: 4px; color: white; font-weight: 600; cursor: pointer;">
                            <span class="material-icons" style="vertical-align: middle; margin-right: 8px;">add_shopping_cart</span>
                            Add to Catalog
                        </button>
                    </form>
                </div>

                <h3>Existing Items</h3>
                <div class="items-grid">
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
                            <form method="POST" style="margin-top: 10px;">
                                <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                <button type="submit" name="remove_item" class="delete-btn" onclick="return confirm('Are you sure you want to delete this item?')" style="background: #dc3545; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-weight: 600;">
                                    <span class="material-icons" style="vertical-align: middle; font-size: 16px;">delete</span>
                                    Delete Item
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </main>
    </div>

    <script>
        const imageUpload = document.getElementById('item_image');
        const imagePreview = document.getElementById('image-preview');
        const uploadContainer = document.querySelector('.image-upload-container');

        uploadContainer.addEventListener('dragover', (e) => {
            e.preventDefault();
            uploadContainer.classList.add('dragover');
        });

        uploadContainer.addEventListener('dragleave', () => {
            uploadContainer.classList.remove('dragover');
        });

        uploadContainer.addEventListener('drop', (e) => {
            e.preventDefault();
            uploadContainer.classList.remove('dragover');

            const files = e.dataTransfer.files;
            if (files.length > 0) {
                handleImageUpload(files[0]);
            }
        });

        imageUpload.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                handleImageUpload(e.target.files[0]);
            }
        });

        function handleImageUpload(file) {
            if (!file.type.startsWith('image/')) {
                alert('Please select a valid image file');
                return;
            }

            const reader = new FileReader();
            reader.onload = (e) => {
                imagePreview.src = e.target.result;
                imagePreview.style.display = 'block';
            };
            reader.readAsDataURL(file);
        }

        function toggleLogout() {
            const dropdown = document.getElementById('logoutDropdown');
            dropdown.classList.toggle('show');
        }

        
        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('logoutDropdown');
            const username = document.querySelector('.username');
            if (!username.contains(event.target) && !dropdown.contains(event.target)) {
                dropdown.classList.remove('show');
            }
        });
    </script>
</body>
</html>
