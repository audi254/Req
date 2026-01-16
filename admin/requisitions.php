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
    if (isset($_POST['approve'])) {
        $requisition_id = $_POST['requisition_id'];
        $stmt = $pdo->prepare("UPDATE requisitions SET status = 'approved', approved_by = ?, approved_date = NOW() WHERE id = ?");
        if ($stmt->execute([$user['id'], $requisition_id])) {
            
            $stmt = $pdo->prepare("SELECT r.*, u.first_name, u.last_name FROM requisitions r JOIN users u ON r.user_id = u.id WHERE r.id = ?");
            $stmt->execute([$requisition_id]);
            $requisition = $stmt->fetch();

           
            send_notification($requisition['user_id'], 'approval', 'Requisition Approved',
                "Your requisition {$requisition['requisition_number']} has been approved.", $requisition_id);

            $message = 'Requisition approved successfully.';
        } else {
            $message = 'Error approving requisition.';
        }
    } elseif (isset($_POST['reject'])) {
        $requisition_id = $_POST['requisition_id'];
        $rejection_reason = isset($_POST['rejection_reason']) ? sanitize_input($_POST['rejection_reason']) : '';
        $stmt = $pdo->prepare("UPDATE requisitions SET status = 'rejected', rejection_reason = ? WHERE id = ?");
        if ($stmt->execute([$rejection_reason, $requisition_id])) {
            
            $stmt = $pdo->prepare("SELECT r.*, u.first_name, u.last_name FROM requisitions r JOIN users u ON r.user_id = u.id WHERE r.id = ?");
            $stmt->execute([$requisition_id]);
            $requisition = $stmt->fetch();

            
            send_notification($requisition['user_id'], 'rejection', 'Requisition Rejected',
                "Your requisition {$requisition['requisition_number']} has been rejected. Reason: {$rejection_reason}", $requisition_id);

            $message = 'Requisition rejected successfully.';
        } else {
            $message = 'Error rejecting requisition.';
        }
    }
}

$stmt = $pdo->query("
    SELECT r.*, u.first_name, u.last_name 
    FROM requisitions r 
    JOIN users u ON r.user_id = u.id 
    ORDER BY r.created_at DESC
");
$requisitions = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>All Requisitions - KICD Admin</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css" />
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
    <style>
        .requisitions-container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            border-bottom: 1px solid #ddd;
            text-align: left;
        }
        th {
            background: #f8f9fa;
        }
        .status-pill {
            padding: 5px 10px;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            text-transform: capitalize;
        }
        .pending {
            background-color: #ffc107;
        }
        .approved {
            background-color: #28a745;
        }
        .rejected {
            background-color: #dc3545;
        }
        .action-buttons form {
            display: inline-block;
            margin-right: 5px;
        }
        .action-buttons button {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            color: white;
        }
        .approve-btn {
            background-color: #28a745;
        }
        .reject-btn {
            background-color: #dc3545;
        }
        .rejection-reason {
            width: 100%;
            padding: 6px;
            margin-top: 4px;
            border-radius: 4px;
            border: 1px solid #ddd;
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
                        <span class="material-icons">people</span>
                        <a href="users.php">Manage Users</a>
                    </li>
                    <li>
                        <span class="material-icons">inventory</span>
                        <a href="items.php">Manage Items</a>
                    </li>
                    <li class="active">
                        <span class="material-icons">assignment</span>
                        <a href="requisitions.php">All Requisitions</a>
                    </li>
                </ul>
            </nav>
        </aside>

        <main class="main-content">
            <header class="navbar">
                <div class="user-info">
                    <img src="../assets/avatar.jpg" alt="User Avatar" class="user-avatar" />
                    <div class="user-details">
                        <span class="username" onclick="toggleLogout()"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></span>
                        <span class="role">Administrator</span>
                    </div>
                    <div class="logout-dropdown" id="logoutDropdown">
                        <a href="../auth/logout.php" class="logout-btn">
                            <span class="material-icons">logout</span>
                            Logout
                        </a>
                    </div>
                </div>
            </header>

            <section class="welcome-section">
                <h1>All Requisitions</h1>
                <p>Approve or reject requisitions</p>
            </section>

            <section class="requisitions-container">
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo strpos($message, 'success') !== false ? 'success' : 'error'; ?>" style="margin-bottom: 20px; padding: 15px; border-radius: 4px; <?php echo strpos($message, 'success') !== false ? 'background: #d4edda; color: #155724; border: 1px solid #c3e6cb;' : 'background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>

                <div style="margin-bottom: 20px;">
                    <a href="generate-report.php" class="btn btn-primary" style="padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 4px; font-weight: 600;">
                        <span class="material-icons" style="vertical-align: middle; margin-right: 5px;">description</span>
                        Generate Report
                    </a>
                </div>

                <table>
                    <thead>
                        <tr>
                            <th>Requisition Number</th>
                            <th>Title</th>
                            <th>Requested By</th>
                            <th>Created At</th>
                            <th>Status</th>
                            <th>Priority</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($requisitions as $req): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($req['requisition_number']); ?></td>
                                <td><?php echo htmlspecialchars($req['title']); ?></td>
                                <td><?php echo htmlspecialchars($req['first_name'] . ' ' . $req['last_name']); ?></td>
                                <td><?php echo date('M j, Y', strtotime($req['created_at'])); ?></td>
                                <td><span class="status-pill <?php echo $req['status']; ?>"><?php echo ucfirst($req['status']); ?></span></td>
                                <td><?php echo ucfirst($req['priority']); ?></td>
                                <td class="action-buttons">
                                    <form method="POST" style="display:inline; margin-right: 5px;">
                                        <input type="hidden" name="requisition_id" value="<?php echo $req['id']; ?>">
                                        <button type="submit" name="approve" class="approve-btn">Approve</button>
                                    </form>
                                    <form method="POST" style="display:inline; margin-left: 5px;">
                                        <input type="hidden" name="requisition_id" value="<?php echo $req['id']; ?>">
                                        <button type="submit" name="reject" class="reject-btn">Reject</button>
                                    </form>
                                    <a href="download_requisition.php?id=<?php echo $req['id']; ?>" class="download-btn" style="margin-left: 10px; padding: 6px 12px; background-color: #007bff; color: white; border-radius: 4px; text-decoration: none; font-weight: 600;">Download</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        </main>
    </div>

    <script>
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
