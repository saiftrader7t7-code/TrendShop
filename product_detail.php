<?php
include 'common/header.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<p class='text-center p-8'>Invalid product ID.</p>";
    include 'common/bottom.php';
    exit();
}

$product_id = intval($_GET['id']);

// Handle Add to Cart AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_to_cart') {
    header('Content-Type: application/json');
    $p_id = intval($_POST['product_id']);
    $qty = intval($_POST['quantity']);
    
    if ($qty > 0 && $p_id > 0) {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        // If product already in cart, update quantity. Otherwise, add it.
        if (isset($_SESSION['cart'][$p_id])) {
            $_SESSION['cart'][$p_id] += $qty;
        } else {
            $_SESSION['cart'][$p_id] = $qty;
        }
        echo json_encode(['success' => true, 'message' => 'Product added to cart!', 'cart_count' => count($_SESSION['cart'])]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid product or quantity.']);
    }
    exit();
}


// Fetch product details
$stmt = $conn->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.cat_id = c.id WHERE p.id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows === 0) {
    echo "<p class='text-center p-8'>Product not found.</p>";
    include 'common/bottom.php';
    exit();
}
$product = $result->fetch_assoc();
$page_title = htmlspecialchars($product['name']);

// Fetch related products
$related_stmt = $conn->prepare("SELECT * FROM products WHERE cat_id = ? AND id != ? LIMIT 4");
$related_stmt->bind_param("ii", $product['cat_id'], $product_id);
$related_stmt->execute();
$related_result = $related_stmt->get_result();
?>

<div class="pb-20">
    <!-- Product Image Slider -->
    <div class="relative bg-gray-200">
        <!-- In a real app, you'd have multiple images. We'll simulate with one. -->
        <img src="<?= $product['image'] ? 'uploads/products/'.$product['image'] : 'https://via.placeholder.com/400' ?>" alt="<?= $page_title ?>" class="w-full h-80 object-cover">
        <a href="javascript:history.back()" class="absolute top-4 left-4 bg-white rounded-full w-8 h-8 flex items-center justify-center shadow-md">
            <i class="fas fa-arrow-left text-gray-700"></i>
        </a>
    </div>

    <!-- Product Info -->
    <div class="p-4 bg-white">
        <p class="text-sm text-blue-500 font-semibold"><?= htmlspecialchars($product['category_name']) ?></p>
        <h1 class="text-2xl font-bold text-gray-800 mt-1"><?= $page_title ?></h1>
        <p class="text-3xl font-bold text-blue-600 my-3">₹<?= number_format($product['price']) ?></p>
        <div class="flex items-center space-x-2">
            <span class="text-sm font-medium px-2 py-1 rounded <?= $product['stock'] > 0 ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                <?= $product['stock'] > 0 ? 'In Stock' : 'Out of Stock' ?>
            </span>
            <span class="text-gray-500 text-sm">(<?= $product['stock'] ?> items left)</span>
        </div>
        
        <div class="mt-4 text-gray-600">
            <h3 class="font-semibold text-gray-800 mb-2">Description</h3>
            <p><?= nl2br(htmlspecialchars($product['description'])) ?></p>
        </div>
    </div>

    <!-- Related Products -->
    <?php if($related_result->num_rows > 0): ?>
    <div class="mt-6 px-4">
        <h2 class="text-xl font-semibold mb-3 text-gray-700">Related Products</h2>
        <div class="grid grid-cols-2 gap-4">
            <?php while($related_prod = $related_result->fetch_assoc()): ?>
            <div class="bg-white rounded-lg shadow-sm overflow-hidden">
                <a href="product_detail.php?id=<?= $related_prod['id'] ?>">
                    <div class="w-full h-32 bg-gray-200">
                        <img src="<?= $related_prod['image'] ? 'uploads/products/'.$related_prod['image'] : 'https://via.placeholder.com/150' ?>" alt="<?= htmlspecialchars($related_prod['name']) ?>" class="w-full h-full object-cover">
                    </div>
                    <div class="p-3">
                        <h3 class="text-sm font-semibold text-gray-800 truncate"><?= htmlspecialchars($related_prod['name']) ?></h3>
                        <p class="text-lg font-bold text-blue-600 mt-1">₹<?= number_format($related_prod['price']) ?></p>
                    </div>
                </a>
            </div>
            <?php endwhile; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Sticky Add to Cart Footer -->
<div class="fixed bottom-16 left-0 right-0 bg-white border-t p-3 flex items-center justify-between z-30">
    <div class="flex items-center border rounded-md">
        <button id="qty-minus" class="w-10 h-10 text-xl text-gray-600">-</button>
        <input id="quantity" type="text" value="1" readonly class="w-12 h-10 text-center font-bold border-l border-r">
        <button id="qty-plus" class="w-10 h-10 text-xl text-gray-600">+</button>
    </div>
    <button id="add-to-cart-btn" class="flex-1 ml-4 bg-blue-500 text-white font-bold py-3 rounded-lg hover:bg-blue-600 disabled:bg-gray-400" <?= $product['stock'] <= 0 ? 'disabled' : '' ?>>
        <i class="fas fa-cart-plus mr-2"></i><?= $product['stock'] > 0 ? 'Add to Cart' : 'Out of Stock' ?>
    </button>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const qtyInput = document.getElementById('quantity');
    const maxStock = <?= $product['stock'] ?>;

    document.getElementById('qty-minus').addEventListener('click', () => {
        let currentQty = parseInt(qtyInput.value);
        if (currentQty > 1) {
            qtyInput.value = currentQty - 1;
        }
    });

    document.getElementById('qty-plus').addEventListener('click', () => {
        let currentQty = parseInt(qtyInput.value);
        if (currentQty < maxStock) {
            qtyInput.value = currentQty + 1;
        }
    });

    document.getElementById('add-to-cart-btn').addEventListener('click', async function() {
        const formData = new FormData();
        formData.append('action', 'add_to_cart');
        formData.append('product_id', '<?= $product_id ?>');
        formData.append('quantity', qtyInput.value);

        const response = await window.ajax('product_detail.php?id=<?= $product_id ?>', {
            method: 'POST',
            body: formData
        });

        if (response.success) {
            alert(response.message);
            // Optionally, update cart icon count dynamically without page reload
            location.reload(); 
        } else {
            alert(response.message);
        }
    });
});
</script>

<?php include 'common/bottom.php'; ?>