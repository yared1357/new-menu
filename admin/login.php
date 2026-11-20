<?php
session_start();
require_once '../includes/db.php';

// Check if user is already logged in
if (isset($_SESSION['admin_logged_in'])) {
    header('Location: ' . (!empty($_SESSION['admin_must_change_password']) ? 'change_password.php' : 'dashboard.php'));
    exit;
}

$error = '';
$brand_name = get_app_setting('brand_name', 'Tasty Pizza & Burger');
$brand_logo = get_app_setting('brand_logo');
$brand_initial = strtoupper(mb_substr(trim($brand_name), 0, 1, 'UTF-8') ?: 'A');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone = normalize_ethiopian_phone($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare('SELECT * FROM users WHERE phone = ?');
    $stmt->execute([$phone]);
    $matched_users = $phone === null ? [] : $stmt->fetchAll();
    $user = count($matched_users) === 1 ? $matched_users[0] : null;

    // Verify password (allowing for trailing spaces if manually added via DB manager)
    $plain_match = $user ? (trim($password) === trim($user['password'])) : false;

    if ($user && (password_verify($password, $user['password']) || $plain_match)) {

        // If the password matched as plain text (manually added admin), hash it securely for future logins
        if ($plain_match) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hashed_password, $user['id']]);
        }

        $_SESSION['admin_logged_in'] = true;
        $_SESSION['admin_id'] = $user['id'];
        $_SESSION['admin_user'] = $user['full_name'] ?: $user['username'];
        $_SESSION['admin_role'] = $user['role'] ?? 'Admin';
        $_SESSION['admin_must_change_password'] = !empty($user['must_change_password']);
        header('Location: ' . ($_SESSION['admin_must_change_password'] ? 'change_password.php' : 'dashboard.php'));
        exit;
    } else {
        $error = 'Invalid phone number or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | <?= htmlspecialchars(get_app_setting('brand_name', 'Tasty Pizza & Burger')) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="../assets/js/alpine.min.js"></script>
        <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
        <style>
            body {
                font-family: 'Outfit', sans-serif;
                background: #f7f7f2;
            }

            [x-cloak] {
                display: none !important;
            }
        </style>
</head>

<body class="relative flex min-h-screen flex-col overflow-x-hidden px-4 py-6 sm:px-6 sm:py-8" x-data="{ showPassword: false }">
        <div class="pointer-events-none absolute -left-24 -top-24 h-72 w-72 rounded-full bg-emerald-200/50 blur-3xl"></div>
        <div class="pointer-events-none absolute -bottom-28 -right-20 h-80 w-80 rounded-full bg-orange-200/60 blur-3xl"></div>

        <header class="relative z-10 mx-auto flex w-full max-w-6xl items-center justify-between">
            <a href="../index.php" class="group inline-flex items-center gap-3" aria-label="<?= htmlspecialchars($brand_name) ?> home">
                <?php if ($brand_logo !== ''): ?>
                    <span class="flex h-9 w-fit max-w-[34vw] shrink-0 items-center justify-center rounded-xl border border-white/50 bg-white p-1 shadow-xl shadow-black/20 transition-transform duration-300 group-hover:scale-105 sm:h-10 sm:max-w-[30vw] md:h-12 md:max-w-[9rem]">
                        <img src="../<?= htmlspecialchars($brand_logo) ?>" alt="<?= htmlspecialchars($brand_name) ?> logo" class="block h-full w-auto max-w-full object-contain">
                    </span>
                <?php else: ?>
                    <span class="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-orange-400 to-orange-600 text-xl font-black text-white shadow-lg shadow-orange-600/20 transition-transform group-hover:-rotate-3 group-hover:scale-105"><?= htmlspecialchars($brand_initial) ?></span>
                <?php endif; ?>
                <span class="leading-tight">
                    <span class="block text-sm font-black uppercase tracking-wide text-[#20332b]"><?= htmlspecialchars($brand_name) ?></span>
                    <span class="mt-0.5 block text-[10px] font-bold uppercase tracking-[0.2em] text-emerald-800">Management portal</span>
                </span>
            </a>
            <a href="../index.php" class="rounded-full border border-gray-200 bg-white/70 px-4 py-2 text-xs font-bold text-gray-600 transition-colors hover:border-emerald-700/30 hover:text-emerald-800">Back to Home</a>
        </header>

        <main class="relative z-10 mx-auto flex w-full max-w-6xl flex-1 items-center py-8 sm:py-12">
            <section class="grid w-full overflow-hidden rounded-[2rem] border border-white/80 bg-white/85 shadow-[0_30px_90px_rgba(27,52,43,0.14)] backdrop-blur-xl lg:grid-cols-[1.05fr_0.95fr]">
                <div class="relative flex min-h-56 flex-col justify-between overflow-hidden bg-gradient-to-br from-[#1f322e] via-[#17503d] to-[#3a2314] p-7 text-white sm:p-10 lg:min-h-[590px] lg:p-14">
                    <div class="pointer-events-none absolute -right-20 -top-20 h-64 w-64 rounded-full border-[40px] border-white/5"></div>
                    <div class="pointer-events-none absolute -bottom-28 -left-20 h-72 w-72 rounded-full bg-orange-400/15 blur-2xl"></div>
                    <div class="relative flex flex-1 flex-col items-center justify-center text-center">
                        <span class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/10 px-3 py-1.5 text-[10px] font-bold uppercase tracking-[0.2em] text-orange-100">
                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-300"></span>
                            Admin Portal
                        </span>
                        <h1 class="mt-6 max-w-lg text-3xl font-semibold leading-tight tracking-tight sm:text-4xl lg:text-5xl">Welcome to ሚራክል ሆቴል Admin Portal</h1>
                    </div>
                </div>

                <div class="flex items-center p-7 sm:p-10 lg:p-14">
                    <div class="mx-auto w-full max-w-md">
                        <div class="mb-8 text-center">
                            <p class="text-xs font-bold uppercase tracking-[0.2em] text-emerald-800">Welcome back</p>
                            <h2 class="mt-2 text-3xl font-bold tracking-tight text-[#20332b]">Admin sign in</h2>
                            </div>

                        <?php if ($error): ?>
                            <div role="alert" class="mb-6 flex items-start gap-3 rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-sm font-semibold text-red-700">
                                <svg class="mt-0.5 h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v4m0 4h.01M10.3 3.9 1.8 18.5A1.7 1.7 0 0 0 3.3 21h17.4a1.7 1.7 0 0 0 1.5-2.5L13.7 3.9a2 2 0 0 0-3.4 0Z" />
                                </svg>
                                <span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="space-y-5">
                            <div>
                                <label for="phone" class="mb-2 block text-sm font-semibold text-gray-700">Phone number</label>
                                <input id="phone" type="tel" name="phone" required inputmode="tel" autocomplete="tel"
                                    class="w-full rounded-2xl border border-gray-200 bg-[#f8faf8] px-5 py-4 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-emerald-700 focus:bg-white focus:ring-4 focus:ring-emerald-700/10"
                                    placeholder="Enter your phone number">
                            </div>
                            <div>
                                <label for="password" class="mb-2 block text-sm font-semibold text-gray-700">Password</label>
                                <div class="relative">
                                    <input id="password" :type="showPassword ? 'text' : 'password'" name="password" required autocomplete="current-password"
                                        class="w-full rounded-2xl border border-gray-200 bg-[#f8faf8] px-5 py-4 pr-14 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-emerald-700 focus:bg-white focus:ring-4 focus:ring-emerald-700/10"
                                        placeholder="Enter your password">
                                    <button type="button" @click="showPassword = !showPassword" :aria-label="showPassword ? 'Hide password' : 'Show password'"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 rounded-xl p-2 text-gray-400 transition-colors hover:bg-gray-100 hover:text-emerald-800 focus:outline-none focus:ring-2 focus:ring-emerald-700/30">
                                        <svg x-show="!showPassword" class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M2.5 12s3.4-6.5 9.5-6.5 9.5 6.5 9.5 6.5-3.4 6.5-9.5 6.5S2.5 12 2.5 12Z" />
                                            <circle cx="12" cy="12" r="3" stroke-width="1.8" />
                                        </svg>
                                        <svg x-show="showPassword" x-cloak class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m3 3 18 18M10.6 10.6a2 2 0 0 0 2.8 2.8M9.9 5.7A10.8 10.8 0 0 1 12 5.5c6.1 0 9.5 6.5 9.5 6.5a16.5 16.5 0 0 1-3.2 4.1M6.2 6.2C3.8 7.8 2.5 12 2.5 12S5.9 18.5 12 18.5a10 10 0 0 0 3-.5" />
                                        </svg>
                                    </button>
                                </div>
                            </div>
                            <button type="submit" class="group flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-emerald-800 to-[#246447] py-4 font-bold text-white shadow-lg shadow-emerald-900/20 transition-all hover:-translate-y-0.5 hover:shadow-xl hover:shadow-emerald-900/25 focus:outline-none focus:ring-4 focus:ring-emerald-700/20">
                                Sign in to dashboard
                                <svg class="h-4 w-4 transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14m-6-6 6 6-6 6" />
                                </svg>
                            </button>
                        </form>
                        <div class="mt-5 text-right">
                            <a href="forgot_password.php" class="text-sm font-semibold text-emerald-800 transition-colors hover:text-orange-600">Forgot password?</a>
                        </div>
                         </div>
                </div>
            </section>
        </main>

        <footer class="relative z-10 mx-auto flex w-full max-w-6xl items-center justify-between gap-2 border-t border-gray-200/80 py-5 text-left text-[10px] text-gray-500 sm:text-xs">
            <span class="min-w-0 truncate">&copy; <?= date('Y') ?> <?= htmlspecialchars(get_app_setting('copyright_name', 'Tasty Pizza & Burger')) ?></span>
            <a href="../index.php" class="shrink-0 whitespace-nowrap font-semibold text-emerald-800 transition-colors hover:text-orange-600">Back to the menu</a>
        </footer>
</body>

</html>