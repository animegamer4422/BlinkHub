<?php
// src/auth_admin.php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// If no admin session, boot to admin login
if (empty($_SESSION['admin_id'])) {
    header('Location: /admin/login.php');
    exit;
}
