<?php
// admin/index.php

// Step 1: Include the central authentication file at the very top.
require_once 'common/auth.php';

// Step 2: Set the page title and include the header.
$admin_page_title = 'Dashboard';
include 'common/header.php';

// Step 3: Fetch ALL statistics from the database for the dashboard cards.
$total_users = $conn->query("SELECT COUNT(id) as count FROM users")->fetch_assoc()['count'];
$total_orders = $conn->query("SELECT COUNT(id) as count FROM orders")->fetch_assoc()['count'];
$total_revenue = $conn->query("SELECT SUM(total_amount) as sum FROM orders WHERE status = 'Delivered'")->fetch_assoc()['sum'];
$active_products = $conn->query("SELECT COUNT(id) as count FROM products WHERE stock > 0")->fetch_assoc()['count'];
$pending_orders = $conn->query("SELECT COUNT(id) as count FROM orders WHERE status = 'Placed'")->fetch_assoc()['count'];
$cancellations = $conn->query("SELECT COUNT(id) as count FROM orders WHERE status = 'Cancelled'")->fetch_assoc()['count'];
?>

<!-- Step 4: Display the HTML content for the dashboard with ALL cards. -->
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
    <!-- Total Users Card -->
    <div class="bg-white p-5 rounded-lg shadow-md flex items-center space-x-4">
        <div class="bg-blue-100 text-blue-600 p-3 rounded-full">
            <i class="fas fa-users text-2xl"></i>
        </div>
        <div>
            <p class="text-gray-500 text-sm font-medium">Total Users</p>
            <p class="text-2xl font-bold text-gray-800"><?= $total_users ?></p>
        </div>
    </div>

    <!-- Total Orders Card -->
    <div class="bg-white p-5 rounded-lg shadow-md flex items-center space-x-4">
        <div class="bg-green-100 text-green-600 p-3 rounded-full">
            <i class="fas fa-shopping-cart text-2xl"></i>
        </div>
        <div>
            <p class="text-gray-500 text-sm font-medium">Total Orders</p>
            <p class="text-2xl font-bold text-gray-800"><?= $total_orders ?></p>
        </div>
    </div>

    <!-- Total Revenue Card -->
    <div class="bg-white p-5 rounded-lg shadow-md flex items-center space-x-4">
        <div class="bg-yellow-100 text-yellow-600 p-3 rounded-full">
            <i class="fas fa-rupee-sign text-2xl"></i>
        </div>
        <div>
            <p class="text-gray-500 text-sm font-medium">Total Revenue</p>
            <p class="text-2xl font-bold text-gray-800">₹<?= number_format($total_revenue ?? 0, 2) ?></p>
        </div>
    </div>

    <!-- Active Products Card -->
    <div class="bg-white p-5 rounded-lg shadow-md flex items-center space-x-4">
        <div class="bg-purple-100 text-purple-600 p-3 rounded-full">
            <i class="fas fa-box-open text-2xl"></i>
        </div>
        <div>
            <p class="text-gray-500 text-sm font-medium">Active Products</p>
            <p class="text-2xl font-bold text-gray-800"><?= $active_products ?></p>
        </div>
    </div>

    <!-- Pending Orders Card -->
     <div class="bg-white p-5 rounded-lg shadow-md flex items-center space-x-4">
        <div class="bg-indigo-100 text-indigo-600 p-3 rounded-full">
            <i class="fas fa-truck-loading text-2xl"></i>
        </div>
        <div>
            <p class="text-gray-500 text-sm font-medium">Pending Orders</p>
            <p class="text-2xl font-bold text-gray-800"><?= $pending_orders ?></p>
        </div>
    </div>

    <!-- Cancellations Card -->
    <div class="bg-white p-5 rounded-lg shadow-md flex items-center space-x-4">
        <div class="bg-red-100 text-red-600 p-3 rounded-full">
            <i class="fas fa-times-circle text-2xl"></i>
        </div>
        <div>
            <p class="text-gray-500 text-sm font-medium">Cancellations</p>
            <p class="text-2xl font-bold text-gray-800"><?= $cancellations ?></p>
        </div>
    </div>
</div>

<!-- Quick Actions Section -->
<div class="mt-8 bg-white p-6 rounded-lg shadow-md">
    <h2 class="text-xl font-semibold mb-4">Quick Actions</h2>
    <div class="flex flex-wrap gap-4">
        <a href="product.php" class="bg-blue-500 text-white px-5 py-2 rounded-md font-semibold hover:bg-blue-600"><i class="fas fa-plus mr-2"></i>Add Product</a>
        <a href="order.php" class="bg-green-500 text-white px-5 py-2 rounded-md font-semibold hover:bg-green-600"><i class="fas fa-eye mr-2"></i>Manage Orders</a>
        <a href="user.php" class="bg-yellow-500 text-white px-5 py-2 rounded-md font-semibold hover:bg-yellow-600"><i class="fas fa-users-cog mr-2"></i>Manage Users</a>
    </div>
</div>

<?php 
// Step 5: Include the footer file.
include 'common/bottom.php'; 
?>