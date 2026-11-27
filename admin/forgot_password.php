<?php
session_start();
require_once '../includes/db.php';

if (isset($_SESSION['admin_logged_in'])) {
    header('Location: ' . (!empty($_SESSION['admin_must_change_password']) ? 'change_password.php' : 'dashboard.php'));
    exit;
}

$super_admin_query = $pdo->query("SELECT phone FROM users WHERE role = 'Super Admin' ORDER BY id LIMIT 1");
$super_admin_phone = $super_admin_query->fetchColumn() ?: '';
$super_admin_email = get_app_setting('super_admin_email');
if (!filter_var($super_admin_email, FILTER_VALIDATE_EMAIL)) {
    $super_admin_email = '';
}
$safe_phone_link = preg_replace('/[^0-9+]/', '', $super_admin_phone);
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
            <p class="text-sm leading-6 text-gray-600">
                Password resets are handled by the Super Admin. Contact them to receive a temporary password.
                Sign in with your registered phone number and that password; you will be asked to choose a new password before continuing.
            </p>

            <section class="mt-6 rounded-2xl border border-emerald-100 bg-emerald-50 p-5" aria-labelledby="super-admin-contact">
                <h2 id="super-admin-contact" class="font-bold text-emerald-950">Contact the Super Admin</h2>
                <?php if ($super_admin_phone !== '' || $super_admin_email !== ''): ?>
                    <div class="mt-3 space-y-3 text-sm">
                        <?php if ($super_admin_phone !== ''): ?>
                            <p>
                                <span class="font-semibold text-gray-600">Phone: </span>
                                <a href="tel:<?= htmlspecialchars($safe_phone_link, ENT_QUOTES, 'UTF-8') ?>" class="font-bold text-emerald-900 underline underline-offset-2">
                                    <?= htmlspecialchars($super_admin_phone, ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            </p>
                        <?php endif; ?>
                        <?php if ($super_admin_email !== ''): ?>
                            <p>
                                <span class="font-semibold text-gray-600">Email: </span>
                                <a href="mailto:<?= htmlspecialchars($super_admin_email, ENT_QUOTES, 'UTF-8') ?>" class="break-all font-bold text-emerald-900 underline underline-offset-2">
                                    <?= htmlspecialchars($super_admin_email, ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            </p>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <p class="mt-2 text-sm leading-6 text-gray-600">No Super Admin phone number or email is currently configured. Please contact the system administrator directly.</p>
                <?php endif; ?>
            </section>

            <a href="login.php" class="mt-6 flex w-full items-center justify-center rounded-xl bg-emerald-800 px-4 py-3 font-bold text-white transition hover:bg-emerald-900 focus:outline-none focus:ring-4 focus:ring-emerald-700/20">
                Return to sign in
            </a>
        </div>
    </main>
</body>
</html>
