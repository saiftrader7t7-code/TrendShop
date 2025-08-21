<?php
$page_title = "My Orders";
include 'common/header.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch orders with first product item for display
$sql = "
    SELECT 
        o.id, o.total_amount, o.status, o.created_at,
        (SELECT p.name FROM products p JOIN order_items oi ON p.id = oi.product_id WHERE oi.order_id = o.id LIMIT 1) as product_name,
        (SELECT p.image FROM products p JOIN order_items oi ON p.id = oi.product_id WHERE oi.order_id = o.id LIMIT 1) as product_image
    FROM orders o
    WHERE o.user_id = ?
    ORDER BY o.created_at DESC
";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$orders_result = $stmt->get_result();

$active_orders = [];
$past_orders = [];
while ($order = $orders_result->fetch_assoc()) {
    if (in_array($order['status'], ['Delivered', 'Cancelled'])) {
        $past_orders[] = $order;
    } else {
        $active_orders[] = $order;
    }
}
?>
<div class="container mx-auto px-4 pt-4 pb-20">
    <h1 class="text-2xl font-bold text-gray-800 mb-4">My Orders</h1>

    <!-- Tabs -->
    <div class="flex border-b mb-4">
        <button id="active-tab" class="flex-1 py-2 text-center font-semibold text-blue-600 border-b-2 border-blue-600">Active Orders</button>
        <button id="history-tab" class="flex-1 py-2 text-center font-semibold text-gray-500">Order History</button>
    </div>

    <!-- Active Orders Content -->
    <div id="active-content">
        <?php if (count($active_orders) > 0): ?>
            <?php foreach ($active_orders as $order): ?>
                <div class="bg-white rounded-lg shadow-sm mb-4 p-4">
                    <div class="flex">
                        <img src="<?= $order['product_image'] ? 'uploads/products/'.$order['product_image'] : 'https://via.placeholder.com/80' ?>" class="w-16 h-16 rounded-md object-cover">
                        <div class="ml-4 flex-1">
                            <h3 class="font-bold text-gray-800 truncate"><?= htmlspecialchars($order['product_name']) ?></h3>
                            <p class="text-sm text-gray-500">Order #<?= $order['id'] ?></p>
                            <p class="text-md font-semibold text-gray-700 mt-1">₹<?= number_format($order['total_amount']) ?></p>
                        </div>
                    </div>
                    <!-- Horizontal Progress Tracker -->
                    <?php
                        $status = $order['status'];
                        $placed_class = 'text-blue-600';
                        $dispatch_class = 'text-gray-400';
                        $deliver_class = 'text-gray-400';
                        $progress_width = 'w-0';

                        if ($status == 'Dispatched') {
                            $dispatch_class = 'text-blue-600';
                            $progress_width = 'w-1/2';
                        } elseif ($status == 'Delivered') {
                            $dispatch_class = 'text-blue-600';
                            $deliver_class = 'text-blue-600';
                            $progress_width = 'w-full';
                        }
                    ?>
                    <div class="mt-4">
                        <div class="relative w-full h-1 bg-gray-200 rounded-full">
                            <div class="absolute top-0 left-0 h-1 bg-blue-600 rounded-full transition-all duration-500 <?= $progress_width ?>"></div>
                        </div>
                        <div class="flex justify-between text-center mt-2">
                            <div class="w-1/3">
                                <i class="fas fa-box-open <?= $placed_class ?>"></i>
                                <p class="text-xs font-semibold <?= $placed_class ?>">Placed</p>
                            </div>
                            <div class="w-1/3">
                                <i class="fas fa-truck <?= $dispatch_class ?>"></i>
                                <p class="text-xs font-semibold <?= $dispatch_class ?>">Dispatched</p>
                            </div>
                            <div class="w-1/3">
                                <i class="fas fa-check-circle <?= $deliver_class ?>"></i>
                                <p class="text-xs font-semibold <?= $deliver_class ?>">Delivered</p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-center text-gray-500 py-10">You have no active orders.</p>
        <?php endif; ?>
    </div>

    <!-- Order History Content -->
    <div id="history-content" class="hidden">
        <?php if (count($past_orders) > 0): ?>
             <?php foreach ($past_orders as $order): ?>
                <div class="bg-white rounded-lg shadow-sm mb-4 p-4 opacity-75">
                     <div class="flex">
                        <img src="<?= $order['product_image'] ? 'uploads/products/'.$order['product_image'] : 'https://via.placeholder.com/80' ?>" class="w-16 h-16 rounded-md object-cover">
                        <div class="ml-4 flex-1">
                            <h3 class="font-bold text-gray-800 truncate"><?= htmlspecialchars($order['product_name']) ?></h3>
                             <p class="text-sm text-gray-500">Order #<?= $order['id'] ?></p>
                            <span class="text-xs font-semibold px-2 py-0.5 rounded-full <?= $order['status'] == 'Delivered' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
                                <?= $order['status'] ?> on <?= date('d M Y', strtotime($order['created_at'])) ?>
                            </span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p class="text-center text-gray-500 py-10">Your order history is empty.</p>
        <?php endif; ?>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const activeTab = document.getElementById('active-tab');
    const historyTab = document.getElementById('history-tab');
    const activeContent = document.getElementById('active-content');
    const historyContent = document.getElementById('history-content');

    activeTab.addEventListener('click', () => {
        activeContent.classList.remove('hidden');
        historyContent.classList.add('hidden');
        activeTab.classList.add('text-blue-600', 'border-blue-600');
        historyTab.classList.remove('text-blue-600', 'border-blue-600');
    });

    historyTab.addEventListener('click', () => {
        historyContent.classList.remove('hidden');
        activeContent.classList.add('hidden');
        historyTab.classList.add('text-blue-600', 'border-blue-600');
        activeTab.classList.remove('text-blue-600', 'border-blue-600');
    });
});
</script>

<?php include 'common/bottom.php'; ?>