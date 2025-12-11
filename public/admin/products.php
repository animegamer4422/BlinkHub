<?php
require_once __DIR__ . '/../../src/auth_admin.php';
require_once __DIR__ . '/../../config/db.php';

$statusMessage = '';
$statusType    = '';

// ---------- LOAD CATEGORIES ----------
$catRes     = $mysqli->query("SELECT id, name FROM categories ORDER BY name ASC");
$categories = $catRes ? $catRes->fetch_all(MYSQLI_ASSOC) : [];

// ---------- TOGGLE ACTIVE / INACTIVE ----------
if (isset($_GET['toggle'])) {
    $id = (int) ($_GET['toggle'] ?? 0);

    if ($id > 0) {
        $stmt = $mysqli->prepare("UPDATE products SET is_active = 1 - is_active WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
        }
    }

    header("Location: products.php");
    exit;
}

// ---------- CREATE / UPDATE PRODUCT ----------
if (isset($_POST['action'])) {
    $name     = trim($_POST['name'] ?? '');
    $mrp      = (float) ($_POST['mrp'] ?? 0);
    $price    = (float) ($_POST['price'] ?? 0);
    $img      = trim($_POST['image_url'] ?? '');
    $catId    = (int) ($_POST['category_id'] ?? 0);
    $tags     = trim($_POST['tags'] ?? '');
    $isActive = isset($_POST['is_active']) ? 1 : 0;

    if ($name === '' || $price <= 0 || $mrp <= 0) {
        $statusMessage = "Name, MRP, and Price are required.";
        $statusType    = "error";
    } else {
        if ($_POST['action'] === 'create') {
            $stmt = $mysqli->prepare("
                INSERT INTO products (name, mrp, price, image_url, category_id, tags, is_active)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            if ($stmt) {
                $stmt->bind_param('sddsisi', $name, $mrp, $price, $img, $catId, $tags, $isActive);
                $ok = $stmt->execute();
                $stmt->close();

                $statusMessage = $ok ? "Product added successfully." : "Error adding product.";
                $statusType    = $ok ? "success" : "error";
            }
        } elseif ($_POST['action'] === 'update') {
            $id = (int) ($_POST['product_id'] ?? 0);

            if ($id > 0) {
                $stmt = $mysqli->prepare("
                    UPDATE products 
                    SET name=?, mrp=?, price=?, image_url=?, category_id=?, tags=?, is_active=?
                    WHERE id=?
                ");
                if ($stmt) {
                    // NOTE: 8 params → 'sddsisii'
                    $stmt->bind_param('sddsisii', $name, $mrp, $price, $img, $catId, $tags, $isActive, $id);
                    $ok = $stmt->execute();
                    $stmt->close();

                    $statusMessage = $ok ? "Product updated successfully." : "Error updating product.";
                    $statusType    = $ok ? "success" : "error";
                }
            }
        }
    }
}

// ---------- DELETE PRODUCT ----------
if (isset($_GET['delete'])) {
    $id = (int) ($_GET['delete'] ?? 0);

    if ($id > 0) {
        $stmt = $mysqli->prepare("DELETE FROM products WHERE id = ?");
        if ($stmt) {
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->close();
        }
    }

    header("Location: products.php");
    exit;
}

// ---------- SEARCH ----------
$search = trim($_GET['q'] ?? '');

if ($search !== '') {
    $stmt = $mysqli->prepare("
        SELECT p.*, c.name AS category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE p.name LIKE CONCAT('%', ?, '%')
           OR p.tags LIKE CONCAT('%', ?, '%')
           OR c.name LIKE CONCAT('%', ?, '%')
        ORDER BY p.id DESC
    ");
    $stmt->bind_param('sss', $search, $search, $search);
    $stmt->execute();
    $products = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} else {
    $res = $mysqli->query("
        SELECT p.*, c.name AS category_name
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        ORDER BY p.id DESC
    ");
    $products = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
}

// ---------- EDITING PRODUCT ----------
$editingProduct = null;
if (isset($_GET['edit'])) {
    $id  = (int) ($_GET['edit'] ?? 0);
    $res = $mysqli->query("SELECT * FROM products WHERE id = {$id}");
    $editingProduct = $res ? $res->fetch_assoc() : null;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BlinkHub Admin – Products</title>
    <link rel="stylesheet" href="../css/admin.css">
</head>
<body class="admin-page products-page">

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
                Logged in as <?= htmlspecialchars($_SESSION['admin_name'] ?? 'Admin') ?>
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
                <a href="products.php" class="menu-item active">Products</a>
                <a href="users.php" class="menu-item">Users</a>
            </nav>
        </aside>

        <!-- MAIN -->
        <main class="admin-main">

            <div class="page-header">
                <div>
                    <h1 class="page-title">Manage Products</h1>
                    <p class="page-subtitle">
                        Add, edit, enable or disable products in your store.
                    </p>
                </div>

                <!-- SEARCH BAR -->
                <form class="search-bar" method="get">
                    <input
                        type="text"
                        name="q"
                        placeholder="Search by name, tag or category..."
                        value="<?= htmlspecialchars($search) ?>"
                    >
                    <button class="admin-btn primary" type="submit">Search</button>
                </form>
            </div>

            <?php if ($statusMessage): ?>
                <div class="alert alert-<?= $statusType ?>">
                    <?= htmlspecialchars($statusMessage) ?>
                </div>
            <?php endif; ?>

            <div class="products-layout">
                <!-- ADD / EDIT PANEL -->
                <section class="product-form-card">
                    <div class="form-header">
                        <h2 class="section-title">
                            <?= $editingProduct ? 'Edit Product' : 'Add Product' ?>
                        </h2>
                        <span class="badge <?= $editingProduct ? 'badge-editing' : 'badge-creating' ?>">
                            <?= $editingProduct ? 'Editing existing item' : 'Creating new item' ?>
                        </span>
                    </div>
                    <p class="section-subtitle">
                        Fill in product details, pricing and category. You can toggle visibility with the
                        <em>Active</em> switch.
                    </p>

                    <form method="post" class="admin-form">
                        <?php if ($editingProduct): ?>
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="product_id" value="<?= (int)$editingProduct['id'] ?>">
                        <?php else: ?>
                            <input type="hidden" name="action" value="create">
                        <?php endif; ?>

                        <label>Name</label>
                        <input
                            type="text"
                            name="name"
                            required
                            value="<?= htmlspecialchars($editingProduct['name'] ?? '') ?>"
                        >

                        <div class="form-row">
                            <label>
                                MRP
                                <input
                                    type="number"
                                    step="0.01"
                                    name="mrp"
                                    required
                                    value="<?= htmlspecialchars($editingProduct['mrp'] ?? '') ?>"
                                >
                            </label>
                            <label>
                                Selling Price
                                <input
                                    type="number"
                                    step="0.01"
                                    name="price"
                                    required
                                    value="<?= htmlspecialchars($editingProduct['price'] ?? '') ?>"
                                >
                            </label>
                        </div>

                        <label>Image URL</label>
                        <input
                            type="text"
                            name="image_url"
                            placeholder="https://…"
                            value="<?= htmlspecialchars($editingProduct['image_url'] ?? '') ?>"
                        >

                        <label>Category</label>
                        <select name="category_id">
                            <option value="">Select category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?= (int)$cat['id'] ?>"
                                    <?php
                                    if (!empty($editingProduct['category_id']) &&
                                        (int)$editingProduct['category_id'] === (int)$cat['id']
                                    ) {
                                        echo 'selected';
                                    }
                                    ?>
                                >
                                    <?= htmlspecialchars($cat['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <label>Tags (comma separated)</label>
                        <input
                            type="text"
                            name="tags"
                            placeholder="snacks, instant, spicy"
                            value="<?= htmlspecialchars($editingProduct['tags'] ?? '') ?>"
                        >

                        <label class="checkbox-row">
                            <input
                                type="checkbox"
                                name="is_active"
                                value="1"
                                <?= !isset($editingProduct['is_active']) || (int)$editingProduct['is_active'] === 1 ? 'checked' : '' ?>
                            >
                            <span>Active (visible on storefront)</span>
                        </label>

                        <button class="admin-btn primary btn-full" type="submit">
                            Save Product
                        </button>

                        <?php if ($editingProduct): ?>
                            <a href="products.php" class="admin-btn ghost btn-full">Cancel edit</a>
                        <?php endif; ?>
                    </form>
                </section>

                <!-- PRODUCT LIST -->
                <section class="product-table-card">
                    <div class="panel-header">
                        <h2 class="section-title">Products (<?= count($products) ?>)</h2>
                        <?php if ($search !== ''): ?>
                            <span class="panel-note">
                                Search: "<strong><?= htmlspecialchars($search) ?></strong>"
                            </span>
                        <?php endif; ?>
                    </div>

                    <div class="table-wrapper table-scroll">
                        <table class="product-table">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Product</th>
                                <th>Pricing</th>
                                <th>Status</th>
                                <th>Tags</th>
                                <th>Actions</th>
                            </tr>
                            </thead>

                            <tbody>
                            <?php if (!count($products)): ?>
                                <tr>
                                    <td colspan="6" class="empty-row">
                                        No products found.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($products as $p): ?>
                                    <tr>
                                        <td>#<?= (int)$p['id'] ?></td>

                                        <td>
                                            <div class="prod-name">
                                                <?= htmlspecialchars($p['name']) ?>
                                            </div>
                                            <?php if (!empty($p['category_name'])): ?>
                                                <div class="prod-meta">
                                                    <?= htmlspecialchars($p['category_name']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </td>

                                        <td>
                                            <div class="prod-price">₹<?= htmlspecialchars($p['price']) ?></div>
                                            <div class="prod-mrp">₹<?= htmlspecialchars($p['mrp']) ?></div>
                                        </td>

                                        <td>
                                            <?php if ((int)$p['is_active'] === 1): ?>
                                                <span class="status-pill active">Active</span>
                                            <?php else: ?>
                                                <span class="status-pill inactive">Inactive</span>
                                            <?php endif; ?>
                                        </td>

                                        <td class="tags-cell">
                                            <span class="tags-text">
                                                <?= htmlspecialchars($p['tags']) ?>
                                            </span>
                                        </td>

                                        <td class="actions-cell">
                                            <a href="products.php?edit=<?= (int)$p['id'] ?>" class="table-link">
                                                Edit
                                            </a>
                                            <span class="divider">•</span>
                                            <a href="products.php?toggle=<?= (int)$p['id'] ?>" class="table-link">
                                                <?= (int)$p['is_active'] === 1 ? 'Deactivate' : 'Activate' ?>
                                            </a>
                                            <span class="divider">•</span>
                                            <a
                                                href="products.php?delete=<?= (int)$p['id'] ?>"
                                                onclick="return confirm('Delete product?')"
                                                class="table-link danger"
                                            >
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
