<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "inventory";
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Function to fetch top 5 customers
function getTopCustomers($conn) {
    $sql = "SELECT customer_id, first_name, last_name, email, balance 
            FROM customers 
            ORDER BY balance DESC 
            LIMIT 5";
    $result = $conn->query($sql);
    return ($result->num_rows > 0) ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

$topCustomers = getTopCustomers($conn);
$amountOwed = null;
$message = "";

// Handle balance check
if (isset($_POST['check_balance'])) {
    $customer_id = intval($_POST['customer_id']);
    $stmt = $conn->prepare("SELECT balance FROM customers WHERE customer_id = ?");
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $amountOwed = $row['balance'];
        if ($amountOwed <= 0) {
            $message = "No outstanding balance for this customer.";
        }
    } else {
        $message = "Customer not found.";
    }
}

// Handle payment processing
if (isset($_POST['make_payment'])) {
    $customer_id = intval($_POST['customer_id']);
    $payment = floatval($_POST['payment_amount']);
    
    $conn->begin_transaction();
    try {
        // Get current balance
        $stmt = $conn->prepare("SELECT balance FROM customers WHERE customer_id = ?");
        $stmt->bind_param("i", $customer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Customer not found.");
        }
        
        $currentBalance = $result->fetch_assoc()['balance'];
        
        if ($payment <= 0) {
            throw new Exception("Payment amount must be positive.");
        }
        
        if ($payment > $currentBalance) {
            throw new Exception("Payment exceeds outstanding balance.");
        }
        
        // Update customer balance
        $newBalance = $currentBalance - $payment;
        $stmt = $conn->prepare("UPDATE customers SET balance = ? WHERE customer_id = ?");
        $stmt->bind_param("di", $newBalance, $customer_id);
        $stmt->execute();
        
        // Record transaction
        $stmt = $conn->prepare("INSERT INTO transactions 
                              (customer_id, total_amount, paid_amount, transaction_date) 
                              VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("idd", $customer_id, $currentBalance, $payment);
        $stmt->execute();
        
        $conn->commit();
        $message = "Payment successful. Remaining balance: Rs." . number_format($newBalance, 2);
        $amountOwed = $newBalance;
        $topCustomers = getTopCustomers($conn); // Refresh top list
        
    } catch (Exception $e) {
        $conn->rollback();
        $message = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Customer Balance Management</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #f2f2f2; }
        .form-section { margin: 20px 0; padding: 15px; border: 1px solid #eee; }
        input[type='number'] { width: 200px; padding: 5px; }
        button { padding: 8px 15px; background-color: #4CAF50; color: white; border: none; }
        .message { color: #d32f2f; margin: 10px 0; }
    </style>
</head>
<body>
    <h2>Top 5 Customers with Highest Balances</h2>
    <table>
        <tr>
            <th>Customer ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Balance</th>
        </tr>
        <?php foreach ($topCustomers as $customer): ?>
        <tr>
            <td><?= htmlspecialchars($customer['customer_id']) ?></td>
            <td><?= htmlspecialchars($customer['first_name'] . ' ' . $customer['last_name']) ?></td>
            <td><?= htmlspecialchars($customer['email']) ?></td>
            <td>Rs.<?= number_format($customer['balance'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <div class="form-section">
        <h3>Check Customer Balance</h3>
        <form method="POST">
            <label>Customer ID:</label>
            <input type="number" name="customer_id" required>
            <button type="submit" name="check_balance">Check Balance</button>
        </form>
        
        <?php if ($amountOwed !== null): ?>
            <p>Outstanding Balance: Rs.<?= number_format($amountOwed, 2) ?></p>
        <?php endif; ?>
    </div>

    <?php if ($amountOwed > 0): ?>
    <div class="form-section">
        <h3>Make Payment</h3>
        <form method="POST">
            <input type="hidden" name="customer_id" value="<?= htmlspecialchars($_POST['customer_id']) ?>">
            <label>Payment Amount:</label>
            <input type="number" name="payment_amount" step="0.01" 
                   min="0.01" max="<?= $amountOwed ?>" required>
            <button type="submit" name="make_payment">Process Payment</button>
        </form>
    </div>
    <?php endif; ?>

    <?php if ($message): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
</body>
</html>
