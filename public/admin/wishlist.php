<?php
require_once __DIR__ . '/../../src/auth_admin.php';
require_once __DIR__ . '/../../config/db.php';

$statusMessage = '';
$statusType    = '';

// Make sure table exists
$tableCheck = $mysqli->query("SHOW TABLES LIKE 'wishlist_requests'");
$wishlistTableExists = $tableCheck && $tableCheck->num_rows > 0;

// --- Handle status updates ---
$allowedStatuses = ['pending', 'reviewed', 'added', 'rejected'];

if ($wishlistTableExists && isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'set_status') {
    $id        = (int)($_GET['id'] ?? 0);
    $newStatus = $_GET['to'] ?? 'pending';

    if ($id > 0 && in_array($newStatus, $allowedStatuses, true)) {
        $stmt = $mysqli->prepare("UPDATE wishlist_requests SET status = ? WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('si', $newStatus, $id);
            $ok = $stmt->execute();
            $stmt->close();

            if ($ok) {
                $statusMessage = "Updated wishlist request #{$id} to " . ucfirst($newStatus) . ".";
                $statusType    = 'success';
            } else {
                $statusMessage = "Failed to update wishlist request.";
                $statusType    = 'error';
            }
        } else {
            $statusMessage = "Could not prepare update statement.";
            $statusType    = 'error';
        }
    }
}

// --- Filter ---
$filterStatus = $_GET['status'] ?? 'pending';
if (!in_array($filterStatus, array_merge(['all'], $allowedStatuses), true)) {
    $filterStatus = 'pending';
}

// --- Load requests ---
$wishlistItems = [];

if ($wishlistTableExists) {
    if ($filterStatus === 'all') {
        $sql = "
            SELECT w.*, u.name AS user_name, u.email AS user_email
            FROM wishlist_requests w
            LEFT JOIN users u ON u.id = w.user_id
            ORDER BY w.created_at DESC
        ";
        $res = $mysqli->query($sql);
        if ($res) {
            $wishlistItems = $res->fetch_all(MYSQLI_ASSOC);
        }
    } else {
        $sql = "
            SELECT w.*, u.name AS user_name, u.email AS user_email
            FROM wishlist_requests w
            LEFT JOIN users u ON u.id = w.user_id
            WHERE w.status = ?
            ORDER BY w.created_at DESC
        ";
        $stmt = $mysqli->prepare($sql);
        if ($stmt) {
            $stmt->bind_param('s', $filterStatus);
            $stmt->execute();
            $wishlistItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BlinkHub Admin – Wishlist</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body class="admin-page wishlist-admin-page">
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
                <a href="index.php" class="menu-item">Dashboard</a>
                <a href="products.php" class="menu-item">Products</a>
                <a href="users.php" class="menu-item">Users</a>
                <a href="wishlist.php" class="menu-item active">Wishlist</a>
            </nav>
        </aside>

        <!-- Main -->
        <main class="admin-main">
            <div class="page-header">
                <div>
                    <h1 class="page-title">Wishlist requests</h1>
                    <p class="page-subtitle">
                        See what users are asking for and mark requests as reviewed, added or rejected.
                    </p>
                </div>

                <?php if ($wishlistTableExists): ?>
                    <form method="get" class="filter-bar">
                        <label>
                            Status:
                            <select name="status" onchange="this.form.submit()">
                                <option value="pending" <?= $filterStatus === 'pending' ? 'selected' : '' ?>>Pending</option>
                                <option value="reviewed" <?= $filterStatus === 'reviewed' ? 'selected' : '' ?>>Reviewed</option>
                                <option value="added" <?= $filterStatus === 'added' ? 'selected' : '' ?>>Added</option>
                                <option value="rejected" <?= $filterStatus === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                                <option value="all" <?= $filterStatus === 'all' ? 'selected' : '' ?>>All</option>
                            </select>
                        </label>
                    </form>
                <?php endif; ?>
            </div>

            <?php if ($statusMessage): ?>
                <div class="alert alert-<?= htmlspecialchars($statusType) ?>">
                    <?= htmlspecialchars($statusMessage) ?>
                </div>
            <?php endif; ?>

            <?php if (!$wishlistTableExists): ?>
                <div class="alert alert-error">
                    The <code>wishlist_requests</code> table does not exist. Create it in MySQL before using this page.
                </div>
            <?php else: ?>
                <div class="table-wrapper table-scroll">
                    <table class="product-table wishlist-table">
                        <thead>
                        <tr>
                            <th>#</th>
                            <th>Product</th>
                            <th>User</th>
                            <th>Approx price</th>
                            <th>Status</th>
                            <th>Created</th>
                            <th>Actions</th>
                        </tr>
                        </thead>

                        <tbody>
                        <?php if (!count($wishlistItems)): ?>
                            <tr>
                                <td colspan="7" class="empty-row">
                                    No wishlist requests found for this filter.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($wishlistItems as $item): ?>
                                <tr>
                                    <td>#<?= (int)$item['id'] ?></td>

                                    <td>
                                        <div class="prod-name">
                                            <?= htmlspecialchars($item['product_name']) ?>
                                        </div>
                                        <?php if (!empty($item['brand'])): ?>
                                            <div class="prod-meta">
                                                Brand: <?= htmlspecialchars($item['brand']) ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($item['details'])): ?>
                                            <div class="wishlist-admin-details">
                                                <?= nl2br(htmlspecialchars($item['details'])) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <div class="prod-meta">
                                            <?= htmlspecialchars($item['user_name'] ?? $item['user_email'] ?? 'Unknown') ?>
                                        </div>
                                        <?php if (!empty($item['user_email'])): ?>
                                            <div class="prod-meta small">
                                                <?= htmlspecialchars($item['user_email']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <?php if ((int)$item['approx_price'] > 0): ?>
                                            ₹<?= (int)$item['approx_price'] ?>
                                        <?php else: ?>
                                            —
                                        <?php endif; ?>
                                    </td>

                                    <td>
                                        <span class="status-pill wishlist-status status-<?= htmlspecialchars($item['status']) ?>">
                                            <?= ucfirst($item['status']) ?>
                                        </span>
                                    </td>

                                    <td>
                                        <span class="prod-meta small">
                                            <?= htmlspecialchars($item['created_at']) ?>
                                        </span>
                                    </td>

                                    <td class="actions-cell wishlist-actions">
                                        <?php foreach ($allowedStatuses as $st): ?>
                                            <?php if ($st === $item['status']) continue; ?>
                                            <a
                                                href="wishlist.php?action=set_status&id=<?= (int)$item['id'] ?>&to=<?= urlencode($st) ?>&status=<?= urlencode($filterStatus) ?>"
                                                class="table-link wishlist-action-link status-<?= htmlspecialchars($st) ?>"
                                            >
                                                <?= ucfirst($st) ?>
                                            </a>
                                            <span class="divider">•</span>
                                        <?php endforeach; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </main>

    </div>
</div>
</body>
</html>
