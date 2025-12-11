<?php
// public/api/save_address.php

session_start();

header('Content-Type: application/json');

// include DB
require_once __DIR__ . '/../../config/db.php';

if (!isset($_SESSION['user']['id'])) {
    echo json_encode([
        'ok'    => false,
        'error' => 'You must be logged in to save an address.'
    ]);
    exit;
}

$userId = (int) $_SESSION['user']['id'];

$label    = trim($_POST['label']    ?? 'Home');
$line1    = trim($_POST['line1']    ?? '');
$line2    = trim($_POST['line2']    ?? '');
$landmark = trim($_POST['landmark'] ?? '');
$city     = trim($_POST['city']     ?? '');
$pincode  = trim($_POST['pincode']  ?? '');
$phone    = trim($_POST['phone']    ?? '');

if ($line1 === '' || $city === '' || $pincode === '') {
    echo json_encode([
        'ok'    => false,
        'error' => 'Flat, city and pincode are required.'
    ]);
    exit;
}

$mysqli->begin_transaction();

try {
    // make this one default, clear others
    $clear = $mysqli->prepare("UPDATE addresses SET is_default = 0 WHERE user_id = ?");
    $clear->bind_param('i', $userId);
    $clear->execute();
    $clear->close();

    $stmt = $mysqli->prepare("
        INSERT INTO addresses
            (user_id, label, line1, line2, landmark, city, pincode, phone, is_default)
        VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, 1)
    ");

    $stmt->bind_param(
        'isssssss',
        $userId,
        $label,
        $line1,
        $line2,
        $landmark,
        $city,
        $pincode,
        $phone
    );

    $stmt->execute();
    $stmt->close();

    $mysqli->commit();

    $short = $line1 . ', ' . $city;

    echo json_encode([
        'ok'    => true,
        'short' => $short
    ]);
} catch (Throwable $e) {
    $mysqli->rollback();

    echo json_encode([
        'ok'    => false,
        'error' => 'DB error: ' . $e->getMessage()
    ]);
}
