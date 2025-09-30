// Display Revenue Model
function displayRevenueModel() {
    const content = document.getElementById('revenueContent');
    
    let html = `
        <div class="mb-6">
            <h3 class="text-xl font-semibold mb-4">Sales Summary</h3>
            <div class="grid md:grid-cols-3 gap-4 mb-6">
                <div class="bg-blue-50 p-4 rounded-lg">
                    <p class="text-sm text-gray-600">First Month Sales</p>
                    <p class="text-2xl font-bold text-blue-600">₱${modelData.months[0].revenue.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p>
                </div>
                <div class="bg-green-50 p-4 rounded-lg">
                    <p class="text-sm text-gray-600">Highest Monthly Sales</p>
                    <p class="text-2xl font-bold text-green-600">₱${Math.max(...modelData.months.map(m => m.revenue)).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p>
                </div>
                <div class="bg-purple-50 p-4 rounded-lg">
                    <p class="text-sm text-gray-600">Total Sales (All Months)</p>
                    <p class="text-2xl font-bold text-purple-600">₱${modelData.months.reduce((sum, m) => sum + m.revenue, 0).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p>
                </div>
            </div>
        </div>

        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Month</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Units Sold</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sales</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Growth</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
    `;

    modelData.months.forEach((month, index) => {
        const growth = index > 0 ? ((month.revenue - modelData.months[index-1].revenue) / modelData.months[index-1].revenue * 100).toFixed(1) : 'N/A';
        html += `
            <tr>
                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">Month ${month.month}</td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">${month.units}</td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">₱${month.revenue.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                <td class="px-4 py-3 whitespace-nowrap text-sm ${growth !== 'N/A' && parseFloat(growth) > 0 ? 'text-green-600' : 'text-gray-500'}">${growth !== 'N/A' ? growth + '%' : growth}</td>
            </tr>
        `;
    });

    html += `</tbody></table>`;
    content.innerHTML = html;
}

// Display Cost Structure
function displayCostStructure() {
    const content = document.getElementById('costsContent');
    
    const avgGrossMargin = (modelData.months.reduce((sum, m) => sum + m.grossMargin, 0) / modelData.months.length).toFixed(2);
    const totalCOGS = modelData.months.reduce((sum, m) => sum + m.cogs, 0);
    const totalOpex = modelData.months.reduce((sum, m) => sum + m.totalOpex, 0);

    let html = `
        <div class="mb-6">
            <h3 class="text-xl font-semibold mb-4">Total Costs</h3>
            <div class="grid md:grid-cols-3 gap-4 mb-6">
                <div class="bg-red-50 p-4 rounded-lg">
                    <p class="text-sm text-gray-600">Total Product Costs</p>
                    <p class="text-2xl font-bold text-red-600">₱${totalCOGS.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p>
                    <p class="text-xs text-gray-500 mt-1">Cost to make/buy all products</p>
                </div>
                <div class="bg-orange-50 p-4 rounded-lg">
                    <p class="text-sm text-gray-600">Total Operating Costs</p>
                    <p class="text-2xl font-bold text-orange-600">₱${totalOpex.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p>
                    <p class="text-xs text-gray-500 mt-1">Rent, marketing, salaries, etc.</p>
                </div>
                <div class="bg-indigo-50 p-4 rounded-lg">
                    <p class="text-sm text-gray-600">Average Profit per Sale</p>
                    <p class="text-2xl font-bold text-indigo-600">${avgGrossMargin}%</p>
                    <p class="text-xs text-gray-500 mt-1">After product costs</p>
                </div>
            </div>
        </div>

        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Month</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Product Costs</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Fixed Expenses</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Extra Expenses</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Costs</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Profit %</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
    `;

    modelData.months.forEach(month => {
        const totalCosts = month.cogs + month.totalOpex;
        html += `
            <tr>
                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">Month ${month.month}</td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">₱${month.cogs.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">₱${month.fixedOpex.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">₱${month.variableOpex.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">₱${totalCosts.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                <td class="px-4 py-3 whitespace-nowrap text-sm ${month.grossMargin > 50 ? 'text-green-600' : month.grossMargin > 30 ? 'text-yellow-600' : 'text-red-600'}">${month.grossMargin.toFixed(2)}%</td>
            </tr>
        `;
    });

    html += `</tbody></table>`;
    content.innerHTML = html;
}

