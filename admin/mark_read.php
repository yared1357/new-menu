<?php
session_start();
require_once '../includes/db.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("UPDATE feedback SET is_read = 1 WHERE id = ?");
    $stmt->execute([$id]);
}

header('Location: feedback.php');
exit;
