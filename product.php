<?php
// admin/product.php

// Step 1: Include the central authentication file at the very top.
require_once 'common/auth.php';

// --- AJAX HANDLER FOR CRUD OPERATIONS ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    // We need the database connection, which is included via header.php.
    // So, we include the config file manually here for the AJAX request.
    require_once __DIR__ . '/../common/config.php';
    
    $response = ['success' => false, 'message' => 'Invalid Request'];

    // ADD / EDIT PRODUCT LOGIC
    if ($_POST['action'] === 'add' || $_POST['action'] === 'edit') {
        $name = trim($_POST['name'] ?? '');
        $cat_id = intval($_POST['cat_id'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $stock = intval($_POST['stock'] ?? 0);
        $prod_id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $image_name = $_POST['existing_image'] ?? '';

        if (empty($name) || $cat_id <= 0 || $price <= 0) {
            $response['message'] = 'Name, category, and price are required fields.';
        } else {
            if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
                $target_dir = __DIR__ . "/../uploads/products/";
                $image_name = uniqid() . '-' . basename($_FILES["image"]["name"]);
                $target_file = $target_dir . $image_name;
                if (!move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                    $response['message'] = 'Failed to upload image.';
                    echo json_encode($response); exit();
                }
            }

            if ($_POST['action'] === 'add') {
                $stmt = $conn->prepare("INSERT INTO products (cat_id, name, description, price, stock, image) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("issdis", $cat_id, $name, $description, $price, $stock, $image_name);
            } else { // Edit operation
                $stmt = $conn->prepare("UPDATE products SET cat_id=?, name=?, description=?, price=?, stock=?, image=? WHERE id=?");
                $stmt->bind_param("issdisi", $cat_id, $name, $description, $price, $stock, $image_name, $prod_id);
            }

            if ($stmt->execute()) {
                $response = ['success' => true, 'message' => 'Product saved successfully!'];
            } else {
                $response['message'] = 'Database error: ' . $stmt->error;
            }
        }
    }
    
    // DELETE PRODUCT LOGIC
    elseif ($_POST['action'] === 'delete'){
        $prod_id = intval($_POST['id'] ?? 0);
        if ($prod_id > 0) {
            $stmt = $conn->prepare("DELETE FROM products WHERE id=?");
            $stmt->bind_param("i", $prod_id);
            if($stmt->execute()){
                $response = ['success' => true, 'message' => 'Product deleted.'];
            }
        }
    }

    echo json_encode($response);
    exit();
}

// --- HTML PAGE DISPLAY ---

// Step 2: Set the page title and include the header.
$admin_page_title = 'Manage Products';
include 'common/header.php';

// Fetch categories for the dropdown and products for the list
$categories_result = $conn->query("SELECT id, name FROM categories ORDER BY name ASC");
$products_result = $conn->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.cat_id = c.id ORDER BY p.created_at DESC");
?>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Form Panel -->
    <div class="lg:col-span-1 bg-white p-6 rounded-lg shadow-md h-fit">
        <h2 id="form-title" class="text-xl font-bold mb-4">Add New Product</h2>
        <form id="product-form" class="space-y-4">
            <input type="hidden" name="id" id="prod-id">
            <input type="hidden" name="action" id="prod-action" value="add">
            <input type="hidden" name="existing_image" id="existing-image">
            <div>
                <label class="font-medium">Product Name</label>
                <input type="text" name="name" id="prod-name" required class="w-full mt-1 px-4 py-2 rounded-md bg-gray-100">
            </div>
            <div>
                <label class="font-medium">Category</label>
                <select name="cat_id" id="prod-cat" required class="w-full mt-1 px-4 py-2 rounded-md bg-gray-100">
                    <option value="">Select Category</option>
                    <?php while($cat = $categories_result->fetch_assoc()): ?>
                        <option value="<?= $cat['id'] ?>"><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div>
                <label class="font-medium">Price (₹)</label>
                <input type="number" name="price" id="prod-price" required step="0.01" class="w-full mt-1 px-4 py-2 rounded-md bg-gray-100">
            </div>
            <div>
                <label class="font-medium">Stock Quantity</label>
                <input type="number" name="stock" id="prod-stock" required class="w-full mt-1 px-4 py-2 rounded-md bg-gray-100">
            </div>
            <div>
                <label class="font-medium">Description</label>
                <textarea name="description" id="prod-desc" rows="3" class="w-full mt-1 px-4 py-2 rounded-md bg-gray-100"></textarea>
            </div>
            <div>
                <label class="font-medium">Product Image</label>
                <input type="file" name="image" id="prod-image" class="w-full mt-1 text-sm">
                <img id="image-preview" src="" class="mt-2 h-20 rounded-md hidden" alt="Preview">
            </div>
            <div class="flex space-x-2">
                <button type="submit" id="submit-btn" class="flex-1 bg-blue-600 text-white font-bold py-2 rounded-md">Add Product</button>
                <button type="button" id="cancel-btn" class="flex-1 bg-gray-300 hidden">Cancel</button>
            </div>
        </form>
    </div>

    <!-- Product List Table -->
    <div class="lg:col-span-2 bg-white p-6 rounded-lg shadow-md">
        <h2 class="text-xl font-bold mb-4">Product List</h2>
        <div class="overflow-x-auto">
            <table class="w-full text-left">
                <thead><tr class="border-b"><th class="p-2">Product</th><th class="p-2">Category</th><th class="p-2">Price</th><th class="p-2">Stock</th><th class="p-2">Actions</th></tr></thead>
                <tbody>
                <?php while($prod = $products_result->fetch_assoc()): ?>
                    <tr id="prod-row-<?= $prod['id'] ?>" class="border-b">
                        <td class="p-2 flex items-center space-x-3">
                            <img src="../uploads/products/<?= htmlspecialchars($prod['image']) ?>" class="w-12 h-12 rounded-md object-cover">
                            <span class="font-medium"><?= htmlspecialchars($prod['name']) ?></span>
                        </td>
                        <td class="p-2"><?= htmlspecialchars($prod['category_name'] ?? 'N/A') ?></td>
                        <td class="p-2">₹<?= number_format($prod['price']) ?></td>
                        <td class="p-2"><?= $prod['stock'] ?></td>
                        <td class="p-2 space-x-2">
                            <button onclick="editProduct(<?= htmlspecialchars(json_encode($prod)) ?>)" class="text-blue-500"><i class="fas fa-edit"></i></button>
                            <button onclick="deleteProduct(<?= $prod['id'] ?>)" class="text-red-500"><i class="fas fa-trash"></i></button>
                        </td>
                    </tr>
                <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
// JavaScript for form handling
const form = document.getElementById('product-form');
const formTitle = document.getElementById('form-title');
const prodId = document.getElementById('prod-id');
const prodName = document.getElementById('prod-name');
const prodCat = document.getElementById('prod-cat');
const prodPrice = document.getElementById('prod-price');
const prodStock = document.getElementById('prod-stock');
const prodDesc = document.getElementById('prod-desc');
const prodAction = document.getElementById('prod-action');
const prodImageInput = document.getElementById('prod-image');
const existingImage = document.getElementById('existing-image');
const imagePreview = document.getElementById('image-preview');
const submitBtn = document.getElementById('submit-btn');
const cancelBtn = document.getElementById('cancel-btn');

function resetForm() {
    form.reset();
    formTitle.textContent = 'Add New Product';
    submitBtn.textContent = 'Add Product';
    prodAction.value = 'add';
    imagePreview.classList.add('hidden');
    cancelBtn.classList.add('hidden');
}

cancelBtn.addEventListener('click', resetForm);

function editProduct(prod) {
    formTitle.textContent = 'Edit Product';
    submitBtn.textContent = 'Update Product';
    prodAction.value = 'edit';
    prodId.value = prod.id;
    prodName.value = prod.name;
    prodCat.value = prod.cat_id;
    prodPrice.value = prod.price;
    prodStock.value = prod.stock;
    prodDesc.value = prod.description;
    existingImage.value = prod.image;
    if (prod.image) {
        imagePreview.src = `../uploads/products/${prod.image}`;
        imagePreview.classList.remove('hidden');
    } else {
        imagePreview.classList.add('hidden');
    }
    cancelBtn.classList.remove('hidden');
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

async function deleteProduct(id) {
    if (!confirm('Are you sure?')) return;
    const formData = new FormData();
    formData.append('action', 'delete');
    formData.append('id', id);
    const response = await window.adminAjax('product.php', { method: 'POST', body: formData });
    if (response.success) {
        alert(response.message);
        document.getElementById(`prod-row-${id}`).remove();
    } else {
        alert(response.message);
    }
}

form.addEventListener('submit', async function(e) {
    e.preventDefault();
    const formData = new FormData();
    formData.append('action', prodAction.value);
    formData.append('id', prodId.value);
    formData.append('name', prodName.value);
    formData.append('cat_id', prodCat.value);
    formData.append('price', prodPrice.value);
    formData.append('stock', prodStock.value);
    formData.append('description', prodDesc.value);
    formData.append('existing_image', existingImage.value);
    if (prodImageInput.files[0]) {
        formData.append('image', prodImageInput.files[0]);
    }
    
    const response = await window.adminAjax('product.php', { method: 'POST', body: formData });
    if (response.success) {
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
?>```

এই কাজটি করার পর আপনার অ্যাডমিন প্যানেলের "Products" পেজটি সঠিকভাবে লোড হবে এবং আপনি প্রোডাক্ট যোগ করতে পারবেন।