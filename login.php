<?php
// admin/login.php

// Start the session at the absolute beginning of the file.
// Using the default session name is more reliable.
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// If the user is ALREADY logged in, redirect them to the dashboard immediately.
if (isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit();
}

// Now, it is safe to include the header file.
$admin_page_title = 'Admin Login';
require_once 'common/header.php'; // Using require_once for safety.

$error_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($conn)) { // Check if the database connection exists.
        $username = trim($_POST['username']);
        $password = $_POST['password'];

        if (!empty($username) && !empty($password)) {
            $stmt = $conn->prepare("SELECT id, password FROM admin WHERE username = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($admin = $result->fetch_assoc()) {
                if (password_verify($password, $admin['password'])) {
                    // Set the session variable on successful login.
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_username'] = $username;
                    
                    // Regenerate session ID for security after login. This is very important.
                    session_regenerate_id(true);

                    header("Location: index.php"); // Redirect to the dashboard.
                    exit();
                }
            }
        }
        // If the script reaches this point, login failed.
        $error_message = 'Invalid username or password.';
    } else {
        $error_message = 'Database connection error.';
    }
}
?>
<!-- HTML for the login page -->
<div class="min-h-screen flex items-center justify-center bg-gray-900">
    <div class="w-full max-w-sm bg-white rounded-lg shadow-md p-8">
        <h2 class="text-2xl font-bold text-center text-gray-800 mb-6">Admin Panel Login</h2>
        <?php if ($error_message): ?>
            <p class="bg-red-100 text-red-700 text-sm p-3 rounded-md mb-4"><?= htmlspecialchars($error_message) ?></p>
        <?php endif; ?>
        <form method="POST" class="space-y-6">
            <div>
                <label for="username" class="text-sm font-medium text-gray-700">Username</label>
                <input type="text" name="username" id="username" required class="w-full mt-1 px-4 py-2 rounded-md bg-gray-100">
            </div>
            <div>
                <label for="password" class="text-sm font-medium text-gray-700">Password</label>
                <input type="password" name="password" id="password" required class="w-full mt-1 px-4 py-2 rounded-md bg-gray-100">
            </div>
            <button type="submit" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 rounded-md">Login</button>
        </form>
    </div>
</div>
<?php 
require_once 'common/bottom.php'; 
?>