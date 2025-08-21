<!-- Sidebar Overlay -->
<div id="sidebar-overlay" class="fixed inset-0 bg-black bg-opacity-50 z-40 hidden"></div>

<!-- Sidebar Menu -->
<aside id="sidebar" class="fixed top-0 left-0 w-64 h-full bg-white shadow-lg z-50 transform -translate-x-full transition-transform duration-300 ease-in-out">
    <div class="p-4 border-b">
        <h2 class="text-2xl font-bold text-blue-600">Quick Kart</h2>
        <?php if(isset($_SESSION['user_id'])): 
            $user_id = $_SESSION['user_id'];
            $result = $conn->query("SELECT name FROM users WHERE id = $user_id");
            $user = $result->fetch_assoc();
        ?>
            <p class="text-sm text-gray-600 mt-2">Hello, <?= htmlspecialchars($user['name']) ?></p>
        <?php else: ?>
            <p class="text-sm text-gray-600 mt-2">Welcome Guest</p>
        <?php endif; ?>
    </div>
    <nav class="mt-4">
        <a href="index.php" class="block px-4 py-3 text-gray-700 hover:bg-gray-100"><i class="fas fa-home w-6 mr-2"></i>Home</a>
        <a href="product.php" class="block px-4 py-3 text-gray-700 hover:bg-gray-100"><i class="fas fa-box-open w-6 mr-2"></i>All Products</a>
        <a href="order.php" class="block px-4 py-3 text-gray-700 hover:bg-gray-100"><i class="fas fa-receipt w-6 mr-2"></i>My Orders</a>
        <a href="profile.php" class="block px-4 py-3 text-gray-700 hover:bg-gray-100"><i class="fas fa-user-cog w-6 mr-2"></i>Profile</a>
        <?php if(isset($_SESSION['user_id'])): ?>
            <a href="profile.php?logout=1" class="block px-4 py-3 text-red-500 hover:bg-red-50"><i class="fas fa-sign-out-alt w-6 mr-2"></i>Logout</a>
        <?php else: ?>
            <a href="login.php" class="block px-4 py-3 text-green-500 hover:bg-green-50"><i class="fas fa-sign-in-alt w-6 mr-2"></i>Login</a>
        <?php endif; ?>
    </nav>
</aside>