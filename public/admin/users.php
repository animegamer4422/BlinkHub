<?php
require_once __DIR__ . '/../../src/auth_admin.php';
require_once __DIR__ . '/../../config/db.php';

$statusMessage = '';
$statusType = '';

// ---------- CREATE USER ----------
if (isset($_POST['action']) && $_POST['action'] === 'create') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($name === '' || $email === '' || $password === '') {
        $statusMessage = "Name, email and password are required.";
        $statusType = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $statusMessage = "Invalid email format.";
        $statusType = "error";
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $mysqli->prepare(
            "INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)"
        );
        if ($stmt) {
            $stmt->bind_param('sss', $name, $email, $hash);
            if ($stmt->execute()) {
                $statusMessage = "User created successfully.";
                $statusType = "success";
            } else {
                $statusMessage = "Could not create user (maybe email already exists).";
                $statusType = "error";
            }
            $stmt->close();
        }
    }
}

// ---------- UPDATE USER ----------
if (isset($_POST['action']) && $_POST['action'] === 'update') {
    $id       = (int)($_POST['user_id'] ?? 0);
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['new_password'] ?? '');

    if ($name === '' || $email === '') {
        $statusMessage = "Name and email cannot be empty.";
        $statusType = "error";
    } else {
        if ($password !== '') {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $mysqli->prepare(
                "UPDATE users SET name = ?, email = ?, password_hash = ? WHERE id = ?"
            );
            $stmt->bind_param('sssi', $name, $email, $hash, $id);
        } else {
            $stmt = $mysqli->prepare(
                "UPDATE users SET name = ?, email = ? WHERE id = ?"
            );
            $stmt->bind_param('ssi', $name, $email, $id);
        }

        if ($stmt && $stmt->execute()) {
            $statusMessage = "User updated.";
            $statusType = "success";
        } else {
            $statusMessage = "Error updating user.";
            $statusType = "error";
        }
        if ($stmt) {
            $stmt->close();
        }
    }
}

// ---------- DELETE USER ----------
if (isset($_GET['delete'])) {
    $deleteId = (int)$_GET['delete'];
    if ($deleteId > 0) {
        $stmt = $mysqli->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $deleteId);
        $stmt->execute();
        $stmt->close();
    }
}

// ---------- LOAD USERS (WITH SEARCH) ----------
$search = trim($_GET['q'] ?? '');

