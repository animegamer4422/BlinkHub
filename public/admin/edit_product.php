<?php
require_once __DIR__ . '/../../src/auth_admin.php';
require_once __DIR__ . '/../../config/db.php';

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: products.php');
    exit;
}

// fetch product
$stmt = $mysqli->prepare("SELECT * FROM products WHERE id = ? LIMIT 1");
$stmt->bind_param('i', $id);
$stmt->execute();
$prod = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$prod) {
    header('Location: products.php');
    exit;
}

// categories
$catsRes = $mysqli->query("SELECT id, name FROM categories ORDER BY sort_order, name");
$categories = $catsRes ? $catsRes->fetch_all(MYSQLI_ASSOC) : [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Product – BlinkHub Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../public/css/admin.css">
</head>
<body class="admin-page">
<div class="admin-shell">

    <header class="admin-navbar">
        <div class="admin-nav-left">
            <a href="../public/index.php" class="admin-logo" aria-label="BlinkHub home">
                <span class="logo-dark">Blink</span><span class="logo-yellow">Hub</span>
                <span class="logo-badge">Admin</span>
            </a>
        </div>

        <div class="admin-nav-center">
            <a href="index.php" class="admin-nav-link">Dashboard</a>
            <a href="products.php" class="admin-nav-link admin-nav-link-active">Products</a>
            <a href="users.php" class="admin-nav-link">Users</a>
        </div>

        <div class="admin-nav-right">
            <a href="logout.php" class="admin-btn ghost">Logout</a>
        </div>
    </header>

    <main class="admin-main">
        <section class="admin-section">
            <h1 class="admin-title">Edit product</h1>
            <p class="admin-subtitle">
                Update pricing, category, ETA or tags for
                <strong><?= htmlspecialchars($prod['name']) ?></strong>.
            </p>

            <form action="save_product.php" method="post" class="admin-form">
                <input type="hidden" name="id" value="<?= (int)$prod['id'] ?>">

                <div class="admin-field">
                    <label>Product name *</label>
                    <input type="text" name="name" required
                           value="<?= htmlspecialchars($prod['name']) ?>">
                </div>

                <div class="admin-field">
                    <label>Category *</label>
                    <select name="category_id" required>
                        <option value="">Select category…</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= (int)$c['id'] ?>"
                                <?= (int)$c['id'] === (int)$prod['category_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($c['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="admin-field">
                    <label>Price (selling) *</label>
                    <input type="number" name="price" min="1" required
                           value="<?= (int)$prod['price'] ?>">
                </div>

                <div class="admin-field">
                    <label>MRP *</label>
                    <input type="number" name="mrp" min="1" required
                           value="<?= (int)$prod['mrp'] ?>">
                </div>

                <div class="admin-field">
                    <label>Tag (badge text)</label>
                    <input type="text" name="tag"
                           value="<?= htmlspecialchars($prod['tag'] ?? '') ?>">
                </div>

                <div class="admin-field">
                    <label>ETA in minutes</label>
                    <input type="number" name="eta_minutes" min="1"
                           value="<?= (int)$prod['eta_minutes'] ?>">
                </div>

                <div class="admin-field">
                    <label>Search tags (comma separated)</label>
                    <textarea name="tags"><?= htmlspecialchars($prod['tags'] ?? '') ?></textarea>
                </div>

                <div class="admin-field">
                    <label>
                        <input type="checkbox" name="is_active" value="1"
                            <?= !empty($prod['is_active']) ? 'checked' : '' ?>>
                        &nbsp; Active (visible in store)
                    </label>
                </div>

                <div class="admin-form-actions">
                    <a href="products.php" class="admin-btn ghost">Back</a>
                    <button type="submit" class="admin-btn primary">Update product</button>
                </div>
            </form>
        </section>
    </main>
</div>
</body>
</html>
