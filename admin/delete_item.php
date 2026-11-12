<?php
session_start();
require_once '../includes/db.php';
require_once __DIR__ . '/notifications.php';

// Auth check
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM menu_items WHERE id = ?");
        if ($stmt->execute([$id]) && $stmt->rowCount() > 0) {
            set_admin_flash('Menu item deleted successfully.', 'Item deleted');
        }
    } catch (PDOException $e) {
        error_log('Unable to delete menu item: ' . $e->getMessage());
    }
}

header("Location: index.php");
exit;
?>