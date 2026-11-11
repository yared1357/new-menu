<?php
session_start();
require_once '../includes/db.php';

// Check if user is already logged in
if (isset($_SESSION['admin_logged_in'])) {
    header('Location: dashboard.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM users WHERE LOWER(username) = LOWER(?) OR LOWER(TRIM(username)) = LOWER(?)");
    $stmt->execute([$username, $username]);
    $user = $stmt->fetch();

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
        $_SESSION['admin_user'] = $user['username'];
        $_SESSION['admin_role'] = $user['role'] ?? 'Admin';
        header('Location: dashboard.php');
        exit;
    } else {
        $error = 'Invalid username or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Tasty Pizza & Burger</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="../assets/js/alpine.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #f3f4f6;
        }

        .glass-login {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(0, 0, 0, 0.05);
            box-shadow: 0 10px 40px rgba(0, 0, 0, 0.1);
        }
    </style>
</head>

<body class="min-h-screen flex items-center justify-center p-6" x-data="{ showPassword: false }">
    <div class="max-w-md w-full glass-login rounded-[2.5rem] p-10">
        <div class="text-center mb-10">
            <div
                class="w-16 h-16 bg-orange-500 rounded-2xl flex items-center justify-center mx-auto mb-6 shadow-lg shadow-orange-500/30">
                <span class="font-bold text-white text-2xl">T</span>
            </div>
            <h1 class="text-3xl font-bold text-gray-900">Admin Login</h1>
            <p class="text-gray-500 mt-2">Enter your credentials to manage the menu</p>
        </div>

        <?php if ($error): ?>
            <div
                class="mb-6 p-4 bg-red-50 border border-red-100 text-red-600 rounded-2xl text-center text-sm font-semibold">
                <?= $error ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-6">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Username</label>
                <input type="text" name="username" required
                    class="w-full px-5 py-4 rounded-2xl bg-gray-50 border border-gray-200 focus:outline-none focus:border-orange-500 transition-all"
                    placeholder="Enter username">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-2">Password</label>
                <div class="relative">
                    <input :type="showPassword ? 'text' : 'password'" name="password" required
                        class="w-full px-5 py-4 rounded-2xl bg-gray-50 border border-gray-200 focus:outline-none focus:border-orange-500 transition-all"
                        placeholder="••••••••">
                    <button type="button" @click="showPassword = !showPassword"
                        class="absolute right-4 top-1/2 -translate-y-1/2 text-gray-400 hover:text-orange-500 transition-colors">
                        <svg x-show="!showPassword" class="w-6 h-6" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z">
                            </path>
                        </svg>
                        <svg x-show="showPassword" class="w-6 h-6" fill="none" stroke="currentColor"
                            viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.542-7a9.97 9.97 0 011.563-3.076m1.406-1.407A10.015 10.015 0 0112 5c4.478 0 8.268 2.943 9.542 7a10.059 10.059 0 01-4.29 5.764M10.735 10.735L13.265 13.265M15 12a3 3 0 11-6 0 3 3 0 016 0zM3 3l18 18">
                            </path>
                        </svg>
                    </button>
                </div>
            </div>
            <button type="submit"
                class="w-full py-4 bg-orange-500 hover:bg-orange-600 text-white font-bold rounded-2xl transition-all shadow-lg shadow-orange-500/20 transform hover:-translate-y-1">
                Sign In
            </button>
        </form>

        <div class="mt-8 text-center text-gray-400 text-sm">
            <p class="mb-4 leading-relaxed">
                You can try adding a new row to the users table manually now (e.g., Username: <span
                    class="text-orange-500 font-semibold">superadmin</span>, Password: <span
                    class="text-orange-500 font-semibold">mycustompass</span>) and you will be able to log right in!
                Everything is ready for you to scale smoothly.
            </p>
            <a href="../index.php" class="hover:text-orange-500 transition-colors inline-block mt-2">← Back to
                website</a>
        </div>
    </div>
</body>

</html>