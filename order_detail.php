<?php
// admin/order_detail.php

// Step 1: Include the central authentication file at the very top.
require_once 'common/auth.php';

// --- AJAX HANDLER FOR STATUS UPDATE ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    header('Content-Type: application/json');
    // We need the database connection, which is included via header.php.
    // So, we include the config file manually here for the AJAX request.
    require_once __DIR__ . '/../common/config.php';
    
    $response = ['success' => false, 'message' => 'Invalid Request'];
    $order_id = intval($_POST['order_id'] ?? 0);
    $new_status = $_POST['status'] ?? '';

    if ($order_id > 0 && !empty($new_status)) {
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $order_id);
        if($stmt->execute()){
            $response = ['success' => true, 'message' => 'Order status updated successfully!'];
        } else {
            $response['message'] = 'Failed to update order status.';
        }
    }

    echo json_encode($response);
    exit();
}

// --- HTML PAGE DISPLAY ---

// Check if an Order ID is provided in the URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // If no ID, redirect back to the orders list
    header("Location: order.php");
    exit();
}
$order_id = intval($_GET['id']);

// Step 2: Set the page title and include the header.
$admin_page_title = 'Order Details #' . $order_id;
include 'common/header.php';

// Step 3: Fetch order details from the database
// Fetch main order info and user info
$stmt = $conn->prepare("
    SELECT o.*, u.name as user_name, u.email as user_email, u.phone as user_phone 
    FROM orders o 
    JOIN users u ON o.user_id = u.id 
    WHERE o.id = ?
");
$stmt->bind_param("i", $order_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();

// If order is not found, show a message
if (!$order) {
    echo '<div class="bg-red-100 text-red-700 p-4 rounded-lg">Order not found.</div>';
    include 'common/bottom.php';
    exit();
}

// Fetch items associated with this order
$items_stmt = $conn->prepare("
    SELECT oi.*, p.name as product_name, p.image as product_image 
    FROM order_items oi 
    JOIN products p ON oi.product_id = p.id 
    WHERE oi.order_id = ?
");
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$items_result = $items_stmt->get_result();
?>

<!-- HTML content for the order details page -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Order Items Panel -->
    <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-xl font-bold mb-4">Items in Order #<?= $order_id ?></h2>
        <div class="divide-y divide-gray-200">
            <?php while($item = $items_result->fetch_assoc()): ?>
            <div class="flex items-center py-4">
                <img src="../uploads/products/<?= htmlspecialchars($item['product_image']) ?>" class="w-16 h-16 rounded-md object-cover flex-shrink-0">
                <div class="flex-1 ml-4">
                    <p class="font-semibold text-gray-800"><?= htmlspecialchars($item['product_name']) ?></p>
                    <p class="text-sm text-gray-500">Quantity: <?= $item['quantity'] ?> | Price: ₹<?= number_format($item['price']) ?></p>
                </div>
                <p class="font-bold text-gray-800">₹<?= number_format($item['quantity'] * $item['price']) ?></p>
            </div>
            <?php endwhile; ?>
        </div>
        <div class="text-right mt-4 border-t pt-4">
            <p class="text-lg">Total Amount: <span class="font-bold text-xl text-blue-600">₹<?= number_format($order['total_amount']) ?></span></p>
        </div>
    </div>

    <!-- Customer & Status Panel -->
    <div class="lg:col-span-1 bg-white p-6 rounded-lg shadow-md h-fit space-y-4">
        <h2 class="text-xl font-bold">Customer & Status</h2>
        <div>
            <p class="font-semibold text-gray-800"><?= htmlspecialchars($order['user_name']) ?></p>
            <p class="text-sm text-gray-600"><?= htmlspecialchars($order['user_email']) ?></p>
            <p class="text-sm text-gray-600"><?= htmlspecialchars($order['user_phone']) ?></p>
        </div>
        <div class="border-t pt-4">
            <h3 class="font-semibold text-gray-800">Shipping Address</h3>
            <p class="text-sm text-gray-600 whitespace-pre-line"><?= htmlspecialchars($order['shipping_address']) ?></p>
        </div>
        <div class="border-t pt-4">
            <h3 class="font-semibold text-gray-800">Update Order Status</h3>
            <select id="status-select" class="w-full mt-1 px-4 py-2 rounded-md bg-gray-100 border-gray-300">
                <option value="Placed" <?= $order['status'] == 'Placed' ? 'selected' : '' ?>>Placed</option>
                <option value="Dispatched" <?= $order['status'] == 'Dispatched' ? 'selected' : '' ?>>Dispatched</option>
                <option value="Delivered" <?= $order['status'] == 'Delivered' ? 'selected' : '' ?>>Delivered</option>
                <option value="Cancelled" <?= $order['status'] == 'Cancelled' ? 'selected' : '' ?>>Cancelled</option>
            </select>
            <button id="update-status-btn" class="w-full mt-2 bg-blue-600 text-white font-bold py-2 rounded-md hover:bg-blue-700">Update Status</button>
            <p id="status-message" class="text-sm mt-2 text-center"></p>
        </div>
    </div>
</div>

<script>
document.getElementById('update-status-btn').addEventListener('click', async () => {
    const statusSelect = document.getElementById('status-select');
    const statusMessage = document.getElementById('status-message');
    const newStatus = statusSelect.value;
    
    const formData = new FormData();
    formData.append('action', 'update_status');
    formData.append('order_id', '<?= $order_id ?>');
    formData.append('status', newStatus);

    statusMessage.textContent = 'Updating...';
    
    const response = await window.adminAjax('order_detail.php', { method: 'POST', body: formData });
    
    if (response.success) {
        statusMessage.textContent = response.message;
        statusMessage.classList.add('text-green-600');
        statusMessage.classList.remove('text-red-600');
        // Optionally, reload the page to see the status change reflected visually
        setTimeout(() => location.reload(), 1500);
    } else {
        statusMessage.textContent = response.message || 'Failed to update.';
        statusMessage.classList.add('text-red-600');
        statusMessage.classList.remove('text-green-600');
    }
});
</script>

<?php 
// Step 4: Include the footer file.
include 'common/bottom.php'; 
?>