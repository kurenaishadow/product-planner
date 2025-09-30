<?php
require_once 'config.php';
check_login();

if (!isset($_GET['id'])) {
    header('Location: dashboard.php');
    exit();
}

$project_id = (int)$_GET['id'];

$database = new Database();
$db = $database->getConnection();

// Get project details
$stmt = $db->prepare("SELECT * FROM projects WHERE project_id = ? AND user_id = ?");
$stmt->execute([$project_id, $_SESSION['user_id']]);
$project = $stmt->fetch();

if (!$project) {
    header('Location: dashboard.php?error=notfound');
    exit();
}

// Get calculations
$stmt = $db->prepare("
    SELECT * FROM project_calculations 
    WHERE project_id = ? 
    ORDER BY month_number ASC
");
$stmt->execute([$project_id]);
$calculations = $stmt->fetchAll();

// Calculate summary metrics
$breakEvenMonth = null;
$peakFunding = 0;
$minCash = $project['initial_cash'] - $project['initial_capex'];

foreach ($calculations as $calc) {
    if ($calc['cumulative_cash'] < $minCash) {
        $minCash = $calc['cumulative_cash'];
    }
    if (!$breakEvenMonth && $calc['cumulative_cash'] >= 0) {
        $breakEvenMonth = $calc['month_number'];
    }
}

$peakFunding = abs(min($minCash, 0));
$cashRunway = $breakEvenMonth ? $breakEvenMonth : count($calculations);

$profitPerUnit = $project['unit_price'] - $project['cogs_per_unit'];
$breakEvenUnits = ceil($project['fixed_opex'] / $profitPerUnit);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($project['project_name']); ?> - <?php echo SITE_NAME; ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .tab-content { display: none; }
        .tab-content.active { display: block; }
        .tab-button.active { background-color: #3b82f6; color: white; }
        @media print {
            .no-print { display: none; }
            .tab-content { display: block !important; }
        }
    </style>
</head>
<body class="bg-gray-50">
    
    <!-- Navigation -->
    <nav class="bg-white shadow-lg no-print">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="dashboard.php" class="text-xl font-bold text-gray-800">Product Money Planner</a>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="dashboard.php" class="text-gray-700 hover:text-gray-900">My Plans</a>
                    <a href="planner.php?edit=<?php echo $project_id; ?>" class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition">Edit Plan</a>
                    <a href="logout.php" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition">Logout</a>
                </div>
            </div>
        </div>
    </nav>
    
    <div class="min-h-screen p-4 md:p-8">
        <!-- Header -->
        <div class="max-w-7xl mx-auto mb-8">
            <h1 class="text-3xl md:text-4xl font-bold text-gray-800 mb-2"><?php echo htmlspecialchars($project['project_name']); ?></h1>
            <p class="text-gray-600">Product: <?php echo htmlspecialchars($project['product_name']); ?> | Created: <?php echo date('M d, Y', strtotime($project['created_at'])); ?></p>
        </div>

        <!-- Key Metrics -->
        <div class="max-w-7xl mx-auto mb-8">
            <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div class="bg-blue-50 p-4 rounded-lg border-l-4 border-blue-500">
                    <p class="text-sm text-gray-600 mb-1">When Will I Profit?</p>
                    <p class="text-3xl font-bold text-blue-600"><?php echo $breakEvenMonth ? 'Month ' . $breakEvenMonth : 'Not Yet'; ?></p>
                </div>
                <div class="bg-red-50 p-4 rounded-lg border-l-4 border-red-500">
                    <p class="text-sm text-gray-600 mb-1">Most Cash Needed</p>
                    <p class="text-3xl font-bold text-red-600">₱<?php echo number_format($peakFunding, 2); ?></p>
                    <p class="text-xs text-gray-500 mt-1">Extra money you might need</p>
                </div>
                <div class="bg-yellow-50 p-4 rounded-lg border-l-4 border-yellow-500">
                    <p class="text-sm text-gray-600 mb-1">How Long Can You Last?</p>
                    <p class="text-3xl font-bold text-yellow-600"><?php echo $cashRunway; ?> months</p>
                    <p class="text-xs text-gray-500 mt-1">Before running out of money</p>
                </div>
                <div class="bg-green-50 p-4 rounded-lg border-l-4 border-green-500">
                    <p class="text-sm text-gray-600 mb-1">Units to Break Even</p>
                    <p class="text-3xl font-bold text-green-600"><?php echo $breakEvenUnits; ?></p>
                    <p class="text-xs text-gray-500 mt-1">Per month</p>
                </div>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <div class="max-w-7xl mx-auto mb-6 no-print">
            <div class="flex flex-wrap gap-2 border-b border-gray-200">
                <button onclick="switchTab('summary')" class="tab-button active px-4 py-2 font-medium rounded-t-lg transition-colors">
                    Summary
                </button>
                <button onclick="switchTab('revenue')" class="tab-button px-4 py-2 font-medium rounded-t-lg transition-colors">
                    Sales Plan
                </button>
                <button onclick="switchTab('costs')" class="tab-button px-4 py-2 font-medium rounded-t-lg transition-colors">
                    Expenses
                </button>
                <button onclick="switchTab('projections')" class="tab-button px-4 py-2 font-medium rounded-t-lg transition-colors">
                    Money Flow
                </button>
            </div>
        </div>

        <div class="max-w-7xl mx-auto">
            
            <!-- TAB 1: Summary -->
            <div id="summary" class="tab-content active">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-2xl font-bold mb-6 text-gray-800">Plan Summary</h2>
                    
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <h3 class="font-semibold text-gray-700 mb-3">Basic Information</h3>
                            <div class="space-y-2">
                                <div class="flex justify-between py-2 border-b">
                                    <span class="text-gray-600">Product:</span>
                                    <span class="font-medium"><?php echo htmlspecialchars($project['product_name']); ?></span>
                                </div>
                                <div class="flex justify-between py-2 border-b">
                                    <span class="text-gray-600">Selling Price:</span>
                                    <span class="font-medium">₱<?php echo number_format($project['unit_price'], 2); ?></span>
                                </div>
                                <div class="flex justify-between py-2 border-b">
                                    <span class="text-gray-600">Product Cost:</span>
                                    <span class="font-medium">₱<?php echo number_format($project['cogs_per_unit'], 2); ?></span>
                                </div>
                                <div class="flex justify-between py-2 border-b">
                                    <span class="text-gray-600">Profit per Unit:</span>
                                    <span class="font-medium text-green-600">₱<?php echo number_format($profitPerUnit, 2); ?></span>
                                </div>
                                <div class="flex justify-between py-2 border-b">
                                    <span class="text-gray-600">Initial Units:</span>
                                    <span class="font-medium"><?php echo $project['initial_units']; ?></span>
                                </div>
                                <div class="flex justify-between py-2 border-b">
                                    <span class="text-gray-600">Monthly Growth:</span>
                                    <span class="font-medium"><?php echo $project['sales_growth_rate']; ?>%</span>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <h3 class="font-semibold text-gray-700 mb-3">Cost Structure</h3>
                            <div class="space-y-2">
                                <div class="flex justify-between py-2 border-b">
                                    <span class="text-gray-600">Fixed Monthly Costs:</span>
                                    <span class="font-medium">₱<?php echo number_format($project['fixed_opex'], 2); ?></span>
                                </div>
                                <div class="flex justify-between py-2 border-b">
                                    <span class="text-gray-600">Variable Costs:</span>
                                    <span class="font-medium"><?php echo $project['variable_opex_rate']; ?>% of sales</span>
                                </div>
                                <div class="flex justify-between py-2 border-b">
                                    <span class="text-gray-600">Starting Investment:</span>
                                    <span class="font-medium">₱<?php echo number_format($project['initial_capex'], 2); ?></span>
                                </div>
                                <div class="flex justify-between py-2 border-b">
                                    <span class="text-gray-600">Initial Cash:</span>
                                    <span class="font-medium">₱<?php echo number_format($project['initial_cash'], 2); ?></span>
                                </div>
                                <div class="flex justify-between py-2 border-b">
                                    <span class="text-gray-600">Projection Period:</span>
                                    <span class="font-medium"><?php echo $project['projection_months']; ?> months</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php
                    $totalRevenue = array_sum(array_column($calculations, 'revenue'));
                    $totalNetIncome = array_sum(array_column($calculations, 'net_income'));
                    $finalCash = end($calculations)['cumulative_cash'];
                    ?>
                    
                    <div class="mt-8">
                        <h3 class="font-semibold text-gray-700 mb-3">Overall Results</h3>
                        <div class="grid md:grid-cols-3 gap-4">
                            <div class="bg-blue-50 p-4 rounded-lg">
                                <p class="text-sm text-gray-600">Total Sales</p>
                                <p class="text-2xl font-bold text-blue-600">₱<?php echo number_format($totalRevenue, 2); ?></p>
                            </div>
                            <div class="bg-<?php echo $totalNetIncome >= 0 ? 'green' : 'red'; ?>-50 p-4 rounded-lg">
                                <p class="text-sm text-gray-600">Total Profit/Loss</p>
                                <p class="text-2xl font-bold text-<?php echo $totalNetIncome >= 0 ? 'green' : 'red'; ?>-600">₱<?php echo number_format($totalNetIncome, 2); ?></p>
                            </div>
                            <div class="bg-<?php echo $finalCash >= 0 ? 'green' : 'red'; ?>-50 p-4 rounded-lg">
                                <p class="text-sm text-gray-600">Final Cash</p>
                                <p class="text-2xl font-bold text-<?php echo $finalCash >= 0 ? 'green' : 'red'; ?>-600">₱<?php echo number_format($finalCash, 2); ?></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 2: Revenue -->
            <div id="revenue" class="tab-content">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-2xl font-bold mb-6 text-gray-800">Sales Plan</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Month</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Units Sold</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sales</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Gross Profit</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Margin %</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($calculations as $calc): ?>
                                <tr>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium">Month <?php echo $calc['month_number']; ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm"><?php echo $calc['units_sold']; ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm">₱<?php echo number_format($calc['revenue'], 2); ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm">₱<?php echo number_format($calc['gross_profit'], 2); ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm"><?php echo number_format($calc['gross_margin'], 2); ?>%</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 3: Costs -->
            <div id="costs" class="tab-content">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-2xl font-bold mb-6 text-gray-800">Monthly Expenses</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Month</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product Costs</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fixed Costs</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Variable Costs</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($calculations as $calc): ?>
                                <tr>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium">Month <?php echo $calc['month_number']; ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm">₱<?php echo number_format($calc['cogs'], 2); ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm">₱<?php echo number_format($calc['fixed_opex'], 2); ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm">₱<?php echo number_format($calc['variable_opex'], 2); ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium">₱<?php echo number_format($calc['cogs'] + $calc['total_opex'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 4: Projections -->
            <div id="projections" class="tab-content">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-2xl font-bold mb-6 text-gray-800">Money Flow</h2>
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Month</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sales</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Costs</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Profit/Loss</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cash Flow</th>
                                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Cash</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-gray-200">
                                <?php foreach ($calculations as $calc): ?>
                                <tr class="<?php echo $calc['cumulative_cash'] < 0 ? 'bg-red-50' : ''; ?>">
                                    <td class="px-4 py-3 whitespace-nowrap text-sm font-medium">Month <?php echo $calc['month_number']; ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm">₱<?php echo number_format($calc['revenue'], 2); ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm">₱<?php echo number_format($calc['cogs'] + $calc['total_opex'], 2); ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm <?php echo $calc['net_income'] >= 0 ? 'text-green-600' : 'text-red-600'; ?> font-medium">₱<?php echo number_format($calc['net_income'], 2); ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm <?php echo $calc['cash_flow'] >= 0 ? 'text-green-600' : 'text-red-600'; ?>">₱<?php echo number_format($calc['cash_flow'], 2); ?></td>
                                    <td class="px-4 py-3 whitespace-nowrap text-sm <?php echo $calc['cumulative_cash'] >= 0 ? 'text-green-600' : 'text-red-600'; ?> font-bold">₱<?php echo number_format($calc['cumulative_cash'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>

        <!-- Action Buttons -->
        <div class="max-w-7xl mx-auto mt-6 no-print flex gap-3">
            <button onclick="window.print()" class="bg-purple-600 hover:bg-purple-700 text-white font-medium px-6 py-3 rounded-lg transition-colors">
                Print Report
            </button>
            <a href="planner.php?edit=<?php echo $project_id; ?>" class="bg-green-600 hover:bg-green-700 text-white font-medium px-6 py-3 rounded-lg transition-colors inline-block">
                Edit This Plan
            </a>
            <a href="dashboard.php" class="bg-gray-600 hover:bg-gray-700 text-white font-medium px-6 py-3 rounded-lg transition-colors inline-block">
                Back to Dashboard
            </a>
        </div>
    </div>

    <script>
        function switchTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            document.querySelectorAll('.tab-button').forEach(btn => {
                btn.classList.remove('active');
            });
            
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
        }
    </script>
    
</body>
</html>