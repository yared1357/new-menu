<?php
try {
    $pdo = new PDO("mysql:host=localhost;dbname=pizzaburgar", "root", "");
    echo "SUCCESS: Connected to MySQL database 'pizzaburgar'\n";
    $stmt = $pdo->query("SELECT id, username, role FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($users as $user) {
        echo "ID: {$user['id']} | User: {$user['username']} | Role: {$user['role']}\n";
    }
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
