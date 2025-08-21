<?php 
$page_title = "Products";
include 'common/header.php';

// Base query
$sql = "SELECT * FROM products";
$where_clauses = [];
$params = [];
$types = '';

// Category filter
if (isset($_GET['cat_id']) && is_numeric($_GET['cat_id'])) {
    $where_clauses[] = "cat_id = ?";
    $params[] = $_GET['cat_id'];
    $types .= 'i';
    
    // Get category name for title
    $cat_stmt = $conn->prepare("SELECT name FROM categories WHERE id = ?");
    $cat_stmt->bind_param("i", $_GET['cat_id']);
    $cat_stmt->execute();
    $cat_result = $cat_stmt->get_result();
    if($cat = $cat_result->fetch_assoc()){
        $page_title = htmlspecialchars($cat['name']);
    }
}

// Search filter
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $where_clauses[] = "name LIKE ?";
    $search_term = '%' . $_GET['search'] . '%';
    $params[] = $search_term;
    $types .= 's';
}

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

// Sorting
$sort_order = " ORDER BY created_at DESC"; // Default sort
if (isset($_GET['sort'])) {
    if ($_GET['sort'] == 'price_asc') {
        $sort_order = " ORDER BY price ASC";
    } elseif ($_GET['sort'] == 'price_desc') {
        $sort_order = " ORDER BY price DESC";
    }
}
$sql .= $sort_order;

$stmt = $conn->prepare($sql);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$products_result = $stmt->get_result();
?>

<div class="container mx-auto px-4 pt-4 pb-20">
    <h1 class="text-2xl font-bold text-gray-800 mb-4"><?= $page_title ?></h1>

    <!-- Filters -->
    <div class="flex justify-between items-center mb-4 bg-white p-2 rounded-lg shadow-sm">
        <span class="text-sm font-medium text-gray-600">Sort by:</span>
        <select id="sort-filter" class="text-sm border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
            <option value="newest" <?= (!isset($_GET['sort']) || $_GET['sort'] == 'newest') ? 'selected' : '' ?>>Newest</option>
            <option value="price_asc" <?= (isset($_GET['sort']) && $_GET['sort'] == 'price_asc') ? 'selected' : '' ?>>Price: Low to High</option>
            <option value="price_desc" <?= (isset($_GET['sort']) && $_GET['sort'] == 'price_desc') ? 'selected' : '' ?>>Price: High to Low</option>
        </select>
    </div>

    <!-- Products Grid -->
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4">
        <?php if ($products_result->num_rows > 0): ?>
            <?php while($prod = $products_result->fetch_assoc()): ?>
                <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                    <a href="product_detail.php?id=<?= $prod['id'] ?>">
                        <div class="w-full h-32 sm:h-40 bg-gray-200">
                            <img src="<?= $prod['image'] ? 'uploads/products/'.$prod['image'] : 'https://via.placeholder.com/150' ?>" alt="<?= htmlspecialchars($prod['name']) ?>" class="w-full h-full object-cover">
                        </div>
                        <div class="p-3">
                            <h3 class="text-sm font-semibold text-gray-800 truncate"><?= htmlspecialchars($prod['name']) ?></h3>
                            <p class="text-lg font-bold text-blue-600 mt-1">₹<?= number_format($prod['price']) ?></p>
                            <span class="text-xs text-gray-500"><?= $prod['stock'] > 0 ? 'In Stock' : 'Out of Stock' ?></span>
                        </div>
                    </a>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="col-span-full text-center text-gray-500 mt-8">No products found matching your criteria.</p>
        <?php endif; ?>
    </div>
</div>

<script>
document.getElementById('sort-filter').addEventListener('change', function() {
    const selectedSort = this.value;
    const url = new URL(window.location);
    if (selectedSort !== 'newest') {
        url.searchParams.set('sort', selectedSort);
    } else {
        url.searchParams.delete('sort');
    }
    window.location.href = url.toString();
});
</script>

<?php include 'common/bottom.php'; ?>```
---

I will now proceed with `product_detail.php`, `cart.php`, `checkout.php`, `order.php`, and `profile.php` in the next response.