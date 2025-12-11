<?php
// config/db.php

$DB_HOST = '127.0.0.1';
$DB_USER = 'root';
$DB_PASS = '12345678';
$DB_NAME = 'blinkit_clone';

$mysqli = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);

if ($mysqli->connect_errno) {
    http_response_code(500);
    echo "Database connection failed: " . htmlspecialchars($mysqli->connect_error);
    exit;
}

$mysqli->set_charset('utf8mb4');
