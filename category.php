<?php
// admin/category.php

// Step 1: Include the central authentication file at the very top.
// This handles all security checks and redirects if the user is not logged in.
require_once 'common/auth.php';

// --- AJAX HANDLER FOR CRUD OPERATIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    // We need the database connection, which is included via header.php.
    // So, we include the config file manually here for the AJAX request.
    require_once __DIR__ . '/../common/config.php';
    
    $response = ['success' => false, 'message' => 'Invalid Request'];

    // ADD / UPDATE CATEGORY LOGIC
    if ($_POST['action'] === 'add' || $_POST['action'] === 'edit') {
        $name = trim($_POST['name'] ?? '');
        $cat_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $image_name = $_POST['existing_image'] ?? '';

        if (empty($name)) {
            $response['message'] = 'Category name is required.';
        } else {
            // Handle file upload
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $target_dir = __DIR__ . "/../uploads/categories/";
                $image_name = uniqid() . '-' . basename($_FILES["image"]["name"]);
                $target_file = $target_dir . $image_name;
                if (!move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                    $response['message'] = 'Failed to upload image.';
                    echo json_encode($response);
                    exit();
                }
            }

            if ($_POST['action'] === 'add') {
                $stmt = $conn->prepare("INSERT INTO categories (name, image) VALUES (?, ?)");
                $stmt->bind_param("ss", $name, $image_name);
            } else { // Edit operation
                $stmt = $conn->prepare("UPDATE categories SET name=?, image=? WHERE id=?");
                $stmt->bind_param("ssi", $name, $image_name, $cat_id);
            }
            
            if($stmt->execute()){
                $response = ['success' => true, 'message' => 'Category saved successfully!'];
            } else {
                $response['message'] = 'Database error: ' . $stmt->error;
            }
        }
    }
    
    // DELETE CATEGORY LOGIC
    elseif ($_POST['action'] === 'delete'){
        $cat_id = intval($_POST['id'] ?? 0);
        if ($cat_id > 0) {
            $stmt = $conn->prepare("DELETE FROM categories WHERE id=?");
            $stmt->bind_param("i", $cat_id);
            if($stmt->execute()){
                $response = ['success' => true, 'message' => 'Category deleted.'];
            }
        }
    }

    echo json_encode($response);
    exit();
}

// --- HTML PAGE DISPLAY ---

// Step 2: Set the page title and include the header.
$admin_page_title = 'Manage Categories';
include 'common/header.php';

// Fetch all categories from the database for display
$categories_result = $conn->query("SELECT * FROM categories ORDER BY name ASC");
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Form Panel -->
    <div class="lg:col-span-1 bg-white p-6 rounded-lg shadow-md h-fit">
        <h2 id="form-title" class="text-xl font-bold mb-4">Add New Category</h2>
        <form id="category-form">
            <input type="hidden" name="id" id="cat-id">
            <input type="hidden" name="action" id="cat-action" value="add">
            <input type="hidden" name="existing_image" id="existing-image">
            <div class="space-y-4">
                <div>
                    <label for="name" class="font-medium">Category Name</label>
                    <input type="text" name="name" id="cat-name" required class="w-full mt-1 px-4 py-2 rounded-md bg-gray-100">
                </div>
                <div>
                    <label for="image" class="font-medium">Category Image</label>
                    <input type="file" name="image" id="cat-image" class="w-full mt-1 text-sm">
                    <img id="image-preview" src="" class="mt-2 h-20 rounded-md hidden" alt="Preview">
                </div>
                <div class="flex space-x-2">
                    <button type="submit" id="submit-btn" class="flex-1 bg-blue-600 text-white font-bold py-2 rounded-md">Add Category</button>
                    <button type="button" id="cancel-btn" class="flex-1 bg-gray-300 hidden">Cancel</button>
                </div>
            </div>
        </form>
    </div>

    <!-- Table Panel -->
    <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-xl font-bold mb-4">Existing Categories</h2>
        <table class="w-full text-left">
            <thead>
                <tr class="border-b"><th class="p-2">Image</th><th class="p-2">Name</th><th class="p-2">Actions</th></tr>
            </thead>
            <tbody>
                <?php while($cat = $categories_result->fetch_assoc()): ?>
                <tr id="cat-row-<?= $cat['id'] ?>">
                    <td class="p-2"><img src="../uploads/categories/<?= htmlspecialchars($cat['image']) ?>" class="w-12 h-12 rounded-md object-cover"></td>
                    <td class="p-2"><?= htmlspecialchars($cat['name']) ?></td>
                    <td class="p-2">
                        <button onclick="editCategory(<?= htmlspecialchars(json_encode($cat)) ?>)" class="text-blue-500"><i class="fas fa-edit"></i></button>
                        <button onclick="deleteCategory(<?= $cat['id'] ?>)" class="text-red-500"><i class="fas fa-trash"></i></button>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
// JavaScript for form handling
const form = document.getElementById('category-form');
const formTitle = document.getElementById('form-title');
const catId = document.getElementById('cat-id');
const catName = document.getElementById('cat-name');
const catAction = document.getElementById('cat-action');
const catImageInput = document.getElementById('cat-image');
const existingImage = document.getElementById('existing-image');
const imagePreview = document.getElementById('image-preview');
const submitBtn = document.getElementById('submit-btn');
const cancelBtn = document.getElementById('cancel-btn');

function resetForm() {
    form.reset();
    formTitle.textContent = 'Add New Category';
    submitBtn.textContent = 'Add Category';
    catAction.value = 'add';
    imagePreview.classList.add('hidden');
    cancelBtn.classList.add('hidden');
}

cancelBtn.addEventListener('click', resetForm);

function editCategory(cat) {
    formTitle.textContent = 'Edit Category';
    submitBtn.textContent = 'Update Category';
    catAction.value = 'edit';
    catId.value = cat.id;
    catName.value = cat.name;
    existingImage.value = cat.image;
    if(cat.image) {
        imagePreview.src = `../uploads/categories/${cat.image}`;
        imagePreview.classList.remove('hidden');
    }
    cancelBtn.classList.remove('hidden');
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

async function deleteCategory(id) {
    if (!confirm('Are you sure?')) return;
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', id);
    const response = await window.adminAjax('category.php', { method: 'POST', body: formData });
    if (response.success) {
        alert(response.message);
        document.getElementById(`cat-row-${id}`).remove();
    } else {
        alert(response.message);
    }
}

form.addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData();
    formData.append('action', catAction.value);
    formData.append('id', catId.value);
    formData.append('name', catName.value);
    formData.append('existing_image', existingImage.value);
    if (catImageInput.files[0]) {
        formData.append('image', catImageInput.files[0]);
    }
    
    const response = await window.adminAjax('category.php', { method: 'POST', body: formData });
    if(response.success) {
        alert(response.message);
        location.reload();
    } else {
        alert(response.message);
    }
});
</script>

<?php 
// Step 3: Include the footer.
include 'common/bottom.php'; 
?>