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
    if (isset($_POST['add_user'])) {
        $employee_id = sanitize_input($_POST['employee_id']);
        $first_name = sanitize_input($_POST['first_name']);
        $last_name = sanitize_input($_POST['last_name']);
        $email = sanitize_input($_POST['email']);
        $password = $_POST['password'];
        $role = sanitize_input($_POST['role']);

        $errors = [];

        if (empty($first_name)) {
            $errors[] = "First Name is required.";
        } elseif (strlen($first_name) < 2 || strlen($first_name) > 30) {
            $errors[] = "First Name must be between 2 and 30 characters.";
        } elseif (!preg_match('/^[A-Z]/', $first_name)) {
            $errors[] = "First Name must start with a capital letter.";
        } elseif (preg_match('/[^a-zA-Z\s\'-]/', $first_name)) {
            $errors[] = "First Name can only contain letters, spaces, apostrophes, and hyphens.";
        } elseif (preg_match('/  /', $first_name) || preg_match('/\'\'/', $first_name) || preg_match('/--/', $first_name)) {
            $errors[] = "First Name cannot have consecutive spaces, apostrophes, or hyphens.";
        }

        if (empty($last_name)) {
            $errors[] = "Last Name is required.";
        } elseif (strlen($last_name) < 2 || strlen($last_name) > 30) {
            $errors[] = "Last Name must be between 2 and 30 characters.";
        } elseif (!preg_match('/^[A-Z]/', $last_name)) {
            $errors[] = "Last Name must start with a capital letter.";
        } elseif (preg_match('/[^a-zA-Z\'-]/', $last_name)) {
            $errors[] = "Last Name can only contain letters, apostrophes, and hyphens.";
        } elseif (preg_match('/\'\'/', $last_name) || preg_match('/--/', $last_name)) {
            $errors[] = "Last Name cannot have consecutive apostrophes or hyphens.";
        }

        if (empty($email)) {
            $errors[] = "Email is required.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email format.";
        } elseif (!preg_match('/^[a-zA-Z0-9._%+-]+@(kicd\.ac\.ke|gmail\.com)$/', $email)) {
            $errors[] = "Email must be from @kicd.ac.ke or @gmail.com domain";
        }

        if (empty($employee_id)) {
            $errors[] = "KICD ID is required.";
        } elseif (!preg_match('/^KICD\/\d{4}\/\d{3}$/', $employee_id)) {
            $errors[] = "KICD ID must be in the format KICD/2024/001";
        }

        if (empty($password)) {
            $errors[] = "Password is required.";
        } else {
            if (strlen($password) < 8) {
                $errors[] = "Password must be at least 8 characters long.";
            }
            if (!preg_match('/[A-Z]/', $password)) {
                $errors[] = "Password must contain at least one uppercase letter.";
            }
            if (!preg_match('/[a-z]/', $password)) {
                $errors[] = "Password must contain at least one lowercase letter.";
            }
            if (!preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
                $errors[] = "Password must contain at least one special character.";
            }
            if (strcasecmp($password, $first_name) === 0 || strcasecmp($password, $last_name) === 0 || strcasecmp($password, $email) === 0) {
                $errors[] = "Password cannot be the same as your first name, last name, or email.";
            }
        }

        if (empty($errors)) {
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? OR employee_id = ?");
            $stmt->execute([$email, $employee_id]);
            if ($stmt->fetch()) {
                $errors[] = 'Email or KICD ID already exists.';
            }
        }

        if (empty($errors)) {
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                INSERT INTO users (employee_id, first_name, last_name, email, password_hash, role) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            if ($stmt->execute([$employee_id, $first_name, $last_name, $email, $password_hash, $role])) {
                $message = 'User added successfully!';
            } else {
                $message = 'Error adding user. Please try again.';
            }
        } else {
            $message = implode('<br>', $errors);
        }
    } elseif (isset($_POST['remove_user'])) {
        $user_id = $_POST['user_id'];
        $stmt = $pdo->prepare("UPDATE users SET is_active = FALSE WHERE id = ?");
        if ($stmt->execute([$user_id])) {
            $message = 'User removed successfully!';
        } else {
            $message = 'Error removing user.';
        }
    } elseif (isset($_POST['change_password'])) {
        $user_id = sanitize_input($_POST['user_id']);
        $new_password = $_POST['new_password'];
        if (empty($new_password)) {
            $message = 'New password cannot be empty';
        } else {
            $password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ? AND is_active = TRUE");
            if ($stmt->execute([$password_hash, $user_id])) {
                $message = 'Password changed successfully!';
            } else {
                $message = 'Error changing password. Please try again.';
            }
        }
    }
}

