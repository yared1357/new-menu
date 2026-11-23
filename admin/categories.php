<?php
session_start();
require_once '../includes/db.php';
require_once __DIR__ . '/notifications.php';

// Auth check
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

// Check and add is_active column if it doesn't exist
try {
    $pdo->exec("ALTER TABLE categories ADD COLUMN is_active TINYINT(1) DEFAULT 1");
} catch (PDOException $e) {
    // Column already exists or other error, ignore
}

$message = '';
$edit_mode = false;
$edit_id = 0;
$edit_name = '';

// Handle Toggle Status
if (isset($_GET['toggle'])) {
    $id = $_GET['toggle'];
    $pdo->prepare("UPDATE categories SET is_active = 1 - COALESCE(is_active, 1) WHERE id = ?")->execute([$id]);
    set_admin_flash('Category visibility updated.', 'Category updated');
    header("Location: categories.php");
    exit;
}

// Handle Add/Edit Category
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_category'])) {
        $name = $_POST['name'];
        try {
            $stmt = $pdo->prepare("INSERT INTO categories (name) VALUES (?)");
            $stmt->execute([$name]);
            set_admin_flash('Category created successfully.', 'Category created');
            header('Location: categories.php');
            exit;
        } catch (PDOException $e) {
            $message = "Error: " . $e->getMessage();
        }
    } elseif (isset($_POST['update_category'])) {
        $id = $_POST['id'];
        $name = $_POST['name'];
        try {
            $stmt = $pdo->prepare("UPDATE categories SET name = ? WHERE id = ?");
            $stmt->execute([$name, $id]);
            set_admin_flash('Category updated successfully.', 'Category updated');
            header('Location: categories.php');
            exit;
        } catch (PDOException $e) {
            $message = "Error: " . $e->getMessage();
        }
    }
}

// Handle Edit Fetch
if (isset($_GET['edit'])) {
    $edit_id = $_GET['edit'];
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$edit_id]);
    $cat = $stmt->fetch();
    if ($cat) {
        $edit_mode = true;
        $edit_name = $cat['name'];
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    try {
        $stmt = $pdo->prepare("DELETE FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        if ($stmt->rowCount() > 0) {
            set_admin_flash('Category deleted successfully.', 'Category deleted');
        }
        header("Location: categories.php");
        exit;
    } catch (PDOException $e) {
        $message = "Cannot delete category while items are assigned to it.";
    }
}

$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

$page_title = "Manage Categories";
include 'header.php';
?>

