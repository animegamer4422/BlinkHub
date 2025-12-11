<?php
require_once __DIR__ . '/../../config/db.php';

/**
 * Basic state vars
 */
$error   = '';
$message = '';
$admins  = [];
$name    = '';
$email   = '';

$debugLog = [];

// --- SAFETY: check DB connection ---
if (!isset($mysqli) || !$mysqli instanceof mysqli) {
    die("<pre style='background:#200;color:#f88;padding:10px;'>".
        "FATAL: \$mysqli is not a valid mysqli instance.\n".
        "Check config/db.php – it must define \$mysqli = new mysqli(...)\n".
        "</pre>");
}

if ($mysqli->connect_errno) {
    die("<pre style='background:#200;color:#f88;padding:10px;'>".
        "FATAL: DB connection failed: {$mysqli->connect_error}\n".
        "</pre>");
}

$debugLog[] = "DB Connected OK: {$mysqli->host_info}";

// --- Handle DELETE (via GET ?delete=ID) ---
if (isset($_GET['delete']) && $_GET['delete'] !== '') {
    $deleteId = (int) $_GET['delete'];
    $debugLog[] = "Requested delete of admin id={$deleteId}";

    if ($deleteId > 0) {
        $stmt = $mysqli->prepare("DELETE FROM admin_users WHERE id = ?");
        if (!$stmt) {
            $error = "Failed to prepare delete statement: " . $mysqli->error;
            $debugLog[] = "ERROR: " . $error;
        } else {
            $stmt->bind_param("i", $deleteId);
            if ($stmt->execute()) {
                if ($stmt->affected_rows > 0) {
                    $message = "Admin with ID {$deleteId} deleted.";
                    $debugLog[] = "Delete OK, affected_rows={$stmt->affected_rows}";
                } else {
                    $error = "No admin found with ID {$deleteId}.";
                    $debugLog[] = "Delete executed but affected_rows=0";
                }
            } else {
                $error = "Failed to delete admin: " . $stmt->error;
                $debugLog[] = "ERROR executing delete: " . $stmt->error;
            }
            $stmt->close();
        }
    } else {
        $error = "Invalid admin ID for delete.";
        $debugLog[] = "Invalid delete ID: {$deleteId}";
    }
}

// --- Handle CREATE (via POST) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $debugLog[] = "Handling POST create admin";

    $name     = trim($_POST['name']  ?? '');
    $email    = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');

    $debugLog[] = "POST name='{$name}', email='{$email}'";

    if ($name === '' || $email === '' || $password === '') {
        $error = "All fields are required.";
        $debugLog[] = "Validation failed: missing fields";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email.";
        $debugLog[] = "Validation failed: invalid email '{$email}'";
    } else {
        // Check if email already exists
        $debugLog[] = "Checking if email already exists";
        $stmt = $mysqli->prepare("SELECT id FROM admin_users WHERE email = ?");
        if (!$stmt) {
            $error = "Failed to prepare email check: " . $mysqli->error;
            $debugLog[] = "ERROR: " . $error;
        } else {
            $stmt->bind_param("s", $email);
            if ($stmt->execute()) {
                $stmt->store_result();
                if ($stmt->num_rows > 0) {
                    $error = "An admin with this email already exists.";
                    $debugLog[] = "Email exists, num_rows={$stmt->num_rows}";
                } else {
                    $debugLog[] = "Email not in use; proceeding to insert";

                    // IMPORTANT: adjust column name if your table uses 'password' instead of 'password_hash'
                    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

                    $stmt->close();
                    $stmt = $mysqli->prepare("
                        INSERT INTO admin_users (name, email, password_hash)
                        VALUES (?, ?, ?)
                    ");
                    if (!$stmt) {
                        $error = "Failed to prepare insert: " . $mysqli->error;
                        $debugLog[] = "ERROR: " . $error;
                    } else {
                        $stmt->bind_param("sss", $name, $email, $passwordHash);
                        if ($stmt->execute()) {
                            $message = "Admin user '{$name}' created successfully.";
                            $debugLog[] = "Insert OK, new id={$stmt->insert_id}";
                            // Clear form fields on success
                            $name  = '';
                            $email = '';
                        } else {
                            $error = "Failed to create admin: " . $stmt->error;
                            $debugLog[] = "ERROR executing insert: " . $stmt->error;
                        }
                        $stmt->close();
                    }
                }
            } else {
                $error = "Failed to check existing admin: " . $stmt->error;
                $debugLog[] = "ERROR executing email check: " . $stmt->error;
            }
        }
    }
}

