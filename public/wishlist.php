<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$user = $_SESSION['user'] ?? null;

// Force login to use wishlist
if (!$user || !isset($user['id'])) {
    header('Location: login.php');
    exit;
}

$statusMessage = '';
$statusType    = '';

$productName  = '';
$brand        = '';
$approxPrice  = '';
$details      = '';

// --------- HANDLE FORM SUBMIT ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productName = trim($_POST['product_name'] ?? '');
    $brand       = trim($_POST['brand'] ?? '');
    $approxPrice = trim($_POST['approx_price'] ?? '');
    $details     = trim($_POST['details'] ?? '');

    if ($productName === '') {
        $statusMessage = 'Please enter at least the product name.';
        $statusType    = 'error';
    } else {
        $uid      = (int)$user['id'];
        $priceInt = ($approxPrice !== '' && ctype_digit($approxPrice))
            ? (int)$approxPrice
            : 0; // 0 = unknown

        $stmt = $mysqli->prepare("
            INSERT INTO wishlist_requests (user_id, product_name, brand, approx_price, details)
            VALUES (?, ?, ?, ?, ?)
        ");
        if ($stmt) {
            $stmt->bind_param('issis', $uid, $productName, $brand, $priceInt, $details);
            $ok = $stmt->execute();
            $stmt->close();

            if ($ok) {
                $statusMessage = 'Wishlist request submitted. We\'ll take a look soon!';
                $statusType    = 'success';
                // Clear form fields
                $productName = $brand = $approxPrice = $details = '';
            } else {
                $statusMessage = 'Could not save your request. Please try again.';
                $statusType    = 'error';
            }
        } else {
            $statusMessage = 'Something went wrong while preparing the wishlist query.';
            $statusType    = 'error';
        }
    }
}

// --------- LOAD RECENT REQUESTS FOR THIS USER ----------
$wishlistItems = [];
$uid = (int)$user['id'];

