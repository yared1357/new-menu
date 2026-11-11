<?php
$pdo = new PDO('sqlite:c:/xamppNew/htdocs/TastyPizzaBurger/pizzaburgar.db');
$stmt = $pdo->query('SELECT id, username, role, password FROM users');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: " . $row['id'] . " | Username: " . $row['username'] . " | Role: " . $row['role'] . " | Password Length: " . strlen($row['password']) . "\n";
}
