<?php
session_start();
require_once __DIR__ . '/../config/db.php';

$user = $_SESSION['user'] ?? null;
if ($user) {
    header('Location: index.php');
    exit;
}

$errors = [];
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name     = trim($_POST['name'] ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    $confirm  = trim($_POST['confirm'] ?? '');

    if ($name === '' || $email === '' || $password === '' || $confirm === '') {
        $errors[] = 'Please fill in all fields.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email.';
    } elseif ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    } else {
        // Check if email is already used
        $check = $mysqli->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        if ($check) {
            $check->bind_param('s', $email);
            $check->execute();
            $check->store_result();
            if ($check->num_rows > 0) {
                $errors[] = 'This email is already registered.';
            }
            $check->close();
        } else {
            $errors[] = 'Error checking existing users.';
        }

        if (!$errors) {
            $hash = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $mysqli->prepare("
                INSERT INTO users (name, email, password_hash, role)
                VALUES (?, ?, ?, 'user')
            ");
            if ($stmt) {
                $stmt->bind_param('sss', $name, $email, $hash);
                if ($stmt->execute()) {
                    $newId = $stmt->insert_id;
                    $_SESSION['user'] = [
                        'id'    => $newId,
                        'name'  => $name,
                        'email' => $email,
                    ];
                    $stmt->close();
                    header('Location: index.php');
                    exit;
                } else {
                    $errors[] = 'Could not create account. Try again.';
                    $stmt->close();
                }
            } else {
                $errors[] = 'Error preparing insert statement.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BlinkHub – Register</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="auth-page">
<div class="page">
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
            <div class="search-box">
                <span class="search-icon">🔍</span>
                <input type="text" placeholder="Search for chips, milk, Coke, bread..." />
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

    <main class="auth-wrapper">
        <div class="auth-card">
            <h1>Create account</h1>
            <p class="auth-subtitle">Sign up to unlock faster re-orders and smarter carts.</p>

            <?php if ($errors): ?>
                <div class="auth-error">
                    <?= htmlspecialchars(implode(' ', $errors)) ?>
                </div>
            <?php endif; ?>

            <form method="post" class="auth-form">
                <label>
                    Full name
                    <input type="text" name="name" value="<?= htmlspecialchars($name) ?>" required>
                </label>
                <label>
                    Email
                    <input type="email" name="email" value="<?= htmlspecialchars($email) ?>" required>
                </label>
                <label>
                    Password
                    <input type="password" name="password" required>
                </label>
                <label>
                    Confirm password
                    <input type="password" name="confirm" required>
                </label>
                <button type="submit" class="cta-btn auth-btn">Create account</button>
            </form>

            <p class="auth-switch">
                Already have an account?
                <a href="login.php">Login instead</a>
            </p>
        </div>
    </main>
</div>

<script src="js/app.js"></script>
</body>
</html>
