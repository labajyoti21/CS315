<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Handle AJAX request first
if (isset($_GET['action']) && $_GET['action'] === 'get_amount') {
    header('Content-Type: application/json');
    
    $server = "localhost";
    $user = "root";
    $password = "";
    $database = "inventory";

    $con = mysqli_connect($server, $user, $password, $database);

    if (!$con) {
        echo json_encode(['error' => 'Connection failed: ' . mysqli_connect_error()]);
        exit;
    }

    $book_title = mysqli_real_escape_string($con, $_GET['book_title']);
    $seller_id = (int)$_GET['seller_id'];
    $quantity = (int)$_GET['quantity'];

    $sql = "SELECT selling_price FROM books WHERE seller_id = $seller_id AND title = '$book_title' ORDER BY book_id DESC LIMIT 1";
    $result = $con->query($sql);

    if ($result && $row = $result->fetch_assoc()) {
        $amount = $row['selling_price'] * $quantity;
        echo json_encode(['amount' => $amount]);
    } else {
        echo json_encode(['error' => 'Book not found for this seller.']);
    }
    $con->close();
    exit; // Stop execution after AJAX response
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Place Seller Order</title>
    <script>
    async function fetchAmount() {
        const sellerId = document.getElementById('seller_id').value;
        const bookTitle = document.getElementById('book_title').value;
        const quantity = document.getElementById('quantity').value;

        if (!sellerId || !bookTitle || !quantity) {
            alert('Please enter Seller ID, Book Title, and Quantity.');
            return;
        }

        const response = await fetch(`place_order.php?action=get_amount&seller_id=${sellerId}&book_title=${encodeURIComponent(bookTitle)}&quantity=${quantity}`);
        const data = await response.json();

        if (data.error) {
            document.getElementById('amount_display').innerText = data.error;
            document.getElementById('amount_to_pay').value = '';
            document.getElementById('amount_to_pay').disabled = true;
            document.getElementById('submit_btn').disabled = true;
        } else {
            document.getElementById('amount_display').innerText = 'Amount to be paid: ₹' + data.amount;
            document.getElementById('amount_to_pay').max = data.amount;
            document.getElementById('amount_to_pay').value = data.amount;
            document.getElementById('amount_to_pay').disabled = false;
            document.getElementById('submit_btn').disabled = false;
        }
    }
    </script>
</head>
<body>
    <h2>Purchase Order Form</h2>
    <form action="place_order.php" method="POST">
        <label>Seller ID:</label>
        <input type="number" id="seller_id" name="seller_id" required><br>
        
        <label>Book Title:</label>
        <input type="text" id="book_title" name="book_title" required><br>
        
        <label>Quantity:</label>
        <input type="number" id="quantity" name="quantity" required><br>
        
        <button type="button" onclick="fetchAmount()">Get Amount</button>
        <div id="amount_display" style="margin:10px 0;"></div>
        
        <label>Amount to Pay:</label>
        <input type="number" id="amount_to_pay" name="amount_to_pay" step="0.01" required disabled><br>
        
        <button type="submit" id="submit_btn" disabled>Place Order</button>
        <input type="hidden" name="action" value="submit_order">
    </form>
</body>
</html>
