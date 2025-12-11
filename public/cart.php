    <?php
    session_start();
    require_once __DIR__ . '/../config/db.php';

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


    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>BlinkHub – Cart</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="css/style.css">
    </head>
    <body class="cart-page">
    <div class="page">
        <!-- Navbar (same as index.php) -->
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
            </div>
        </header>

        <!-- Cart content -->
        <main class="content cart-content">
            <div class="section-header">
                <h2>Your cart</h2>
                <span class="section-subtitle" id="cart-subtitle">Loading your items…</span>
            </div>

            <div id="cart-empty" class="cart-empty">
                <p class="cart-empty-title">Your cart is empty.</p>
                <p class="cart-empty-note">
                    Add some instant noodles, cold drinks or snacks and they’ll appear here.
                </p>
                <a href="index.php" class="cta-btn cart-empty-cta">Browse products</a>
            </div>


            <div id="cart-filled" class="cart-filled hidden">
                <table class="cart-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Item</th>
                            <th>Price</th>
                            <th>Qty</th>
                            <th>Line total</th>
                        </tr>
                    </thead>
                    <tbody id="cart-items"></tbody>
                </table>

                <div class="cart-summary">
                    <div class="cart-summary-row">
                        <span>Subtotal</span>
                        <span id="cart-subtotal">₹0</span>
                    </div>
                    <div class="cart-summary-row">
                        <span>Delivery fee</span>
                        <span id="cart-delivery">₹0</span>
                    </div>
                    <div class="cart-summary-row total">
                        <span>Total</span>
                        <span id="cart-total">₹0</span>
                    </div>
                    <button class="cta-btn cart-checkout" id="cart-checkout-btn">
                        Proceed to payment
                    </button>
                    <p class="cart-note">
                        This is a mock payment flow. Later we’ll write the order into MySQL and show a real order ID.
                    </p>
                    <p id="cart-payment-status" class="cart-note"></p>
                </div>
            </div>
    </main>
</div>

<script>
    window.BLINKHUB_IS_LOGGED_IN = <?= $user ? 'true' : 'false' ?>;
</script>
<script src="js/app.js"></script>
</body>
</html>

