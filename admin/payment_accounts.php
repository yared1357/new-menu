<?php
session_start();
require_once '../includes/db.php';
require_once __DIR__ . '/image_upload.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

if (empty($_SESSION['payment_accounts_token'])) {
    $_SESSION['payment_accounts_token'] = bin2hex(random_bytes(32));
}

$message = '';
$success = false;
$editing = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['payment_accounts_token'], $token)) {
        $message = 'Your session expired. Refresh the page and try again.';
    } else {
        $action = $_POST['action'] ?? '';

        if ($action === 'save') {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT) ?: 0;
            $provider = trim($_POST['provider'] ?? '');
            $account_name = trim($_POST['account_name'] ?? '');
            $account_number = trim($_POST['account_number'] ?? '');
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            $new_qr_path = '';

            if ($provider === '' || $account_name === '' || $account_number === '') {
                $message = 'Enter the payment provider, account name, and account number.';
            } else {
                try {
                    if ($id > 0) {
                        $stmt = $pdo->prepare("SELECT * FROM payment_accounts WHERE id = ?");
                        $stmt->execute([$id]);
                        $existing = $stmt->fetch();
                        if (!$existing) {
                            throw new RuntimeException('This payment account no longer exists.');
                        }
                        $qr_path = $existing['qr_image'] ?? '';
                    } else {
                        $existing = null;
                        $qr_path = '';
                    }

                    if (isset($_FILES['qr_image']) && ($_FILES['qr_image']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
                        $new_qr_path = store_menu_image($_FILES['qr_image']);
                        $qr_path = $new_qr_path;
                    }

                    if ($id > 0) {
                        $stmt = $pdo->prepare("UPDATE payment_accounts SET provider = ?, account_name = ?, account_number = ?, qr_image = ?, is_active = ? WHERE id = ?");
                        $stmt->execute([$provider, $account_name, $account_number, $qr_path, $is_active, $id]);
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO payment_accounts (provider, account_name, account_number, qr_image, is_active) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([$provider, $account_name, $account_number, $qr_path, $is_active]);
                    }

                    if ($new_qr_path !== '' && !empty($existing['qr_image'])) {
                        remove_menu_image($existing['qr_image']);
                    }

                    header('Location: payment_accounts.php?saved=1');
                    exit;
                } catch (RuntimeException $e) {
                    $message = $e->getMessage();
                    if ($new_qr_path !== '') {
                        remove_menu_image($new_qr_path);
                    }
                } catch (PDOException $e) {
                    $message = 'Could not save the payment account. Please try again.';
                    if ($new_qr_path !== '') {
                        remove_menu_image($new_qr_path);
                    }
                }
            }
        } elseif ($action === 'delete') {
            $id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            if (!$id) {
                $message = 'Select a valid payment account to remove.';
            } else {
                $stmt = $pdo->prepare("SELECT qr_image FROM payment_accounts WHERE id = ?");
                $stmt->execute([$id]);
                $account = $stmt->fetch();
                if ($account) {
                    $stmt = $pdo->prepare("DELETE FROM payment_accounts WHERE id = ?");
                    $stmt->execute([$id]);
                    if (!empty($account['qr_image'])) {
                        remove_menu_image($account['qr_image']);
                    }
                    header('Location: payment_accounts.php?deleted=1');
                    exit;
                }
                $message = 'This payment account no longer exists.';
            }
        }
    }
}

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM payment_accounts WHERE id = ?");
    $stmt->execute([filter_input(INPUT_GET, 'edit', FILTER_VALIDATE_INT)]);
    $editing = $stmt->fetch() ?: null;
}

$accounts = $pdo->query("SELECT * FROM payment_accounts ORDER BY id DESC")->fetchAll();
$page_title = 'Payment Accounts';
include 'header.php';
?>

