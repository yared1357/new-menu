<?php
session_start();
require_once '../includes/db.php';
require_once __DIR__ . '/notifications.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("DELETE FROM feedback WHERE id = ?");
    $stmt->execute([$id]);
    if ($stmt->rowCount() > 0) {
        set_admin_flash('Feedback deleted successfully.', 'Feedback deleted');
    }
}

header('Location: feedback.php');
exit;