<div x-data="{ showForm: <?= $edit_mode ? 'true' : 'false' ?> }">
    <header
        class="sticky top-[64px] lg:top-0 z-30 bg-gray-50/95 backdrop-blur-xl pb-6 pt-8 lg:pt-12 -mt-8 lg:-mt-12 -mx-8 lg:-mx-12 px-8 lg:px-12 border-b border-gray-200/50 shadow-sm mb-10 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="hidden md:block">
            <h2 class="text-3xl font-bold text-gray-900">Manage Categories</h2>
        </div>
        <!-- Right side actions -->
        <div class="flex flex-row items-center gap-3 w-full md:w-auto">
            <div class="relative flex-1 sm:w-64 md:w-72">
                <span class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>
                </span>
                <input type="text" x-model="search" placeholder="Search categories..."
                    class="w-full bg-white border border-gray-200 rounded-xl pl-10 pr-4 py-2.5 focus:outline-none focus:border-orange-500 text-sm shadow-sm">
            </div>

            <button @click="showForm = !showForm"
                class="flex items-center justify-center gap-2 px-4 sm:px-6 py-2.5 bg-gradient-to-r from-amber-300 to-orange-300 hover:from-amber-200 hover:to-orange-200 text-[#25342d] rounded-xl font-bold transition-all transform hover:-translate-y-0.5 shadow-lg shadow-orange-950/10 whitespace-nowrap shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                <span class="hidden sm:inline">Add Category</span>
            </button>
        </div>
    </header>

    <div class="grid grid-cols-1 gap-8 items-start" :class="showForm ? 'lg:grid-cols-3' : 'lg:grid-cols-1'">
        <div class="lg:col-span-1 sticky top-24" x-show="showForm"
            x-transition:enter="transition ease-out duration-300 transform"
            x-transition:enter-start="opacity-0 translate-y-4" x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-200 transform"
            x-transition:leave-start="opacity-100 translate-y-0" x-transition:leave-end="opacity-0 translate-y-4"
            x-cloak>
            <div class="glass-card rounded-[2.5rem] p-8">
                <h3 class="text-xl font-bold mb-6"><?= $edit_mode ? 'Edit Category' : 'Add New Category' ?></h3>
                <form method="POST">
                    <?php if ($edit_mode): ?>
                        <input type="hidden" name="id" value="<?= $edit_id ?>">
                    <?php endif; ?>
                    <input type="hidden" name="<?= $edit_mode ? 'update_category' : 'add_category' ?>" value="1">
                    <div class="mb-6">
                        <label class="block text-sm font-semibold text-gray-600 mb-2">Name</label>
                        <input type="text" name="name" required value="<?= htmlspecialchars($edit_name) ?>"
                            class="w-full bg-gray-50 border border-gray-200 rounded-xl px-4 py-3 focus:outline-none focus:border-orange-500 text-gray-900">
                    </div>
                    <div class="flex gap-3">
                        <button type="button"
                            @click="requestConfirmation(
                                <?= $edit_mode
                                    ? "'Are you sure you want to update this category?'"
                                    : "'Are you sure you want to add this category?'"
                                ?>,
                                () => $el.closest('form').requestSubmit(),
                                false
                            )"
                            class="flex-1 py-3 bg-gradient-to-r from-amber-300 to-orange-300 hover:from-amber-200 hover:to-orange-200 text-[#25342d] rounded-xl font-bold transition-all transform hover:-translate-y-0.5 shadow-lg shadow-orange-950/10">
                            <?= $edit_mode ? 'Update' : 'Create Category' ?>
                        </button>
                        <?php if ($edit_mode): ?>
                            <a href="categories.php"
                                class="flex-1 py-3 bg-gray-100 hover:bg-gray-200 text-center text-gray-600 rounded-xl font-bold transition-all">Cancel</a>
                        <?php endif; ?>
                    </div>
                </form>
                <?php if ($message): ?>
                    <p
                        class="mt-4 text-sm font-semibold text-rose-500">
                        <?= htmlspecialchars($message, ENT_QUOTES, 'UTF-8') ?>
                    </p>
                <?php endif; ?>
            </div>
        </div>

        <div :class="showForm ? 'lg:col-span-2' : ''" class="transition-all duration-300">
            <div class="glass-card rounded-[2rem] overflow-hidden">
                <table class="w-full text-left">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 font-semibold text-gray-600 uppercase text-[10px] tracking-widest">
                                Category Name</th>
                            <th
                                class="px-6 py-3 font-semibold text-gray-600 uppercase text-[10px] tracking-widest text-center">
                                Status</th>
                            <th
                                class="px-6 py-3 font-semibold text-gray-600 uppercase text-[10px] tracking-widest text-right">
                                Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <?php foreach ($categories as $cat): ?>
                            <tr class="hover:bg-gray-50 transition-colors group"
                                x-show="'<?= addslashes(strtolower($cat['name'])) ?>'.includes(search.toLowerCase())">
                                <td class="px-6 py-2.5 font-bold text-gray-800">
                                    <?= htmlspecialchars($cat['name']) ?>
                                </td>
                                <td class="px-6 py-2.5 text-center">
                                    <?php
                                    $is_active = isset($cat['is_active']) ? (int) $cat['is_active'] : 1;
                                    ?>
                                    <span
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-md text-[10px] font-bold uppercase tracking-wider <?= $is_active ? 'bg-emerald-50 text-emerald-600' : 'bg-gray-100 text-gray-500' ?>">
                                        <?php if ($is_active): ?>
                                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span> Active
                                        <?php else: ?>
                                            <span class="w-1.5 h-1.5 rounded-full bg-gray-400"></span> Disabled
                                        <?php endif; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-2.5 text-right">
                                    <div class="flex justify-end gap-1.5">
                                        <button type="button"
                                            title="<?= $is_active ? 'Disable Category' : 'Enable Category' ?>"
                                            @click="requestConfirmation(
                                                'Are you sure you want to <?= $is_active ? 'disable' : 'enable' ?> this category?',
                                                () => window.location.href = '?toggle=<?= (int) $cat['id'] ?>',
                                                <?= $is_active ? 'true' : 'false' ?>
                                            )"
                                            class="w-8 h-8 flex items-center justify-center rounded-lg transition-all <?= $is_active ? 'bg-amber-500/10 text-amber-500 hover:bg-amber-500 hover:text-white' : 'bg-emerald-500/10 text-emerald-500 hover:bg-emerald-500 hover:text-white' ?>">
                                            <?php if ($is_active): ?>
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636">
                                                    </path>
                                                </svg>
                                            <?php else: ?>
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                        d="M5 13l4 4L19 7"></path>
                                                </svg>
                                            <?php endif; ?>
                                        </button>
                                        <a href="?edit=<?= $cat['id'] ?>" title="Edit Category"
                                            class="w-8 h-8 flex items-center justify-center rounded-lg bg-blue-500/10 text-blue-500 hover:bg-blue-500 hover:text-white transition-all">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path
                                                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                                </path>
                                            </svg>
                                        </a>
                                        <button type="button" title="Delete Category"
                                            @click="requestConfirmation('Delete this category? This action cannot be undone.', () => window.location.href = '?delete=<?= (int) $cat['id'] ?>')"
                                            class="w-8 h-8 flex items-center justify-center rounded-lg bg-rose-500/10 text-rose-500 hover:bg-rose-500 hover:text-white transition-all">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                </path>
                                            </svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php include 'footer.php'; ?>