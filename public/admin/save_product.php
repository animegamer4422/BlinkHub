<?php
require_once __DIR__ . '/../../src/auth_admin.php';
require_once __DIR__ . '/../../config/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: products.php');
    exit;
}

$id          = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$name        = trim($_POST['name'] ?? '');
$category_id = (int)($_POST['category_id'] ?? 0);
$price       = (int)($_POST['price'] ?? 0);
$mrp         = (int)($_POST['mrp'] ?? 0);
$tag         = trim($_POST['tag'] ?? '');
$eta         = (int)($_POST['eta_minutes'] ?? 10);
$tags        = trim($_POST['tags'] ?? '');
$is_active   = isset($_POST['is_active']) ? 1 : 0;

if ($name === '' || $category_id <= 0 || $price <= 0 || $mrp <= 0) {
    // keep it simple: just go back to products list
    header('Location: products.php');
    exit;
}

if ($id > 0) {
    // update
    $stmt = $mysqli->prepare("
        UPDATE products
        SET name = ?, category_id = ?, price = ?, mrp = ?, tag = ?, eta_minutes = ?, tags = ?, is_active = ?
        WHERE id = ?
        LIMIT 1
    ");
    $stmt->bind_param(
        'siiisissi',
        $name,
        $category_id,
        $price,
        $mrp,
        $tag,
        $eta,
        $tags,
        $is_active,
        $id
    );
    $stmt->execute();
    $stmt->close();
} else {
    // insert
    $stmt = $mysqli->prepare("
        INSERT INTO products (name, category_id, price, mrp, tag, eta_minutes, tags, is_active)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        'siiisisi',
        $name,
        $category_id,
        $price,
        $mrp,
        $tag,
        $eta,
        $tags,
        $is_active
    );
    $stmt->execute();
    $stmt->close();
}

header('Location: products.php');
exit;
