<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$info = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if ($email === '') {
        $error = "Please enter your email.";
    } else {
        $stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $res = $stmt->get_result();
        $user = $res->fetch_assoc();
        $stmt->close();

        if ($user) {
            $newPass = "12345678";
            $hash = password_hash($newPass, PASSWORD_BCRYPT);

            $stmt = $mysqli->prepare("UPDATE users SET password_hash = ? WHERE id = ?");
            $stmt->bind_param('si', $hash, $user['id']);
            $stmt->execute();
            $stmt->close();

            $info = "Password reset successful. Your new password is: <strong>$newPass</strong>";
        } else {
            $error = "No user found with that email.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password – BlinkHub</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-page">
<div class="page">

    <!-- Same navbar as login -->
    <header class="navbar">
        <div class="nav-left">
            <a href="index.php" class="logo">
                <span class="logo-dark">Blink</span><span class="logo-yellow">Hub</span>
            </a>
            <div class="location-pill">
                <span class="loc-label">Deliver to</span>
                <span class="loc-main">Kingsbury, Charholi</span>
                <span class="loc-eta">⏱ 10-15 mins</span>
            </div>
        </div>
        <div class="nav-center">
            <div class="search-box">
                <span class="search-icon">🔍</span>
                <input type="text" placeholder="Search..." disabled />
            </div>
        </div>
        <div class="nav-right">
            <a href="login.php" class="nav-btn ghost">👤 Login</a>
            <a href="cart.php" class="nav-btn cart-btn">
                🛒 <span class="cart-label">Cart</span>
                <span class="cart-count-badge" id="cart-count">0</span>
            </a>
        </div>
    </header>

    <main class="auth-wrapper">
        <div class="auth-card">
            <h1>Reset Password</h1>
            <p class="auth-subtitle">Enter your email to reset your password instantly.</p>

            <?php if ($error): ?>
                <div class="auth-error"><?= $error ?></div>
            <?php endif; ?>

            <?php if ($info): ?>
                <div class="auth-success"><?= $info ?></div>
            <?php endif; ?>

            <form method="post" class="auth-form">
                <label>Email
                    <input type="email" name="email" required>
                </label>
                <button type="submit" class="cta-btn auth-btn">Reset Password</button>
            </form>

            <p class="auth-switch">
                Remembered? <a href="login.php">Go back to login</a>
            </p>
        </div>
    </main>

</div>
</body>
</html>
