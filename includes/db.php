<?php
// Database configuration
$db_host = 'localhost';
$db_name = 'menu';
$db_user = 'root';
$db_pass = '';
$db_file = __DIR__ . '/../pizzaburgar.db';

if (!function_exists('normalize_ethiopian_phone')) {
    function normalize_ethiopian_phone(string $phone): ?string
    {
        $phone = preg_replace('/[\s().-]/', '', trim($phone));
        if (preg_match('/\A(?:\+?251|00251)0?([79][0-9]{8})\z/', $phone, $matches)) {
            return '+251' . $matches[1];
        }
        if (preg_match('/\A0?([79][0-9]{8})\z/', $phone, $matches)) {
            return '+251' . $matches[1];
        }
        return null;
    }
}

try {
    // Try MySQL first (standard for XAMPP environments)
    try {
        $pdo = new PDO("mysql:host=$db_host;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $is_sqlite = false;
    } catch (PDOException $e) {
        // Fallback to SQLite
        $pdo = new PDO("sqlite:" . $db_file);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $is_sqlite = true;
    }

    // Define table creation SQL based on driver
    $pk = $is_sqlite ? "INTEGER PRIMARY KEY AUTOINCREMENT" : "INT AUTO_INCREMENT PRIMARY KEY";
    $text = $is_sqlite ? "TEXT" : "VARCHAR(255)";
    $longtext = $is_sqlite ? "TEXT" : "TEXT";

    // Helper to add column if it doesn't exist (MySQL/SQLite)
    $addColumn = function ($table, $column, $definition) use ($pdo, $is_sqlite) {
        try {
            $exists = false;
            if ($is_sqlite) {
                $cols = $pdo->query("PRAGMA table_info($table)")->fetchAll();
                foreach ($cols as $c)
                    if ($c['name'] === $column)
                        $exists = true;
            } else {
                $cols = $pdo->query("SHOW COLUMNS FROM $table LIKE '$column'")->fetchAll();
                if (count($cols) > 0)
                    $exists = true;
            }
            if (!$exists) {
                $pdo->exec("ALTER TABLE $table ADD COLUMN $column $definition");
            }
        } catch (PDOException $e) { /* Ignore errors */
        }
    };

    // Create tables if they don't exist
    $pdo->exec("CREATE TABLE IF NOT EXISTS categories (
        id $pk,
        name VARCHAR(255) NOT NULL UNIQUE,
        is_active INTEGER DEFAULT 1
    )");
    $addColumn('categories', 'is_active', "INTEGER DEFAULT 1");

    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id $pk,
        username VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        role VARCHAR(50) DEFAULT 'Admin'
    )");
    $addColumn('users', 'role', "VARCHAR(50) DEFAULT 'Admin'");
    $addColumn('users', 'phone', "VARCHAR(30)");
    $addColumn('users', 'full_name', "VARCHAR(150)");
    $addColumn('users', 'must_change_password', "INTEGER DEFAULT 0");
    $pdo->exec("UPDATE users SET full_name = username WHERE full_name IS NULL OR TRIM(full_name) = ''");
    $pdo->prepare("UPDATE users SET full_name = 'Yared', phone = ? WHERE LOWER(TRIM(username)) = 'yared'")
        ->execute([normalize_ethiopian_phone('0926862255')]);

    foreach ($pdo->query("SELECT id, phone FROM users WHERE phone IS NOT NULL AND TRIM(phone) <> ''")->fetchAll() as $user_phone) {
        $normalized_phone = normalize_ethiopian_phone($user_phone['phone']);
        if ($normalized_phone !== null && $normalized_phone !== $user_phone['phone']) {
            $pdo->prepare('UPDATE users SET phone = ? WHERE id = ?')->execute([$normalized_phone, $user_phone['id']]);
        }
    }
    try {
        $pdo->exec('CREATE UNIQUE INDEX users_phone_unique ON users(phone)');
    } catch (PDOException $e) {
        // Keep startup working if legacy accounts still have duplicate phone numbers.
    }

    // Seed initial admin ONLY if the users table is completely empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    if ($stmt->fetchColumn() == 0) {
        $hashed_password = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)")->execute(['admin', $hashed_password, 'System Admin']);
    }

    $pdo->prepare("UPDATE users SET role = 'Super Admin' WHERE LOWER(TRIM(username)) = 'yared'")->execute();

    $pdo->exec("CREATE TABLE IF NOT EXISTS menu_items (
        id $pk,
        category_id INT,
        name VARCHAR(255) NOT NULL,
        description $longtext,
        price DECIMAL(10,2) NOT NULL,
        image_url $longtext,
        is_available INTEGER DEFAULT 1,
        is_fasting INTEGER DEFAULT 0
    )");
    $addColumn('menu_items', 'is_fasting', "INTEGER DEFAULT 0");
    $addColumn('menu_items', 'is_available', "INTEGER DEFAULT 1");

    // Seed initial categories if empty
    $stmt = $pdo->query("SELECT COUNT(*) FROM categories");
    if ($stmt->fetchColumn() == 0) {
        $categories = ['Pizza', 'Burgers', 'Drinks', 'Sides', 'Desserts'];
        $insert = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
        foreach ($categories as $cat) {
            $insert->execute([$cat]);
        }
    }

    // Feedback Table
    $pdo->exec("CREATE TABLE IF NOT EXISTS feedback (
        id $pk,
        stars INTEGER NOT NULL,
        comment $longtext NOT NULL,
        phone VARCHAR(20) NOT NULL,
        email VARCHAR(100),
        is_read INTEGER DEFAULT 0,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    )");
    $addColumn('feedback', 'is_read', "INTEGER DEFAULT 0");

    $pdo->exec("CREATE TABLE IF NOT EXISTS payment_accounts (
        id $pk,
        provider VARCHAR(120) NOT NULL,
        account_name VARCHAR(160) NOT NULL,
        account_number VARCHAR(120) NOT NULL,
        qr_image $longtext,
        is_active INTEGER DEFAULT 1
    )");

    $pdo->exec("CREATE TABLE IF NOT EXISTS app_settings (
        id $pk,
        setting_key VARCHAR(100) NOT NULL UNIQUE,
        setting_value $longtext
    )");

    $default_settings = [
        'brand_name' => 'Tasty Pizza & Burger',
        'brand_short_name' => 'Tasty Pizza',
        'brand_logo' => '',
        'brand_tagline' => 'Digital Menu',
        'phone_number' => '0926862255',
        'super_admin_email' => '',
        'copyright_name' => 'Tasty Pizza & Burger',
        'storefront_background_color' => '#f7f7f2',
        'storefront_header_color' => '#317D53',
        'storefront_footer_color' => '#1f322e',
        'storefront_action_color' => '#1f4d3e',
    ];
    $setting_insert = $pdo->prepare("INSERT INTO app_settings (setting_key, setting_value) VALUES (?, ?)");
    foreach ($default_settings as $key => $value) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM app_settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        if ((int) $stmt->fetchColumn() === 0) {
            $setting_insert->execute([$key, $value]);
        }
    }

    $pdo->exec("CREATE TABLE IF NOT EXISTS social_links (
        id $pk,
        platform VARCHAR(50) NOT NULL,
        name VARCHAR(100) NOT NULL,
        url $longtext NOT NULL,
        is_active INTEGER DEFAULT 1,
        sort_order INTEGER DEFAULT 0
    )");
    $addColumn('social_links', 'sort_order', "INTEGER DEFAULT 0");

    $stmt = $pdo->query("SELECT COUNT(*) FROM social_links");
    if ((int) $stmt->fetchColumn() === 0) {
        $seed_links = [
            ['facebook', 'Facebook', 'https://facebook.com/yourpage', 1, 10],
            ['instagram', 'Instagram', 'https://instagram.com/yourpage', 1, 20],
            ['tiktok', 'TikTok', 'https://tiktok.com/@yourprofile', 1, 30],
            ['telegram', 'Telegram', 'https://t.me/yourchannel', 1, 40],
        ];
        $insert = $pdo->prepare("INSERT INTO social_links (platform, name, url, is_active, sort_order) VALUES (?, ?, ?, ?, ?)");
        foreach ($seed_links as $link) {
            $insert->execute($link);
        }
    }

} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

if (!function_exists('get_app_setting')) {
    function get_app_setting(string $key, string $default = ''): string
    {
        global $pdo;
        static $settings = null;
        if ($settings === null) {
            $settings = [];
            $rows = $pdo->query("SELECT setting_key, setting_value FROM app_settings")->fetchAll(PDO::FETCH_KEY_PAIR);
            $settings = $rows ?: [];
        }
        return isset($settings[$key]) && $settings[$key] !== '' ? (string) $settings[$key] : $default;
    }
}

if (session_status() === PHP_SESSION_ACTIVE
    && !empty($_SESSION['admin_logged_in'])
    && !empty($_SESSION['admin_must_change_password'])
    && strpos(str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? ''), '/admin/') !== false
    && !in_array(basename($_SERVER['SCRIPT_NAME'] ?? ''), ['change_password.php', 'logout.php', 'login.php'], true)) {
    header('Location: change_password.php');
    exit;
}
?>