$stmt = $pdo->query("SELECT * FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - KICD Admin</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        .users-container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .add-user-form {
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
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        .users-table {
            width: 100%;
            border-collapse: collapse;
        }
        .users-table th, .users-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        .users-table th {
            background: #f8f9fa;
            font-weight: 600;
        }
        .remove-btn {
            background: #dc3545;
            color: white;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
        }
        .remove-btn:hover {
            background: #c82333;
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
        .change-btn {
            background: #ffc107;
            color: black;
            border: none;
            padding: 5px 10px;
            border-radius: 4px;
            cursor: pointer;
            margin-left: 5px;
        }
        .change-btn:hover {
            background: #e0a800;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgb(0,0,0);
            background-color: rgba(0,0,0,0.4);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 15% auto;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 400px;
            border-radius: 8px;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
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
                    <li class="active">
                        <span class="material-icons">people</span>
                        <a href="users.php">Manage Users</a>
                    </li>
                    <li>
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
                <h1>Manage Users</h1>
                <p>Add new users and manage existing ones</p>
            </section>

            <section class="users-container">
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo strpos($message, 'success') !== false ? 'success' : 'error'; ?>" style="margin-bottom: 20px; padding: 15px; border-radius: 4px; <?php echo strpos($message, 'success') !== false ? 'background: #d4edda; color: #155724; border: 1px solid #c3e6cb;' : 'background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <div class="add-user-form">
                    <h3>Add New User</h3>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="employee_id">Employee ID</label>
                            <input type="text" id="employee_id" name="employee_id" required placeholder="e.g., KICD/EMP/001" value="<?php echo isset($_POST['employee_id']) ? htmlspecialchars($_POST['employee_id']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="first_name">First Name</label>
                            <input type="text" id="first_name" name="first_name" required value="<?php echo isset($_POST['first_name']) ? htmlspecialchars($_POST['first_name']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="last_name">Last Name</label>
                            <input type="text" id="last_name" name="last_name" required value="<?php echo isset($_POST['last_name']) ? htmlspecialchars($_POST['last_name']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                        <div class="form-group">
                            <label for="password">Password</label>
                            <input type="password" id="password" name="password" required>
                        </div>
                        <div class="form-group">
                            <label for="role">Role</label>
                            <select id="role" name="role" required>
                                <option value="user" <?php echo (isset($_POST['role']) && $_POST['role'] == 'user') ? 'selected' : ''; ?>>User</option>
                                <option value="supervisor" <?php echo (isset($_POST['role']) && $_POST['role'] == 'supervisor') ? 'selected' : ''; ?>>Supervisor</option>
                                <option value="procurement" <?php echo (isset($_POST['role']) && $_POST['role'] == 'procurement') ? 'selected' : ''; ?>>Procurement</option>
                                <option value="admin" <?php echo (isset($_POST['role']) && $_POST['role'] == 'admin') ? 'selected' : ''; ?>>Admin</option>
                            </select>
                        </div>
                        <button type="submit" name="add_user" class="new-requisition-btn" style="background: #28a745; border: none; padding: 12px 24px; border-radius: 4px; color: white; font-weight: 600; cursor: pointer;">
                            <span class="material-icons" style="vertical-align: middle; margin-right: 8px;">person_add</span>
                            Add User
                        </button>
                    </form>
                </div>

                <h3>Existing Users</h3>
                <table class="users-table">
                    <thead>
                        <tr>
                            <th>Employee ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $u): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($u['employee_id']); ?></td>
                                <td><?php echo htmlspecialchars($u['first_name'] . ' ' . $u['last_name']); ?></td>
                                <td><?php echo htmlspecialchars($u['email']); ?></td>
                                <td><?php echo htmlspecialchars($u['role']); ?></td>
                                <td><?php echo $u['is_active'] ? 'Active' : 'Inactive'; ?></td>
                                <td>
                                    <?php if ($u['is_active']): ?>
                                        <?php if ($u['employee_id'] !== 'KICD/ADMIN/001'): ?>
                                            <form method="POST" action="" style="display: inline;">
                                                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                                <button type="submit" name="remove_user" class="remove-btn" onclick="return confirm('Are you sure you want to remove this user?')">
                                                    <span class="material-icons" style="vertical-align: middle; font-size: 16px;">delete</span>
                                                    Remove
                                                </button>
                                            </form>
                                            <button type="button" class="change-btn" onclick="openModal(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars(addslashes($u['first_name'] . ' ' . $u['last_name'])); ?>')">
                                                <span class="material-icons" style="vertical-align: middle; font-size: 16px;">lock</span>
                                                Change Password
                                            </button>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>

    <div id="changePasswordModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal()">&times;</span>
            <h3>Change Password for <span id="userName"></span></h3>
            <form method="POST" action="">
                <input type="hidden" name="user_id" id="modalUserId">
                <div class="form-group">
                    <label for="new_password">New Password</label>
                    <div style="position: relative;">
                        <input type="password" id="new_password" name="new_password" required style="padding-right: 40px;">
                        <button type="button" id="togglePassword" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: #555;">
                            <span class="material-icons" id="passwordIcon" style="font-size: 20px;">visibility_off</span>
                        </button>
                    </div>
                </div>
                <button type="submit" name="change_password" class="new-requisition-btn" style="background: #007bff; border: none; padding: 12px 24px; border-radius: 4px; color: white; font-weight: 600; cursor: pointer;">Change Password</button>
            </form>
        </div>
    </div>

    <script>
        function toggleLogout() {
            const dropdown = document.getElementById('logoutDropdown');
            dropdown.classList.toggle('show');
        }

        function openModal(userId, userName) {
            document.getElementById('modalUserId').value = userId;
            document.getElementById('userName').textContent = userName;
            document.getElementById('changePasswordModal').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('changePasswordModal').style.display = 'none';
        }

        function togglePasswordVisibility() {
            const passwordInput = document.getElementById('new_password');
            const icon = document.getElementById('passwordIcon');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                icon.textContent = 'visibility';
            } else {
                passwordInput.type = 'password';
                icon.textContent = 'visibility_off';
            }
        }

        document.getElementById('togglePassword').addEventListener('click', togglePasswordVisibility);

        document.addEventListener('click', function(event) {
            const dropdown = document.getElementById('logoutDropdown');
            const username = document.querySelector('.username');
            if (!username.contains(event.target) && !dropdown.contains(event.target)) {
                dropdown.classList.remove('show');
            }
            const modal = document.getElementById('changePasswordModal');
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        });
    </script>
</body>
</html>
