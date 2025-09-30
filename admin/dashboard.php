<?php
require_once '../config.php';
check_admin();

$database = new Database();
$db = $database->getConnection();

// Get statistics
$stmt = $db->query("SELECT COUNT(*) as total_users FROM users WHERE user_type = 'user'");
$total_users = $stmt->fetch()['total_users'];

$stmt = $db->query("SELECT COUNT(*) as total_projects FROM projects");
$total_projects = $stmt->fetch()['total_projects'];

$stmt = $db->query("SELECT COUNT(*) as active_users FROM users WHERE status = 'active' AND user_type = 'user'");
$active_users = $stmt->fetch()['active_users'];

// Get recent users
$stmt = $db->query("
    SELECT user_id, username, full_name, email, created_at, last_login, status 
    FROM users 
    WHERE user_type = 'user' 
    ORDER BY created_at DESC 
    LIMIT 10
");
$recent_users = $stmt->fetchAll();

// Get recent activity
$stmt = $db->query("
    SELECT al.*, u.username, u.full_name 
    FROM activity_logs al
    INNER JOIN users u ON al.user_id = u.user_id
    ORDER BY al.created_at DESC 
    LIMIT 15
");
$recent_activities = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-50">
    
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <h1 class="text-xl font-bold text-gray-800">Admin Panel</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-700">Welcome, <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong></span>
                    <a href="manage-users.php" class="text-blue-600 hover:text-blue-700 font-medium">Manage Users</a>
                    <a href="../logout.php" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition">Logout</a>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Statistics Cards -->
        <div class="grid md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Total Users</p>
                        <p class="text-3xl font-bold text-blue-600 mt-2"><?php echo $total_users; ?></p>
                    </div>
                    <div class="bg-blue-100 p-4 rounded-full">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Active Users</p>
                        <p class="text-3xl font-bold text-green-600 mt-2"><?php echo $active_users; ?></p>
                    </div>
                    <div class="bg-green-100 p-4 rounded-full">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Total Projects</p>
                        <p class="text-3xl font-bold text-purple-600 mt-2"><?php echo $total_projects; ?></p>
                    </div>
                    <div class="bg-purple-100 p-4 rounded-full">
                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="grid lg:grid-cols-2 gap-8">
            <!-- Recent Users -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Recent Users</h2>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Joined</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($recent_users as $user): ?>
                            <tr>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <div>
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['full_name']); ?></div>
                                        <div class="text-sm text-gray-500">@<?php echo htmlspecialchars($user['username']); ?></div>
                                    </div>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars($user['email']); ?>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php echo $user['status'] === 'active' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'; ?>">
                                        <?php echo ucfirst($user['status']); ?>
                                    </span>
                                </td>
                                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo date('M d, Y', strtotime($user['created_at'])); ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <div class="mt-4 text-center">
                    <a href="manage-users.php" class="text-blue-600 hover:text-blue-700 font-medium">View All Users →</a>
                </div>
            </div>
            
            <!-- Recent Activity -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">Recent Activity</h2>
                <div class="space-y-4 max-h-96 overflow-y-auto">
                    <?php foreach ($recent_activities as $activity): ?>
                    <div class="flex items-start space-x-3 pb-3 border-b border-gray-100">
                        <div class="flex-shrink-0">
                            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center">
                                <span class="text-blue-600 font-semibold text-sm">
                                    <?php echo strtoupper(substr($activity['full_name'], 0, 2)); ?>
                                </span>
                            </div>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900">
                                <?php echo htmlspecialchars($activity['full_name']); ?>
                            </p>
                            <p class="text-sm text-gray-600">
                                <?php echo htmlspecialchars($activity['action_description']); ?>
                            </p>
                            <p class="text-xs text-gray-500 mt-1">
                                <?php echo date('M d, Y g:i A', strtotime($activity['created_at'])); ?>
                            </p>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
    </div>
    
</body>
</html>