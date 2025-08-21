<?php
// Step 1: Include config first to start the session
require_once 'common/config.php';

// Step 2: Handle all PHP logic and redirects BEFORE any HTML is printed
// Redirect user to login page if they are not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit(); // Always exit after a header redirect
}

$user_id = $_SESSION['user_id'];

// Handle Logout request
if(isset($_GET['logout'])) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json');
    $response = ['success' => false, 'message' => 'An error occurred.'];

    // Update Profile logic
    if (isset($_POST['action']) && $_POST['action'] === 'update_profile') {
        $name = trim($_POST['name']);
        $phone = trim($_POST['phone']);
        $address = trim($_POST['address']);
        $stmt = $conn->prepare("UPDATE users SET name=?, phone=?, address=? WHERE id=?");
        $stmt->bind_param("sssi", $name, $phone, $address, $user_id);
        if ($stmt->execute()) {
            $response = ['success' => true, 'message' => 'Profile updated successfully!'];
        } else {
            $response['message'] = 'Failed to update profile. Phone number might be taken.';
        }
    }

    // Change Password logic
    if (isset($_POST['action']) && $_POST['action'] === 'change_password') {
        $current_pass = $_POST['current_password'];
        $new_pass = $_POST['new_password'];
        
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if (password_verify($current_pass, $user['password'])) {
            if (strlen($new_pass) >= 6) {
                $hashed_pass = password_hash($new_pass, PASSWORD_DEFAULT);
                $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update_stmt->bind_param("si", $hashed_pass, $user_id);
                if ($update_stmt->execute()) {
                    $response = ['success' => true, 'message' => 'Password changed successfully.'];
                }
            } else {
                $response['message'] = 'New password must be at least 6 characters.';
            }
        } else {
            $response['message'] = 'Incorrect current password.';
        }
    }
    
    echo json_encode($response);
    exit(); // End script for AJAX requests
}

// Step 3: AFTER all PHP logic is done, set the page title and start printing HTML
$page_title = "My Profile";
include 'common/header.php';

// Step 4: Now, fetch data from the database to display on the page
$stmt = $conn->prepare("SELECT name, phone, email, address FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
?>

<!-- Step 5: The HTML Content -->
<div class="container mx-auto px-4 pt-4 pb-20">
    <h1 class="text-2xl font-bold text-gray-800 mb-4">Account Information</h1>

    <div id="profile-message" class="mb-4"></div>

    <!-- Edit Profile Form -->
    <form id="profile-form" class="bg-white p-6 rounded-lg shadow-sm space-y-4 mb-6">
        <input type="hidden" name="action" value="update_profile">
        <div>
            <label class="font-semibold text-gray-700">Full Name</label>
            <input type="text" name="name" value="<?= htmlspecialchars($user['name']) ?>" required class="w-full mt-1 px-4 py-3 rounded-lg bg-gray-100 border-transparent">
        </div>
        <div>
            <label class="font-semibold text-gray-700">Phone Number</label>
            <input type="tel" name="phone" value="<?= htmlspecialchars($user['phone']) ?>" required class="w-full mt-1 px-4 py-3 rounded-lg bg-gray-100 border-transparent">
        </div>
        <div>
            <label class="font-semibold text-gray-700">Email (Cannot be changed)</label>
            <input type="email" value="<?= htmlspecialchars($user['email']) ?>" readonly disabled class="w-full mt-1 px-4 py-3 rounded-lg bg-gray-200 text-gray-500">
        </div>
        <div>
            <label class="font-semibold text-gray-700">Default Address</label>
            <textarea name="address" rows="3" class="w-full mt-1 px-4 py-3 rounded-lg bg-gray-100 border-transparent"><?= htmlspecialchars($user['address']) ?></textarea>
        </div>
        <button type="submit" class="w-full bg-blue-500 text-white font-bold py-3 rounded-lg">Save Changes</button>
    </form>

    <!-- Change Password Form -->
    <h2 class="text-xl font-bold text-gray-800 mb-2 mt-8">Change Password</h2>
    <form id="password-form" class="bg-white p-6 rounded-lg shadow-sm space-y-4">
        <input type="hidden" name="action" value="change_password">
        <div>
            <input type="password" name="current_password" placeholder="Current Password" required class="w-full px-4 py-3 rounded-lg bg-gray-100 border-transparent">
        </div>
        <div>
            <input type="password" name="new_password" placeholder="New Password" required class="w-full px-4 py-3 rounded-lg bg-gray-100 border-transparent">
        </div>
        <button type="submit" class="w-full bg-gray-700 text-white font-bold py-3 rounded-lg">Update Password</button>
    </form>
    
    <!-- Logout Button -->
    <a href="?logout=1" class="block w-full text-center bg-red-500 hover:bg-red-600 text-white font-bold py-3 rounded-lg mt-8">Logout</a>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const profileMessage = document.getElementById('profile-message');
    
    document.getElementById('profile-form').addEventListener('submit', async function(e){
        e.preventDefault();
        const formData = new FormData(this);
        const response = await window.ajax('profile.php', { method: 'POST', body: formData });
        showMessage(response.message, response.success);
    });

    document.getElementById('password-form').addEventListener('submit', async function(e){
        e.preventDefault();
        const formData = new FormData(this);
        const response = await window.ajax('profile.php', { method: 'POST', body: formData });
        showMessage(response.message, response.success);
        if(response.success) this.reset();
    });
    
    function showMessage(msg, isSuccess){
        profileMessage.innerHTML = `<div class="p-3 rounded-lg ${isSuccess ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'}">${msg}</div>`;
        setTimeout(() => profileMessage.innerHTML = '', 4000);
    }
});
</script>

<?php 
// Step 6: Finally, include the bottom part of the page
include 'common/bottom.php'; 
?>