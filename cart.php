<?php
$page_title = "My Cart";
include 'common/header.php';

// Handle AJAX requests for cart updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    $response = ['success' => false];
    $product_id = intval($_POST['product_id']);

    if (isset($_POST['action'])) {
        if ($_POST['action'] === 'update' && isset($_POST['quantity'])) {
            $quantity = intval($_POST['quantity']);
            if ($quantity > 0 && isset($_SESSION['cart'][$product_id])) {
                $_SESSION['cart'][$product_id] = $quantity;
                $response['success'] = true;
            }
        } elseif ($_POST['action'] === 'delete') {
            if (isset($_SESSION['cart'][$product_id])) {
                unset($_SESSION['cart'][$product_id]);
                $response['success'] = true;
            }
        }
    }
    echo json_encode($response);
    exit();
}

$cart_items = [];
$total_amount = 0;
if (!empty($_SESSION['cart'])) {
    $product_ids = implode(',', array_keys($_SESSION['cart']));
    $sql = "SELECT id, name, price, image, stock FROM products WHERE id IN ($product_ids)";
    $result = $conn->query($sql);

    while ($product = $result->fetch_assoc()) {
        $quantity = $_SESSION['cart'][$product['id']];
        $cart_items[] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => $product['price'],
            'image' => $product['image'],
            'stock' => $product['stock'],
            'quantity' => $quantity
        ];
        $total_amount += $product['price'] * $quantity;
    }
}
?>
<div class="container mx-auto px-4 pt-4 pb-28">
    <h1 class="text-2xl font-bold text-gray-800 mb-4">Shopping Cart</h1>

    <div id="cart-items-container">
    <?php if (!empty($cart_items)): ?>
        <?php foreach ($cart_items as $item): ?>
        <div class="flex items-center bg-white p-3 rounded-lg shadow-sm mb-3" id="item-<?= $item['id'] ?>">
            <img src="<?= $item['image'] ? 'uploads/products/'.$item['image'] : 'https://via.placeholder.com/80' ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="w-20 h-20 rounded-md object-cover">
            <div class="flex-1 ml-4">
                <h3 class="font-semibold text-gray-800"><?= htmlspecialchars($item['name']) ?></h3>
                <p class="text-blue-600 font-bold">₹<?= number_format($item['price']) ?></p>
                <div class="flex items-center mt-2">
                    <button onclick="updateQty(<?= $item['id'] ?>, -1)" class="w-6 h-6 bg-gray-200 rounded-full">-</button>
                    <input type="text" id="qty-<?= $item['id'] ?>" value="<?= $item['quantity'] ?>" readonly class="w-10 text-center font-semibold">
                    <button onclick="updateQty(<?= $item['id'] ?>, 1, <?= $item['stock'] ?>)" class="w-6 h-6 bg-gray-200 rounded-full">+</button>
                </div>
            </div>
            <button onclick="deleteItem(<?= $item['id'] ?>)" class="text-red-500 ml-4"><i class="fas fa-trash-alt"></i></button>
        </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="text-center py-16">
            <i class="fas fa-shopping-cart text-5xl text-gray-300"></i>
            <p class="mt-4 text-gray-500">Your cart is empty.</p>
            <a href="index.php" class="mt-4 inline-block bg-blue-500 text-white px-6 py-2 rounded-lg">Shop Now</a>
        </div>
    <?php endif; ?>
    </div>
</div>

<?php if (!empty($cart_items)): ?>
<div class="fixed bottom-16 left-0 right-0 bg-white border-t p-4 z-30">
    <div class="flex justify-between items-center mb-3">
        <span class="text-gray-600 font-semibold">Total</span>
        <span class="text-2xl font-bold text-blue-600" id="total-amount">₹<?= number_format($total_amount) ?></span>
    </div>
    <a href="checkout.php" class="block w-full text-center bg-blue-500 text-white font-bold py-3 rounded-lg hover:bg-blue-600">Proceed to Checkout</a>
</div>
<?php endif; ?>

<script>
async function updateCart(productId, quantity) {
    const formData = new FormData();
    formData.append('action', 'update');
    formData.append('product_id', productId);
    formData.append('quantity', quantity);
    const response = await window.ajax('cart.php', { method: 'POST', body: formData });
    if (response.success) {
        location.reload(); // Simple reload for now to update total
    } else {
        alert('Failed to update cart.');
    }
}

async function deleteItem(productId) {
    if (!confirm('Are you sure you want to remove this item?')) return;
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('product_id', productId);
    const response = await window.ajax('cart.php', { method: 'POST', body: formData });
    if (response.success) {
        document.getElementById(`item-${productId}`).remove();
        location.reload(); // Simple reload
    } else {
        alert('Failed to delete item.');
    }
}

function updateQty(productId, change, maxStock) {
    const qtyInput = document.getElementById(`qty-${productId}`);
    let newQty = parseInt(qtyInput.value) + change;
    if (newQty > 0 && (!maxStock || newQty <= maxStock)) {
        qtyInput.value = newQty;
        // Debounce update
        clearTimeout(window.updateTimeout);
        window.updateTimeout = setTimeout(() => {
            updateCart(productId, newQty);
        }, 500);
    }
}
</script>

<?php include 'common/bottom.php'; ?>