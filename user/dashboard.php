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


$stmt = $pdo->prepare("
    SELECT * FROM requisitions 
    WHERE user_id = ? 
    ORDER BY created_at DESC
");
$stmt->execute([$user['id']]);
$my_requisitions = $stmt->fetchAll();


$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
    FROM requisitions 
    WHERE user_id = ?
");
$stmt->execute([$user['id']]);
$stats = $stmt->fetch();


$stmt = $pdo->prepare("
    SELECT * FROM notifications 
    WHERE user_id = ? AND is_read = 0 
    ORDER BY created_at DESC 
    LIMIT 5
");
$stmt->execute([$user['id']]);
$notifications = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - KICD Requisitions</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
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
            <header class="navbar" style="position: relative; display: flex; justify-content: flex-end; align-items: center; gap: 10px;">
                <div class="user-info" style="display: flex; align-items: center; gap: 5px;">
                    <span class="material-icons" style="color: #3f51b5; font-size: 32px;">account_circle</span>
                    <div class="user-details" style="display: flex; flex-direction: column;">
                        <span class="username" style="font-weight: 600; cursor: default;"><?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name']); ?></span>
                        <span class="role" style="font-size: 12px; color: #666;"><?php echo htmlspecialchars($user['role']); ?></span>
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
                <p>Track your requisitions and manage your procurement needs.</p>
                <button class="new-requisition-btn" onclick="window.location.href='new-requisition.php'">
                    <span class="material-icons">add</span>
                    New Requisition
                </button>
            </section>

            <section class="overview-cards">
                <div class="card">
                    <div class="card-header">
                        <h3>Total Requisitions</h3>
                        <span class="material-icons">assignment</span>
                    </div>
                    <div class="card-content">
                        <h2><?php echo $stats['total']; ?></h2>
                        <p>Created by you</p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Pending Approval</h3>
                        <span class="status-pill pending">Pending</span>
                        <span class="material-icons">schedule</span>
                    </div>
                    <div class="card-content">
                        <h2><?php echo $stats['pending']; ?></h2>
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
                        <h2><?php echo $stats['approved']; ?></h2>
                        <p>Successfully processed</p>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3>Rejected</h3>
                        <span class="status-pill rejected">Rejected</span>
                        <span class="material-icons">cancel</span>
                    </div>
                    <div class="card-content">
                        <h2><?php echo $stats['rejected']; ?></h2>
                        <p>Rejected requisitions</p>
                    </div>
                </div>
            </section>

            <section class="recent-activity">
                <h2>Recent Activity</h2>
                <div class="activity-list">
                    <?php foreach ($my_requisitions as $requisition): ?>
                        <div class="activity-item">
                            <div class="avatar">
                                <?php echo substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1); ?>
                            </div>
                            <div class="activity-details">
                                <p>You created requisition <strong><?php echo htmlspecialchars($requisition['requisition_number']); ?></strong></p>
                                <span class="time"><?php echo date('M j, Y', strtotime($requisition['created_at'])); ?></span>
                            </div>
                            <span class="status-pill <?php echo $requisition['status']; ?>"><?php echo ucfirst($requisition['status']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        </main>
    </div>
</body>
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
</html>
