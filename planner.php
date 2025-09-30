<?php
require_once 'config.php';
check_login();

$database = new Database();
$db = $database->getConnection();

$edit_mode = false;
$project_data = null;

// Check if editing existing project
if (isset($_GET['edit'])) {
    $project_id = (int)$_GET['edit'];
    $stmt = $db->prepare("SELECT * FROM projects WHERE project_id = ? AND user_id = ?");
    $stmt->execute([$project_id, $_SESSION['user_id']]);
    $project_data = $stmt->fetch();
    
    if ($project_data) {
        $edit_mode = true;
    }
}

// Handle Save Project
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_project'])) {
    $project_name = sanitize_input($_POST['project_name']);
    $product_name = sanitize_input($_POST['product_name']);
    $projection_months = (int)$_POST['projection_months'];
    $unit_price = (float)$_POST['unit_price'];
    $initial_units = (int)$_POST['initial_units'];
    $sales_growth_rate = (float)$_POST['sales_growth'];
    $cogs_per_unit = (float)$_POST['cogs_per_unit'];
    $fixed_opex = (float)$_POST['fixed_opex'];
    $variable_opex_rate = (float)$_POST['variable_opex'];
    $initial_capex = (float)$_POST['capex'];
    $initial_cash = (float)$_POST['initial_cash'];
    $calculations = json_decode($_POST['calculations_data'], true);
    
    try {
        if ($edit_mode && isset($_POST['project_id'])) {
            // Update existing project
            $project_id = (int)$_POST['project_id'];
            
            $stmt = $db->prepare("
                UPDATE projects SET 
                project_name = ?, product_name = ?, projection_months = ?,
                unit_price = ?, initial_units = ?, sales_growth_rate = ?,
                cogs_per_unit = ?, fixed_opex = ?, variable_opex_rate = ?,
                initial_capex = ?, initial_cash = ?, updated_at = NOW()
                WHERE project_id = ? AND user_id = ?
            ");
            
            $stmt->execute([
                $project_name, $product_name, $projection_months,
                $unit_price, $initial_units, $sales_growth_rate,
                $cogs_per_unit, $fixed_opex, $variable_opex_rate,
                $initial_capex, $initial_cash, $project_id, $_SESSION['user_id']
            ]);
            
            // Delete old calculations
            $stmt = $db->prepare("DELETE FROM project_calculations WHERE project_id = ?");
            $stmt->execute([$project_id]);
            
            log_activity($_SESSION['user_id'], 'project_updated', "Updated project: $project_name", $db);
            $message = 'Project updated successfully!';
            
        } else {
            // Create new project
            $stmt = $db->prepare("
                INSERT INTO projects (
                    user_id, project_name, product_name, projection_months,
                    unit_price, initial_units, sales_growth_rate,
                    cogs_per_unit, fixed_opex, variable_opex_rate,
                    initial_capex, initial_cash
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $_SESSION['user_id'], $project_name, $product_name, $projection_months,
                $unit_price, $initial_units, $sales_growth_rate,
                $cogs_per_unit, $fixed_opex, $variable_opex_rate,
                $initial_capex, $initial_cash
            ]);
            
            $project_id = $db->lastInsertId();
            log_activity($_SESSION['user_id'], 'project_created', "Created project: $project_name", $db);
            $message = 'Project saved successfully!';
        }
        
        // Save calculations
        if (!empty($calculations)) {
            $stmt = $db->prepare("
                INSERT INTO project_calculations (
                    project_id, month_number, units_sold, revenue, cogs,
                    gross_profit, gross_margin, fixed_opex, variable_opex,
                    total_opex, net_income, cash_flow, cumulative_cash
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            foreach ($calculations as $calc) {
                $stmt->execute([
                    $project_id, $calc['month'], $calc['units'], $calc['revenue'],
                    $calc['cogs'], $calc['grossProfit'], $calc['grossMargin'],
                    $calc['fixedOpex'], $calc['variableOpex'], $calc['totalOpex'],
                    $calc['netIncome'], $calc['cashFlow'], $calc['cumulativeCash']
                ]);
            }
        }
        
        echo "<script>
            Swal.fire({
                icon: 'success',
                title: 'Success!',
                text: '$message',
                confirmButtonColor: '#3b82f6'
            }).then(() => {
                window.location.href = 'dashboard.php';
            });
        </script>";
        
    } catch (PDOException $e) {
        error_log("Save Project Error: " . $e->getMessage());
        echo "<script>
            Swal.fire({
                icon: 'error',
                title: 'Error!',
                text: 'Failed to save project. Please try again.',
                confirmButtonColor: '#d33'
            });
        </script>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $edit_mode ? 'Edit' : 'Create'; ?> Plan - <?php echo SITE_NAME; ?></title>
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
                    <span class="text-gray-700"><?php echo htmlspecialchars($_SESSION['full_name']); ?></span>
                    <a href="logout.php" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition">Logout</a>
                </div>
            </div>
        </div>
    </nav>
    
    <div class="min-h-screen p-4 md:p-8">
        <!-- Header -->
        <div class="max-w-7xl mx-auto mb-8">
            <h1 class="text-3xl md:text-4xl font-bold text-gray-800 mb-2">
                <?php echo $edit_mode ? 'Edit Your Plan' : 'Create New Plan'; ?>
            </h1>
            <p class="text-gray-600">Plan your product finances and see when you'll make profit</p>
        </div>

        <!-- Navigation Tabs -->
        <div class="max-w-7xl mx-auto mb-6 no-print">
            <div class="flex flex-wrap gap-2 border-b border-gray-200">
                <button onclick="switchTab('inputs')" class="tab-button active px-4 py-2 font-medium rounded-t-lg transition-colors">
                    Basic Info
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
                <button onclick="switchTab('analysis')" class="tab-button px-4 py-2 font-medium rounded-t-lg transition-colors">
                    When Will I Profit?
                </button>
            </div>
        </div>

        <div class="max-w-7xl mx-auto">
            <!-- TAB 1: Input Parameters -->
            <div id="inputs" class="tab-content active">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-2xl font-bold mb-6 text-gray-800">Tell Us About Your Product</h2>
                    
                    <div class="grid md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Plan Name *</label>
                            <input type="text" id="projectName" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="My Business Plan 2025" value="<?php echo $edit_mode ? htmlspecialchars($project_data['project_name']) : ''; ?>" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Product Name *</label>
                            <input type="text" id="productName" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500" placeholder="What are you selling?" value="<?php echo $edit_mode ? htmlspecialchars($project_data['product_name']) : ''; ?>" required>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">How many months to plan? (12-60)</label>
                            <input type="number" id="projectionMonths" value="<?php echo $edit_mode ? $project_data['projection_months'] : '36'; ?>" min="12" max="60" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Selling Price (₱ per piece)</label>
                            <input type="number" id="unitPrice" value="<?php echo $edit_mode ? $project_data['unit_price'] : '100'; ?>" step="0.01" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">How many will you sell in Month 1?</label>
                            <input type="number" id="initialUnits" value="<?php echo $edit_mode ? $project_data['initial_units'] : '50'; ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">How fast will sales grow each month? (%)</label>
                            <input type="number" id="salesGrowth" value="<?php echo $edit_mode ? $project_data['sales_growth_rate'] : '5'; ?>" step="0.1" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <p class="text-xs text-gray-500 mt-1">Example: 5% means you'll sell 5% more each month</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Cost to make/buy each product (₱)</label>
                            <input type="number" id="cogsPerUnit" value="<?php echo $edit_mode ? $project_data['cogs_per_unit'] : '40'; ?>" step="0.01" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <p class="text-xs text-gray-500 mt-1">Materials, labor, or supplier cost per unit</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Fixed Monthly Expenses (₱)</label>
                            <input type="number" id="fixedOpex" value="<?php echo $edit_mode ? $project_data['fixed_opex'] : '5000'; ?>" step="100" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <p class="text-xs text-gray-500 mt-1">Rent, salaries, utilities - costs that don't change</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Extra Costs (% of sales)</label>
                            <input type="number" id="variableOpex" value="<?php echo $edit_mode ? $project_data['variable_opex_rate'] : '10'; ?>" step="0.1" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <p class="text-xs text-gray-500 mt-1">Marketing, commissions - costs that increase with sales</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Starting Investment (₱)</label>
                            <input type="number" id="capex" value="<?php echo $edit_mode ? $project_data['initial_capex'] : '20000'; ?>" step="1000" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <p class="text-xs text-gray-500 mt-1">Equipment, inventory, setup costs</p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Cash You Have Now (₱)</label>
                            <input type="number" id="initialCash" value="<?php echo $edit_mode ? $project_data['initial_cash'] : '30000'; ?>" step="1000" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                            <p class="text-xs text-gray-500 mt-1">Money available to start</p>
                        </div>
                    </div>
                    
                    <div class="mt-6 flex gap-3">
                        <button onclick="calculateProjections()" class="bg-blue-600 hover:bg-blue-700 text-white font-medium px-6 py-3 rounded-lg transition-colors">
                            Calculate My Plan
                        </button>
                        <button onclick="saveProject()" class="bg-green-600 hover:bg-green-700 text-white font-medium px-6 py-3 rounded-lg transition-colors" id="saveBtn" disabled>
                            <?php echo $edit_mode ? 'Update Plan' : 'Save Plan'; ?>
                        </button>
                        <a href="dashboard.php" class="bg-gray-600 hover:bg-gray-700 text-white font-medium px-6 py-3 rounded-lg transition-colors inline-flex items-center">
                            Cancel
                        </a>
                    </div>
                </div>
            </div>

            <!-- Other tabs remain the same as your original HTML -->
            <!-- TAB 2: Revenue Model -->
            <div id="revenue" class="tab-content">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-2xl font-bold mb-6 text-gray-800">Your Sales Plan</h2>
                    <div id="revenueContent" class="overflow-x-auto">
                        <p class="text-gray-600">Click "Calculate My Plan" first.</p>
                    </div>
                </div>
            </div>

            <!-- TAB 3: Cost Structure -->
            <div id="costs" class="tab-content">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-2xl font-bold mb-6 text-gray-800">Your Monthly Expenses</h2>
                    <div id="costsContent">
                        <p class="text-gray-600">Click "Calculate My Plan" first.</p>
                    </div>
                </div>
            </div>

            <!-- TAB 4: Financial Projections -->
            <div id="projections" class="tab-content">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-2xl font-bold mb-6 text-gray-800">Your Money Flow</h2>
                    <div id="projectionsContent">
                        <p class="text-gray-600">Click "Calculate My Plan" first.</p>
                    </div>
                </div>
            </div>

            <!-- TAB 5: Analysis & Scenarios -->
            <div id="analysis" class="tab-content">
                <div class="bg-white rounded-lg shadow-md p-6">
                    <h2 class="text-2xl font-bold mb-6 text-gray-800">When Will I Make Profit?</h2>
                    <div id="analysisContent">
                        <p class="text-gray-600">Click "Calculate My Plan" first.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Export Button -->
        <div class="max-w-7xl mx-auto mt-6 no-print">
            <button onclick="exportReport()" class="bg-purple-600 hover:bg-purple-700 text-white font-medium px-6 py-3 rounded-lg transition-colors">
                Print Report
            </button>
        </div>
    </div>

    <script>
        let modelData = null;
        const editMode = <?php echo $edit_mode ? 'true' : 'false'; ?>;
        const projectId = <?php echo $edit_mode ? $project_data['project_id'] : 'null'; ?>;

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

        function calculateProjections() {
            const projectName = document.getElementById('projectName').value || 'My Plan';
            const productName = document.getElementById('productName').value || 'My Product';
            const months = parseInt(document.getElementById('projectionMonths').value);
            const unitPrice = parseFloat(document.getElementById('unitPrice').value);
            const initialUnits = parseInt(document.getElementById('initialUnits').value);
            const salesGrowth = parseFloat(document.getElementById('salesGrowth').value) / 100;
            const cogsPerUnit = parseFloat(document.getElementById('cogsPerUnit').value);
            const fixedOpex = parseFloat(document.getElementById('fixedOpex').value);
            const variableOpexRate = parseFloat(document.getElementById('variableOpex').value) / 100;
            const capex = parseFloat(document.getElementById('capex').value);
            const initialCash = parseFloat(document.getElementById('initialCash').value);

            if (!projectName || !productName) {
                Swal.fire({
                    icon: 'error',
                    title: 'Missing Information',
                    text: 'Please fill in Plan Name and Product Name.'
                });
                return;
            }

            if (isNaN(unitPrice) || isNaN(initialUnits) || unitPrice <= 0 || initialUnits <= 0) {
                Swal.fire({
                    icon: 'error',
                    title: 'Oops!',
                    text: 'Please fill in all the numbers correctly.'
                });
                return;
            }

            modelData = {
                projectName,
                productName,
                months: [],
                breakEven: null,
                peakFunding: 0,
                cashRunway: 0
            };

            let cumulativeCash = initialCash - capex;
            let units = initialUnits;
            let breakEvenMonth = null;
            let minCash = cumulativeCash;

            for (let i = 1; i <= months; i++) {
                const revenue = units * unitPrice;
                const cogs = units * cogsPerUnit;
                const grossProfit = revenue - cogs;
                const grossMargin = (grossProfit / revenue) * 100;
                
                const variableOpex = revenue * variableOpexRate;
                const totalOpex = fixedOpex + variableOpex;
                
                const netIncome = grossProfit - totalOpex;
                const cashFlow = netIncome - (i === 1 ? capex : 0);
                cumulativeCash += cashFlow;
                
                if (cumulativeCash < minCash) {
                    minCash = cumulativeCash;
                }
                
                if (!breakEvenMonth && cumulativeCash >= 0) {
                    breakEvenMonth = i;
                }

                modelData.months.push({
                    month: i,
                    units,
                    revenue,
                    cogs,
                    grossProfit,
                    grossMargin,
                    fixedOpex,
                    variableOpex,
                    totalOpex,
                    netIncome,
                    cashFlow,
                    cumulativeCash
                });

                units = Math.round(units * (1 + salesGrowth));
            }

            modelData.breakEven = breakEvenMonth;
            modelData.peakFunding = Math.abs(Math.min(minCash, 0));
            modelData.cashRunway = breakEvenMonth || months;

            displayRevenueModel();
            displayCostStructure();
            displayFinancialProjections();
            displayAnalysis();

            // Enable save button
            document.getElementById('saveBtn').disabled = false;

            Swal.fire({
                icon: 'success',
                title: 'Done!',
                text: 'Your plan is ready to view.',
                timer: 2000,
                showConfirmButton: false
            });
        }

        function saveProject() {
            if (!modelData) {
                Swal.fire({
                    icon: 'warning',
                    title: 'Calculate First',
                    text: 'Please calculate your plan before saving.'
                });
                return;
            }

            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="save_project" value="1">
                ${editMode ? '<input type="hidden" name="project_id" value="' + projectId + '">' : ''}
                <input type="hidden" name="project_name" value="${document.getElementById('projectName').value}">
                <input type="hidden" name="product_name" value="${document.getElementById('productName').value}">
                <input type="hidden" name="projection_months" value="${document.getElementById('projectionMonths').value}">
                <input type="hidden" name="unit_price" value="${document.getElementById('unitPrice').value}">
                <input type="hidden" name="initial_units" value="${document.getElementById('initialUnits').value}">
                <input type="hidden" name="sales_growth" value="${document.getElementById('salesGrowth').value}">
                <input type="hidden" name="cogs_per_unit" value="${document.getElementById('cogsPerUnit').value}">
                <input type="hidden" name="fixed_opex" value="${document.getElementById('fixedOpex').value}">
                <input type="hidden" name="variable_opex" value="${document.getElementById('variableOpex').value}">
                <input type="hidden" name="capex" value="${document.getElementById('capex').value}">
                <input type="hidden" name="initial_cash" value="${document.getElementById('initialCash').value}">
                <input type="hidden" name="calculations_data" value='${JSON.stringify(modelData.months)}'>
            `;
            document.body.appendChild(form);
            form.submit();
        }

        // All display functions from your original HTML go here
        // (displayRevenueModel, displayCostStructure, displayFinancialProjections, displayAnalysis, etc.)
        
        <?php 
        // Include all the JavaScript functions from the original HTML
        echo file_get_contents('planner-functions.js');
        ?>
    </script>
     <script src="planner-functions.js"></script>
</body>
</html>