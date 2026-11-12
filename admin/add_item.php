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

$message = '';
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $category_id = $_POST['category_id'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $is_available = isset($_POST['is_available']) ? 1 : 0;
    $is_fasting = isset($_POST['is_fasting']) ? $_POST['is_fasting'] : 0;

    $image_path = '';
    try {
        $image_path = store_menu_image($_FILES['image'] ?? []);
        $stmt = $pdo->prepare("INSERT INTO menu_items (name, category_id, description, price, image_url, is_available, is_fasting) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$name, $category_id, $description, $price, $image_path, $is_available, $is_fasting]);
        set_admin_flash('Menu item created successfully.', 'Item created');
        header("Location: index.php");
        exit;
    } catch (RuntimeException $e) {
        $message = $e->getMessage();
        if ($image_path !== '') {
            remove_menu_image($image_path);
        }
    } catch (PDOException $e) {
        if ($image_path !== '') {
            remove_menu_image($image_path);
        }
        $message = "Error: " . $e->getMessage();
    }
}

$page_title = "Add Masterpiece";
include 'header.php';
?>

<div class="max-w-4xl mx-auto">
    <header class="mb-6">
        <span class="text-orange-500 font-bold uppercase tracking-widest text-[10px] mb-1 block">New Addition</span>
        <h2 class="text-3xl font-bold text-gray-900 leading-tight">Create Masterpiece</h2>
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

        <form method="POST" enctype="multipart/form-data" class="space-y-5">
            <!-- Primary Info -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-700 ml-1 uppercase tracking-wider">Name</label>
                    <input type="text" name="name" required
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-gray-50 focus:outline-none focus:border-orange-500 text-sm text-gray-900"
                        placeholder="e.g. Truffle Burger">
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-700 ml-1 uppercase tracking-wider">Category</label>
                    <select name="category_id" required
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-gray-50 focus:outline-none focus:border-orange-500 text-sm text-gray-900 appearance-none">
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>"><?= $cat['name'] ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <!-- Secondary Info -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-700 ml-1 uppercase tracking-wider">Price
                        (ETB)</label>
                    <div class="relative">
                        <span
                            class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400 font-bold text-sm">ETB</span>
                        <input type="number" step="0.01" name="price" required
                            class="w-full pl-14 pr-4 py-2.5 rounded-xl border border-gray-200 bg-gray-50 focus:outline-none focus:border-orange-500 text-sm text-gray-900"
                            placeholder="12.99">
                    </div>
                </div>
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-700 ml-1 uppercase tracking-wider">Visual</label>
                    <input type="file" name="image" accept="image/*" required
                        class="w-full px-4 py-2 rounded-xl border-2 border-dashed border-gray-200 bg-gray-50 text-gray-500 file:mr-3 file:py-1 file:px-3 file:rounded-full file:border-0 file:text-[10px] file:font-bold file:bg-amber-300 file:text-[#25342d] hover:file:bg-amber-200 transition-all cursor-pointer text-xs">
                </div>
            </div>

            <!-- Dietary Type -->
            <div class="space-y-1.5">
                <label class="block text-xs font-bold text-gray-700 ml-1 uppercase tracking-wider">Dietary Type</label>
                <div class="relative">
                    <select name="is_fasting" required
                        class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-gray-50 focus:outline-none focus:border-orange-500 text-sm text-gray-900 appearance-none">
                        <option value="0">Non-Fasting</option>
                        <option value="1">Fasting</option>
                    </select>
                    <div class="absolute inset-y-0 right-4 flex items-center pointer-events-none text-gray-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                            </path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Description -->
            <div class="space-y-1.5">
                <label class="block text-xs font-bold text-gray-700 ml-1 uppercase tracking-wider">Description
                    (Optional)</label>
                <textarea name="description" rows="3"
                    class="w-full px-4 py-2.5 rounded-xl border border-gray-200 bg-gray-50 focus:outline-none focus:border-orange-500 text-sm text-gray-900"
                    placeholder="Describe the soul of this dish..."></textarea>
            </div>

            <!-- Status Toggle -->
            <div class="flex items-center justify-between p-4 rounded-2xl bg-orange-50/50 border border-orange-100">
                <div class="flex items-center gap-3">
                    <div
                        class="w-10 h-10 bg-white rounded-xl flex items-center justify-center text-orange-500 shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7">
                            </path>
                        </svg>
                    </div>
                    <div>
                        <p class="font-bold text-gray-900 text-sm italic">Visible on Menu</p>
                    </div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="is_available" id="is_available" checked class="sr-only peer">
                    <div
                        class="w-12 h-7 bg-gray-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-amber-400">
                    </div>
                </label>
            </div>

            <!-- Actions -->
            <div class="flex flex-row gap-3 pt-2">
                <button type="submit"
                    class="flex-[2] py-3 bg-gradient-to-r from-amber-300 to-orange-300 hover:from-amber-200 hover:to-orange-200 text-[#25342d] rounded-xl font-bold transition-all shadow-lg shadow-orange-950/10 transform hover:-translate-y-0.5 text-sm sm:text-base">
                    Save Item
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