if ($search !== '') {
    $stmt = $mysqli->prepare("
        SELECT id, name, email, created_at
        FROM users
        WHERE name  LIKE CONCAT('%', ?, '%')
           OR email LIKE CONCAT('%', ?, '%')
        ORDER BY id DESC
    ");
    $stmt->bind_param('ss', $search, $search);
    $stmt->execute();
    $users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $res   = $mysqli->query("SELECT id, name, email, created_at FROM users ORDER BY id DESC");
    $users = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

// ---------- EDITING USER (IF ANY) ----------
$editingUser = null;
if (isset($_GET['edit'])) {
    $id = (int)$_GET['edit'];
    $res = $mysqli->query("SELECT * FROM users WHERE id = $id");
    $editingUser = $res ? $res->fetch_assoc() : null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BlinkHub Admin – Users</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body class="admin-page users-page">
<div class="admin-shell">

    <!-- NAVBAR -->
    <header class="admin-navbar">
        <div class="admin-nav-left">
            <a href="index.php" class="admin-logo">
                <span class="logo-dark">Blink</span><span class="logo-yellow">Hub</span>
            </a>
            <span class="logo-badge">Admin Panel</span>
        </div>
        <div class="admin-nav-right">
            <span class="admin-welcome">
                Logged in as <?= htmlspecialchars($_SESSION['admin_name']) ?>
            </span>
            <a href="logout.php" class="admin-btn logout-btn">Logout</a>
        </div>
    </header>

    <div class="admin-body">
        <!-- SIDEBAR -->
        <aside class="admin-sidebar">
            <div class="sidebar-title">Navigation</div>
            <nav class="admin-menu">
                <a href="index.php" class="menu-item">Dashboard</a>
                <a href="products.php" class="menu-item">Products</a>
                <a href="users.php" class="menu-item active">Users</a>
                <a href="wishlist.php" class="menu-item">Wishlist</a>
            </nav>
        </aside>

        <!-- MAIN CONTENT -->
        <main class="admin-main">
            <div class="page-header">
                <div>
                    <h1 class="page-title">Manage Users</h1>
                    <p class="page-subtitle">
                        Add new accounts, update details or search existing users.
                    </p>
                </div>

                <!-- SEARCH BAR -->
                <form class="search-bar" method="get">
                    <input
                        type="text"
                        name="q"
                        placeholder="Search users by name or email..."
                        value="<?= htmlspecialchars($search) ?>"
                    >
                    <button class="admin-btn primary" type="submit">Search</button>
                </form>
            </div>

            <?php if ($statusMessage): ?>
                <div class="alert <?= $statusType ?>">
                    <?= htmlspecialchars($statusMessage) ?>
                </div>
            <?php endif; ?>

            <div class="users-grid">
                <!-- ADD / EDIT PANEL -->
                <section class="panel form-panel">
                    <h2><?= $editingUser ? 'Edit User' : 'Add User' ?></h2>

                    <form method="post" class="admin-form">
                        <?php if ($editingUser): ?>
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="user_id" value="<?= $editingUser['id'] ?>">
                        <?php else: ?>
                            <input type="hidden" name="action" value="create">
                        <?php endif; ?>

                        <label for="name">Name</label>
                        <input
                            id="name"
                            type="text"
                            name="name"
                            required
                            value="<?= htmlspecialchars($editingUser['name'] ?? '') ?>"
                        >

                        <label for="email">Email</label>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            required
                            value="<?= htmlspecialchars($editingUser['email'] ?? '') ?>"
                        >

                        <label for="password">
                            <?= $editingUser ? 'New Password (optional)' : 'Password' ?>
                        </label>
                        <input
                            id="password"
                            type="password"
                            name="<?= $editingUser ? 'new_password' : 'password' ?>"
                            <?= $editingUser ? '' : 'required' ?>
                        >

                        <button type="submit" class="admin-btn primary btn-full">
                            Save
                        </button>

                        <?php if ($editingUser): ?>
                            <a href="users.php" class="admin-btn ghost btn-full">Cancel edit</a>
                        <?php endif; ?>
                    </form>
                </section>

                <!-- USERS TABLE PANEL -->
                <section class="panel table-panel">
                    <div class="panel-header">
                        <h2>Users (<?= count($users) ?>)</h2>
                        <?php if ($search !== ''): ?>
                            <span class="panel-note">
                                Filtered by "<strong><?= htmlspecialchars($search) ?></strong>"
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="table-wrapper">
                        <table class="admin-table users-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name / Email</th>
                                    <th>Created</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php if (!count($users)): ?>
                                <tr>
                                    <td colspan="4" class="empty-row">
                                        No users found.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $u): ?>
                                    <tr>
                                        <td>#<?= $u['id'] ?></td>
                                        <td>
                                            <div class="user-cell">
                                                <span class="user-name">
                                                    <?= htmlspecialchars($u['name']) ?>
                                                </span>
                                                <span class="user-email">
                                                    <?= htmlspecialchars($u['email']) ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td><span class="user-date"><?= $u['created_at'] ?></span></td>
                                        <td class="actions-cell">
                                            <a href="users.php?edit=<?= $u['id'] ?>" class="table-link">
                                                Edit
                                            </a>
                                            <span class="divider">•</span>
                                            <a href="users.php?delete=<?= $u['id'] ?>"
                                               class="table-link danger"
                                               onclick="return confirm('Delete this user?')">
                                                Delete
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </main>
    </div>
</div>
</body>
</html>