// --- Fetch all admins for listing ---
$debugLog[] = "Fetching all admin_users";

$admins = [];
$result = $mysqli->query("SELECT id, name, email FROM admin_users ORDER BY id DESC");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $admins[] = $row;
    }
    $debugLog[] = "Loaded " . count($admins) . " admin(s)";
} else {
    $error = $error ?: "Failed to fetch admins: " . $mysqli->error;
    $debugLog[] = "ERROR fetching admins: " . $mysqli->error;
}

// --- Extra DB info / diagnostics ---
$dbRes = $mysqli->query("SELECT DATABASE() AS dbname");
$dbName = $dbRes ? ($dbRes->fetch_assoc()['dbname'] ?? 'UNKNOWN') : 'UNKNOWN';
$debugLog[] = "Using Database: {$dbName}";

$tableCheck = $mysqli->query("SHOW TABLES LIKE 'admin_users'");
$hasTable   = $tableCheck && $tableCheck->num_rows > 0;
$debugLog[] = "admin_users table exists: " . ($hasTable ? "YES" : "NO");

$tableStructure = [];
if ($hasTable) {
    $desc = $mysqli->query("DESCRIBE admin_users");
    if ($desc) {
        while ($c = $desc->fetch_assoc()) {
            $tableStructure[] = $c;
        }
    }
}

// --- Output debug block (always on; comment out if you want) ---
echo "<pre style='background:#111;color:#0f0;padding:10px;border:1px solid #333;margin-bottom:10px;'>";
echo "=== DEBUG: Manage Admin Users ===\n\n";
echo "DB Host Info: {$mysqli->host_info}\n";
echo "Database: {$dbName}\n";
echo "admin_users table exists: " . ($hasTable ? "YES" : "NO") . "\n\n";

echo "Table Structure (admin_users):\n";
if ($tableStructure) {
    foreach ($tableStructure as $c) {
        echo "- {$c['Field']} ({$c['Type']})\n";
    }
} else {
    echo "- [No DESCRIBE data / table missing]\n";
}

echo "\nSteps Log:\n";
foreach ($debugLog as $line) {
    echo "* {$line}\n";
}

echo "\n\$_GET:\n";
print_r($_GET);

echo "\n\$_POST:\n";
print_r($_POST);

echo "\nLoaded Admins:\n";
print_r($admins);

