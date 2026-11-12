<?php
function set_admin_flash(string $message, string $title = 'Success'): void
{
    $_SESSION['admin_flash'] = [
        'title' => $title,
        'message' => $message,
    ];
}
