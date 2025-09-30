<?php
require_once '../config.php';
check_admin();

$database = new Database();
$db = $database->getConnection();

$success_message = '';
$error_message = '';

// Handle Change Password
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $user_id = (int)$_POST['user_id'];
    $new_password = $_POST['new_password'];
    
    if (strlen($new_password) < 6) {
        $error_message = 'Password must be at least 6 characters long.';
    } else {
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
        
        $stmt = $db->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE user_id = ?");
        if ($stmt->execute([$hashed_password, $user_id])) {
            log_activity($_SESSION['user_id'], 'password_changed', "Admin changed password for user ID: $user_id", $db);
            $success_message = 'Password changed successfully!';
        } else {
            $error_message = 'Failed to change password.';
        }
    }
}

// Handle Status Change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_status'])) {
    $user_id = (int)$_POST['user_id'];
    $new_status = $_POST['status'];
    
    $stmt = $db->prepare("UPDATE users SET status = ?, updated_at = NOW() WHERE user_id = ?");
    if ($stmt->execute([$new_status, $user_id])) {
        log_activity($_SESSION['user_id'], 'status_changed', "Admin changed status for user ID: $user_id to $new_status", $db);
        $success_message = 'User status updated successfully!';
    } else {
        $error_message = 'Failed to update status.';
    }
}

// Handle Delete User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user'])) {
    $user_id = (int)$_POST['user_id'];
    
    $stmt = $db->prepare("DELETE FROM users WHERE user_id = ? AND user_type = 'user'");
    if ($stmt->execute([$user_id])) {
        log_activity($_SESSION['user_id'], 'user_deleted', "Admin deleted user ID: $user_id", $db);
        $success_message = 'User deleted successfully!';
    } else {
        $error_message = 'Failed to delete user.';
    }
}

// Get all users with their statistics
$stmt = $db->query("SELECT * FROM v_user_statistics ORDER BY created_at DESC");
$users = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-50">
    
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="dashboard.php" class="text-xl font-bold text-gray-800">Admin Panel</a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="text-gray-700 hover:text-gray-900">Dashboard</a>
                    <span class="text-gray-700">Welcome, <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong></span>
                    <a href="../logout.php" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition">Logout</a>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <div class="mb-6">
            <h1 class="text-3xl font-bold text-gray-800">User Management</h1>
            <p class="text-gray-600 mt-2">Manage all users, change passwords, and view statistics</p>
        </div>
        
        <?php if ($success_message): ?>
        <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6">
            <p class="text-green-700"><?php echo $success_message; ?></p>
        </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
        <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6">
            <p class="text-red-700"><?php echo $error_message; ?></p>
        </div>
        <?php endif; ?>
        
        <!-- Users Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Projects</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Last Login</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($users as $user): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div>
                                    <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['full_name']); ?></div>
                                    <div class="text-sm text-gray-500">@<?php echo htmlspecialchars($user['username']); ?></div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($user['email']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900"><?php echo $user['total_projects']; ?> total</div>
                                <div class="text-sm text-gray-500"><?php echo $user['active_projects']; ?> active</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?php echo $user['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                    <?php echo ucfirst($user['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo $user['last_login'] ? date('M d, Y g:i A', strtotime($user['last_login'])) : 'Never'; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <button onclick="changePassword(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')" 
                                        class="text-blue-600 hover:text-blue-900 mr-3">
                                    Change Password
                                </button>
                                <button onclick="changeStatus(<?php echo $user['user_id']; ?>, '<?php echo $user['status']; ?>', '<?php echo htmlspecialchars($user['username']); ?>')" 
                                        class="text-yellow-600 hover:text-yellow-900 mr-3">
                                    <?php echo $user['status'] === 'active' ? 'Deactivate' : 'Activate'; ?>
                                </button>
                                <button onclick="deleteUser(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')" 
                                        class="text-red-600 hover:text-red-900">
                                    Delete
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
    </div>
    
    <script>
    function changePassword(userId, username) {
        Swal.fire({
            title: 'Change Password',
            html: `
                <p class="mb-4">Change password for: <strong>${username}</strong></p>
                <input type="password" id="new_password" class="swal2-input" placeholder="New Password" minlength="6">
                <input type="password" id="confirm_password" class="swal2-input" placeholder="Confirm Password">
            `,
            showCancelButton: true,
            confirmButtonText: 'Change Password',
            confirmButtonColor: '#3b82f6',
            preConfirm: () => {
                const newPassword = document.getElementById('new_password').value;
                const confirmPassword = document.getElementById('confirm_password').value;
                
                if (!newPassword || !confirmPassword) {
                    Swal.showValidationMessage('Please fill in both fields');
                    return false;
                }
                
                if (newPassword.length < 6) {
                    Swal.showValidationMessage('Password must be at least 6 characters');
                    return false;
                }
                
                if (newPassword !== confirmPassword) {
                    Swal.showValidationMessage('Passwords do not match');
                    return false;
                }
                
                return { newPassword: newPassword };
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="change_password" value="1">
                    <input type="hidden" name="user_id" value="${userId}">
                    <input type="hidden" name="new_password" value="${result.value.newPassword}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
    
    function changeStatus(userId, currentStatus, username) {
        const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
        const action = newStatus === 'active' ? 'activate' : 'deactivate';
        
        Swal.fire({
            title: `${action.charAt(0).toUpperCase() + action.slice(1)} User?`,
            text: `Are you sure you want to ${action} ${username}?`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#3b82f6',
            cancelButtonColor: '#d33',
            confirmButtonText: `Yes, ${action}!`
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="change_status" value="1">
                    <input type="hidden" name="user_id" value="${userId}">
                    <input type="hidden" name="status" value="${newStatus}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
    
    function deleteUser(userId, username) {
        Swal.fire({
            title: 'Delete User?',
            html: `Are you sure you want to delete <strong>${username}</strong>?<br><span class="text-red-600">This action cannot be undone!</span>`,
            icon: 'error',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3b82f6',
            confirmButtonText: 'Yes, delete!'
        }).then((result) => {
            if (result.isConfirmed) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="delete_user" value="1">
                    <input type="hidden" name="user_id" value="${userId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        });
    }
    </script>
    
</body>
</html>