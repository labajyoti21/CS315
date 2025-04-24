<?php
// connect to DB
$servername = "localhost";
$username   = "root";
$password   = "";
$dbname     = "inventory";
$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// fetch total paid_amount for real customer transactions
$sql    = "SELECT IFNULL(SUM(paid_amount),0) AS total_sales
           FROM transactions
           WHERE customer_id IS NOT NULL";
$res    = $conn->query($sql);
$row    = $res->fetch_assoc();
$totalSales = (float)$row['total_sales'];

$profitSql  = "SELECT IFNULL(SUM(profit),0) AS total_profit FROM customers";
$profitRes  = $conn->query($profitSql);
$profitRow  = $profitRes->fetch_assoc();
$totalProfit = (float)$profitRow['total_profit'];

$countSql   = "SELECT COUNT(*) AS total_customers FROM customers";
$countRes   = $conn->query($countSql);
$countRow   = $countRes->fetch_assoc();
$totalCustomers = (int)$countRow['total_customers'];

$invSql       = "SELECT IFNULL(SUM(stock),0) AS total_inv FROM books";
$invRes       = $conn->query($invSql);
$invRow       = $invRes->fetch_assoc();
$totalInventory = (int)$invRow['total_inv'];

if (isset($_GET['action']) && $_GET['action'] === 'get_top_customers') {
    $sql = "
      SELECT 
        c.customer_id,
        COALESCE(c.customer_name, CONCAT('ID ',c.customer_id)) AS customer_name,
        COUNT(t.transaction_id)   AS purchase_count,
        SUM(t.total_amount)       AS total_spent,
        MAX(t.transaction_date)   AS last_purchase
      FROM customers c
      JOIN transactions t 
        ON c.customer_id = t.customer_id
      GROUP BY c.customer_id, customer_name
      ORDER BY total_spent DESC
      LIMIT 10
    ";
    $res  = $conn->query($sql);
    $data = [];
    while ($row = $res->fetch_assoc()) {
        $data[] = $row;
    }
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bookstore Inventory Dashboard</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        .card {
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            transition: transform 0.3s;
        }
        .card:hover {
            transform: translateY(-5px);
        }
        .card-header {
            border-radius: 10px 10px 0 0 !important;
            font-weight: bold;
        }
        .metric-value {
            font-size: 2rem;
            font-weight: bold;
        }
        .metric-label {
            font-size: 0.9rem;
            color: #6c757d;
        }
        .table-responsive {
            max-height: 300px;
            overflow-y: auto;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
            <div class="container-fluid">
                <a class="navbar-brand" href="#">
                    <i class="fas fa-book me-2"></i>Bookstore Inventory Dashboard
                </a>
                <div class="collapse navbar-collapse" id="navbarNav">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="#"><i class="fas fa-cog me-1"></i>Settings</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#"><i class="fas fa-user me-1"></i>Profile</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#"><i class="fas fa-sign-out-alt me-1"></i>Logout</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav> -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
            <div class="container-fluid">
                <a class="navbar-brand" href="#">
                    <i class="fas fa-book me-2"></i>Bookstore Inventory Dashboard
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarNavDropdown">
                    <ul class="navbar-nav ms-auto">
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="customersDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-users me-1"></i>Customers
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="customersDropdown">
                                <li><a class="dropdown-item" href="register_customer.php">Register Customer</a></li>
                                <li><a class="dropdown-item" href="purchase.php">Purchase Books</a></li>
                                <li><a class="dropdown-item" href="payment.php">Payment</a></li>
                            </ul>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="sellersDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-store me-1"></i>Sellers
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="sellersDropdown">
                                <li><a class="dropdown-item" href="register_sellers.php">Register Seller</a></li>
                                <li><a class="dropdown-item" href="place_order.php">Place Order</a></li>
                                <li><a class="dropdown-item" href="seller_payment.php">Payment</a></li>
                            </ul>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="insert_book.php">
                                <i class="fas fa-plus me-1"></i>Insert Book Details
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#"><i class="fas fa-cog me-1"></i>Settings</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#"><i class="fas fa-user me-1"></i>Profile</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="#"><i class="fas fa-sign-out-alt me-1"></i>Logout</a>
                        </li>
                    </ul>
                </div>
            </div>
        </nav>
        

        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="metric-value" id="total-sales"><?php echo '$' . number_format($totalSales, 2); ?>
                            </div>
                                <div class="metric-label">Total Sales</div>
                            </div>
                            <i class="fas fa-chart-line fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                            <div class="metric-value" id="total-profit">
                                <?php echo '$' . number_format($totalProfit, 2); ?>
                            </div>
                                <div class="metric-label">Total Profit</div>
                            </div>
                            <i class="fas fa-dollar-sign fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="metric-value" id="total-customers"> <?php echo $totalCustomers; ?></div>
                                <div class="metric-label">Active Customers</div>
                            </div>
                            <i class="fas fa-users fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="metric-value" id="total-inventory"><?php echo $totalInventory; ?></div>
                                <div class="metric-label">Books in Stock</div>
                            </div>
                            <i class="fas fa-boxes fa-3x opacity-50"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <span><i class="fas fa-chart-area me-2"></i>Sales Trend</span>
                            <div>
                                <select class="form-select form-select-sm" id="sales-period">
                                    <option value="daily">Daily</option>
                                    <option value="weekly">Weekly</option>
                                    <option value="monthly" selected>Monthly</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <canvas id="sales-chart" height="250"></canvas>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header bg-light">
                        <i class="fas fa-chart-pie me-2"></i>Sales Distribution by Seller
                    </div>
                    <div class="card-body">
                        <canvas id="sellers-chart" height="250"></canvas>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-light">
                        <i class="fas fa-trophy me-2"></i>Top Selling Books
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="top-books">
                                <thead>
                                    <tr>
                                        <th>Book Title</th>
                                        <th>Seller</th>
                                        <th>Copies Sold</th>
                                        <th>Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data will be populated by JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-light">
                        <i class="fas fa-user-tag me-2"></i>Top Customers
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="top-customers">
                                <thead>
                                    <tr>
                                        <th>Customer</th>
                                        <th>Purchase Count</th>
                                        <th>Total Spent</th>
                                        <th>Last Purchase</th>
                                    </tr>
                                </thead>
                                <tbody>
                                
                                    <!-- Data will be populated by JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-light">
                        <i class="fas fa-exclamation-triangle me-2"></i>Low Stock Alert
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="low-stock">
                                <thead>
                                    <tr>
                                        <th>Book Title</th>
                                        <th>Current Stock</th>
                                        <th>Seller</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data will be populated by JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-light">
                        <i class="fas fa-money-bill-wave me-2"></i>Recent Transactions
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="recent-transactions">
                                <thead>
                                    <tr>
                                        <th>Transaction ID</th>
                                        <th>Customer</th>
                                        <th>Amount</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <!-- Data will be populated by JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Bootstrap & Dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Custom JavaScript for Dashboard -->
    <script>
        // Sample data - In a real application, this would come from your database
        const sampleData = {
            monthlySales: [
                { month: 'Jan', sales: 4200 },
                { month: 'Feb', sales: 4800 },
                { month: 'Mar', sales: 5500 },
                { month: 'Apr', sales: 4700 },
                { month: 'May', sales: 6200 },
                { month: 'Jun', sales: 7500 },
                { month: 'Jul', sales: 8200 },
                { month: 'Aug', sales: 7800 },
                { month: 'Sep', sales: 6500 },
                { month: 'Oct', sales: 5200 },
                { month: 'Nov', sales: 4800 },
                { month: 'Dec', sales: 6500 }
            ],
            
            sellerDistribution: [
                { name: 'Academic Publishers', sales: 28500 },
                { name: 'Classic Books Inc', sales: 16200 },
                { name: 'Modern Literature Ltd', sales: 9800 },
                { name: 'Children\'s Books Co', sales: 7500 },
                { name: 'Others', sales: 4250 }
            ],
            
            topBooks: [
                { title: 'The Great Adventure', seller: 'Modern Literature Ltd', sold: 128, revenue: 3840 },
                { title: 'Physics Fundamentals', seller: 'Academic Publishers', sold: 112, revenue: 3360 },
                { title: 'World History', seller: 'Academic Publishers', sold: 95, revenue: 2850 },
                { title: 'Programming Basics', seller: 'Technical Books Inc', sold: 87, revenue: 2610 },
                { title: 'Fairy Tales Collection', seller: 'Children\'s Books Co', sold: 76, revenue: 1900 }
            ],
            
            topCustomers: [
               { name: 'John Smith', purchases: 12, spent: 1850, lastPurchase: '2025-04-18' },
               { name: 'Emily Johnson', purchases: 10, spent: 1620, lastPurchase: '2025-04-20' },
               { name: 'Michael Brown', purchases: 9, spent: 1450, lastPurchase: '2025-04-15' },
              { name: 'Sarah Williams', purchases: 7, spent: 1120, lastPurchase: '2025-04-22' },
               { name: 'David Lee', purchases: 6, spent: 980, lastPurchase: '2025-04-19' }
            ],
            
            lowStock: [
                { title: 'Advanced Mathematics', stock: 2, seller: 'Academic Publishers' },
                { title: 'Cooking Masterclass', stock: 3, seller: 'Lifestyle Books Ltd' },
                { title: 'Space Exploration', stock: 4, seller: 'Academic Publishers' },
                { title: 'Classic Poetry Collection', stock: 5, seller: 'Classic Books Inc' },
                { title: 'Gardening Guide', stock: 5, seller: 'Lifestyle Books Ltd' }
            ],
            
            recentTransactions: [
                { id: 'TRX-1089', customer: 'Sarah Williams', amount: 150, date: '2025-04-24', status: 'Completed' },
                { id: 'TRX-1088', customer: 'James Wilson', amount: 85, date: '2025-04-24', status: 'Completed' },
                { id: 'TRX-1087', customer: 'Emily Johnson', amount: 210, date: '2025-04-23', status: 'Completed' },
                { id: 'TRX-1086', customer: 'Michael Brown', amount: 65, date: '2025-04-23', status: 'Completed' },
                { id: 'TRX-1085', customer: 'John Smith', amount: 180, date: '2025-04-22', status: 'Completed' }
            ]
        };

        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            // Create sales trend chart
            const salesCtx = document.getElementById('sales-chart').getContext('2d');
            const salesChart = new Chart(salesCtx, {
                type: 'line',
                data: {
                    labels: sampleData.monthlySales.map(item => item.month),
                    datasets: [{
                        label: 'Monthly Sales ($)',
                        data: sampleData.monthlySales.map(item => item.sales),
                        borderColor: '#4e73df',
                        backgroundColor: 'rgba(78, 115, 223, 0.05)',
                        borderWidth: 3,
                        pointRadius: 3,
                        pointBackgroundColor: '#4e73df',
                        pointBorderColor: '#4e73df',
                        pointHoverRadius: 5,
                        pointHoverBackgroundColor: '#4e73df',
                        pointHoverBorderColor: '#4e73df',
                        pointHitRadius: 10,
                        pointBorderWidth: 2,
                        tension: 0.3,
                        fill: true
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        x: {
                            grid: {
                                display: false
                            }
                        },
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0, 0, 0, 0.05)'
                            }
                        }
                    }
                }
            });
            
            // Create sellers distribution chart
            const sellersCtx = document.getElementById('sellers-chart').getContext('2d');
            const sellersChart = new Chart(sellersCtx, {
                type: 'doughnut',
                data: {
                    labels: sampleData.sellerDistribution.map(item => item.name),
                    datasets: [{
                        data: sampleData.sellerDistribution.map(item => item.sales),
                        backgroundColor: [
                            '#4e73df',
                            '#1cc88a',
                            '#36b9cc',
                            '#f6c23e',
                            '#e74a3b'
                        ],
                        hoverBackgroundColor: [
                            '#2e59d9',
                            '#17a673',
                            '#2c9faf',
                            '#dda20a',
                            '#be2617'
                        ],
                        hoverBorderColor: 'rgba(234, 236, 244, 1)',
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'right',
                        }
                    },
                    cutout: '70%'
                }
            });
            
            // Populate Top Books table
            const topBooksTable = document.getElementById('top-books').getElementsByTagName('tbody')[0];
            sampleData.topBooks.forEach(book => {
                let row = topBooksTable.insertRow();
                let titleCell = row.insertCell(0);
                let sellerCell = row.insertCell(1);
                let soldCell = row.insertCell(2);
                let revenueCell = row.insertCell(3);
                
                titleCell.textContent = book.title;
                sellerCell.textContent = book.seller;
                soldCell.textContent = book.sold;
                revenueCell.textContent = '$' + book.revenue.toLocaleString();
            });
            
            // Populate Top Customers table
            const topCustomersTable = document.getElementById('top-customers').getElementsByTagName('tbody')[0];
            sampleData.topCustomers.forEach(customer => {
                let row = topCustomersTable.insertRow();
                let nameCell = row.insertCell(0);
                let purchasesCell = row.insertCell(1);
                let spentCell = row.insertCell(2);
                let lastPurchaseCell = row.insertCell(3);
                
                nameCell.textContent = customer.name;
                purchasesCell.textContent = customer.purchases;
                spentCell.textContent = '$' + customer.spent.toLocaleString();
                lastPurchaseCell.textContent = new Date(customer.lastPurchase).toLocaleDateString();
            });
            
            // Populate Low Stock table
            const lowStockTable = document.getElementById('low-stock').getElementsByTagName('tbody')[0];
            sampleData.lowStock.forEach(book => {
                let row = lowStockTable.insertRow();
                let titleCell = row.insertCell(0);
                let stockCell = row.insertCell(1);
                let sellerCell = row.insertCell(2);
                let actionCell = row.insertCell(3);
                
                titleCell.textContent = book.title;
                stockCell.innerHTML = `<span class="badge bg-danger">${book.stock}</span>`;
                sellerCell.textContent = book.seller;
                actionCell.innerHTML = '<button class="btn btn-sm btn-outline-primary">Reorder</button>';
            });
            
            // Populate Recent Transactions table
            const recentTransactionsTable = document.getElementById('recent-transactions').getElementsByTagName('tbody')[0];
            sampleData.recentTransactions.forEach(transaction => {
                let row = recentTransactionsTable.insertRow();
                let idCell = row.insertCell(0);
                let customerCell = row.insertCell(1);
                let amountCell = row.insertCell(2);
                let dateCell = row.insertCell(3);
                let statusCell = row.insertCell(4);
                
                idCell.textContent = transaction.id;
                customerCell.textContent = transaction.customer;
                amountCell.textContent = '$' + transaction.amount.toLocaleString();
                dateCell.textContent = new Date(transaction.date).toLocaleDateString();
                statusCell.innerHTML = `<span class="badge bg-success">${transaction.status}</span>`;
            });
            
            // Add event listener for sales period selection
            document.getElementById('sales-period').addEventListener('change', function() {
                // In a real application, you would fetch new data based on the selected period
                alert('In a real application, the chart would update with ' + this.value + ' data.');
            });
        });
        
        // Function to fetch real data from database (in a real implementation)
        async function fetchDashboardData() {
            try {
                // Example of how you would fetch real data
                // const response = await fetch('/api/dashboard-stats');
                // const data = await response.json();
                // return data;
                return sampleData; // Using sample data for demonstration
            } catch (error) {
                console.error('Error fetching dashboard data:', error);
                return sampleData; // Fallback to sample data on error
            }
        }
    </script>
</body>
</html>
