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
    <header class="mb-10 w-full">
        <div class="flex w-full items-center gap-2 sm:gap-4">
            <!-- Filters & Search -->
            <div class="flex min-w-0 flex-1 items-center gap-2 sm:gap-3">
                <div
                    class="relative w-[40%] min-w-0 shrink-0 overflow-hidden rounded-xl border border-gray-200 bg-white shadow-sm transition-colors hover:border-orange-500 focus-within:border-orange-500 sm:w-[38%] sm:max-w-md sm:rounded-2xl">
                    <select x-model="categoryFilter"
                        class="h-full w-full appearance-none bg-transparent py-3 pl-2 pr-6 text-xs font-semibold text-gray-700 outline-none sm:pl-4 sm:pr-8 sm:text-sm">
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
                <div class="relative group min-w-0 flex-1">
                    <span
                        class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 group-focus-within:text-orange-500 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </span>
                    <input type="text" x-model="search" placeholder="Search menu..."
                        class="w-full rounded-xl border border-gray-200 bg-white py-3 pl-8 pr-2 text-xs shadow-sm transition-all focus:border-orange-500 focus:outline-none sm:rounded-2xl sm:pl-10 sm:pr-4 sm:text-sm">
                </div>
            </div>

            <a href="add_item.php"
                class="flex shrink-0 items-center justify-center gap-1 rounded-xl bg-gradient-to-r from-amber-300 to-orange-300 px-2.5 py-3 text-xs font-bold text-[#25342d] shadow-lg shadow-orange-950/10 transition-all hover:from-amber-200 hover:to-orange-200 sm:gap-2 sm:rounded-[1.2rem] sm:px-6 sm:py-3.5 sm:text-base"
                aria-label="Add Item">
                <svg class="h-4 w-4 sm:h-5 sm:w-5" fill="none" stroke="currentColor"
                    viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                </svg>
                <span class="sm:hidden">Add</span>
                <span class="hidden sm:inline">Add Item</span>
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
                                        class="relative flex w-20 h-20 md:w-16 md:h-16 items-center justify-center rounded-2xl overflow-hidden border border-gray-100 bg-gradient-to-br from-amber-50 to-emerald-50 group-hover:scale-105 transition-transform duration-500 mb-2 md:mb-0">
                                        <?php if (!empty($item['image_url'])): ?>
                                            <img src="<?= strpos($item['image_url'], 'http') === 0 ? htmlspecialchars($item['image_url'], ENT_QUOTES, 'UTF-8') : '../' . htmlspecialchars($item['image_url'], ENT_QUOTES, 'UTF-8') ?>"
                                                alt="<?= htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8') ?>"
                                                class="w-full h-full object-cover">
                                        <?php else: ?>
                                            <svg class="h-7 w-7 text-emerald-800/35" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16.5 8.5 12l3 3L16 9l4 4.5M5 20h14a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2Z" />
                                            </svg>
                                        <?php endif; ?>
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
                                    <?php if ((int) $item['is_fasting'] === 1): ?>
                                        <span class="text-[8px] font-bold uppercase tracking-widest text-emerald-500 block">Fasting</span>
                                    <?php elseif ((int) $item['is_fasting'] === 0): ?>
                                        <span class="text-[8px] font-bold uppercase tracking-widest text-rose-500 block">Non-Fasting</span>
                                    <?php else: ?>
                                        <span class="text-[8px] font-bold uppercase tracking-widest text-gray-400 block">Not applicable</span>
                                    <?php endif; ?>
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
                                            @click.prevent="requestConfirmation('Delete this menu item permanently?', () => window.location.href = 'delete_item.php?id=<?= (int) $item['id'] ?>')"
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