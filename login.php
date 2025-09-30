<?php
require_once 'config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error_message = '';
$success_message = '';

if (isset($_GET['timeout'])) {
    $error_message = 'Your session has expired. Please login again.';
}

if (isset($_GET['logout'])) {
    $success_message = 'You have been logged out successfully.';
}

// Handle Login Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = sanitize_input($_POST['username']);
    $password = $_POST['password'];
    
    if (empty($username) || empty($password)) {
        $error_message = 'Please fill in all fields.';
    } else {
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            $stmt = $db->prepare("SELECT * FROM users WHERE username = ? AND status = 'active'");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Login successful
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['user_type'] = $user['user_type'];
                $_SESSION['last_activity'] = time();
                
                // Update last login
                $update_stmt = $db->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
                $update_stmt->execute([$user['user_id']]);
                
                // Log activity
                log_activity($user['user_id'], 'login', 'User logged in', $db);
                
                // Redirect based on user type
                if ($user['user_type'] === 'admin') {
                    header('Location: admin/dashboard.php');
                } else {
                    header('Location: dashboard.php');
                }
                exit();
            } else {
                $error_message = 'Invalid username or password.';
                
                // Log failed attempt
                if ($user) {
                    log_activity($user['user_id'], 'login_failed', 'Failed login attempt', $db);
                }
            }
        } catch (PDOException $e) {
            $error_message = 'An error occurred. Please try again.';
            error_log("Login Error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center p-4">
    
    <div class="w-full max-w-md">
        <!-- Logo/Title -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-gray-800 mb-2">Product Money Planner</h1>
            <p class="text-gray-600">Plan your business finances easily</p>
        </div>
        
        <!-- Login Card -->
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Login to Your Account</h2>
            
            <?php if ($error_message): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                <p class="text-red-700"><?php echo $error_message; ?></p>
            </div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
            <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6">
                <p class="text-green-700"><?php echo $success_message; ?></p>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="space-y-6">
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                        placeholder="Enter your username"
                        value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                    >
                </div>
                
                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 mb-2">Password</label>
                    <input 
                        type="password" 
                        id="password" 
                        name="password" 
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                        placeholder="Enter your password"
                    >
                </div>
                
                <div class="flex items-center justify-between">
                    <label class="flex items-center">
                        <input type="checkbox" class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                        <span class="ml-2 text-sm text-gray-600">Remember me</span>
                    </label>
                    <!-- <a href="forgot-password.php" class="text-sm text-blue-600 hover:text-blue-700">Forgot Password?</a> -->
                </div>
                
                <button 
                    type="submit" 
                    name="login"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-lg transition duration-200 shadow-lg hover:shadow-xl"
                >
                    Login
                </button>
            </form>
            
            <div class="mt-6 text-center">
                <p class="text-gray-600">Don't have an account? <a href="register.php" class="text-blue-600 hover:text-blue-700 font-medium">Sign Up</a></p>
            </div>
        </div>       
    </div>
    
</body>
</html>