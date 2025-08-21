<?php
// admin/user.php

// Step 1: Include the central authentication file at the very top.
require_once 'common/auth.php';

// --- HANDLE DELETE REQUEST ---
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $user_id_to_delete = intval($_GET['id']);
    
    // It's a good practice not to delete the very first user (assuming it's a test/main user)
    if ($user_id_to_delete > 1) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id_to_delete);
        $stmt->execute();
    }
    
    // Redirect back to the same page to show the updated list
    header("Location: user.php");
    exit();
}

// --- HTML PAGE DISPLAY ---

// Step 2: Set the page title and include the header.
$admin_page_title = 'Manage Users';
include 'common/header.php';

// Step 3: Fetch all users from the database
$users_result = $conn->query("SELECT id, name, email, phone, created_at FROM users ORDER BY created_at DESC");
?>

<!-- HTML content for the users list page -->
<div class="bg-white p-6 rounded-lg shadow-md">
    <div class="overflow-x-auto">
        <table class="w-full text-left table-auto">
            <thead>
                <tr class="border-b bg-gray-50">
                    <th class="p-3 text-sm font-semibold">User ID</th>
                    <th class="p-3 text-sm font-semibold">Name</th>
                    <th class="p-3 text-sm font-semibold">Email</th>
                    <th class="p-3 text-sm font-semibold">Phone</th>
                    <th class="p-3 text-sm font-semibold">Date Joined</th>
                    <th class="p-3 text-sm font-semibold">Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($users_result->num_rows > 0): ?>
                    <?php while($user = $users_result->fetch_assoc()): ?>
                    <tr class="border-b hover:bg-gray-50">
                        <td class="p-3 font-semibold">#<?= $user['id'] ?></td>
                        <td class="p-3"><?= htmlspecialchars($user['name']) ?></td>
                        <td class="p-3 text-gray-600"><?= htmlspecialchars($user['email']) ?></td>
                        <td class="p-3 text-gray-600"><?= htmlspecialchars($user['phone']) ?></td>
                        <td class="p-3 text-sm text-gray-500"><?= date('d M Y', strtotime($user['created_at'])) ?></td>
                        <td class="p-3">
                            <a href="?action=delete&id=<?= $user['id'] ?>" 
                               onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')" 
                               class="text-red-500 font-semibold hover:underline text-sm">
                               Delete
                            </a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="6" class="p-3 text-center text-gray-500">No users found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php 
// Step 4: Include the footer file.
include 'common/bottom.php'; 
?>