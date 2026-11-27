<?php
session_start();
require_once '../includes/db.php';

if (isset($_SESSION['admin_logged_in'])) {
    header('Location: ' . (!empty($_SESSION['admin_must_change_password']) ? 'change_password.php' : 'dashboard.php'));
    exit;
}

$super_admin_query = $pdo->query(
    "SELECT full_name, username, phone
     FROM users
     WHERE role = 'Super Admin'
     ORDER BY id"
);
$super_admins = $super_admin_query->fetchAll();
$super_admin_email = get_app_setting('super_admin_email');
if (!filter_var($super_admin_email, FILTER_VALIDATE_EMAIL)) {
    $super_admin_email = '';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password | <?= htmlspecialchars(get_app_setting('brand_name', 'Menu'), ENT_QUOTES, 'UTF-8') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>body{font-family:'Outfit',sans-serif}</style>
</head>
<body class="flex min-h-screen items-center justify-center bg-[#f4f7f4] px-4 py-8 text-[#20332b]">
    <main class="w-full max-w-md overflow-hidden rounded-3xl border border-white bg-white shadow-2xl shadow-emerald-950/10">
        <div class="bg-gradient-to-br from-[#1f322e] via-[#17503d] to-[#28704f] p-6 text-white sm:p-8">
            <a href="login.php" class="text-xs font-semibold text-emerald-100 hover:text-white">&larr; Back to sign in</a>
            <h1 class="mt-7 text-3xl font-bold tracking-tight">Forgot password?</h1>
        </div>
        <div class="p-6 sm:p-8">
            <section class="mt-6 rounded-2xl border border-emerald-100 bg-emerald-50 p-5" aria-labelledby="super-admin-contact">
                <h2 id="super-admin-contact" class="font-bold text-emerald-950">Contact a super admin to reset your password.</h2>
                <?php if (!empty($super_admins)): ?>
                    <div class="mt-3 space-y-3 text-sm">
                        <?php foreach ($super_admins as $super_admin): ?>
                            <?php
                            $super_admin_name = trim($super_admin['full_name'] ?: $super_admin['username']);
                            $super_admin_phone = trim($super_admin['phone'] ?? '');
                            $safe_phone_link = preg_replace('/[^0-9+]/', '', $super_admin_phone);
                            ?>
                            <div>
                                <p class="font-bold text-emerald-950"><?= htmlspecialchars($super_admin_name, ENT_QUOTES, 'UTF-8') ?></p>
                                <?php if ($super_admin_phone !== ''): ?>
                                    <p class="mt-1">
                                        <span class="font-semibold text-gray-600">Phone: </span>
                                        <a href="tel:<?= htmlspecialchars($safe_phone_link, ENT_QUOTES, 'UTF-8') ?>" class="font-bold text-emerald-900 underline underline-offset-2">
                                            <?= htmlspecialchars($super_admin_phone, ENT_QUOTES, 'UTF-8') ?>
                                        </a>
                                    </p>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="mt-2 text-sm leading-6 text-gray-600">No Super Admin is currently configured. Please contact the system administrator directly.</p>
                <?php endif; ?>
                <?php if ($super_admin_email !== ''): ?>
                    <p class="mt-4 border-t border-emerald-100 pt-3 text-sm">
                        <span class="font-semibold text-gray-600">Email: </span>
                        <a href="mailto:<?= htmlspecialchars($super_admin_email, ENT_QUOTES, 'UTF-8') ?>" class="break-all font-bold text-emerald-900 underline underline-offset-2">
                            <?= htmlspecialchars($super_admin_email, ENT_QUOTES, 'UTF-8') ?>
                        </a>
                    </p>
                <?php endif; ?>
            </section>

            <a href="login.php" class="mt-6 flex w-full items-center justify-center rounded-xl bg-emerald-800 px-4 py-3 font-bold text-white transition hover:bg-emerald-900 focus:outline-none focus:ring-4 focus:ring-emerald-700/20">
                Return to sign in
            </a>
        </div>
    </main>
</body>
</html>
