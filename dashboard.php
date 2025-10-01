<?php
require_once 'config.php';
check_login();

$database = new Database();
$db = $database->getConnection();

// Get user's projects
$stmt = $db->prepare("
    SELECT * FROM projects 
    WHERE user_id = ? 
    ORDER BY updated_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$projects = $stmt->fetchAll();

// Get project count
$project_count = count($projects);
$active_projects = count(array_filter($projects, function($p) { return $p['status'] === 'active'; }));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="bg-gray-50">
    
    <!-- Navigation -->
    <nav class="bg-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <h1 class="text-xl font-bold text-gray-800"><?php echo SITE_NAME; ?></h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-gray-700">Welcome, <strong><?php echo htmlspecialchars($_SESSION['full_name']); ?></strong></span>
                    <a href="planner.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition">New Plan</a>
                    <a href="logout.php" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition">Logout</a>
                </div>
            </div>
        </div>
    </nav>
    
    <!-- Main Content -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        
        <!-- Statistics -->
        <div class="grid md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Total Plans</p>
                        <p class="text-3xl font-bold text-blue-600 mt-2"><?php echo $project_count; ?></p>
                    </div>
                    <div class="bg-blue-100 p-4 rounded-full">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                        </svg>
                    </div>
                </div>
            </div>
            
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-600 text-sm font-medium">Active Plans</p>
                        <p class="text-3xl font-bold text-green-600 mt-2"><?php echo $active_projects; ?></p>
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
                        <p class="text-gray-600 text-sm font-medium">Member Since</p>
                        <p class="text-lg font-bold text-purple-600 mt-2">
                            <?php 
                            $stmt = $db->prepare("SELECT created_at FROM users WHERE user_id = ?");
                            $stmt->execute([$_SESSION['user_id']]);
                            echo date('M Y', strtotime($stmt->fetch()['created_at']));
                            ?>
                        </p>
                    </div>
                    <div class="bg-purple-100 p-4 rounded-full">
                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Projects List -->
        <div class="bg-white rounded-lg shadow-md p-6">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-800">Your Plans</h2>
                <a href="planner.php" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition flex items-center">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                    </svg>
                    Create New Plan
                </a>
            </div>
            
            <?php if (empty($projects)): ?>
            <div class="text-center py-12">
                <svg class="w-24 h-24 mx-auto text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <h3 class="text-xl font-semibold text-gray-600 mb-2">No Plans Yet</h3>
                <p class="text-gray-500 mb-4">Start planning your product finances by creating your first plan!</p>
                <a href="planner.php" class="inline-block bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg transition">
                    Create Your First Plan
                </a>
            </div>
            <?php else: ?>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Plan Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Period</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Price</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Last Updated</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($projects as $project): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($project['project_name']); ?></div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo htmlspecialchars($project['product_name']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo $project['projection_months']; ?> months
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                ₱<?php echo number_format($project['unit_price'], 2); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?php 
                                    if ($project['status'] === 'active') echo 'bg-green-100 text-green-800';
                                    elseif ($project['status'] === 'draft') echo 'bg-yellow-100 text-yellow-800';
                                    else echo 'bg-gray-100 text-gray-800';
                                    ?>">
                                    <?php echo ucfirst($project['status']); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo date('M d, Y', strtotime($project['updated_at'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="view-plan.php?id=<?php echo $project['project_id']; ?>" class="text-blue-600 hover:text-blue-900 mr-3">View</a>
                                <a href="planner.php?edit=<?php echo $project['project_id']; ?>" class="text-green-600 hover:text-green-900 mr-3">Edit</a>
                                <button onclick="deleteProject(<?php echo $project['project_id']; ?>)" class="text-red-600 hover:text-red-900">Delete</button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
        
    </div>
    
    <script>
    function deleteProject(projectId) {
        Swal.fire({
            title: 'Delete Plan?',
            text: "This action cannot be undone!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#d33',
            cancelButtonColor: '#3b82f6',
            confirmButtonText: 'Yes, delete it!'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = `delete-project.php?id=${projectId}`;
            }
        });
    }
    </script>
    
</body>
</html>