<?php
// Must include config first for DB connection
require_once 'common/config.php';

// --- AJAX REQUEST HANDLER ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // **THE FIX STARTS HERE**
    // 1. Suppress all PHP errors/warnings from being outputted, which corrupts JSON
    error_reporting(0);
    ini_set('display_errors', 0);

    // 2. Set the content type to JSON immediately
    header('Content-Type: application/json; charset=utf-8');

    // 3. Prepare a default response
    $response = ['success' => false, 'message' => 'An unknown server error occurred.'];

    // 4. Use a try-catch block to handle any potential database errors gracefully
    try {
        // --- SIGN UP LOGIC ---
        if (isset($_POST['action']) && $_POST['action'] === 'signup') {
            $name = trim($_POST['name'] ?? '');
            $phone = trim($_POST['phone'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($name) || empty($phone) || empty($email) || empty($password)) {
                $response['message'] = 'Please fill in all fields.';
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $response['message'] = 'Invalid email format.';
            } elseif (strlen($password) < 6) {
                $response['message'] = 'Password must be at least 6 characters.';
            } else {
                $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR phone = ?");
                $stmt->bind_param("ss", $email, $phone);
                $stmt->execute();
                $stmt->store_result();
                
                if ($stmt->num_rows > 0) {
                    $response['message'] = 'This email or phone number is already registered.';
                } else {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt_insert = $conn->prepare("INSERT INTO users (name, phone, email, password) VALUES (?, ?, ?, ?)");
                    $stmt_insert->bind_param("ssss", $name, $phone, $email, $hashed_password);
                    
                    if ($stmt_insert->execute()) {
                        $response['success'] = true;
                        $response['message'] = 'Registration successful! Please login.';
                    } else {
                        $response['message'] = 'Registration failed. Please try again.';
                    }
                    $stmt_insert->close();
                }
                $stmt->close();
            }
        }

        // --- LOGIN LOGIC ---
        elseif (isset($_POST['action']) && $_POST['action'] === 'login') {
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($email) || empty($password)) {
                $response['message'] = 'Email and Password are required.';
            } else {
                $stmt = $conn->prepare("SELECT id, password FROM users WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($user = $result->fetch_assoc()) {
                    if (password_verify($password, $user['password'])) {
                        $_SESSION['user_id'] = $user['id'];
                        $response = [
                            'success' => true,
                            'message' => 'Login successful! Redirecting...',
                            'redirect' => 'index.php'
                        ];
                    } else {
                        $response['message'] = 'Invalid email or password.';
                    }
                } else {
                    $response['message'] = 'Invalid email or password.';
                }
                $stmt->close();
            }
        }
        
    } catch (mysqli_sql_exception $e) {
        // This will catch any database query errors
        $response['message'] = "Database error. Please check configuration.";
        // For developers: you can log the actual error to a file instead of showing it
        // error_log($e->getMessage());
    } catch (Exception $e) {
        // This will catch any other unexpected errors
        $response['message'] = "A general server error occurred.";
    }

    // **This is the most important part:**
    // It ensures that only the JSON response is ever sent back.
    echo json_encode($response);
    exit();
}

// --- HTML PART ---
// This part will only run if the request is NOT a POST request (i.e., when a user first visits the page)

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}
?>
<?php $page_title = "Welcome"; include 'common/header.php'; ?>