// Display Financial Projections
function displayFinancialProjections() {
    const content = document.getElementById('projectionsContent');
    
    const totalRevenue = modelData.months.reduce((sum, m) => sum + m.revenue, 0);
    const totalNetIncome = modelData.months.reduce((sum, m) => sum + m.netIncome, 0);
    const finalCash = modelData.months[modelData.months.length - 1].cumulativeCash;

    let html = `
        <div class="mb-6">
            <h3 class="text-xl font-semibold mb-4">Money Summary</h3>
            <div class="grid md:grid-cols-3 gap-4 mb-6">
                <div class="bg-blue-50 p-4 rounded-lg">
                    <p class="text-sm text-gray-600">Total Money Coming In</p>
                    <p class="text-2xl font-bold text-blue-600">₱${totalRevenue.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p>
                </div>
                <div class="bg-${totalNetIncome >= 0 ? 'green' : 'red'}-50 p-4 rounded-lg">
                    <p class="text-sm text-gray-600">Total Profit/Loss</p>
                    <p class="text-2xl font-bold text-${totalNetIncome >= 0 ? 'green' : 'red'}-600">₱${totalNetIncome.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p>
                </div>
                <div class="bg-${finalCash >= 0 ? 'green' : 'red'}-50 p-4 rounded-lg">
                    <p class="text-sm text-gray-600">Cash Left at End</p>
                    <p class="text-2xl font-bold text-${finalCash >= 0 ? 'green' : 'red'}-600">₱${finalCash.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Month</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Sales</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Profit After Product Cost</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">All Expenses</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Monthly Profit/Loss</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Cash In/Out</th>
                        <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Total Cash</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
    `;

    modelData.months.forEach(month => {
        html += `
            <tr class="${month.cumulativeCash < 0 ? 'bg-red-50' : ''}">
                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium text-gray-900">Month ${month.month}</td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">₱${month.revenue.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">₱${month.grossProfit.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-900">₱${month.totalOpex.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                <td class="px-4 py-3 whitespace-nowrap text-sm ${month.netIncome >= 0 ? 'text-green-600' : 'text-red-600'} font-medium">₱${month.netIncome.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                <td class="px-4 py-3 whitespace-nowrap text-sm ${month.cashFlow >= 0 ? 'text-green-600' : 'text-red-600'}">₱${month.cashFlow.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                <td class="px-4 py-3 whitespace-nowrap text-sm ${month.cumulativeCash >= 0 ? 'text-green-600' : 'text-red-600'} font-bold">₱${month.cumulativeCash.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
            </tr>
        `;
    });

    html += `</tbody></table>`;
    if (finalCash < 0) {
        html += `<div class="mt-4 bg-red-50 border-l-4 border-red-500 p-4">
            <p class="text-red-700 font-medium">⚠️ Warning: You'll run out of money before the end of the period!</p>
            <p class="text-sm text-red-600 mt-1">You may need more starting cash or need to adjust your plan.</p>
        </div>`;
    }
    html += `</div>`;

    content.innerHTML = html;
}