$tableCheck = $mysqli->query("SHOW TABLES LIKE 'wishlist_requests'");
if ($tableCheck && $tableCheck->num_rows > 0) {
    $stmt = $mysqli->prepare("
        SELECT id, product_name, brand, approx_price, details, status, created_at
        FROM wishlist_requests
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 20
    ");
    if ($stmt) {
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $wishlistItems = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BlinkHub – Wishlist</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="wishlist-page">
<div class="page">
    <!-- Top navbar -->
    <header class="navbar">
        <div class="nav-left">
            <a href="index.php" class="logo" aria-label="BlinkHub home">
                <span class="logo-dark">Blink</span><span class="logo-yellow">Hub</span>
            </a>
            <div class="location-pill wishlist-pill">
                <span class="loc-label">Deliver to</span>
                <span class="loc-main">
                    Wishlist – tell us what to stock
                </span>
                <span class="loc-eta">💡 Help improve BlinkHub</span>
            </div>
        </div>

        <div class="nav-center">
            <div class="search-box">
                <span class="search-icon">🔍</span>
                <input
                    type="text"
                    placeholder="Search for chips, milk, Coke, bread..."
                    disabled
                />
            </div>
        </div>

        <div class="nav-right">
            <span class="nav-user">Hi, <?= htmlspecialchars($user['name'] ?? $user['email']) ?></span>
            <a href="logout.php" class="nav-btn ghost">Logout</a>
            <a href="wishlist.php" class="nav-btn ghost active-nav-btn">🤍 Wishlist</a>
            <a href="cart.php" class="nav-btn cart-btn">
                🛒
                <span class="cart-label">Cart</span>
                <span class="cart-count-badge" id="cart-count">0</span>
            </a>
        </div>
    </header>

    <main class="wishlist-main">
        <!-- Page header text -->
        <section class="wishlist-header">
            <h1>Request a product</h1>
            <p>
                Can’t find something on <strong>BlinkHub</strong>? Share the details and we’ll
                try to add it to your local store assortment.
            </p>
        </section>

        <!-- Two-column layout: form + recent requests -->
        <section class="wishlist-layout">
            <!-- Left: form card -->
            <section class="wishlist-card">
                <header class="wishlist-card-header">
                    <div>
                        <h2>Wishlist request</h2>
                        <p>Fill in as much detail as you can – brand, size, flavour, etc.</p>
                    </div>
                    <span class="wishlist-chip">New</span>
                </header>

                <?php if ($statusMessage): ?>
                    <div class="wishlist-alert wishlist-alert-<?= htmlspecialchars($statusType) ?>">
                        <?= htmlspecialchars($statusMessage) ?>
                    </div>
                <?php endif; ?>

                <form method="post" class="wishlist-form">
                    <label>
                        Product name <span class="field-required">*</span>
                        <input
                            type="text"
                            name="product_name"
                            required
                            placeholder="e.g., Doritos Nacho Cheese 70g"
                            value="<?= htmlspecialchars($productName) ?>"
                        >
                    </label>

                    <div class="wishlist-row">
                        <label>
                            Preferred brand
                            <input
                                type="text"
                                name="brand"
                                placeholder="e.g., Doritos, Lay’s, Amul"
                                value="<?= htmlspecialchars($brand) ?>"
                            >
                        </label>
                        <label>
                            Approximate price (₹)
                            <input
                                type="number"
                                name="approx_price"
                                min="0"
                                placeholder="e.g., 60"
                                value="<?= htmlspecialchars($approxPrice) ?>"
                            >
                        </label>
                    </div>

                    <label>
                        Extra details
                        <textarea
                            name="details"
                            rows="4"
                            placeholder="Pack size, flavour, specific brand variant, or link to the product page elsewhere…"
                        ><?= htmlspecialchars($details) ?></textarea>
                    </label>

                    <button type="submit" class="cta-btn wishlist-submit-btn">
                        Submit request
                    </button>

                    <p class="wishlist-note">
                        Wishlist is just a request – not a guarantee. But we’ll try our best 🙂
                    </p>
                </form>
            </section>

            <!-- Right: recent requests / info -->
            <aside class="wishlist-sidebar">
                <div class="wishlist-side-card">
                    <h3>Your recent requests</h3>

                    <?php if (!$wishlistItems): ?>
                        <p class="wishlist-side-empty">
                            You haven’t requested anything yet. Your first few requests will show up here.
                        </p>
                    <?php else: ?>
                        <ul class="wishlist-history-list">
                            <?php foreach ($wishlistItems as $item): ?>
                                <li class="wishlist-history-item">
                                    <div class="history-main">
                                        <div class="history-name">
                                            <?= htmlspecialchars($item['product_name']) ?>
                                        </div>
                                        <?php if (!empty($item['brand'])): ?>
                                            <div class="history-brand">
                                                Brand: <?= htmlspecialchars($item['brand']) ?>
                                            </div>
                                        <?php endif; ?>
                                        <?php if (!empty($item['details'])): ?>
                                            <div class="history-details">
                                                <?= nl2br(htmlspecialchars($item['details'])) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="history-meta">
                                        <?php if ((int)$item['approx_price'] > 0): ?>
                                            <span class="history-price">≈ ₹<?= (int)$item['approx_price'] ?></span>
                                        <?php endif; ?>
                                        <span class="history-status status-<?= htmlspecialchars($item['status']) ?>">
                                            <?= ucfirst($item['status']) ?>
                                        </span>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>

                <div class="wishlist-side-card wishlist-tips">
                    <h3>Tips for faster approvals</h3>
                    <ul>
                        <li>Mention pack size (e.g., 1L, 500g, 6-pack).</li>
                        <li>Share the exact flavour or variant.</li>
                        <li>Include a link to the product from another site if possible.</li>
                    </ul>
                </div>
            </aside>
        </section>
    </main>

    <footer class="footer wishlist-footer">
        <span>Helping us stock better things for you ✨</span>
    </footer>
</div>

<script src="js/app.js"></script>
</body>
</html>