<div class="min-h-screen flex flex-col items-center justify-center bg-gray-100 px-4">
    <div class="w-full max-w-md bg-white rounded-2xl shadow-xl p-2">
        <!-- Tabs -->
        <div class="flex space-x-1 p-1">
            <button id="login-tab-btn" class="flex-1 py-2.5 text-center text-sm font-medium rounded-lg bg-blue-500 text-white">Login</button>
            <button id="signup-tab-btn" class="flex-1 py-2.5 text-center text-sm font-medium rounded-lg text-gray-500">Sign Up</button>
        </div>

        <!-- Login Form -->
        <div id="login-form-container" class="px-6 py-8">
            <h2 class="text-2xl font-bold text-center text-gray-800">Login to Quick Kart</h2>
            <form id="login-form" class="mt-8 space-y-6">
                <input type="hidden" name="action" value="login">
                <input type="email" name="email" placeholder="Email Address" required class="w-full px-4 py-3 rounded-lg bg-gray-100">
                <input type="password" name="password" placeholder="Password" required class="w-full px-4 py-3 rounded-lg bg-gray-100">
                <div id="login-error" class="text-red-500 text-sm text-center min-h-[1.25rem]"></div>
                <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-3 rounded-lg">Login</button>
            </form>
        </div>

        <!-- Signup Form -->
        <div id="signup-form-container" class="px-6 py-8 hidden">
            <h2 class="text-2xl font-bold text-center text-gray-800">Create New Account</h2>
            <form id="signup-form" class="mt-8 space-y-4">
                <input type="hidden" name="action" value="signup">
                <input type="text" name="name" placeholder="Full Name" required class="w-full px-4 py-3 rounded-lg bg-gray-100">
                <input type="tel" name="phone" placeholder="Phone Number" required class="w-full px-4 py-3 rounded-lg bg-gray-100">
                <input type="email" name="email" placeholder="Email Address" required class="w-full px-4 py-3 rounded-lg bg-gray-100">
                <input type="password" name="password" placeholder="New Password" required class="w-full px-4 py-3 rounded-lg bg-gray-100">
                <div id="signup-message" class="text-sm text-center min-h-[1.25rem]"></div>
                <button type="submit" class="w-full bg-blue-500 hover:bg-blue-600 text-white font-bold py-3 rounded-lg">Sign Up</button>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Tab switching logic (remains the same)
    const loginTabBtn = document.getElementById('login-tab-btn');
    const signupTabBtn = document.getElementById('signup-tab-btn');
    const loginForm = document.getElementById('login-form-container');
    const signupForm = document.getElementById('signup-form-container');

    loginTabBtn.addEventListener('click', () => {
        loginForm.classList.remove('hidden');
        signupForm.classList.add('hidden');
        loginTabBtn.classList.add('bg-blue-500', 'text-white');
        signupTabBtn.classList.remove('bg-blue-500', 'text-white');
    });

    signupTabBtn.addEventListener('click', () => {
        signupForm.classList.remove('hidden');
        loginForm.classList.add('hidden');
        signupTabBtn.classList.add('bg-blue-500', 'text-white');
        loginTabBtn.classList.remove('bg-blue-500', 'text-white');
    });

    // Login AJAX
    document.getElementById('login-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        const loginError = document.getElementById('login-error');
        loginError.textContent = '';
        const response = await window.ajax('login.php', { method: 'POST', body: new FormData(this) });

        if (response.success) {
            loginError.className = 'text-green-500 text-sm text-center min-h-[1.25rem]';
            loginError.textContent = response.message;
            setTimeout(() => { window.location.href = response.redirect; }, 1000);
        } else {
            loginError.className = 'text-red-500 text-sm text-center min-h-[1.25rem]';
            loginError.textContent = response.message;
        }
    });
    
    // Signup AJAX
    document.getElementById('signup-form').addEventListener('submit', async function(e) {
        e.preventDefault();
        const signupMessage = document.getElementById('signup-message');
        signupMessage.textContent = '';
        const response = await window.ajax('login.php', { method: 'POST', body: new FormData(this) });

        if (response.success) {
            signupMessage.className = 'text-green-500 text-sm text-center min-h-[1.25rem]';
            signupMessage.textContent = response.message;
            this.reset();
            setTimeout(() => loginTabBtn.click(), 2000);
        } else {
            signupMessage.className = 'text-red-500 text-sm text-center min-h-[1.25rem]';
            signupMessage.textContent = response.message;
        }
    });
});
</script>

<?php include 'common/bottom.php'; ?>