// Display Analysis
function displayAnalysis() {
    const content = document.getElementById('analysisContent');
    
    const unitPrice = parseFloat(document.getElementById('unitPrice').value);
    const cogsPerUnit = parseFloat(document.getElementById('cogsPerUnit').value);
    const fixedOpex = parseFloat(document.getElementById('fixedOpex').value);
    const profitPerUnit = unitPrice - cogsPerUnit;
    const breakEvenUnits = Math.ceil(fixedOpex / profitPerUnit);

    let html = `
        <div class="space-y-6">
            <div>
                <h3 class="text-xl font-semibold mb-4">Important Numbers</h3>
                <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div class="bg-blue-50 p-4 rounded-lg border-l-4 border-blue-500">
                        <p class="text-sm text-gray-600 mb-1">When Will I Profit?</p>
                        <p class="text-3xl font-bold text-blue-600">${modelData.breakEven ? 'Month ' + modelData.breakEven : 'Not Yet'}</p>
                    </div>
                    <div class="bg-red-50 p-4 rounded-lg border-l-4 border-red-500">
                        <p class="text-sm text-gray-600 mb-1">Most Cash Needed</p>
                        <p class="text-3xl font-bold text-red-600">₱${modelData.peakFunding.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p>
                        <p class="text-xs text-gray-500 mt-1">Extra money you might need</p>
                    </div>
                    <div class="bg-yellow-50 p-4 rounded-lg border-l-4 border-yellow-500">
                        <p class="text-sm text-gray-600 mb-1">How Long Can You Last?</p>
                        <p class="text-3xl font-bold text-yellow-600">${modelData.cashRunway} months</p>
                        <p class="text-xs text-gray-500 mt-1">Before running out of money</p>
                    </div>
                    <div class="bg-green-50 p-4 rounded-lg border-l-4 border-green-500">
                        <p class="text-sm text-gray-600 mb-1">Units to Sell to Break Even</p>
                        <p class="text-3xl font-bold text-green-600">${breakEvenUnits}</p>
                        <p class="text-xs text-gray-500 mt-1">Per month</p>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <h3 class="text-xl font-semibold mb-4">Understanding Your Numbers</h3>
                <div class="space-y-3">
                    <div class="flex justify-between py-2 border-b">
                        <span class="font-medium text-gray-700">You sell each for:</span>
                        <span class="text-gray-900">₱${unitPrice.toFixed(2)}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b">
                        <span class="font-medium text-gray-700">Each costs you:</span>
                        <span class="text-gray-900">₱${cogsPerUnit.toFixed(2)}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b">
                        <span class="font-medium text-gray-700">You make per item:</span>
                        <span class="text-green-600 font-semibold">₱${profitPerUnit.toFixed(2)}</span>
                    </div>
                    <div class="flex justify-between py-2 border-b">
                        <span class="font-medium text-gray-700">Your monthly fixed costs:</span>
                        <span class="text-gray-900">₱${fixedOpex.toFixed(2)}</span>
                    </div>
                    <div class="flex justify-between py-3 bg-blue-50 px-3 rounded">
                        <span class="font-bold text-gray-800">You need to sell per month:</span>
                        <span class="text-blue-600 font-bold text-lg">${breakEvenUnits} units</span>
                    </div>
                    <div class="flex justify-between py-3 bg-blue-50 px-3 rounded">
                        <span class="font-bold text-gray-800">That's monthly sales of:</span>
                        <span class="text-blue-600 font-bold text-lg">₱${(breakEvenUnits * unitPrice).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</span>
                    </div>
                </div>
            </div>

            <div class="bg-white border border-gray-200 rounded-lg p-6">
                <h3 class="text-xl font-semibold mb-4">What If Things Change?</h3>
                <p class="text-gray-600 mb-4">Test different scenarios to see how they affect your business:</p>
                
                <div class="grid md:grid-cols-2 gap-6 mb-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Change Price By (%)</label>
                        <input type="number" id="scenarioPrice" value="0" step="5" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <p class="text-xs text-gray-500 mt-1">Example: -10 = 10% cheaper, +10 = 10% more expensive</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Change Growth Speed By (%)</label>
                        <input type="number" id="scenarioGrowth" value="0" step="5" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500">
                        <p class="text-xs text-gray-500 mt-1">Example: -50 = half the growth, +50 = 50% faster growth</p>
                    </div>
                </div>
                
                <button onclick="runScenario()" class="bg-purple-600 hover:bg-purple-700 text-white font-medium px-6 py-2 rounded-lg transition-colors">
                    Test This Scenario
                </button>
                
                <div id="scenarioResults" class="mt-4"></div>
            </div>

            <div class="bg-red-50 border border-red-200 rounded-lg p-6">
                <h3 class="text-xl font-semibold mb-4 text-red-800">If Things Go Badly</h3>
                <p class="text-gray-700 mb-4">What if sales grow slower and you have to lower prices?</p>
                <p class="text-sm text-gray-600 mb-4">(50% slower growth + 10% lower price)</p>
                <div id="worstCaseResults"></div>
            </div>

            <div class="bg-green-50 border border-green-200 rounded-lg p-6">
                <h3 class="text-xl font-semibold mb-4 text-green-800">If Things Go Great</h3>
                <p class="text-gray-700 mb-4">What if sales grow faster and you can charge more?</p>
                <p class="text-sm text-gray-600 mb-4">(50% faster growth + 10% higher price)</p>
                <div id="bestCaseResults"></div>
            </div>
        </div>
    `;

    content.innerHTML = html;
    
    calculateWorstCase();
    calculateBestCase();
}

