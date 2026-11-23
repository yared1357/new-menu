<?php
session_start();
require_once '../includes/db.php';
require_once __DIR__ . '/image_upload.php';
require_once __DIR__ . '/notifications.php';

// Auth check
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit;
}

$id = $_GET['id'];
$stmt = $pdo->prepare("SELECT * FROM menu_items WHERE id = ?");
$stmt->execute([$id]);
$item = $stmt->fetch();

if (!$item) {
    header("Location: index.php");
    exit;
}

$message = '';
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $category_id = $_POST['category_id'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $is_available = isset($_POST['is_available']) ? 1 : 0;
    $is_fasting = in_array($_POST['is_fasting'] ?? '2', ['0', '1', '2'], true)
        ? (int) $_POST['is_fasting']
        : 2;

    $image_path = $item['image_url'];
    $new_image_path = '';
    $item_updated = false;

    try {
        if (isset($_FILES['image']) && $_FILES['image']['error'] !== UPLOAD_ERR_NO_FILE) {
            $new_image_path = store_menu_image($_FILES['image']);
            $image_path = $new_image_path;
        }

        $stmt = $pdo->prepare("UPDATE menu_items SET name = ?, category_id = ?, description = ?, price = ?, image_url = ?, is_available = ?, is_fasting = ? WHERE id = ?");
        $stmt->execute([$name, $category_id, $description, $price, $image_path, $is_available, $is_fasting, $id]);
        $item_updated = true;

        if ($new_image_path !== '' && !empty($item['image_url'])) {
            remove_menu_image($item['image_url']);
        }

        set_admin_flash('Menu item updated successfully.', 'Item updated');
        header("Location: index.php");
        exit;
    } catch (RuntimeException $e) {
        $message = $e->getMessage();
        if ($item_updated) {
            $item['name'] = $name;
            $item['category_id'] = $category_id;
            $item['description'] = $description;
            $item['price'] = $price;
            $item['image_url'] = $image_path;
            $item['is_available'] = $is_available;
            $item['is_fasting'] = $is_fasting;
        } elseif ($new_image_path !== '') {
            remove_menu_image($new_image_path);
        }
    } catch (PDOException $e) {
        if ($new_image_path !== '') {
            remove_menu_image($new_image_path);
        }
        $message = "Error: " . $e->getMessage();
    }
}

$page_title = "Edit Masterpiece";
include 'header.php';
?>

