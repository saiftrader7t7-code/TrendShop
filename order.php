<?php
// admin/order.php

// Step 1: Include the central authentication file at the very top.
require_once 'common/auth.php';

// Step 2: Set the page title and include the header.
$admin_page_title = 'Manage Orders';
include 'common/header.php';

// Step 3: Fetch all orders from the database
$orders_result = $conn->query("
    SELECT o.id, o.total_amount, o.status, o.created_at, u.name as user_name
    FROM orders o
    JOIN users u ON o.user_id = u.id
    ORDER BY o.created_at DESC
");
?>

<!-- HTML content for the orders page -->
<div class="bg-white p-6 rounded-lg shadow-md">
    <div class="overflow-x-auto">
        <table class="w-full text-left table-auto">
            <thead>
                <tr class="border-b bg-gray-50">
                    <th class="p-3 text-sm font-semibold">Order ID</th>
                    <th class="p-3 text-sm font-semibold">User</th>
                    <th class="p-3 text-sm font-semibold">Amount</th>
                    <th class="p-3 text-sm font-semibold">Status</th>
                    <th class="p-3 text-sm font-semibold">Date</th>
                    <th class="p-3 text-sm font-semibold">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php while($order = $orders_result->fetch_assoc()): ?>
                <tr class="border-b hover:bg-gray-50">
                    <td class="p-3 font-semibold text-blue-600">#<?= $order['id'] ?></td>
                    <td class="p-3"><?= htmlspecialchars($order['user_name']) ?></td>
                    <td class="p-3">₹<?= number_format($order['total_amount']) ?></td>
                    <td class="p-3">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full
                            <?php 
                                switch($order['status']){
                                    case 'Placed': echo 'bg-blue-100 text-blue-800'; break;
                                    case 'Dispatched': echo 'bg-yellow-100 text-yellow-800'; break;
                                    case 'Delivered': echo 'bg-green-100 text-green-800'; break;
                                    case 'Cancelled': echo 'bg-red-100 text-red-800'; break;
                                    default: echo 'bg-gray-100 text-gray-800'; break;
                                }
                            ?>">
                            <?= htmlspecialchars($order['status']) ?>
                        </span>
                    </td>
                    <td class="p-3 text-sm text-gray-600"><?= date('d M Y, h:i A', strtotime($order['created_at'])) ?></td>
                    <td class="p-3">
                        <a href="order_detail.php?id=<?= $order['id'] ?>" class="text-indigo-600 font-semibold hover:underline text-sm">View Details</a>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<?php 
// Step 4: Include the footer file.
include 'common/bottom.php'; 
?>