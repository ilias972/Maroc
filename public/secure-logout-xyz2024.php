<?php
require_once '../includes/config.php';
require_once '../includes/database.php';
require_once '../includes/auth.php';

$database = new Database();
$conn = $database->connect();
$auth = new Auth($conn);

$auth->logout();

$conn->close();

header('Location: admin_login.php');
exit;
?>