<div class="max-w-4xl mx-auto">
    <header class="mb-6">
        <span class="text-orange-500 font-bold uppercase tracking-widest text-[10px] mb-1 block">Refine Details</span>
        <p class="text-gray-500 mt-2 text-sm">Update ingredients, pricing, or visuals for
            "<?= htmlspecialchars($item['name']) ?>".</p>
    </header>

    <div class="glass-card rounded-[2rem] p-6 lg:p-8">
        <?php if ($message): ?>
            <div
                class="bg-rose-50 border border-rose-100 text-rose-600 p-5 rounded-2xl mb-8 flex items-center gap-3 font-semibold">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <?= $message ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" class="space-y-5"
            @submit.prevent="requestConfirmation('Are you sure you want to update this menu item?', () => $el.submit(), false)">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-700 ml-1 uppercase tracking-wider">Name</label>
                    <input type="text" name="name" required value="<?= htmlspecialchars($item['name']) ?>"
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-gray-50 focus:outline-none focus:border-orange-500 text-sm text-gray-900">
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-700 ml-1 uppercase tracking-wider">Category</label>
                    <select name="category_id" required
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-gray-50 focus:outline-none focus:border-orange-500 text-sm text-gray-900 appearance-none">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $item['category_id'] ? 'selected' : '' ?>>
                                <?= $cat['name'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-700 ml-1 uppercase tracking-wider">Price
                        (ETB)</label>
                    <div class="relative">
                        <span
                            class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 font-bold text-sm">ETB</span>
                        <input type="number" step="0.01" name="price" required value="<?= $item['price'] ?>"
                            class="w-full pl-14 pr-4 py-2.5 rounded-xl border border-gray-200 bg-gray-50 focus:outline-none focus:border-orange-500 text-sm text-gray-900">
                    </div>
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-700 ml-1 uppercase tracking-wider">Item Image <span class="font-medium normal-case tracking-normal text-gray-400">(Optional)</span></label>
                    <div class="flex flex-col gap-2">
                        <?php if ($item['image_url']): ?>
                            <div class="flex items-center gap-3 p-2 bg-gray-50 rounded-xl border border-gray-100 w-fit">
                                <img src="../<?= htmlspecialchars($item['image_url'], ENT_QUOTES, 'UTF-8') ?>" class="w-10 h-10 rounded-lg object-cover shadow-sm"
                                    alt="Current">
                                <span class="text-[10px] font-bold text-gray-400 uppercase tracking-wider">Current</span>
                            </div>
                        <?php else: ?>
                            <p class="text-xs text-gray-400">No image currently set. The menu will show a placeholder until you upload one.</p>
                        <?php endif; ?>
                        <input type="file" name="image" accept="image/*"
                            class="w-full px-4 py-2 rounded-xl border-2 border-dashed border-gray-200 bg-gray-50 text-gray-500 file:mr-3 file:py-1 file:px-3 file:rounded-full file:border-0 file:text-[10px] file:font-bold file:bg-amber-300 file:text-[#25342d] hover:file:bg-amber-200 transition-all cursor-pointer text-xs">
                        <p class="ml-1 text-xs text-gray-400">Leave empty to keep the current image.</p>
                    </div>
                </div>
            </div>

            <div class="space-y-1.5">
                <label class="block text-xs font-bold text-gray-700 ml-1 uppercase tracking-wider">Dietary Type</label>
                <div class="relative">
                    <select name="is_fasting" required
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-gray-50 focus:outline-none focus:border-orange-500 text-sm text-gray-900 appearance-none">
                        <option value="2" <?= (int) $item['is_fasting'] === 2 ? 'selected' : '' ?>>Not applicable</option>
                        <option value="0" <?= (int) $item['is_fasting'] === 0 ? 'selected' : '' ?>>Non-Fasting</option>
                        <option value="1" <?= (int) $item['is_fasting'] === 1 ? 'selected' : '' ?>>Fasting</option>
                    </select>
                    <div class="absolute inset-y-0 right-4 flex items-center pointer-events-none text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                            </path>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="space-y-1.5">
                <label class="block text-xs font-bold text-gray-700 ml-1 uppercase tracking-wider">Description
                    (Optional)</label>
                <textarea name="description" rows="3"
                    class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-gray-50 focus:outline-none focus:border-orange-500 text-sm text-gray-900"><?= htmlspecialchars($item['description']) ?></textarea>
            </div>

            <div class="flex items-center justify-between p-4 rounded-2xl bg-orange-50/50 border border-orange-100">
                <div class="flex items-center gap-3">
                    <div
                        class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-orange-500 shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                            </path>
                        </svg>
                    </div>
                    <p class="font-bold text-gray-900 text-sm italic">Visible on Menu</p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="is_available" id="is_available" <?= $item['is_available'] ? 'checked' : '' ?> class="sr-only peer">
                    <div
                        class="w-12 h-7 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-400">
                    </div>
                </label>
            </div>

            <div class="flex flex-row gap-3 pt-2">
                <button type="submit"
                    class="flex-[2] py-3 bg-gradient-to-r from-amber-300 to-orange-300 hover:from-amber-200 hover:to-orange-200 text-[#25342d] rounded-xl font-bold transition-all shadow-lg shadow-orange-950/10 transform hover:-translate-y-0.5 text-sm sm:text-base">
                    Update Item
                </button>
                <a href="index.php"
                    class="flex-1 py-3 bg-rose-500 hover:bg-rose-600 text-white rounded-xl font-bold transition-all text-center shadow-lg shadow-rose-500/20 transform hover:-translate-y-1 text-sm sm:text-base">
                    Cancel
                </a>
            </div>
        </form>
    </div>
</div>

<?php include 'footer.php'; ?>