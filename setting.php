<?php
// admin/setting.php

// Step 1: Include the central authentication file at the very top.
require_once 'common/auth.php';

// --- HANDLE LOGOUT REQUEST ---
// It's good practice to have the logout logic in a central place like this.
if (isset($_GET['logout']) && $_GET['logout'] == '1') {
    // Unset all of the session variables.
    $_SESSION = array();

    // Destroy the session.
    session_destroy();

    // Redirect to login page.
    header("location: login.php");
    exit;
}


// --- HANDLE PASSWORD CHANGE FORM SUBMISSION ---
$message = '';
$is_success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $admin_id = $_SESSION['admin_id'];

    if (empty($current_pass) || empty($new_pass)) {
        $message = 'All fields are required.';
    } elseif (strlen($new_pass) < 6) {
        $message = 'New password must be at least 6 characters long.';
    } else {
        $stmt = $conn->prepare("SELECT password FROM admin WHERE id = ?");
        $stmt->bind_param("i", $admin_id);
        $stmt->execute();
        $admin = $stmt->get_result()->fetch_assoc();

        if ($admin && password_verify($current_pass, $admin['password'])) {
            // Current password is correct, now hash and update the new one.
            $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
            $update_stmt = $conn->prepare("UPDATE admin SET password = ? WHERE id = ?");
            $update_stmt->bind_param("si", $hashed_pass, $admin_id);
            if ($update_stmt->execute()) {
                $message = 'Password changed successfully!';
                $is_success = true;
            } else {
                 $message = 'Error updating password. Please try again.';
            }
        } else {
            $message = 'Incorrect current password.';
        }
    }
}


// --- HTML PAGE DISPLAY ---

// Step 2: Set the page title and include the header.
$admin_page_title = 'Settings';
include 'common/header.php';
?>

<!-- HTML content for the settings page -->
<div class="max-w-lg mx-auto bg-white p-6 rounded-lg shadow-md">
    <h2 class="text-xl font-bold mb-4">Change Admin Password</h2>

    <?php if ($message): ?>
        <div class="p-3 mb-4 rounded-md text-sm <?= $is_success ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' ?>">
            <?= htmlspecialchars($message) ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="setting.php" class="space-y-4">
        <div>
            <label for="current_password" class="font-medium text-gray-700">Current Password</label>
            <input type="password" name="current_password" id="current_password" required class="w-full mt-1 px-4 py-2 rounded-md bg-gray-100 border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200">
        </div>
        <div>
            <label for="new_password" class="font-medium text-gray-700">New Password</label>
            <input type="password" name="new_password" id="new_password" required class="w-full mt-1 px-4 py-2 rounded-md bg-gray-100 border-gray-300 focus:border-blue-500 focus:ring focus:ring-blue-200">
        </div>
        <button type="submit" class="w-full bg-blue-600 text-white font-bold py-2 rounded-md hover:bg-blue-700">Update Password</button>
    </form>
</div>

<?php 
// Step 3: Include the footer file.
include 'common/bottom.php'; 
?>