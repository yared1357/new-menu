<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>
        <?= isset($page_title) ? $page_title . " | Tasty Pizza & Burger" : "Admin Panel | Tasty Pizza & Burger" ?>
    </title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script defer src="../assets/js/alpine.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Outfit', sans-serif;
            background-color: #f3f4f6;
            color: #1f2937;
        }

        .glass-card {
            background: #ffffff;
            border: 1px solid rgba(0, 0, 0, 0.05);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.05);
        }

        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease-in-out;
            }

            .sidebar.active {
                transform: translateX(0);
            }
        }

        [x-cloak] {
            display: none !important;
        }
    </style>
</head>

<body class="bg-gray-50" x-data="{ sidebarOpen: false, search: '' }">
    <!-- Mobile Header -->
    <div class="lg:hidden bg-white border-b border-gray-100 p-4 flex justify-between items-center sticky top-0 z-40">
        <div class="flex items-center gap-2">
            <?php
            $admin_name = $_SESSION['admin_user'] ?? 'Admin';
            $admin_initial = strtoupper(substr($admin_name, 0, 1));
            ?>
            <div
                class="w-8 h-8 bg-orange-500 rounded-lg flex items-center justify-center font-bold text-white text-sm shadow-sm shadow-orange-500/20">
                <?= $admin_initial ?>
            </div>
            <span class="font-bold text-gray-800"><?= htmlspecialchars(ucfirst($admin_name)) ?></span>
        </div>
        <button @click="sidebarOpen = !sidebarOpen" class="p-2 hover:bg-gray-100 rounded-xl">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"></path>
            </svg>
        </button>
    </div>

    <div class="flex">
        <?php include 'sidebar.php'; ?>
        <main class="flex-1 flex flex-col min-h-screen">
            <div class="p-8 lg:p-12 flex-1">