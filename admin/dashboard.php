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
        $rejection_reason = sanitize_input($_POST['rejection_reason']);
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

$stmt = $pdo->query("SELECT COUNT(*) as total_users FROM users");
$total_users = $stmt->fetch()['total_users'];

$stmt = $pdo->query("SELECT COUNT(*) as total_requisitions FROM requisitions");
$total_requisitions = $stmt->fetch()['total_requisitions'];

$stmt = $pdo->query("SELECT COUNT(*) as pending_requisitions FROM requisitions WHERE status = 'pending'");
$pending_requisitions = $stmt->fetch()['pending_requisitions'];

$stmt = $pdo->query("SELECT COUNT(*) as approved_requisitions FROM requisitions WHERE status = 'approved'");
$approved_requisitions = $stmt->fetch()['approved_requisitions'];


$stmt = $pdo->query("
    SELECT r.*, u.first_name, u.last_name 
    FROM requisitions r 
    JOIN users u ON r.user_id = u.id 
    ORDER BY r.created_at DESC 
    LIMIT 10
");
$recent_requisitions = $stmt->fetchAll();


$stmt = $pdo->query("
    SELECT * FROM users 
    ORDER BY created_at DESC 
    LIMIT 5
");
$recent_users = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - KICD Requisitions</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        .approve-btn, .reject-btn {
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: 600;
            color: white;
            margin-left: 5px;
        }
        .approve-btn {
            background-color: #28a745;
        }
        .approve-btn:hover {
            background-color: #218838;
        }
        .reject-btn {
            background-color: #dc3545;
        }
        .reject-btn:hover {
            background-color: #c82333;
        }
        .rejection-reason {
            width: 150px;
            padding: 4px;
            margin-left: 5px;
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
                    <img src="../assets/images.jpeg" alt="KICD Logo"> <span>KICD</span>
                </div>
                <div class="system-name">Requisition Management System</div>
                <nav class="navigation">
                    <h3>Navigation</h3>
                    <ul>
                        <li class="active">
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
                        <li>
                            <span class="material-icons">assignment</span>
                            <a href="requisitions.php">All Requisitions</a>
                        </li>
                    </ul>
                </nav>
            </aside>

        <main class="main-content">
<header class="navbar" style="position: relative; display: flex; justify-content: flex-end; align-items: center; gap: 10px;">
    <div class="user-info" style="display: flex; align-items: center; gap: 5px;">
        <span class="material-icons" style="color: #3f51b5; font-size: 32px;">account_circle</span>
        <div class="user-details" style="display: flex; flex-direction: column;">
            <span class="username" style="font-weight: 600; cursor: default;"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></span>
            <span class="role" style="font-size: 12px; color: #666;">Administrator</span>
        </div>
    </div>
    <div class="navbar-actions">
<a href="../auth/logout.php" class="logout-btn" style="display: flex; align-items: center; gap: 5px; padding: 5px 10px; background-color: #b71c1c; color: white; border-radius: 4px; text-decoration: none; font-weight: 600;">
            <span class="material-icons" style="font-size: 20px;">logout</span>
            Logout
        </a>
    </div>
</header>

            <section class="welcome-section">
                <h1>Welcome back, <?php echo htmlspecialchars($user['first_name']); ?>!</h1>
                <p>Manage the entire KICD requisition system from this dashboard.</p>
                <?php if ($message): ?>
                    <div class="alert alert-<?php echo strpos($message, 'success') !== false ? 'success' : 'error'; ?>" style="margin-top: 20px; padding: 15px; border-radius: 4px; <?php echo strpos($message, 'success') !== false ? 'background: #d4edda; color: #155724; border: 1px solid #c3e6cb;' : 'background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;'; ?>">
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                <?php endif; ?>
            </section>

            <section class="overview-cards">
                <div class="card">
                    <div class="card-header">
                        <h3>Total Users</h3>
                        <span class="material-icons">people</span>
                    </div>
                    <div class="card-content">
                        <h2><?php echo $total_users; ?></h2>
                        <p>Active system users</p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Total Requisitions</h3>
                        <span class="material-icons">assignment</span>
                    </div>
                    <div class="card-content">
                        <h2><?php echo $total_requisitions; ?></h2>
                        <p>All requisitions created</p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Pending Approvals</h3>
                        <span class="status-pill pending">Pending</span>
                        <span class="material-icons">schedule</span>
                    </div>
                    <div class="card-content">
                        <h2><?php echo $pending_requisitions; ?></h2>
                        <p>Awaiting approval</p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Approved</h3>
                        <span class="status-pill approved">Approved</span>
                        <span class="material-icons">check_circle</span>
                    </div>
                    <div class="card-content">
                        <h2><?php echo $approved_requisitions; ?></h2>
                        <p>Successfully processed</p>
                    </div>
                </div>
            </section>

            <section class="recent-activity">
                <h2>Recent Requisitions</h2>
                <div class="activity-list">
                    <?php foreach ($recent_requisitions as $requisition): ?>
                        <div class="activity-item">
                            <div class="avatar">
                                <?php echo substr($requisition['first_name'], 0, 1) . substr($requisition['last_name'], 0, 1); ?>
                            </div>
                            <div class="activity-details">
                                <p><strong><?php echo htmlspecialchars($requisition['first_name'] . ' ' . $requisition['last_name']); ?></strong> 
                                created requisition <strong><?php echo htmlspecialchars($requisition['requisition_number']); ?></strong></p>
                                <span class="time"><?php echo date('M j, Y', strtotime($requisition['created_at'])); ?></span>
                                <?php if ($requisition['status'] === 'pending'): ?>
                                    <form method="POST" style="display:inline;">
                                        <input type="hidden" name="requisition_id" value="<?php echo $requisition['id']; ?>">
                                        <button type="submit" name="approve" class="approve-btn">Approve</button>
                                    </form>
<form method="POST" style="display:inline;">
    <input type="hidden" name="requisition_id" value="<?php echo $requisition['id']; ?>">
    <select name="rejection_reason" class="rejection-reason" required>
        <option value="" disabled selected>Select rejection reason</option>
        <option value="Incorrect Information">Incorrect Information</option>
        <option value="Insufficient Funds">Insufficient Funds</option>
        <option value="Duplicate Requests">Duplicate Requests</option>
        <option value="Item Unavailability">Item Unavailability</option>
    </select>
    <button type="submit" name="reject" class="reject-btn">Reject</button>
</form>
                                <?php endif; ?>
                            </div>
                            <span class="status-pill <?php echo $requisition['status']; ?>"><?php echo ucfirst($requisition['status']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
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
