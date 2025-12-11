<?php
session_start();
require_once __DIR__ . '/../../config/db.php';

$error = "";
$email = "";

// Optional: tiny helper to log to PHP error log (does NOT break redirects)
function dbg($msg) {
    error_log("[admin-login] " . $msg);
}

dbg("login.php hit, method=" . ($_SERVER['REQUEST_METHOD'] ?? 'UNKNOWN'));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? "");
    $password = trim($_POST['password'] ?? "");

    dbg("POST email={$email}");

    if ($email === "" || $password === "") {
        $error = "Email and password are required.";
        dbg("Validation failed – empty email/password");
    } else {
        if (!isset($mysqli) || !$mysqli instanceof mysqli) {
            $error = "Database connection not available.";
            dbg("FATAL: \$mysqli is not a valid mysqli instance");
        } else {
            $stmt = $mysqli->prepare(
                "SELECT id, name, email, password_hash 
                 FROM admin_users 
                 WHERE email = ?"
            );

            if (!$stmt) {
                $error = "Login failed (prep error).";
                dbg("prepare() failed: " . $mysqli->error);
            } else {
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $res   = $stmt->get_result();
                $admin = $res->fetch_assoc();

                dbg("Row found? " . ($admin ? "YES" : "NO"));

                if ($admin && password_verify($password, $admin['password_hash'])) {
                    // Login success
                    $_SESSION['admin'] = [
                        'id'    => $admin['id'],
                        'name'  => $admin['name'],
                        'email' => $admin['email']
                    ];

                    // Extra keys for compatibility with guards like auth_admin.php
                    $_SESSION['admin_id']    = $admin['id'];
                    $_SESSION['admin_name']  = $admin['name'];
                    $_SESSION['admin_email'] = $admin['email'];

                    dbg("Login OK for admin_id={$admin['id']}, redirecting to /admin/index.php");

                    // IMPORTANT: use absolute path so it always hits the admin dashboard
                    header("Location: /admin/index.php");
                    exit;
                } else {
                    $error = "Invalid email or password.";
                    dbg("password_verify failed or no row for email={$email}");
                }

                $stmt->close();
            }
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
            <a href="/index.php" class="admin-logo">
                <span class="logo-dark">Blink</span><span class="logo-yellow">Hub</span>
            </a>
            <span class="logo-badge">Admin Panel</span>
        </div>
    </header>

    <main class="admin-main auth-main">
        <div class="auth-card admin-auth-card">

            <h1 class="page-title">Admin Login</h1>
            <p class="page-subtitle">
                Sign in with your admin credentials to manage products & users.
            </p>

            <?php if ($error): ?>
                <div class="alert error"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST" class="admin-form">

                <label>Email</label>
                <input 
                    type="email" 
                    name="email" 
                    required 
                    value="<?= htmlspecialchars($email) ?>"
                >

                <label>Password</label>
                <div class="input-group">
                    <input type="password" name="password" id="password" required>
                    <button type="button" id="togglePassword" class="show-btn">Show</button>
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
document.getElementById("togglePassword").addEventListener("click", function () {
    const field = document.getElementById("password");
    if (field.type === "password") {
        field.type = "text";
        this.textContent = "Hide";
    } else {
        field.type = "password";
        this.textContent = "Show";
    }
});
</script>

</body>
</html>
