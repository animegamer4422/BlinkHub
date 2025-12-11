<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$user = $_SESSION['user'] ?? null;
if ($user) {
    header('Location: index.php');
    exit;
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $errors[] = 'Please fill in both fields.';
    } else {
        $stmt = $mysqli->prepare("SELECT id, name, email, password_hash, role FROM users WHERE email = ? LIMIT 1");
        if ($stmt) {
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();

            if ($row && password_verify($password, $row['password_hash'])) {
                $_SESSION['user'] = [
                    'id'    => $row['id'],
                    'name'  => $row['name'],
                    'email' => $row['email'],
                    'role'  => $row['role'] ?? 'user',
                ];
                header('Location: index.php');
                exit;
            } else {
                $errors[] = 'Invalid email or password.';
            }
        } else {
            $errors[] = 'Something went wrong with the login query.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BlinkHub – Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-page">
<div class="page">
    <!-- Navbar (same layout as index, but disabled interactions) -->
    <header class="navbar">
        <div class="nav-left">
            <a href="index.php" class="logo" aria-label="BlinkHub home">
                <span class="logo-dark">Blink</span><span class="logo-yellow">Hub</span>
            </a>

            <!-- Deliver-to pill DISABLED on login -->
            <div class="location-pill disabled-pill">
                <span class="loc-label">Deliver to</span>
                <span class="loc-main">Login required</span>
                <span class="loc-eta">⏱ --</span>
            </div>
        </div>

        <div class="nav-center">
            <div class="search-box disabled-box">
                <span class="search-icon">🔍</span>
                <input
                    type="text"
                    placeholder="Search disabled on login page"
                    disabled
                />
            </div>
        </div>

        <div class="nav-right">
            <a href="login.php" class="nav-btn ghost active-nav-btn">👤 Login</a>
            <a href="register.php" class="nav-btn ghost">Register</a>

            <!-- Cart visible but disabled on login -->
            <div class="nav-btn cart-btn disabled-cart" title="Login to use cart">
                🛒
                <span class="cart-label">Cart</span>
                <span class="cart-count-badge" id="cart-count">0</span>
            </div>
        </div>
    </header>

    <main class="auth-wrapper">
        <div class="auth-card">
            <h1>Welcome back</h1>
            <p class="auth-subtitle">Login to continue shopping in <strong>BlinkHub</strong>.</p>

            <?php if ($errors): ?>
                <div class="auth-error">
                    <?= htmlspecialchars(implode(' ', $errors)) ?>
                </div>
            <?php endif; ?>

            <form method="post" class="auth-form">
                <label>
                    Email
                    <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
                </label>
                <label>
                    Password
                    <input type="password" name="password" required>
                </label>
                <button type="submit" class="cta-btn auth-btn">Login</button>
            </form>

            <p class="auth-reset">
                Forgot password?
                <a href="reset_password.php" class="reset-link">Reset it instantly</a>
            </p>

            <p class="auth-switch">
                New to BlinkHub?
                <a href="register.php">Create an account</a>
            </p>
        </div>
    </main>
</div>

<script src="js/app.js"></script>
</body>
</html>
