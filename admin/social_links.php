<?php
session_start();
require_once '../includes/db.php';
require_once __DIR__ . '/notifications.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

if (empty($_SESSION['social_links_token'])) {
    $_SESSION['social_links_token'] = bin2hex(random_bytes(32));
}

$message = '';
$platforms = [
    'facebook' => 'Facebook',
    'instagram' => 'Instagram',
    'tiktok' => 'TikTok',
    'telegram' => 'Telegram',
    'whatsapp' => 'WhatsApp',
    'youtube' => 'YouTube',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['social_links_token'], $token)) {
        $message = 'Your session expired. Refresh the page and try again.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'save') {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
            $platform = trim($_POST['platform'] ?? '');
            $name = trim($_POST['name'] ?? '');
            $url = trim($_POST['url'] ?? '');
            $sort_order = filter_input(INPUT_POST, 'sort_order', FILTER_VALIDATE_INT);
            $sort_order = $sort_order === false || $sort_order === null ? 0 : $sort_order;
            $is_active = isset($_POST['is_active']) ? 1 : 0;

            if (!isset($platforms[$platform])) {
                $message = 'Select a valid social platform.';
            } elseif ($name === '') {
                $message = 'Enter the social media name.';
            } elseif (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match('/^https?:\\/\\//i', $url)) {
                $message = 'Enter a valid http or https URL.';
            } else {
                try {
                    if ($id > 0) {
                        $stmt = $pdo->prepare("UPDATE social_links SET platform = ?, name = ?, url = ?, is_active = ?, sort_order = ? WHERE id = ?");
                        $stmt->execute([$platform, $name, $url, $is_active, $sort_order, $id]);
                        set_admin_flash('Social link updated successfully.', 'Link updated');
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO social_links (platform, name, url, is_active, sort_order) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([$platform, $name, $url, $is_active, $sort_order]);
                        set_admin_flash('Social link added successfully.', 'Link added');
                    }
                    header('Location: social_links.php');
                    exit;
                } catch (PDOException $e) {
                    $message = 'Could not save the social link. Please try again.';
                }
            }
        } elseif ($action === 'delete') {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            if (!$id) {
                $message = 'Select a valid social link to remove.';
            } else {
                $pdo->prepare("DELETE FROM social_links WHERE id = ?")->execute([$id]);
                set_admin_flash('Social link deleted successfully.', 'Link deleted');
                header('Location: social_links.php');
                exit;
            }
        }
    }
}

$editing = null;
if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM social_links WHERE id = ?");
    $stmt->execute([filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT)]);
    $editing = $stmt->fetch() ?: null;
}

$links = $pdo->query("SELECT * FROM social_links ORDER BY sort_order ASC, id ASC")->fetchAll();
$link_form_open = $editing !== null
    || ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save');
$form_values = $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save'
    ? $_POST
    : ($editing ?? []);
$page_title = 'Social Links';
include 'header.php';
?>

