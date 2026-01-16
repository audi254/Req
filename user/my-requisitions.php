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


$status_filter = isset($_GET['status']) ? $_GET['status'] : '';
$where_clause = $status_filter ? "AND status = ?" : "";
$where_params = $status_filter ? [$user['id'], $status_filter] : [$user['id']];

$stmt = $pdo->prepare("
    SELECT * FROM requisitions 
    WHERE user_id = ? $where_clause 
    ORDER BY created_at DESC
");
$stmt->execute($where_params);
$requisitions = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Requisitions - KICD</title>
    <link rel="stylesheet" href="../assets/css/dashboard.css">
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
                    <li class="active">
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
                <h1>My Requisitions</h1>
                <p>View and manage all your requisitions</p>
                
                <div style="margin-top: 20px;">
                    <a href="my-requisitions.php" class="new-requisition-btn">All</a>
                    <a href="my-requisitions.php?status=pending" class="new-requisition-btn">Pending</a>
                    <a href="my-requisitions.php?status=approved" class="new-requisition-btn">Approved</a>
                    <a href="my-requisitions.php?status=rejected" class="new-requisition-btn">Rejected</a>
                </div>
            </section>

            <section class="recent-activity">
                <h2>Requisitions List</h2>
                <div class="activity-list">
                    <?php if (empty($requisitions)): ?>
                        <div class="activity-item">
                            <div class="activity-details">
                                <p>No requisitions found</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($requisitions as $requisition): ?>
                            <div class="activity-item">
                                <div class="avatar">
                                    <?php echo substr($user['first_name'], 0, 1) . substr($user['last_name'], 0, 1); ?>
                                </div>
                                <div class="activity-details">
                                    <p><strong><?php echo htmlspecialchars($requisition['requisition_number']); ?></strong></p>
                                    <p><?php echo htmlspecialchars($requisition['title']); ?></p>
                                    <span class="time"><?php echo date('M j, Y', strtotime($requisition['created_at'])); ?></span>
                                    <?php if ($requisition['status'] === 'rejected' && !empty($requisition['rejection_reason'])): ?>
                                        <p style="color: #dc3545; font-weight: 600;">Rejection Reason: <?php echo htmlspecialchars($requisition['rejection_reason']); ?></p>
                                    <?php endif; ?>
                                    <a href="generate-pdf.php?id=<?php echo $requisition['id']; ?>" class="download-link">Download PDF</a>
                                </div>
                                <span class="status-pill <?php echo $requisition['status']; ?>"><?php echo ucfirst($requisition['status']); ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

    <script>
    </script>
</body>
</html>
