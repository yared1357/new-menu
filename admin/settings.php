<?php
session_start();
require_once '../includes/db.php';
require_once __DIR__ . '/image_upload.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$current_admin_query = $pdo->prepare('SELECT username, role FROM users WHERE id = ?');
$current_admin_query->execute([$_SESSION['admin_id'] ?? 0]);
$current_admin = $current_admin_query->fetch();
if (!$current_admin
    || strtolower(trim($current_admin['username'])) !== 'yared'
    || $current_admin['role'] !== 'Super Admin') {
    http_response_code(403);
    exit('You do not have permission to access settings.');
}

$settings_flash = $_SESSION['settings_flash'] ?? null;
unset($_SESSION['settings_flash']);
$message = $settings_flash['message'] ?? '';
$success = (bool) ($settings_flash['success'] ?? false);
$brand_logo = get_app_setting('brand_logo');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'branding') {
    $current_logo = $brand_logo;
    $uploaded_logo = '';
    $branding = [
        'brand_name' => trim($_POST['brand_name'] ?? ''),
        'brand_short_name' => trim($_POST['brand_short_name'] ?? ''),
        'brand_tagline' => trim($_POST['brand_tagline'] ?? ''),
        'phone_number' => trim($_POST['phone_number'] ?? ''),
        'copyright_name' => trim($_POST['copyright_name'] ?? ''),
    ];

    if ($branding['brand_name'] === '' || $branding['brand_short_name'] === '') {
        $message = 'Business name and short name are required.';
    } else {
        try {
            if (isset($_FILES['brand_logo']) && ($_FILES['brand_logo']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                $uploaded_logo = store_menu_image($_FILES['brand_logo']);
            }
            $branding['brand_logo'] = $uploaded_logo !== ''
                ? $uploaded_logo
                : (!empty($_POST['remove_brand_logo']) ? '' : $current_logo);

            $pdo->beginTransaction();
            try {
                $stmt = $pdo->prepare('UPDATE app_settings SET setting_value = ? WHERE setting_key = ?');
                foreach ($branding as $key => $value) {
                    $stmt->execute([$value, $key]);
                    if ($stmt->rowCount() === 0) {
                        $exists = $pdo->prepare('SELECT 1 FROM app_settings WHERE setting_key = ?');
                        $exists->execute([$key]);
                        if (!$exists->fetchColumn()) {
                            $pdo->prepare('INSERT INTO app_settings (setting_key, setting_value) VALUES (?, ?)')
                                ->execute([$key, $value]);
                        }
                    }
                }
                $pdo->commit();
            } catch (PDOException $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                throw $e;
            }

            $brand_logo = $branding['brand_logo'];
            $message = 'Business branding updated successfully.';
            $success = true;
            if ($current_logo !== '' && $current_logo !== $brand_logo) {
                try {
                    remove_menu_image($current_logo);
                } catch (RuntimeException $e) {
                    $message = 'Branding updated, but the previous logo could not be removed.';
                }
            }
            $_SESSION['settings_flash'] = ['message' => $message, 'success' => $success];
            header('Location: settings.php');
            exit;
        } catch (RuntimeException $e) {
            if ($uploaded_logo !== '') {
                remove_menu_image($uploaded_logo);
            }
            $message = $e->getMessage();
        } catch (PDOException $e) {
            if ($uploaded_logo !== '') {
                remove_menu_image($uploaded_logo);
            }
            $message = 'Could not update branding settings.';
        }
    }
}

$page_title = 'Settings';
include 'header.php';
?>

<section class="flex flex-1 flex-col items-center py-8 sm:py-12">
    <div class="glass-card mb-8 w-full max-w-3xl rounded-[2rem] border border-white p-6 shadow-[0_20px_60px_rgba(27,52,43,0.10)] sm:p-9">
        <div class="mb-7 flex items-center gap-4 border-b border-gray-100 pb-6">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-emerald-700 to-[#153b34] text-white shadow-md">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 20V8l8-4 8 4v12M8 20v-7h8v7M3 20h18" />
                </svg>
            </div>
            <div>
                <h2 class="text-lg font-bold text-[#20332b]">Business branding</h2>
                 </div>
        </div>

        <?php if ($message !== ''): ?>
            <div role="<?= $success ? 'status' : 'alert' ?>"
                class="mb-6 rounded-2xl border p-4 text-center text-sm font-semibold <?= $success ? 'border-emerald-100 bg-emerald-50 text-emerald-700' : 'border-red-100 bg-red-50 text-red-700' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <input type="hidden" name="action" value="branding">
            <label class="text-sm font-semibold text-gray-700">
                Business name
                <input name="brand_name" required maxlength="150" value="<?= htmlspecialchars(get_app_setting('brand_name', 'Tasty Pizza & Burger')) ?>"
                    class="mt-2 w-full rounded-2xl border border-gray-200 bg-[#f8faf8] px-4 py-3.5 outline-none focus:border-emerald-700 focus:ring-4 focus:ring-emerald-700/10">
            </label>
            <label class="text-sm font-semibold text-gray-700">
                Header short name
                <input name="brand_short_name" required maxlength="80" value="<?= htmlspecialchars(get_app_setting('brand_short_name', 'Tasty Pizza')) ?>"
                    class="mt-2 w-full rounded-2xl border border-gray-200 bg-[#f8faf8] px-4 py-3.5 outline-none focus:border-emerald-700 focus:ring-4 focus:ring-emerald-700/10">
            </label>
            <label class="text-sm font-semibold text-gray-700">
                Tagline
                <input name="brand_tagline" maxlength="100" value="<?= htmlspecialchars(get_app_setting('brand_tagline', 'Digital Menu')) ?>"
                    class="mt-2 w-full rounded-2xl border border-gray-200 bg-[#f8faf8] px-4 py-3.5 outline-none focus:border-emerald-700 focus:ring-4 focus:ring-emerald-700/10">
            </label>
            <label class="text-sm font-semibold text-gray-700">
                Phone number
                <input name="phone_number" maxlength="30" value="<?= htmlspecialchars(get_app_setting('phone_number', '0926862255')) ?>"
                    class="mt-2 w-full rounded-2xl border border-gray-200 bg-[#f8faf8] px-4 py-3.5 outline-none focus:border-emerald-700 focus:ring-4 focus:ring-emerald-700/10">
            </label>
            <label class="text-sm font-semibold text-gray-700 md:col-span-2">
                Copyright name
                <input name="copyright_name" maxlength="150" value="<?= htmlspecialchars(get_app_setting('copyright_name', 'Tasty Pizza & Burger')) ?>"
                    class="mt-2 w-full rounded-2xl border border-gray-200 bg-[#f8faf8] px-4 py-3.5 outline-none focus:border-emerald-700 focus:ring-4 focus:ring-emerald-700/10">
            </label>
            <div class="md:col-span-2">
                <label for="brand_logo" class="block text-sm font-semibold text-gray-700">Header logo</label>
                <?php if ($brand_logo !== ''): ?>
                    <div class="mt-3 flex items-center gap-4">
                        <img src="../<?= htmlspecialchars($brand_logo) ?>" alt="Current header logo" class="h-20 w-auto max-w-[12rem] rounded-xl border border-gray-200 bg-white p-2 object-contain">
                        <label class="flex items-center gap-2 text-sm font-medium text-gray-600">
                            <input type="checkbox" name="remove_brand_logo" value="1" class="rounded border-gray-300 text-emerald-700 focus:ring-emerald-700">
                            Remove current logo
                        </label>
                    </div>
                <?php endif; ?>
                <input id="brand_logo" name="brand_logo" type="file" accept="image/jpeg,image/png,image/gif,image/webp"
                    class="mt-3 block w-full rounded-2xl border border-gray-200 bg-[#f8faf8] px-4 py-3 text-sm text-gray-700 file:mr-4 file:rounded-xl file:border-0 file:bg-emerald-800 file:px-4 file:py-2 file:font-semibold file:text-white">
                <p class="mt-2 text-xs text-gray-500">Upload a JPG, PNG, GIF, or WebP image. Choosing a new logo replaces the current one.</p>
            </div>
            <button type="submit" class="rounded-2xl bg-gradient-to-r from-[#1f322e] via-[#17503d] to-[#246447] px-6 py-4 font-bold text-white shadow-lg md:col-span-2">
                Save branding
            </button>
        </form>
    </div>
</section>

<?php include 'footer.php'; ?>