<div class="mx-auto max-w-5xl" x-data="{ linkFormOpen: <?= $link_form_open ? 'true' : 'false' ?> }">
    <header class="mb-6 flex items-center justify-between gap-3">
        <h2 class="min-w-0 text-2xl font-bold text-gray-900 sm:text-3xl">Social Links</h2>
        <button type="button" @click="linkFormOpen = !linkFormOpen"
            :aria-expanded="linkFormOpen.toString()"
            class="inline-flex shrink-0 items-center gap-2 rounded-xl bg-gradient-to-r from-[#1f322e] to-emerald-700 px-3 py-2.5 text-xs font-bold text-white shadow-md transition hover:to-emerald-800 focus:outline-none focus:ring-4 focus:ring-emerald-700/20 sm:px-5 sm:text-sm">
            <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 5v14m-7-7h14" />
            </svg>
            <span x-text="linkFormOpen ? 'Close form' : 'Add social link'"><?= $editing ? 'Close form' : 'Add social link' ?></span>
        </button>
    </header>

    <?php if ($message): ?>
        <div role="alert" class="mb-6 rounded-2xl border border-rose-100 bg-rose-50 p-4 font-semibold text-rose-700">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <section x-show="linkFormOpen" x-cloak x-transition class="glass-card mb-8 rounded-3xl p-5 sm:p-6 lg:p-8">
        <h3 class="mb-6 text-xl font-bold text-gray-900"><?= $editing ? 'Edit social link' : 'Add social link' ?></h3>
        <form method="POST" class="grid grid-cols-1 gap-5 md:grid-cols-2">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['social_links_token']) ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">

            <label class="text-sm font-semibold text-gray-700">
                Platform
                <select name="platform" required class="mt-2 w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 focus:border-orange-500 focus:outline-none">
                    <?php foreach ($platforms as $value => $label): ?>
                        <option value="<?= htmlspecialchars($value) ?>" <?= ($form_values['platform'] ?? 'facebook') === $value ? 'selected' : '' ?>>
                            <?= htmlspecialchars($label) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <label class="text-sm font-semibold text-gray-700">
                Display name
                <input name="name" required maxlength="100" value="<?= htmlspecialchars($form_values['name'] ?? '') ?>" placeholder="e.g. Facebook"
                    class="mt-2 w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 focus:border-orange-500 focus:outline-none">
            </label>

            <label class="text-sm font-semibold text-gray-700 md:col-span-2">
                Profile URL
                <input type="url" name="url" required maxlength="500" value="<?= htmlspecialchars($form_values['url'] ?? '') ?>" placeholder="https://..."
                    class="mt-2 w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 focus:border-orange-500 focus:outline-none">
            </label>

            <label class="text-sm font-semibold text-gray-700">
                Display order
                <input type="number" name="sort_order" min="0" max="999" value="<?= (int) ($form_values['sort_order'] ?? 0) ?>"
                    class="mt-2 w-full rounded-xl border border-gray-200 bg-gray-50 px-4 py-3 focus:border-orange-500 focus:outline-none">
            </label>

            <label class="flex items-center gap-3 text-sm font-semibold text-gray-700">
                <input type="checkbox" name="is_active" value="1" <?= $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save'
                    ? (isset($_POST['is_active']) ? 'checked' : '')
                    : (!isset($editing['is_active']) || $editing['is_active'] ? 'checked' : '') ?>
                    class="h-4 w-4 accent-emerald-600">
                Show on customer menu
            </label>

            <div class="flex flex-col items-center gap-3 md:col-span-2">
                <button type="submit" class="w-full rounded-full bg-gradient-to-r from-amber-300 to-orange-300 px-6 py-3.5 font-bold text-[#25342d] shadow-lg transition hover:-translate-y-0.5 md:w-3/5">
                    <?= $editing ? 'Save changes' : 'Add social link' ?>
                </button>
                <?php if ($editing): ?>
                    <a href="social_links.php" class="rounded-full px-5 py-2 text-sm font-semibold text-gray-500 hover:bg-gray-100">Cancel editing</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="space-y-4">
        <div class="flex items-center justify-between gap-3">
            <h3 class="text-lg font-bold text-gray-900 sm:text-xl">Configured links</h3>
            <span class="shrink-0 rounded-full bg-emerald-50 px-3 py-1 text-xs font-bold text-emerald-800"><?= count($links) ?> total</span>
        </div>
        <?php if (!$links): ?>
            <div class="glass-card rounded-2xl p-6 text-sm text-gray-500">No social links have been added yet.</div>
        <?php endif; ?>
        <?php foreach ($links as $link): ?>
            <article class="glass-card flex min-w-0 items-center gap-2.5 rounded-2xl p-3.5 sm:gap-4 sm:p-5">
                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-emerald-700 to-[#153b34] text-white sm:h-14 sm:w-14">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M18 8a3 3 0 1 0-2.83-4M6 14a3 3 0 1 0 0-4m12 11a3 3 0 1 0-2.83-4M8.7 10.7l6.6-3.4m-6.6 6 6.6 3.4" />
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex min-w-0 flex-wrap items-center gap-1.5 sm:gap-2">
                        <h4 class="max-w-full truncate text-sm font-bold leading-5 text-gray-900"><?= htmlspecialchars($link['name']) ?></h4>
                        <span class="shrink-0 rounded-full px-2 py-1 text-[9px] font-bold leading-none sm:text-[10px] <?= $link['is_active'] ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500' ?>">
                            <?= $link['is_active'] ? 'Visible' : 'Hidden' ?>
                        </span>
                    </div>
                    <p class="mt-0.5 truncate text-xs leading-4 text-gray-600 sm:text-sm"><?= htmlspecialchars($link['url']) ?></p>
                </div>
                <div class="flex shrink-0 gap-1.5 sm:gap-2">
                    <a href="social_links.php?edit=<?= (int) $link['id'] ?>" aria-label="Edit <?= htmlspecialchars($link['name']) ?>" title="Edit link"
                        class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-amber-300/60 bg-gradient-to-br from-amber-200 to-orange-200 text-[#25342d] transition-all hover:-translate-y-0.5 hover:from-amber-100 hover:to-orange-100 focus:outline-none focus:ring-4 focus:ring-amber-400/30 sm:h-10 sm:w-10">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="m15.5 5.5 3 3M4 20l4.3-.9L19 8.4a2.1 2.1 0 0 0-3-3L5.3 16.1 4 20Z" />
                        </svg>
                    </a>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['social_links_token']) ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int) $link['id'] ?>">
                        <button type="button" @click="requestConfirmation('Remove this social link?', () => $el.closest('form').requestSubmit())" aria-label="Delete <?= htmlspecialchars($link['name']) ?>" title="Delete link"
                            class="inline-flex h-9 w-9 items-center justify-center rounded-xl border border-rose-200 bg-rose-50 text-rose-700 transition-all hover:-translate-y-0.5 hover:bg-rose-100 focus:outline-none focus:ring-4 focus:ring-rose-500/20 sm:h-10 sm:w-10">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8" d="M4 7h16m-10 4v6m4-6v6M6 7l1 14h10l1-14M9 7V4h6v3" />
                            </svg>
                        </button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
</div>

<?php include 'footer.php'; ?>