// Run Scenario Analysis
function runScenario() {
    const priceAdj = parseFloat(document.getElementById('scenarioPrice').value) / 100;
    const growthAdj = parseFloat(document.getElementById('scenarioGrowth').value) / 100;
    
    const basePrice = parseFloat(document.getElementById('unitPrice').value);
    const baseGrowth = parseFloat(document.getElementById('salesGrowth').value) / 100;
    
    const scenarioPrice = basePrice * (1 + priceAdj);
    const scenarioGrowth = baseGrowth + (baseGrowth * growthAdj);
    
    const months = parseInt(document.getElementById('projectionMonths').value);
    const initialUnits = parseInt(document.getElementById('initialUnits').value);
    const cogsPerUnit = parseFloat(document.getElementById('cogsPerUnit').value);
    const fixedOpex = parseFloat(document.getElementById('fixedOpex').value);
    const variableOpexRate = parseFloat(document.getElementById('variableOpex').value) / 100;
    const capex = parseFloat(document.getElementById('capex').value);
    const initialCash = parseFloat(document.getElementById('initialCash').value);
    
    let scenarioCash = initialCash - capex;
    let units = initialUnits;
    let totalRevenue = 0;
    let totalNetIncome = 0;
    let breakEvenMonth = null;
    
    for (let i = 1; i <= months; i++) {
        const revenue = units * scenarioPrice;
        const cogs = units * cogsPerUnit;
        const grossProfit = revenue - cogs;
        const variableOpex = revenue * variableOpexRate;
        const totalOpex = fixedOpex + variableOpex;
        const netIncome = grossProfit - totalOpex;
        const cashFlow = netIncome - (i === 1 ? capex : 0);
        
        scenarioCash += cashFlow;
        totalRevenue += revenue;
        totalNetIncome += netIncome;
        
        if (!breakEvenMonth && scenarioCash >= 0) {
            breakEvenMonth = i;
        }
        
        units = Math.round(units * (1 + scenarioGrowth));
    }
    
    const resultsDiv = document.getElementById('scenarioResults');
    resultsDiv.innerHTML = `
        <div class="bg-purple-50 rounded-lg p-4 space-y-2">
            <h4 class="font-semibold text-purple-800 mb-3">Results:</h4>
            <div class="grid md:grid-cols-3 gap-3">
                <div class="bg-white p-3 rounded">
                    <p class="text-xs text-gray-600">Total Sales</p>
                    <p class="text-lg font-bold text-purple-600">₱${totalRevenue.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p>
                </div>
                <div class="bg-white p-3 rounded">
                    <p class="text-xs text-gray-600">Total Profit/Loss</p>
                    <p class="text-lg font-bold ${totalNetIncome >= 0 ? 'text-green-600' : 'text-red-600'}">₱${totalNetIncome.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p>
                </div>
                <div class="bg-white p-3 rounded">
                    <p class="text-xs text-gray-600">When You Profit</p>
                    <p class="text-lg font-bold text-blue-600">${breakEvenMonth ? 'Month ' + breakEvenMonth : 'Not Yet'}</p>
                </div>
            </div>
            <div class="bg-white p-3 rounded mt-2">
                <p class="text-xs text-gray-600">Cash at End</p>
                <p class="text-xl font-bold ${scenarioCash >= 0 ? 'text-green-600' : 'text-red-600'}">₱${scenarioCash.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p>
            </div>
        </div>
    `;
}

