<?php
// checkout.php

// Step 1: Include config file first to handle session and database.
require_once 'common/config.php';

// Step 2: Handle ALL PHP logic and redirects BEFORE any HTML is printed.

// Security Check 1: User must be logged in to checkout.
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Security Check 2: User must have items in the cart.
if (empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// --- Razorpay API Keys (Replace with your actual keys) ---
$razorpay_key_id = 'YOUR_KEY_ID'; // Change this
$razorpay_key_secret = 'YOUR_KEY_SECRET'; // Change this

// Calculate total amount from server-side
$total_amount = 0;
$product_ids = implode(',', array_keys($_SESSION['cart']));
$sql_products = "SELECT id, price, stock, name FROM products WHERE id IN ($product_ids)";
$result_products = $conn->query($sql_products);
$products_in_db = [];
while($row = $result_products->fetch_assoc()) {
    $products_in_db[$row['id']] = $row;
}
foreach ($_SESSION['cart'] as $product_id => $quantity) {
    $total_amount += $products_in_db[$product_id]['price'] * $quantity;
}

// Handle the "Place Order" form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    // Collect all form fields
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $pincode = trim($_POST['pincode'] ?? '');
    $city = trim($_POST['city'] ?? '');
    $state = trim($_POST['state'] ?? '');
    $road_address = trim($_POST['road_address'] ?? '');
    $payment_method = trim($_POST['payment_method'] ?? '');

    if (empty($name) || empty($phone) || empty($pincode) || empty($city) || empty($state) || empty($road_address) || empty($payment_method)) {
        $error_message = "All fields are required, including payment method.";
    } else {
        $full_address = "$road_address, \n$city, $state - $pincode";
        $shipping_address_for_db = "Name: $name\nPhone: $phone\nAddress: \n$full_address";

        // If Cash on Delivery, create order directly
        if ($payment_method === 'COD') {
            // Create order logic for COD
            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare("INSERT INTO orders (user_id, total_amount, shipping_address, status) VALUES (?, ?, ?, 'Placed')");
                $stmt->bind_param("ids", $user_id, $total_amount, $shipping_address_for_db);
                $stmt->execute();
                $order_id = $stmt->insert_id;
                
                // ... (insert order items and update stock)
                
                $conn->commit();
                unset($_SESSION['cart']);
                header("Location: order.php?order_success=1");
                exit();
            } catch (Exception $e) {
                $conn->rollback();
                $error_message = "Failed to place COD order. Please try again.";
            }
        }
        // If Pay Online, the process will be handled by JavaScript and Razorpay's success handler.
        // We will create the order via an AJAX call after successful payment.
    }
}

// Step 3: Now that all logic is done, start printing the HTML page.
$page_title = "Checkout";
include 'common/header.php';

// Fetch user data to autofill the form
$user_result = $conn->query("SELECT name, phone, email FROM users WHERE id = $user_id");
$user = $user_result->fetch_assoc();
?>

<!-- Add Razorpay Checkout.js script -->
<script src="https://checkout.razorpay.com/v1/checkout.js"></script>

