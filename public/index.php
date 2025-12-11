<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$user = $_SESSION['user'] ?? null;


$user = $_SESSION['user'] ?? null;

// --- Load default address (if logged in) ---
$locMainText = 'Kingsbury, Charholi';
$locEtaText  = '⏱ 10-15 mins';

if ($user && isset($user['id'])) {
    $uid = (int)$user['id'];

    $stmt = $mysqli->prepare("
        SELECT line1, city 
        FROM addresses 
        WHERE user_id = ? AND is_default = 1 
        ORDER BY id DESC 
        LIMIT 1
    ");
    if ($stmt) {
        $stmt->bind_param('i', $uid);
        if ($stmt->execute()) {
            $res = $stmt->get_result();
            if ($row = $res->fetch_assoc()) {
                $short = trim($row['line1'] . ', ' . $row['city']);
                if ($short !== '') {
                    $locMainText = $short;
                }
            }
        }
        $stmt->close();
    }
}


// Load categories
$categories = [];
$catResult = $mysqli->query("SELECT name FROM categories ORDER BY sort_order, name");
if ($catResult) {
    while ($row = $catResult->fetch_assoc()) {
        $categories[] = $row['name'];
    }
    $catResult->free();
}

// Load products
$products = [];
$sql = "
    SELECT 
        p.id,
        p.name,
        p.price,
        p.mrp,
        p.tag,
        p.eta_minutes,
        p.tags,
        c.name AS category_name
    FROM products p
    JOIN categories c ON c.id = p.category_id
    WHERE p.is_active = 1
    ORDER BY p.id

";
$prodResult = $mysqli->query($sql);
if ($prodResult) {
    while ($row = $prodResult->fetch_assoc()) {
        $products[] = $row;
    }
    $prodResult->free();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BlinkHub – Blinkit clone</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<div class="page">
    <!-- Top navbar -->
    <header class="navbar">
        <div class="nav-left">
            <a href="index.php" class="logo" aria-label="BlinkHub home">
                <span class="logo-dark">Blink</span><span class="logo-yellow">Hub</span>
            </a>
                <div class="location-pill" id="location-pill">
                    <span class="loc-label">Deliver to</span>
                    <span class="loc-main" id="loc-main-text">
                        <?= htmlspecialchars($locMainText) ?>
                    </span>
                    <span class="loc-eta"><?= $locEtaText ?></span>
                </div>

        </div>
        <div class="nav-center">
            <div class="search-box">
                <span class="search-icon">🔍</span>
                <input 
                    type="text" 
                    id="search-input"
                    placeholder="Search for chips, milk, Coke, bread..." 
                    autocomplete="off"
                />
            </div>

        </div>
        <div class="nav-right">
            <?php if ($user): ?>
                <span class="nav-user">Hi, <?= htmlspecialchars($user['name'] ?? $user['email']) ?></span>
                <a href="logout.php" class="nav-btn ghost">Logout</a>
            <?php else: ?>
                <a href="login.php" class="nav-btn ghost">👤 Login</a>
            <?php endif; ?>
            <a href="cart.php" class="nav-btn cart-btn">
                🛒
                <span class="cart-label">Cart</span>
                <span class="cart-count-badge" id="cart-count">0</span>
            </a>
            <!-- Address modal -->
<div class="address-backdrop hidden" id="address-modal">
    <div class="address-dialog">
        <h2>Delivery address</h2>
        <p class="address-subtitle">
            Save your delivery address so riders know exactly where to drop your snacks.
        </p>

        <form id="address-form" class="address-form">
            <label>
                Address label (Home / Work)
                <input type="text" name="label" placeholder="Home" />
            </label>
            <label>
                Flat / House / Building *
                <input type="text" name="line1" required placeholder="E2-703, Kingsbury" />
            </label>
            <label>
                Street / Area
                <input type="text" name="line2" placeholder="Near XYZ Chowk" />
            </label>
            <label>
                Landmark
                <input type="text" name="landmark" placeholder="Opp. ABC temple / shop" />
            </label>
            <label>
                City *
                <input type="text" name="city" required value="Pune" />
            </label>
            <label>
                Pincode *
                <input type="text" name="pincode" required placeholder="412105" />
            </label>
            <label>
                Phone
                <input type="text" name="phone" placeholder="10-digit mobile" />
            </label>

            <div class="address-form-actions">
                <button type="button" class="nav-btn ghost" id="address-cancel">
                    Cancel
                </button>
                <button type="submit" class="cta-btn">
                    Save & use this address
                </button>
            </div>

            <p class="address-status" id="address-status"></p>
        </form>
    </div>
</div>

        </div>
    </header>

    <!-- Category strip -->
<section class="category-strip">
    <?php if ($categories): ?>
        <!-- Default "All" filter -->
        <button class="category-pill active" data-category="">All</button>

        <?php foreach ($categories as $cat): ?>
            <button 
                class="category-pill" 
                data-category="<?= htmlspecialchars($cat) ?>">
                <?= htmlspecialchars($cat) ?>
            </button>
        <?php endforeach; ?>
    <?php else: ?>
        <span class="section-subtitle">No categories found in DB.</span>
    <?php endif; ?>
</section>

    <!-- Product grid -->
    <main class="content">
        <div class="section-header">
            <h2>Snacks & essentials near you</h2>
        </div>

        <?php if (!$products): ?>
            <p class="section-subtitle">No products found. Check your DB seed.</p>
        <?php else: ?>
            <div class="product-grid">
                <?php foreach ($products as $index => $p): ?>
                    <article class="product-card"
                        data-index="<?= (int)$index ?>"
                        data-name="<?= htmlspecialchars($p['name']) ?>"
                        data-category="<?= htmlspecialchars($p['category_name']) ?>"
                        data-tags="<?= htmlspecialchars($p['tags']) ?>">


                        <div class="product-thumb">
                            <?php if (!empty($p['tag'])): ?>
                                <div class="product-chip"><?= htmlspecialchars($p['tag']) ?></div>
                            <?php endif; ?>
                            <div class="product-placeholder">
                                <span><?= htmlspecialchars($p['category_name']) ?></span>
                            </div>
                        </div>
                        <div class="product-body">
                            <h3 class="product-name"><?= htmlspecialchars($p['name']) ?></h3>
                            <p class="product-meta">
                                ETA: <strong><?= (int)$p['eta_minutes'] ?> mins</strong>
                            </p>
                            <div class="product-price-row">
                                <span class="price">₹<?= number_format((int)$p['price']) ?></span>
                                <span class="mrp">₹<?= number_format((int)$p['mrp']) ?></span>
                            </div>
                                <div class="qty-controls" 
                                    data-id="<?= (int)$p['id'] ?>" 
                                    data-name="<?= htmlspecialchars($p['name']) ?>" 
                                    data-price="<?= (int)$p['price'] ?>">
                                    
                                    <button class="add-btn">+ Add</button>

                                    <div class="stepper hidden">
                                        <button class="stepper-minus">-</button>
                                        <span class="stepper-qty">1</span>
                                        <button class="stepper-plus">+</button>
                                    </div>
                                </div>

                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>

    <footer class="footer">
        <span>Made for fun • Stack: PHP + MySQL + Pornhub theme</span>
    </footer>
</div>

<script src="js/app.js"></script>
</body>
</html>
