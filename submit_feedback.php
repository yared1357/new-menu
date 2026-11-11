<?php
require_once 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stars = $_POST['stars'];
    $comment = $_POST['comment'];
    $phone = $_POST['phone'];
    $email = $_POST['email'] ?: null;

    // Support redirect back to the originating page (index.php or menu.php)
    $allowed_redirects = ['index.php', 'menu.php'];
    $redirect = isset($_POST['redirect']) && in_array($_POST['redirect'], $allowed_redirects)
        ? $_POST['redirect']
        : 'index.php';

    try {
        $stmt = $pdo->prepare("INSERT INTO feedback (stars, comment, phone, email) VALUES (?, ?, ?, ?)");
        $stmt->execute([$stars, $comment, $phone, $email]);
        header("Location: {$redirect}?status=success");
    } catch (PDOException $e) {
        header("Location: {$redirect}?status=error");
    }
} else {
    header("Location: index.php");
}
exit;