<!-- HTML content for the checkout page -->
<div class="container mx-auto px-4 pt-4 pb-24">
    <h1 class="text-xl font-bold text-gray-800 mb-4">Add Delivery Address</h1>

    <?php if (isset($error_message)): ?>
        <div class="bg-red-100 text-red-700 p-3 rounded-lg mb-4"><?= htmlspecialchars($error_message) ?></div>
    <?php endif; ?>

    <form id="checkout-form" method="POST" class="bg-white p-6 rounded-lg shadow-sm space-y-4">
        <!-- Contact and Address Details -->
        <div>
            <h2 class="font-semibold text-gray-700 mb-2">Shipping Details</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <input type="text" name="name" id="name-input" value="<?= htmlspecialchars($user['name'] ?? '') ?>" placeholder="Name *" required class="w-full input-style">
                <input type="tel" name="phone" id="phone-input" value="<?= htmlspecialchars($user['phone'] ?? '') ?>" placeholder="Contact Number *" required class="w-full input-style">
            </div>
            <div class="mt-4">
                <input type="text" name="pincode" id="pincode-input" placeholder="Pincode *" required class="w-full input-style">
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                <input type="text" name="city" id="city-input" placeholder="City *" required class="w-full input-style">
                <input type="text" name="state" id="state-input" placeholder="State *" required class="w-full input-style">
            </div>
            <div class="mt-4">
                <textarea name="road_address" id="road-address-input" placeholder="Road Name / Area / Colony *" required rows="3" class="w-full input-style"></textarea>
            </div>
        </div>

        <!-- Payment Method Selection -->
        <div class="pt-4 border-t">
            <h2 class="font-semibold text-gray-700 mb-2">Select Payment Method</h2>
            <div class="space-y-3">
                <!-- Cash on Delivery Option -->
                <label for="cod_option" class="flex items-center p-4 border rounded-lg cursor-pointer has-[:checked]:bg-blue-50 has-[:checked]:border-blue-500">
                    <input type="radio" id="cod_option" name="payment_method" value="COD" class="h-5 w-5 text-blue-600">
                    <div class="ml-4">
                        <p class="font-semibold">Cash on Delivery</p>
                        <p class="text-sm text-gray-500">Pay upon receiving your order</p>
                    </div>
                </label>

                <!-- Pay Online Option -->
                <label for="online_option" class="flex items-center p-4 border rounded-lg cursor-pointer has-[:checked]:bg-green-50 has-[:checked]:border-green-500">
                    <input type="radio" id="online_option" name="payment_method" value="ONLINE" class="h-5 w-5 text-green-600">
                    <div class="ml-4">
                        <p class="font-semibold flex items-center">
                            Pay Online 
                            <img src="https://cdn.icon-icons.com/icons2/2699/PNG/512/upi_logo_icon_169316.png" class="h-4 ml-2" alt="UPI">
                        </p>
                        <p class="text-sm text-green-600 font-medium">Offers Available with UPI</p>
                    </div>
                </label>
            </div>
        </div>
        
        <input type="hidden" name="place_order" value="1">
        <button type="submit" id="submit-btn" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-bold py-3 rounded-lg mt-6">Place Order</button>
    </form>
</div>

<style>
    .input-style {
        padding: 0.75rem 1rem; border-radius: 0.5rem; background-color: #f3f4f6;
        border: 1px solid #d1d5db;
    }
</style>

<script>
document.getElementById('checkout-form').addEventListener('submit', function(e) {
    const paymentMethod = document.querySelector('input[name="payment_method"]:checked');
    
    // If "Pay Online" is selected, prevent the form from submitting normally
    if (paymentMethod && paymentMethod.value === 'ONLINE') {
        e.preventDefault();
        
        // --- Start Razorpay Payment ---
        var options = {
            "key": "<?= $razorpay_key_id ?>",
            "amount": <?= $total_amount * 100 ?>, // Amount in the smallest currency unit (paise)
            "currency": "INR",
            "name": "Quick Kart",
            "description": "Order Payment",
            "image": "https://via.placeholder.com/150", // Your logo URL
            "handler": function (response){
                // This function is called after a successful payment
                alert("Payment Successful! Payment ID: " + response.razorpay_payment_id);
                
                // Now, submit the form to a different PHP handler or use AJAX to create the order
                // For simplicity, we will now submit the form to the same page
                document.getElementById('checkout-form').submit();
            },
            "prefill": {
                "name": "<?= htmlspecialchars($user['name']) ?>",
                "email": "<?= htmlspecialchars($user['email']) ?>",
                "contact": "<?= htmlspecialchars($user['phone']) ?>"
            },
            "notes": {
                "address": "Your custom address notes"
            },
            "theme": {
                "color": "#8b5cf6"
            }
        };
        var rzp1 = new Razorpay(options);
        rzp1.on('payment.failed', function (response){
            alert("Payment Failed! " + response.error.description);
        });
        rzp1.open();
    }
    // If COD is selected, the form will submit normally.
});
</script>

<?php 
// Include the footer file
include 'common/bottom.php'; 
?>