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
$super_admin_email = get_app_setting('super_admin_email');
$storefront_colors = [
    'storefront_background_color' => get_app_setting('storefront_background_color', '#f7f7f2'),
    'storefront_header_color' => get_app_setting('storefront_header_color', '#317D53'),
    'storefront_footer_color' => get_app_setting('storefront_footer_color', '#1f322e'),
    'storefront_action_color' => get_app_setting('storefront_action_color', '#1f4d3e'),
];
foreach ($storefront_colors as $key => $color) {
    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
        $storefront_colors[$key] = [
            'storefront_background_color' => '#f7f7f2',
            'storefront_header_color' => '#317D53',
            'storefront_footer_color' => '#1f322e',
            'storefront_action_color' => '#1f4d3e',
        ][$key];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'reset_storefront_colors') {
    $default_storefront_colors = [
        'storefront_background_color' => '#f7f7f2',
        'storefront_header_color' => '#317D53',
        'storefront_footer_color' => '#1f322e',
        'storefront_action_color' => '#1f4d3e',
    ];

    try {
        $pdo->beginTransaction();
        $update_setting = $pdo->prepare('UPDATE app_settings SET setting_value = ? WHERE setting_key = ?');
        foreach ($default_storefront_colors as $key => $value) {
            $update_setting->execute([$value, $key]);
            if ($update_setting->rowCount() === 0) {
                $exists = $pdo->prepare('SELECT 1 FROM app_settings WHERE setting_key = ?');
                $exists->execute([$key]);
                if (!$exists->fetchColumn()) {
                    $pdo->prepare('INSERT INTO app_settings (setting_key, setting_value) VALUES (?, ?)')
                        ->execute([$key, $value]);
                }
            }
        }
        $pdo->commit();
        $_SESSION['settings_flash'] = ['message' => 'Storefront colors restored to their defaults.', 'success' => true];
        header('Location: settings.php');
        exit;
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        $message = 'Could not restore the default storefront colors.';
        $success = false;
        $storefront_colors = $default_storefront_colors;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'branding') {
    $current_logo = $brand_logo;
    $uploaded_logo = '';
    $colors_valid = true;
    foreach ($storefront_colors as $key => $current_color) {
        $posted_color = trim($_POST[$key] ?? '');
        if (preg_match('/^#[0-9a-fA-F]{6}$/', $posted_color)) {
            $storefront_colors[$key] = $posted_color;
        } else {
            $colors_valid = false;
        }
    }
    $branding = [
        'brand_name' => get_app_setting('brand_name', 'Tasty Pizza & Burger'),
        'brand_short_name' => trim($_POST['brand_short_name'] ?? ''),
        'brand_tagline' => trim($_POST['brand_tagline'] ?? ''),
        'phone_number' => trim($_POST['phone_number'] ?? ''),
        'super_admin_email' => trim($_POST['super_admin_email'] ?? ''),
        'copyright_name' => trim($_POST['copyright_name'] ?? ''),
        'storefront_background_color' => $storefront_colors['storefront_background_color'],
        'storefront_header_color' => $storefront_colors['storefront_header_color'],
        'storefront_footer_color' => $storefront_colors['storefront_footer_color'],
        'storefront_action_color' => $storefront_colors['storefront_action_color'],
    ];
    $super_admin_email = $branding['super_admin_email'];

    if ($branding['brand_short_name'] === '') {
        $message = 'Header short name is required.';
    } elseif ($branding['super_admin_email'] !== '' && !filter_var($branding['super_admin_email'], FILTER_VALIDATE_EMAIL)) {
        $message = 'Enter a valid Super Admin contact email address.';
    } elseif (!$colors_valid) {
        $message = 'Choose a valid color for each storefront color setting.';
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
            $super_admin_email = $branding['super_admin_email'];
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
            <a href="menu_qr.php" class="ml-auto inline-flex shrink-0 items-center gap-2 rounded-xl bg-emerald-800 px-3 py-2.5 text-xs font-bold text-white transition hover:bg-emerald-900 focus:outline-none focus:ring-4 focus:ring-emerald-700/20 sm:px-4 sm:text-sm">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 4h6v6H4zM14 4h6v6h-6zM4 14h6v6H4zM14 14h2v2h-2zm4 0h2v6h-2zm-4 4h2v2h-2z" />
                </svg>
                <span>Menu QR code</span>
            </a>
        </div>

        <?php if ($message !== ''): ?>
            <div role="<?= $success ? 'status' : 'alert' ?>"
                class="mb-6 rounded-2xl border p-4 text-center text-sm font-semibold <?= $success ? 'border-emerald-100 bg-emerald-50 text-emerald-700' : 'border-red-100 bg-red-50 text-red-700' ?>">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form id="branding-form" method="POST" enctype="multipart/form-data" data-confirm-message="Save these business branding and storefront color changes?" class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <input type="hidden" name="action" value="branding">
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
            <div class="grid grid-cols-2 gap-3 md:col-span-2 sm:gap-5">
                <label class="min-w-0 text-sm font-semibold text-gray-700">
                    Phone number
                    <input name="phone_number" maxlength="30" value="<?= htmlspecialchars(get_app_setting('phone_number', '0926862255')) ?>"
                        class="mt-2 w-full min-w-0 rounded-2xl border border-gray-200 bg-[#f8faf8] px-3 py-3.5 outline-none focus:border-emerald-700 focus:ring-4 focus:ring-emerald-700/10 sm:px-4">
                </label>
                <label class="min-w-0 text-sm font-semibold text-gray-700">
                    Copyright name
                    <input name="copyright_name" maxlength="150" value="<?= htmlspecialchars(get_app_setting('copyright_name', 'Tasty Pizza & Burger')) ?>"
                        class="mt-2 w-full min-w-0 rounded-2xl border border-gray-200 bg-[#f8faf8] px-3 py-3.5 outline-none focus:border-emerald-700 focus:ring-4 focus:ring-emerald-700/10 sm:px-4">
                </label>
            </div>
            <label class="text-sm font-semibold text-gray-700 md:col-span-2">
                Super Admin contact email (optional)
                <input type="email" name="super_admin_email" maxlength="150" value="<?= htmlspecialchars($super_admin_email, ENT_QUOTES, 'UTF-8') ?>"
                    class="mt-2 w-full rounded-2xl border border-gray-200 bg-[#f8faf8] px-4 py-3.5 outline-none focus:border-emerald-700 focus:ring-4 focus:ring-emerald-700/10">
                <span class="mt-1 block text-xs font-normal text-gray-500">Shown on the forgot-password page with the Super Admin phone number.</span>
            </label>
            <div class="md:col-span-2">
                <h3 class="text-sm font-bold text-gray-800">Storefront colors</h3>
                <p class="mt-1 text-xs text-gray-500">The default header and footer match the admin sidebar and footer gradients. Choosing a different color uses it as a solid background, and the text automatically switches between dark and light for readability.</p>
            </div>
            <label class="text-sm font-semibold text-gray-700">
                Page background
                <input type="color" name="storefront_background_color" required value="<?= htmlspecialchars($storefront_colors['storefront_background_color']) ?>"
                    class="mt-2 h-12 w-full cursor-pointer rounded-xl border border-gray-200 bg-white p-1">
            </label>
            <label class="text-sm font-semibold text-gray-700">
                Header background
                <input type="color" name="storefront_header_color" required value="<?= htmlspecialchars($storefront_colors['storefront_header_color']) ?>"
                    class="mt-2 h-12 w-full cursor-pointer rounded-xl border border-gray-200 bg-white p-1">
            </label>
            <label class="text-sm font-semibold text-gray-700 md:col-span-2">
                Footer and mobile navigation background
                <input type="color" name="storefront_footer_color" required value="<?= htmlspecialchars($storefront_colors['storefront_footer_color']) ?>"
                    class="mt-2 h-12 w-full cursor-pointer rounded-xl border border-gray-200 bg-white p-1">
            </label>
            <label class="text-sm font-semibold text-gray-700 md:col-span-2">
                Primary button color
                <input type="color" name="storefront_action_color" required value="<?= htmlspecialchars($storefront_colors['storefront_action_color']) ?>"
                    class="mt-2 h-12 w-full cursor-pointer rounded-xl border border-gray-200 bg-white p-1">
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
        </form>
        <div class="mt-5 flex w-full flex-nowrap gap-3">
            <button type="submit" form="branding-form" class="min-w-0 flex-1 whitespace-nowrap rounded-xl bg-gradient-to-r from-[#1f322e] via-[#17503d] to-[#246447] px-1.5 py-3 text-[10px] font-bold text-white shadow-lg transition hover:brightness-110 focus:outline-none focus:ring-4 focus:ring-emerald-700/20 sm:rounded-2xl sm:px-6 sm:py-4 sm:text-sm">
                Save branding
            </button>
            <form method="POST" class="min-w-0 flex-1" data-confirm-message="Restore the storefront background, header, footer, and button colors to their default colors?">
                <input type="hidden" name="action" value="reset_storefront_colors">
                <button type="submit" class="w-full whitespace-nowrap rounded-xl bg-amber-500 px-1.5 py-3 text-[10px] font-bold text-white shadow-md transition hover:bg-amber-600 focus:outline-none focus:ring-4 focus:ring-amber-500/25 sm:rounded-2xl sm:px-4 sm:py-4 sm:text-sm">
                    <span class="sm:hidden">Reset defaults</span>
                    <span class="hidden sm:inline">Reset colors to defaults</span>
                </button>
            </form>
        </div>
    </div>
</section>

<div id="settings-confirmation" class="fixed inset-0 z-[100] hidden items-center justify-center bg-[#102720]/60 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="settings-confirmation-title" aria-describedby="settings-confirmation-message">
    <div class="w-full max-w-md rounded-3xl border border-white/80 bg-white p-6 text-center shadow-2xl sm:p-8">
        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-800">
            <svg class="h-7 w-7" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M12 9v3m0 4h.01M10.3 3.9 1.8 18.5A1.7 1.7 0 0 0 3.3 21h17.4a1.7 1.7 0 0 0 1.5-2.5L13.7 3.9a2 2 0 0 0-3.4 0Z" />
            </svg>
        </div>
        <h2 id="settings-confirmation-title" class="mt-4 text-xl font-bold text-[#20332b]">Confirm settings change</h2>
        <p id="settings-confirmation-message" class="mt-2 text-sm leading-6 text-gray-600"></p>
        <div class="mt-6 flex justify-center gap-3">
            <button type="button" id="settings-confirmation-cancel" class="rounded-xl border border-gray-200 bg-white px-5 py-2.5 text-sm font-bold text-gray-700 transition hover:bg-gray-50 focus:outline-none focus:ring-4 focus:ring-gray-300/50">Cancel</button>
            <button type="button" id="settings-confirmation-continue" class="rounded-xl bg-emerald-800 px-5 py-2.5 text-sm font-bold text-white transition hover:bg-emerald-900 focus:outline-none focus:ring-4 focus:ring-emerald-700/25">Yes, continue</button>
        </div>
    </div>
</div>
<script>
    (() => {
        const dialog = document.getElementById('settings-confirmation');
        const message = document.getElementById('settings-confirmation-message');
        const cancelButton = document.getElementById('settings-confirmation-cancel');
        const continueButton = document.getElementById('settings-confirmation-continue');
        let pendingForm = null;
        let pendingSubmitter = null;
        let bypassConfirmation = false;

        document.querySelectorAll('form[data-confirm-message]').forEach((form) => {
            form.addEventListener('submit', (event) => {
                if (bypassConfirmation) {
                    bypassConfirmation = false;
                    return;
                }
                event.preventDefault();
                pendingForm = form;
                pendingSubmitter = event.submitter;
                message.textContent = form.dataset.confirmMessage;
                dialog.classList.remove('hidden');
                dialog.classList.add('flex');
                cancelButton.focus();
            });
        });

        const closeDialog = () => {
            dialog.classList.add('hidden');
            dialog.classList.remove('flex');
            pendingForm = null;
            pendingSubmitter = null;
        };

        cancelButton.addEventListener('click', closeDialog);
        dialog.addEventListener('click', (event) => {
            if (event.target === dialog) closeDialog();
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !dialog.classList.contains('hidden')) closeDialog();
        });
        continueButton.addEventListener('click', () => {
            if (!pendingForm) return;
            const form = pendingForm;
            const submitter = pendingSubmitter;
            closeDialog();
            bypassConfirmation = true;
            if (submitter) {
                form.requestSubmit(submitter);
            } else {
                form.requestSubmit();
            }
        });
    })();
</script>

<?php include 'footer.php'; ?>