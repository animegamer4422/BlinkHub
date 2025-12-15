<?php
require_once __DIR__ . '/../../src/auth_admin.php';
require_once __DIR__ . '/../../config/db.php';

// Count products
$totalProducts = $mysqli->query("SELECT COUNT(*) AS c FROM products")->fetch_assoc()['c'] ?? 0;

// Count users
$totalUsers = $mysqli->query("SELECT COUNT(*) AS c FROM users")->fetch_assoc()['c'] ?? 0;

// Count addresses
$totalAddresses = $mysqli->query("SELECT COUNT(*) AS c FROM addresses")->fetch_assoc()['c'] ?? 0;

// Count wishlist (guard if table missing)
$wishlistTotal   = 0;
$wishlistPending = 0;
$tableCheck      = $mysqli->query("SHOW TABLES LIKE 'wishlist_requests'");
if ($tableCheck && $tableCheck->num_rows > 0) {
    $res = $mysqli->query("
        SELECT 
            COUNT(*) AS total,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending
        FROM wishlist_requests
    ");
    if ($res) {
        $row            = $res->fetch_assoc();
        $wishlistTotal   = (int)($row['total'] ?? 0);
        $wishlistPending = (int)($row['pending'] ?? 0);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BlinkHub Admin – Dashboard</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body class="admin-page">
<div class="admin-shell">

    <!-- Navbar -->
    <header class="admin-navbar">
        <div class="admin-nav-left">
            <a href="index.php" class="admin-logo">
                <span class="logo-dark">Blink</span><span class="logo-yellow">Hub</span>
            </a>
            <span class="logo-badge">Admin Panel</span>
        </div>

        <div class="admin-nav-right">
            <span class="admin-welcome">
                Logged in as <?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?>
            </span>
            <a href="logout.php" class="admin-btn logout-btn">Logout</a>
        </div>
    </header>

    <div class="admin-body">

        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <div class="sidebar-title">Navigation</div>
            <nav class="admin-menu">
                <a href="index.php" class="menu-item active">Dashboard</a>
                <a href="products.php" class="menu-item">Products</a>
                <a href="users.php" class="menu-item">Users</a>
                <a href="wishlist.php" class="menu-item">Wishlist</a>
            </nav>
        </aside>

        <!-- Dashboard -->
        <main class="admin-main">
            <h1 class="page-title">Dashboard</h1>
            <p class="page-subtitle">Quick overview of your BlinkHub system.</p>

            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-label">Products</div>
                    <div class="stat-value"><?= $totalProducts ?></div>
                </div>

                <div class="stat-card">
                    <div class="stat-label">Registered Users</div>
                    <div class="stat-value"><?= $totalUsers ?></div>
                </div>

                <div class="stat-card">
                    <div class="stat-label">Saved Addresses</div>
                    <div class="stat-value"><?= $totalAddresses ?></div>
                </div>

                <div class="stat-card stat-accent">
                    <div class="stat-label">
                        Wishlist Requests
                        <?php if ($wishlistPending > 0): ?>
                            <span class="stat-pill"><?= $wishlistPending ?> pending</span>
                        <?php endif; ?>
                    </div>
                    <div class="stat-value"><?= $wishlistTotal ?></div>
                </div>
            </div>

            <div class="quick-links">
                <a href="products.php" class="admin-btn primary">Manage Products</a>
                <a href="users.php" class="admin-btn ghost">Manage Users</a>
                <a href="wishlist.php" class="admin-btn ghost">Review Wishlist</a>
            </div>
        </main>

    </div>
</div>
</body>
</html>