<div class="max-w-5xl mx-auto">
    <header class="mb-8">
        <span class="text-orange-500 font-bold uppercase tracking-widest text-[10px] mb-1 block">Checkout details</span>
        <h2 class="text-3xl font-bold text-gray-900">Payment Accounts</h2>
        <p class="mt-2 text-sm text-gray-500">Manage the account details and optional QR codes shown to customers.</p>
    </header>

    <?php if (isset($_GET['saved']) || isset($_GET['deleted'])): ?>
        <div class="bg-emerald-50 border border-emerald-100 text-emerald-700 p-4 rounded-2xl mb-6 font-semibold">
            <?= isset($_GET['saved']) ? 'Payment account saved.' : 'Payment account removed.' ?>
        </div>
    <?php endif; ?>

    <?php if ($message !== ''): ?>
        <div class="bg-rose-50 border border-rose-100 text-rose-700 p-4 rounded-2xl mb-6 font-semibold">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <section class="glass-card rounded-3xl p-6 lg:p-8 mb-8">
        <h3 class="text-xl font-bold text-gray-900 mb-6">
            <?= $editing ? 'Edit payment account' : 'Add payment account' ?>
        </h3>
        <form method="POST" enctype="multipart/form-data" class="grid grid-cols-1 md:grid-cols-2 gap-5">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['payment_accounts_token']) ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= (int) ($editing['id'] ?? 0) ?>">

            <label class="block text-sm font-semibold text-gray-700">
                Bank or payment provider
                <input name="provider" required maxlength="120" value="<?= htmlspecialchars($editing['provider'] ?? '') ?>"
                    placeholder="e.g. CBE, Telebirr"
                    class="mt-2 w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 focus:outline-none focus:border-orange-500">
            </label>
            <label class="block text-sm font-semibold text-gray-700">
                Account holder name
                <input name="account_name" required maxlength="160" value="<?= htmlspecialchars($editing['account_name'] ?? '') ?>"
                    class="mt-2 w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 focus:outline-none focus:border-orange-500">
            </label>
            <label class="block text-sm font-semibold text-gray-700">
                Account number
                <input name="account_number" required maxlength="120" value="<?= htmlspecialchars($editing['account_number'] ?? '') ?>"
                    class="mt-2 w-full px-4 py-3 rounded-xl border border-gray-200 bg-gray-50 focus:outline-none focus:border-orange-500">
            </label>
            <label class="block text-sm font-semibold text-gray-700">
                QR code <span class="font-normal text-gray-400">(optional JPG, PNG, GIF, or WebP)</span>
                <input type="file" name="qr_image" accept="image/jpeg,image/png,image/gif,image/webp"
                    class="mt-2 w-full px-4 py-3 rounded-xl border border-dashed border-gray-300 bg-gray-50 text-sm">
            </label>

            <?php if (!empty($editing['qr_image'])): ?>
                <div class="flex items-center gap-3 md:col-span-2">
                    <img src="../<?= htmlspecialchars($editing['qr_image']) ?>" alt="Current QR code"
                        class="w-20 h-20 rounded-xl border border-gray-200 object-contain bg-white p-1">
                    <span class="text-sm text-gray-500">Upload a new QR code to replace this one.</span>
                </div>
            <?php endif; ?>

            <label class="flex items-center gap-3 text-sm font-semibold text-gray-700 md:col-span-2">
                <input type="checkbox" name="is_active" value="1" <?= !isset($editing['is_active']) || $editing['is_active'] ? 'checked' : '' ?>
                    class="h-4 w-4 accent-emerald-600">
                Show this account to customers
            </label>

            <div class="flex gap-3 md:col-span-2">
                <button class="px-6 py-3 rounded-xl bg-orange-500 hover:bg-orange-600 text-white font-bold transition-colors">
                    <?= $editing ? 'Save changes' : 'Add account' ?>
                </button>
                <?php if ($editing): ?>
                    <a href="payment_accounts.php" class="px-6 py-3 rounded-xl bg-gray-100 text-gray-700 font-semibold">Cancel</a>
                <?php endif; ?>
            </div>
        </form>
    </section>

    <section class="space-y-4">
        <h3 class="text-xl font-bold text-gray-900">Configured accounts</h3>
        <?php if (!$accounts): ?>
            <div class="glass-card rounded-2xl p-6 text-sm text-gray-500">No payment accounts have been added yet.</div>
        <?php endif; ?>
        <?php foreach ($accounts as $account): ?>
            <article class="glass-card rounded-2xl p-5 flex flex-col sm:flex-row sm:items-center gap-4">
                <?php if (!empty($account['qr_image'])): ?>
                    <img src="../<?= htmlspecialchars($account['qr_image']) ?>" alt="QR code for <?= htmlspecialchars($account['provider']) ?>"
                        class="w-20 h-20 rounded-xl border border-gray-200 object-contain bg-white p-1">
                <?php else: ?>
                    <div class="w-20 h-20 rounded-xl bg-gray-100 flex items-center justify-center text-xs text-gray-400">No QR</div>
                <?php endif; ?>
                <div class="flex-1 min-w-0">
                    <div class="flex flex-wrap items-center gap-2">
                        <h4 class="font-bold text-gray-900"><?= htmlspecialchars($account['provider']) ?></h4>
                        <span class="px-2 py-1 rounded-full text-[10px] font-bold <?= $account['is_active'] ? 'bg-emerald-50 text-emerald-700' : 'bg-gray-100 text-gray-500' ?>">
                            <?= $account['is_active'] ? 'Visible' : 'Hidden' ?>
                        </span>
                    </div>
                    <p class="text-sm text-gray-600"><?= htmlspecialchars($account['account_name']) ?></p>
                    <p class="text-sm font-semibold text-gray-800"><?= htmlspecialchars($account['account_number']) ?></p>
                </div>
                <div class="flex gap-2">
                    <a href="payment_accounts.php?edit=<?= (int) $account['id'] ?>"
                        class="px-4 py-2 rounded-lg bg-orange-50 text-orange-700 text-sm font-semibold">Edit</a>
                    <form method="POST" onsubmit="return confirm('Remove this payment account?')">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['payment_accounts_token']) ?>">
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int) $account['id'] ?>">
                        <button class="px-4 py-2 rounded-lg bg-rose-50 text-rose-700 text-sm font-semibold">Delete</button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
</div>

<?php include 'footer.php'; ?>
