<?php 
$page_title = "Home";
include 'common/header.php'; 

// Fetch categories
$categories_result = $conn->query("SELECT * FROM categories ORDER BY name ASC LIMIT 8");

// Fetch featured products
$products_result = $conn->query("SELECT * FROM products ORDER BY created_at DESC LIMIT 8");
?>

<div class="container mx-auto px-4 pt-4 pb-20">

    <!-- Header Section -->
    <header class="flex items-center justify-between mb-4">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">Quick Kart</h1>
            <p class="text-gray-500">Your one-stop shop</p>
        </div>
        <button id="menu-btn" class="text-2xl text-gray-700 md:hidden">
            <i class="fas fa-bars"></i>
        </button>
    </header>

    <!-- Search Bar -->
    <div class="relative mb-6">
        <input type="text" placeholder="Search for products..." class="w-full pl-10 pr-4 py-3 bg-white border border-gray-200 rounded-full focus:outline-none focus:ring-2 focus:ring-blue-300">
        <i class="fas fa-search absolute left-4 top-1/2 -translate-y-1/2 text-gray-400"></i>
    </div>

    <!-- Categories Section -->
    <section class="mb-8">
        <h2 class="text-xl font-semibold mb-3 text-gray-700">Categories</h2>
        <div class="flex overflow-x-auto space-x-4 pb-4 no-scrollbar">
            <?php if ($categories_result->num_rows > 0): ?>
                <?php while($cat = $categories_result->fetch_assoc()): ?>
                    <a href="product.php?cat_id=<?= $cat['id'] ?>" class="flex-shrink-0 text-center">
                        <div class="w-20 h-20 bg-gray-100 rounded-2xl flex items-center justify-center overflow-hidden">
                            <img src="<?= $cat['image'] ? 'uploads/categories/'.$cat['image'] : 'https://via.placeholder.com/80' ?>" alt="<?= htmlspecialchars($cat['name']) ?>" class="object-cover w-full h-full">
                        </div>
                        <p class="mt-2 text-sm font-medium text-gray-600"><?= htmlspecialchars($cat['name']) ?></p>
                    </a>
                <?php endwhile; ?>
            <?php else: ?>
                <p>No categories found.</p>
            <?php endif; ?>
        </div>
    </section>

    <!-- Featured Products Section -->
    <section>
        <h2 class="text-xl font-semibold mb-3 text-gray-700">Featured Products</h2>
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
            <?php if ($products_result->num_rows > 0): ?>
                <?php while($prod = $products_result->fetch_assoc()): ?>
                    <div class="bg-white rounded-lg shadow-sm overflow-hidden group">
                        <a href="product_detail.php?id=<?= $prod['id'] ?>">
                            <div class="w-full h-32 sm:h-40 bg-gray-200">
                                <img src="<?= $prod['image'] ? 'uploads/products/'.$prod['image'] : 'https://via.placeholder.com/150' ?>" alt="<?= htmlspecialchars($prod['name']) ?>" class="w-full h-full object-cover">
                            </div>
                            <div class="p-3">
                                <h3 class="text-sm font-semibold text-gray-800 truncate"><?= htmlspecialchars($prod['name']) ?></h3>
                                <p class="text-lg font-bold text-blue-600 mt-1">₹<?= number_format($prod['price']) ?></p>
                                <button class="w-full mt-2 bg-blue-500 text-white text-xs font-bold py-2 rounded-md opacity-0 group-hover:opacity-100 transition-opacity">View Details</button>
                            </div>
                        </a>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <p class="col-span-full">No products found.</p>
            <?php endif; ?>
        </div>
    </section>
</div>

<?php include 'common/bottom.php'; ?>