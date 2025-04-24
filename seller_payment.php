<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "inventory";
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get top 5 sellers with highest balances
function getTopSellers($conn) {
    $sql = "SELECT seller_id, name, contact_email, phone, balance 
            FROM sellers 
            ORDER BY balance DESC 
            LIMIT 5";
    $result = $conn->query($sql);
    return ($result->num_rows > 0) ? $result->fetch_all(MYSQLI_ASSOC) : [];
}

$topSellers = getTopSellers($conn);
$owedAmount = null;
$message = "";

// Handle balance check
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['check_balance'])) {
    $seller_id = intval($_POST['seller_id']);
    $stmt = $conn->prepare("SELECT balance FROM sellers WHERE seller_id = ?");
    $stmt->bind_param("i", $seller_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $owedAmount = $row['balance'];
        if ($owedAmount <= 0) {
            $message = "No outstanding balance for this seller.";
        }
    } else {
        $message = "Seller not found.";
    }
}

// Handle payment processing
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['make_payment'])) {
    $seller_id = intval($_POST['seller_id']);
    $payment = floatval($_POST['payment_amount']);
    
    $conn->begin_transaction();
    try {
        // Verify current balance
        $stmt = $conn->prepare("SELECT balance FROM sellers WHERE seller_id = ?");
        $stmt->bind_param("i", $seller_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            throw new Exception("Seller not found.");
        }
        
        $currentBalance = $result->fetch_assoc()['balance'];
        
        if ($payment <= 0) {
            throw new Exception("Payment amount must be positive.");
        }
        
        if ($payment > $currentBalance) {
            throw new Exception("Payment exceeds owed amount.");
        }
        
        // Update seller balance
        $newBalance = $currentBalance - $payment;
        $stmt = $conn->prepare("UPDATE sellers SET balance = ? WHERE seller_id = ?");
        $stmt->bind_param("di", $newBalance, $seller_id);
        $stmt->execute();
        
        // Record transaction
        $stmt = $conn->prepare("INSERT INTO transactions 
                              (seller_id, total_amount, paid_amount, transaction_date) 
                              VALUES (?, ?, ?, NOW())");
        $stmt->bind_param("idd", $seller_id, $currentBalance, $payment);
        $stmt->execute();
        
        $conn->commit();
        $message = "Payment successful. Remaining balance: ₹" . number_format($newBalance, 2);
        $owedAmount = $newBalance;
        $topSellers = getTopSellers($conn); // Refresh top list
        
    } catch (Exception $e) {
        $conn->rollback();
        $message = "Error: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Seller Balance Management</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 800px; margin: 20px auto; padding: 20px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #f2f2f2; }
        .form-section { margin: 20px 0; padding: 15px; border: 1px solid #eee; }
        input[type='number'] { width: 200px; padding: 5px; }
        button { padding: 8px 15px; background-color: #2196F3; color: white; border: none; }
        .message { color: #d32f2f; margin: 10px 0; }
    </style>
</head>
<body>
    <h2>Top 5 Sellers with Highest Balances</h2>
    <table>
        <tr>
            <th>Seller ID</th>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Amount Owed</th>
        </tr>
        <?php foreach ($topSellers as $seller): ?>
        <tr>
            <td><?= htmlspecialchars($seller['seller_id']) ?></td>
            <td><?= htmlspecialchars($seller['name']) ?></td>
            <td><?= htmlspecialchars($seller['contact_email']) ?></td>
            <td><?= htmlspecialchars($seller['phone']) ?></td>
            <td>₹<?= number_format($seller['balance'], 2) ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <div class="form-section">
        <h3>Check Seller Balance</h3>
        <form method="POST">
            <label>Seller ID:</label>
            <input type="number" name="seller_id" required>
            <button type="submit" name="check_balance">Check Balance</button>
        </form>
        
        <?php if ($owedAmount !== null): ?>
            <p>Amount Owed: ₹<?= number_format($owedAmount, 2) ?></p>
        <?php endif; ?>
    </div>

    <?php if ($owedAmount > 0): ?>
    <div class="form-section">
        <h3>Make Payment to Seller</h3>
        <form method="POST">
            <input type="hidden" name="seller_id" value="<?= htmlspecialchars($_POST['seller_id']) ?>">
            <label>Payment Amount:</label>
            <input type="number" name="payment_amount" step="0.01" 
                   min="0.01" max="<?= $owedAmount ?>" required>
            <button type="submit" name="make_payment">Process Payment</button>
        </form>
    </div>
    <?php endif; ?>

    <?php if ($message): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
    <?php endif; ?>
</body>
</html>
