<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../../config/db.php';

$error = '';
$email = '';

// Toggle this off in production if you want quieter logs
const ADMIN_LOGIN_DEBUG = true;

// Small helper for logging
function dbg(string $msg): void {
    if (!ADMIN_LOGIN_DEBUG) {
        return;
    }
    error_log('[admin-login] ' . $msg);
}

dbg('login.php hit, method=' . ($_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN'));

// If already logged in, send straight to dashboard
if (!empty($_SESSION['admin_id'])) {
    dbg('Already logged in as admin_id=' . $_SESSION['admin_id'] . ' – redirecting');
    header('Location: /admin/index.php');
    exit;
}

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    dbg('POST email=' . $email);

    // Basic validations
    if ($email === '' || $password === '') {
        $error = 'Email and password are required.';
        dbg('Validation failed – empty email or password');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
        dbg('Validation failed – invalid email format');
    } elseif (!isset($mysqli) || !$mysqli instanceof mysqli) {
        $error = 'Database connection not available.';
        dbg('FATAL: $mysqli is not a valid mysqli instance');
    } else {
        // All basic validation passed, try to fetch admin row
        $sql = 'SELECT id, name, email, password_hash 
                FROM admin_users 
                WHERE email = ? 
                LIMIT 1';

        $stmt = $mysqli->prepare($sql);

        if (!$stmt) {
            $error = 'Login failed. Please try again later.';
            dbg('prepare() failed: ' . $mysqli->error);
        } else {
            $stmt->bind_param('s', $email);

            if (!$stmt->execute()) {
                $error = 'Login failed. Please try again later.';
                dbg('execute() failed: ' . $stmt->error);
            } else {
                $res   = $stmt->get_result();
                $admin = $res ? $res->fetch_assoc() : null;

                dbg('Row found? ' . ($admin ? 'YES' : 'NO'));

                if ($admin && password_verify($password, $admin['password_hash'])) {
                    // Login success
                    $_SESSION['admin'] = [
                        'id'    => (int)$admin['id'],
                        'name'  => $admin['name'],
                        'email' => $admin['email'],
                    ];

                    // Extra keys for compatibility with guards like auth_admin.php
                    $_SESSION['admin_id']    = (int)$admin['id'];
                    $_SESSION['admin_name']  = $admin['name'];
                    $_SESSION['admin_email'] = $admin['email'];

                    dbg('Login OK for admin_id=' . $admin['id'] . ', redirecting to /admin/index.php');

                    header('Location: /admin/index.php');
                    exit;
                }

                // Either no row or bad password – generic error
                $error = 'Invalid email or password.';
                dbg('password_verify failed or no row for email=' . $email);
            }

            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BlinkHub Admin – Login</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/admin.css">
</head>

<body class="admin-page admin-login-page">
<div class="admin-shell">

    <header class="admin-navbar">
        <div class="admin-nav-left">
            <a href="index.php" class="admin-logo">
                <span class="logo-dark">Blink</span><span class="logo-yellow">Hub</span>
            </a>
            <span class="logo-badge">Admin Panel</span>
        </div>
    </header>

    <main class="admin-main auth-main">
        <div class="auth-card admin-auth-card">

            <h1 class="page-title">Admin Login</h1>
            <p class="page-subtitle">
                Sign in with your admin credentials to manage products &amp; users.
            </p>

            <?php if ($error): ?>
                <div class="alert error"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>

            <form method="POST" class="admin-form" autocomplete="on" novalidate>
                <label for="email">Email</label>
                <input
                    id="email"
                    type="email"
                    name="email"
                    required
                    autocomplete="email"
                    value="<?= htmlspecialchars($email, ENT_QUOTES, 'UTF-8') ?>"
                >

                <label for="password">Password</label>
                <div class="input-group">
                    <input
                        id="password"
                        type="password"
                        name="password"
                        required
                        autocomplete="current-password"
                    >
                    <button
                        type="button"
                        id="togglePassword"
                        class="show-btn"
                        aria-label="Show password"
                        aria-pressed="false"
                    >
                        ⌣
                    </button>
                </div>

                <button class="admin-btn primary btn-full" type="submit">
                    Login
                </button>
            </form>

            <p class="auth-note">
                This area is only for store admins.
                If you’re a customer, use the regular login page.
            </p>
        </div>
    </main>

</div>

<script>
const passwordField = document.getElementById("password");
const togglePassword = document.getElementById("togglePassword");

if (passwordField && togglePassword) {
    togglePassword.addEventListener("click", () => {
        const isVisible = passwordField.type === "text";

        passwordField.type = isVisible ? "password" : "text";

        togglePassword.classList.toggle("is-visible", !isVisible);
        togglePassword.setAttribute("aria-pressed", String(!isVisible));
        togglePassword.setAttribute(
            "aria-label",
            isVisible ? "Show password" : "Hide password"
        );

        togglePassword.textContent = isVisible ? "⌣" : "👁";
    });
}
</script>

</body>
</html>
