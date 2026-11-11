<?php
session_start();
require_once '../includes/db.php';

// Auth check
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

// Fetch all items with category names
$query = "SELECT menu_items.*, categories.name as category_name 
          FROM menu_items 
          JOIN categories ON menu_items.category_id = categories.id 
          ORDER BY menu_items.id DESC";
$items = $pdo->query($query)->fetchAll();

// Fetch active categories for filter
$categories = $pdo->query("SELECT * FROM categories ORDER BY name ASC")->fetchAll();

$page_title = "Menu Items";
include 'header.php';
?>

<div x-data="{ categoryFilter: 'all' }">
    <header
        class="sticky top-[64px] lg:top-0 z-30 bg-gray-50/95 backdrop-blur-xl pb-6 pt-8 lg:pt-12 -mt-8 lg:-mt-12 -mx-8 lg:-mx-12 px-8 lg:px-12 border-b border-gray-200/50 shadow-sm mb-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
        <div class="hidden md:block">
            <h2 class="text-3xl font-bold text-gray-900 leading-tight">Manage <span class="text-orange-500">Menu</span>
            </h2>
        </div>

        <div class="flex flex-col sm:flex-row items-center gap-4 w-full md:w-auto">
            <!-- Filters & Search -->
            <div class="flex items-center gap-3 w-full sm:w-auto">
                <div
                    class="relative w-1/2 sm:w-48 bg-white border border-gray-200 rounded-2xl overflow-hidden shadow-sm hover:border-orange-500 focus-within:border-orange-500 transition-colors">
                    <select x-model="categoryFilter"
                        class="w-full h-full bg-transparent pl-4 pr-8 py-3 outline-none text-sm appearance-none font-semibold text-gray-700">
                        <option value="all">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= htmlspecialchars(strtolower($cat['name'])) ?>">
                                <?= htmlspecialchars($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="absolute inset-y-0 right-0 flex items-center px-2 pointer-events-none">
                        <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7">
                            </path>
                        </svg>
                    </div>
                </div>
                <!-- Search bar -->
                <div class="relative group w-1/2 sm:w-auto">
                    <span
                        class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 group-focus-within:text-orange-500 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </span>
                    <input type="text" x-model="search" placeholder="Search menu..."
                        class="w-full md:w-64 bg-white border border-gray-200 rounded-2xl pl-10 pr-4 py-3 focus:outline-none focus:border-orange-500 transition-all text-sm shadow-sm">
                </div>
            </div>

            <a href="add_item.php"
                class="flex w-full sm:w-auto justify-center items-center gap-2 px-6 py-3.5 bg-orange-500 hover:bg-orange-600 text-white rounded-[1.2rem] font-bold transition-all transform hover:-translate-y-1 shadow-lg shadow-orange-500/20 group shrink-0">
                <svg class="w-5 h-5 group-hover:rotate-90 transition-transform" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                <span class="inline">Add Item</span>
            </a>
        </div>
    </header>

    <div
        class="glass-card rounded-[2.5rem] overflow-hidden p-4 md:p-0 bg-transparent md:bg-white border-none md:border-solid shadow-none md:shadow-sm">
        <div class="overflow-x-hidden md:overflow-x-auto">
            <table class="w-full text-left border-collapse block md:table">
                <thead class="bg-gray-50/50 hidden md:table-header-group">
                    <tr>
                        <th class="px-8 py-5 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Image</th>
                        <th class="px-8 py-5 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Item Detail
                        </th>
                        <th class="px-8 py-5 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Category
                        </th>
                        <th class="px-8 py-5 text-[10px] font-bold text-gray-400 uppercase tracking-widest">Price</th>
                        <th class="px-8 py-5 text-[10px] font-bold text-gray-400 uppercase tracking-widest text-right">
                            Actions</th>
                    </tr>
                </thead>
                <tbody
                    class="divide-y-0 md:divide-y divide-gray-100 grid grid-cols-2 gap-3 md:gap-0 md:flex-none md:table-row-group">
                    <?php if (empty($items)): ?>
                        <tr
                            class="col-span-2 md:table-row bg-white rounded-2xl md:rounded-none shadow-sm md:shadow-none p-5 md:p-0">
                            <td colspan="5" class="px-8 py-20 text-center text-gray-400 italic block md:table-cell">No
                                delicacies found in the vault
                                yet.</td>
                        </tr>
                    <?php else:
                        foreach ($items as $item): ?>
                            <tr class="hover:bg-orange-50/10 transition-colors group flex flex-col md:table-row bg-white rounded-[1.5rem] md:rounded-none border border-gray-100 md:border-none shadow-sm md:shadow-none p-4 md:p-0 relative"
                                x-show="(categoryFilter === 'all' || '<?= addslashes(strtolower($item['category_name'])) ?>' === categoryFilter) && '<?= addslashes(strtolower($item['name'])) ?> <?= addslashes(strtolower($item['category_name'])) ?>'.includes(search.toLowerCase())">
                                <td class="px-0 md:px-8 py-2 md:py-5 flex justify-center md:table-cell">
                                    <div
                                        class="relative w-20 h-20 md:w-16 md:h-16 rounded-2xl overflow-hidden border border-gray-100 group-hover:scale-105 transition-transform duration-500 mb-2 md:mb-0">
                                        <img src="<?= strpos($item['image_url'], 'http') === 0 ? $item['image_url'] : '../' . htmlspecialchars($item['image_url']) ?>"
                                            class="w-full h-full object-cover">
                                    </div>
                                </td>
                                <td
                                    class="px-0 md:px-8 py-1 md:py-5 flex flex-col items-center md:items-start text-center md:text-left md:table-cell">
                                    <h4 class="text-sm md:text-base font-bold text-gray-900 leading-snug">
                                        <?= htmlspecialchars($item['name']) ?>
                                    </h4>
                                    <p
                                        class="text-[10px] md:text-xs text-gray-400 mt-1 line-clamp-2 md:line-clamp-1 italic max-w-full md:max-w-none mx-auto md:mx-0">
                                        <?= htmlspecialchars($item['description']) ?>
                                    </p>
                                </td>
                                <td class="px-0 md:px-8 py-1.5 md:py-5 flex flex-col items-center md:items-start md:table-cell">
                                    <span
                                        class="px-2 md:px-3 py-1 bg-gray-50 text-gray-500 text-[8px] md:text-[10px] font-bold uppercase rounded-lg border border-gray-100 italic mb-1">
                                        <?= htmlspecialchars($item['category_name']) ?>
                                    </span>
                                    <span
                                        class="text-[8px] font-bold uppercase tracking-widest <?= $item['is_fasting'] ? 'text-emerald-500' : 'text-rose-500' ?> block">
                                        <?= $item['is_fasting'] ? 'Fasting' : 'Non-Fasting' ?>
                                    </span>
                                </td>
                                <td class="px-0 md:px-8 py-1.5 md:py-5 flex justify-center md:table-cell">
                                    <span
                                        class="text-sm md:text-base font-black text-orange-600 md:text-gray-800 tracking-tight">ETB
                                        <?= number_format($item['price'], 2) ?></span>
                                </td>
                                <td
                                    class="px-0 md:px-8 py-3 md:py-5 flex justify-center md:table-cell border-t md:border-t-0 border-gray-50 mt-2 md:mt-0">
                                    <div class="flex justify-center md:justify-end gap-2 w-full md:max-w-none">
                                        <a href="edit_item.php?id=<?= $item['id'] ?>"
                                            class="flex-1 md:flex-none flex items-center justify-center py-2 px-1 bg-blue-50 text-blue-600 rounded-xl hover:bg-blue-600 hover:text-white transition-all shadow-sm"
                                            title="Edit">
                                            <svg class="w-4 h-4 md:mr-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z">
                                                </path>
                                            </svg>
                                        </a>
                                        <a href="delete_item.php?id=<?= $item['id'] ?>"
                                            onclick="return confirm('Archive this masterpiece permanently?')"
                                            class="flex-1 md:flex-none flex items-center justify-center py-2 px-1 bg-rose-50 text-rose-600 rounded-xl hover:bg-rose-600 hover:text-white transition-all shadow-sm"
                                            title="Delete">
                                            <svg class="w-4 h-4 md:mr-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                    d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16">
                                                </path>
                                            </svg>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php include 'footer.php'; ?>