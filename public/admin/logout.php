<?php
// public/admin/logout.php
session_start();

// Only clear admin-related keys so customer login can stay if you want
unset($_SESSION['admin_id'], $_SESSION['admin_name'], $_SESSION['admin_email']);

header('Location: login.php');
exit;
