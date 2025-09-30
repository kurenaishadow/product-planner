<?php
require_once 'config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

$error_message = '';
$success_message = '';

// Handle Registration Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    $username = sanitize_input($_POST['username']);
    $email = sanitize_input($_POST['email']);
    $full_name = sanitize_input($_POST['full_name']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validation
    if (empty($username) || empty($email) || empty($full_name) || empty($password)) {
        $error_message = 'Please fill in all fields.';
    } elseif ($password !== $confirm_password) {
        $error_message = 'Passwords do not match.';
    } elseif (strlen($password) < 6) {
        $error_message = 'Password must be at least 6 characters long.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Please enter a valid email address.';
    } else {
        try {
            $database = new Database();
            $db = $database->getConnection();
            
            // Check if username exists
            $stmt = $db->prepare("SELECT user_id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            if ($stmt->fetch()) {
                $error_message = 'Username already exists. Please choose another.';
            } else {
                // Check if email exists
                $stmt = $db->prepare("SELECT user_id FROM users WHERE email = ?");
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $error_message = 'Email already registered. Please use another.';
                } else {
                    // Hash password
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    
                    // Insert new user
                    $stmt = $db->prepare("
                        INSERT INTO users (username, email, password, full_name, user_type, status) 
                        VALUES (?, ?, ?, ?, 'user', 'active')
                    ");
                    
                    if ($stmt->execute([$username, $email, $hashed_password, $full_name])) {
                        $new_user_id = $db->lastInsertId();
                        
                        // Log activity
                        log_activity($new_user_id, 'user_registered', 'New user registered', $db);
                        
                        $success_message = 'Registration successful! You can now login.';
                        
                        // Auto-login after registration
                        $_SESSION['user_id'] = $new_user_id;
                        $_SESSION['username'] = $username;
                        $_SESSION['full_name'] = $full_name;
                        $_SESSION['user_type'] = 'user';
                        $_SESSION['last_activity'] = time();
                        
                        // Redirect to dashboard
                        header('refresh:2;url=dashboard.php');
                    } else {
                        $error_message = 'Registration failed. Please try again.';
                    }
                }
            }
        } catch (PDOException $e) {
            $error_message = 'An error occurred. Please try again.';
            error_log("Registration Error: " . $e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-indigo-100 min-h-screen flex items-center justify-center p-4">
    
    <div class="w-full max-w-md">
        <!-- Logo/Title -->
        <div class="text-center mb-8">
            <h1 class="text-4xl font-bold text-gray-800 mb-2">Product Money Planner</h1>
            <p class="text-gray-600">Create your free account</p>
        </div>
        
        <!-- Registration Card -->
        <div class="bg-white rounded-2xl shadow-xl p-8">
            <h2 class="text-2xl font-bold text-gray-800 mb-6 text-center">Sign Up</h2>
            
            <?php if ($error_message): ?>
            <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
                <p class="text-red-700"><?php echo $error_message; ?></p>
            </div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
            <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6">
                <p class="text-green-700"><?php echo $success_message; ?></p>
                <p class="text-green-600 text-sm mt-1">Redirecting to dashboard...</p>
            </div>
            <?php endif; ?>
            
            <form method="POST" action="" class="space-y-4">
                <div>
                    <label for="full_name" class="block text-sm font-medium text-gray-700 mb-2">Full Name</label>
                    <input 
                        type="text" 
                        id="full_name" 
                        name="full_name" 
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                        placeholder="Juan Dela Cruz"
                        value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>"
                    >
                </div>
                
                <div>
                    <label for="username" class="block text-sm font-medium text-gray-700 mb-2">Username</label>
                    <input 
                        type="text" 
                        id="username" 
                        name="username" 
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                        placeholder="Choose a username"
                        value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                    >
                </div>
                
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">Email Address</label>
                    <input 
                        type="email" 
                        id="email" 
                        name="email" 
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                        placeholder="your.email@example.com"
                        value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
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
                        placeholder="At least 6 characters"
                    >
                </div>
                
                <div>
                    <label for="confirm_password" class="block text-sm font-medium text-gray-700 mb-2">Confirm Password</label>
                    <input 
                        type="password" 
                        id="confirm_password" 
                        name="confirm_password" 
                        required
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent transition"
                        placeholder="Re-enter your password"
                    >
                </div>
                
                <button 
                    type="submit" 
                    name="register"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 rounded-lg transition duration-200 shadow-lg hover:shadow-xl"
                >
                    Create Account
                </button>
            </form>
            
            <div class="mt-6 text-center">
                <p class="text-gray-600">Already have an account? <a href="login.php" class="text-blue-600 hover:text-blue-700 font-medium">Login</a></p>
            </div>
        </div>
    </div>
    
</body>
</html>