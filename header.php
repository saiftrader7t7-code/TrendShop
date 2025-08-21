<?php
// /common/header.php

// Step 1: Include the config file using a reliable absolute path.
// __DIR__ is a PHP constant that gets the full directory path of the current file.
// This ensures that 'config.php' is always found correctly because they are in the same folder.
require_once __DIR__ . '/config.php'; 
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Quick Kart</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css"/>
    <style>
        html, body {
            height: 100%;
            overflow-x: hidden;
            -webkit-tap-highlight-color: transparent;
        }
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</head>
<body class="bg-gray-50 text-gray-800 select-none">
    <!-- App Wrapper -->
    <div id="app-wrapper" class="relative min-h-screen pb-20">

        <!-- Loading Modal -->
        <div id="loading-modal" class="fixed inset-0 bg-black bg-opacity-50 z-[999] flex items-center justify-center hidden">
            <div class="bg-white p-5 rounded-lg flex items-center space-x-3">
                <div class="w-5 h-5 border-4 border-blue-500 border-dotted rounded-full animate-spin"></div>
                <span class="text-gray-700">Loading...</span>
            </div>
        </div>

        <?php 
        // Include the sidebar, also using an absolute path for reliability.
        include __DIR__ . '/sidebar.php'; 
        ?>

        <!-- Main Content -->
        <main id="main-content" class="transition-transform duration-300">
            <!-- Page Header -->
            <?php if (isset($page_title)): ?>
            <header class="sticky top-0 bg-white z-40 shadow-sm">
                <div class="container mx-auto px-4 py-3 flex items-center justify-between">
                     <button id="menu-btn" class="text-xl text-gray-700">
                        <i class="fas fa-bars"></i>
                    </button>
                    <h1 class="text-lg font-bold text-gray-800"><?= htmlspecialchars($page_title) ?></h1>
                    <div class="w-8"></div> <!-- Placeholder for alignment -->
                </div>
            </header>
            <?php endif; ?>