// Calculate Worst Case
function calculateWorstCase() {
    const basePrice = parseFloat(document.getElementById('unitPrice').value);
    const baseGrowth = parseFloat(document.getElementById('salesGrowth').value) / 100;
    
    const worstPrice = basePrice * 0.9;
    const worstGrowth = baseGrowth * 0.5;
    
    const months = parseInt(document.getElementById('projectionMonths').value);
    const initialUnits = parseInt(document.getElementById('initialUnits').value);
    const cogsPerUnit = parseFloat(document.getElementById('cogsPerUnit').value);
    const fixedOpex = parseFloat(document.getElementById('fixedOpex').value);
    const variableOpexRate = parseFloat(document.getElementById('variableOpex').value) / 100;
    const capex = parseFloat(document.getElementById('capex').value);
    const initialCash = parseFloat(document.getElementById('initialCash').value);
    
    let worstCash = initialCash - capex;
    let units = initialUnits;
    let totalRevenue = 0;
    let totalNetIncome = 0;
    let breakEvenMonth = null;
    let minCash = worstCash;
    
    for (let i = 1; i <= months; i++) {
        const revenue = units * worstPrice;
        const cogs = units * cogsPerUnit;
        const grossProfit = revenue - cogs;
        const variableOpex = revenue * variableOpexRate;
        const totalOpex = fixedOpex + variableOpex;
        const netIncome = grossProfit - totalOpex;
        const cashFlow = netIncome - (i === 1 ? capex : 0);
        
        worstCash += cashFlow;
        totalRevenue += revenue;
        totalNetIncome += netIncome;
        
        if (worstCash < minCash) minCash = worstCash;
        if (!breakEvenMonth && worstCash >= 0) breakEvenMonth = i;
        
        units = Math.round(units * (1 + worstGrowth));
    }
    
    const resultsDiv = document.getElementById('worstCaseResults');
    resultsDiv.innerHTML = `
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="bg-white p-3 rounded">
                <p class="text-xs text-gray-600">Total Sales</p>
                <p class="text-lg font-bold text-red-600">₱${totalRevenue.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p>
            </div>
            <div class="bg-white p-3 rounded">
                <p class="text-xs text-gray-600">Total Profit/Loss</p>
                <p class="text-lg font-bold ${totalNetIncome >= 0 ? 'text-green-600' : 'text-red-600'}">${totalNetIncome.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p>
            </div>
            <div class="bg-white p-3 rounded">
                <p class="text-xs text-gray-600">Extra Money Needed</p>
                <p class="text-lg font-bold text-red-600">₱${Math.abs(Math.min(minCash, 0)).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p>
            </div>
            <div class="bg-white p-3 rounded">
                <p class="text-xs text-gray-600">When You Profit</p>
                <p class="text-lg font-bold text-blue-600">${breakEvenMonth ? 'Month ' + breakEvenMonth : 'Not Yet'}</p>
            </div>
        </div>
    `;
}

// Calculate Best Case
function calculateBestCase() {
    const basePrice = parseFloat(document.getElementById('unitPrice').value);
    const baseGrowth = parseFloat(document.getElementById('salesGrowth').value) / 100;
    
    const bestPrice = basePrice * 1.1;
    const bestGrowth = baseGrowth * 1.5;
    
    const months = parseInt(document.getElementById('projectionMonths').value);
    const initialUnits = parseInt(document.getElementById('initialUnits').value);
    const cogsPerUnit = parseFloat(document.getElementById('cogsPerUnit').value);
    const fixedOpex = parseFloat(document.getElementById('fixedOpex').value);
    const variableOpexRate = parseFloat(document.getElementById('variableOpex').value) / 100;
    const capex = parseFloat(document.getElementById('capex').value);
    const initialCash = parseFloat(document.getElementById('initialCash').value);
    
    let bestCash = initialCash - capex;
    let units = initialUnits;
    let totalRevenue = 0;
    let totalNetIncome = 0;
    let breakEvenMonth = null;
    
    for (let i = 1; i <= months; i++) {
        const revenue = units * bestPrice;
        const cogs = units * cogsPerUnit;
        const grossProfit = revenue - cogs;
        const variableOpex = revenue * variableOpexRate;
        const totalOpex = fixedOpex + variableOpex;
        const netIncome = grossProfit - totalOpex;
        const cashFlow = netIncome - (i === 1 ? capex : 0);
        
        bestCash += cashFlow;
        totalRevenue += revenue;
        totalNetIncome += netIncome;
        
        if (!breakEvenMonth && bestCash >= 0) breakEvenMonth = i;
        
        units = Math.round(units * (1 + bestGrowth));
    }
    
    const resultsDiv = document.getElementById('bestCaseResults');
    resultsDiv.innerHTML = `
        <div class="grid md:grid-cols-2 lg:grid-cols-4 gap-3">
            <div class="bg-white p-3 rounded">
                <p class="text-xs text-gray-600">Total Sales</p>
                <p class="text-lg font-bold text-green-600">₱${totalRevenue.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p>
            </div>
            <div class="bg-white p-3 rounded">
                <p class="text-xs text-gray-600">Total Profit</p>
                <p class="text-lg font-bold text-green-600">₱${totalNetIncome.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p>
            </div>
            <div class="bg-white p-3 rounded">
                <p class="text-xs text-gray-600">Cash at End</p>
                <p class="text-lg font-bold text-green-600">₱${bestCash.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</p>
            </div>
            <div class="bg-white p-3 rounded">
                <p class="text-xs text-gray-600">When You Profit</p>
                <p class="text-lg font-bold text-blue-600">${breakEvenMonth ? 'Month ' + breakEvenMonth : 'Month 1'}</p>
            </div>
        </div>
    `;
}

// Export Report Function
function exportReport() {
    window.print();
}