<?php
session_start();
require_once '../includes/db.php';

// Auth check
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM menu_items WHERE id = ?");
        $stmt->execute([$id]);
    } catch (PDOException $e) {
        // Handle error
    }
}

header("Location: index.php");
exit;
?>