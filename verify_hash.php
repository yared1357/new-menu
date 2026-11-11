<?php
$password = 'password';
$hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';
if (password_verify($password, $hash)) {
    echo "VERIFY SUCCESS: 'password' matches the hash.\n";
} else {
    echo "VERIFY FAILED: 'password' does not match the hash.\n";
}