echo "</pre>";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>BlinkHub Admin – Manage Admin Users</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../css/admin.css">
    <style>
        body {
            background: #050505;
            color: #f5f5f5;
            font-family: system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif;
        }
        .admin-shell {
            max-width: 960px;
            margin: 40px auto;
            padding: 20px;
        }
        h1 {
            font-size: 24px;
            margin-bottom: 12px;
        }
        .subtle {
            color: #aaa;
            font-size: 13px;
            margin-bottom: 18px;
        }
        .alert {
            padding: 8px 12px;
            border-radius: 6px;
            margin-bottom: 12px;
            font-size: 14px;
        }
        .alert.error {
            background: rgba(255, 80, 80, 0.1);
            border: 1px solid rgba(255, 80, 80, 0.6);
            color: #ffb3b3;
        }
        .alert.success {
            background: rgba(80, 200, 120, 0.1);
            border: 1px solid rgba(80, 200, 120, 0.6);
            color: #b3ffd2;
        }

        .admin-grid {
            display: grid;
            grid-template-columns: minmax(0, 1.2fr) minmax(0, 1.2fr);
            gap: 20px;
            align-items: flex-start;
        }
        @media (max-width: 800px) {
            .admin-grid {
                grid-template-columns: 1fr;
            }
        }

        .card {
            background: #101010;
            border-radius: 12px;
            border: 1px solid #272727;
            padding: 16px 18px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.7);
        }
        .card h2 {
            font-size: 18px;
            margin-bottom: 8px;
        }
        .card p {
            font-size: 13px;
            color: #b5b5b5;
            margin-bottom: 12px;
        }

        .admin-form {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-top: 4px;
        }
        .admin-form label {
            font-size: 13px;
            margin-bottom: 2px;
        }
        .admin-form input {
            width: 100%;
            border-radius: 999px;
            border: 1px solid #303030;
            background: #050505;
            color: #f5f5f5;
            padding: 7px 12px;
            font-size: 14px;
        }
        .admin-form input:focus {
            outline: none;
            border-color: #ffcc00;
            box-shadow: 0 0 0 1px rgba(255, 204, 0, 0.6);
        }

        .btn-primary {
            margin-top: 6px;
            border-radius: 999px;
            padding: 8px 14px;
            border: none;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            background: linear-gradient(135deg, #ff9900, #ffcc00);
            color: #000;
        }
        .btn-primary:hover {
            filter: brightness(1.05);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        th, td {
            padding: 6px 8px;
            border-bottom: 1px solid #262626;
            text-align: left;
        }
        th {
            font-weight: 500;
            color: #b5b5b5;
            text-transform: uppercase;
            font-size: 11px;
        }
        .badge-id {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 24px;
            padding: 2px 6px;
            border-radius: 999px;
            background: #181818;
            border: 1px solid #333;
            font-size: 11px;
            color: #ffcc00;
        }
        .delete-link {
            color: #ff8080;
            text-decoration: none;
            font-size: 12px;
        }
        .delete-link:hover {
            text-decoration: underline;
        }
        .email-cell {
            font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
            font-size: 12px;
        }
    </style>
</head>
<body>
<div class="admin-shell">
    <h1>Manage Admin Users</h1>
    <div class="subtle">
        Local utility page to create or delete admin accounts in <code>admin_users</code>.
    </div>

    <?php if ($error): ?>
        <div class="alert error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($message): ?>
        <div class="alert success"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>

    <div class="admin-grid">
        <!-- Add admin form -->
        <div class="card">
            <h2>Add New Admin</h2>
            <p>Set name, email and password. Password will be stored as a secure hash.</p>

            <form method="post" class="admin-form">
                <div>
                    <label for="name">Name</label>
                    <input
                        type="text"
                        id="name"
                        name="name"
                        required
                        value="<?= htmlspecialchars($name) ?>"
                        placeholder="Super Admin"
                    >
                </div>

                <div>
                    <label for="email">Email</label>
                    <input
                        type="email"
                        id="email"
                        name="email"
                        required
                        value="<?= htmlspecialchars($email) ?>"
                        placeholder="admin@blinkhub.com"
                    >
                </div>

                <div>
                    <label for="password">Password</label>
                    <input
                        type="text"
                        id="password"
                        name="password"
                        required
                        placeholder="Choose a password"
                    >
                </div>

                <button class="btn-primary" type="submit">
                    Create Admin
                </button>
            </form>
        </div>

        <!-- Existing admins -->
        <div class="card">
            <h2>Existing Admins</h2>
            <p>Click delete to remove an admin record by ID.</p>

            <?php if (!$admins): ?>
                <p style="margin-top:8px;">No admin users found.</p>
            <?php else: ?>
                <table>
                    <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Action</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($admins as $adm): ?>
                        <tr>
                            <td><span class="badge-id"><?= (int)$adm['id'] ?></span></td>
                            <td><?= htmlspecialchars($adm['name']) ?></td>
                            <td class="email-cell"><?= htmlspecialchars($adm['email']) ?></td>
                            <td>
                                <a class="delete-link"
                                   href="?delete=<?= (int)$adm['id'] ?>"
                                   onclick="return confirm('Delete this admin?');">
                                    Delete
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
