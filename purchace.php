<?php
// Database configuration
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "inventory";
$conn = new mysqli($servername, $username, $password, $dbname);
if (!$conn) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Connection failed: ' . mysqli_connect_error()]);
    exit;
}
// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    if (isset($_POST['amount_paid'])) {
        $customer_id = $_POST['customer_id'];
        $total_amount = $_POST['total_amount'];
        $amount_paid = $_POST['amount_paid'];
        $total_profit = $_POST['total_profit'];
        $book_title = $_POST['book_title'];
        $quantity = $_POST['quantity'];

        if ($amount_paid > $total_amount) {
            die("Payment cannot exceed total amount");
        }

        $conn->begin_transaction();
        try {
            $balanceChange = $total_amount - $amount_paid;

            $conn->query("
                UPDATE customers 
                SET balance = balance + $balanceChange,
                    profit = profit + $total_profit 
                WHERE customer_id = $customer_id
            ");

            $conn->query("
                INSERT INTO transactions (customer_id, total_amount, paid_amount, transaction_date)
                VALUES ($customer_id, $total_amount, $amount_paid, NOW())
            ");

            $conn->commit();
            echo "Transaction completed successfully!<br>
                  Total: $total_amount<br>
                  Paid: $amount_paid<br>
                  Balance Due: " . ($total_amount - $amount_paid);

        } catch (Exception $e) {
            $conn->rollback();
            die("Error processing payment: " . $e->getMessage());
        }

    }
    elseif (isset($_POST['customer_id']) && isset($_POST['book_title']) && isset($_POST['quantity'])) {
        // Stage 1: Validate and calculate total
        $customer_id = $_POST['customer_id'];
        $book_title = $_POST['book_title'];
        $quantity = $_POST['quantity'];
        $custCheck = $conn->prepare("SELECT 1
                                        FROM customers
                                        WHERE customer_id = ?
                                        LIMIT 1
        ");
        $custCheck->bind_param('i', $customer_id);
        $custCheck->execute();
        // must call store_result() to get num_rows
        $custCheck->store_result();

        if ($custCheck->num_rows === 0) {
            // no such customer → abort
            echo "Error: Customer ID {$customer_id} does not exist.";
            $custCheck->close();
            exit;
        }

        $custCheck->close();
        // Check total available stock
        $stockCheck = $conn->prepare("SELECT SUM(stock) AS total_stock FROM books WHERE book_title = ?");
        $stockCheck->bind_param("s", $book_title);
        $stockCheck->execute();
        $totalStock = $stockCheck->get_result()->fetch_assoc()['total_stock'];
        
        if ($totalStock < $quantity) {
            echo "Error: Only $totalStock units available for $book_title";
            exit;
        }
        
        // Calculate total amount and profit
        $conn->begin_transaction();
        try {
            $getBooks = $conn->prepare("SELECT book_id, seller_id, purchase_price, selling_price, stock 
                                        FROM books 
                                        WHERE book_title = ? 
                                        ORDER BY selling_price ASC, book_id ASC
            ");
            $getBooks->bind_param("s", $book_title);
            $getBooks->execute();
            $books = $getBooks->get_result()->fetch_all(MYSQLI_ASSOC);
            
            $remaining = $quantity;
            $totalAmount = 0;
            $totalProfit = 0;
            $latestBookId = 0;

            foreach ($books as $book) {
                if ($remaining <= 0) break;
                
                $take = min($remaining, $book['stock']);
                $remaining -= $take;
                $max_seller_id = $book['seller_id'];
                // Update stock
                $newStock = $book['stock'] - $take;
                if ($newStock == 0) {
                    // Check if this is the latest book entry
                    $latestBook = $conn->query("SELECT MAX(book_id) AS max_id 
                                                FROM books 
                                                WHERE book_title = '$book_title' AND seller_id = '$max_seller_id'
                    ")->fetch_assoc();
                    
                    if ($book['book_id'] != $latestBook['max_id']) {
                        $conn->query("DELETE FROM books WHERE book_id = {$book['book_id']}");
                    } else {
                        $conn->query("UPDATE books SET stock = 0 WHERE book_id = {$book['book_id']}");
                    }
                } else {
                    $conn->query("UPDATE books SET stock = $newStock WHERE book_id = {$book['book_id']}");
                }
                
                $totalAmount += $take * $book['selling_price'];
                $totalProfit += $take * ($book['selling_price'] - $book['purchase_price']);
                $latestBookId = $book['book_id'];
            }
            
            // Show payment form
            echo "<h3>Total Amount: $totalAmount</h3>
                  <form method='POST'>
                      <input type='hidden' name='customer_id' value='$customer_id'>
                      <input type='hidden' name='book_title' value='$book_title'>
                      <input type='hidden' name='quantity' value='$quantity'>
                      <input type='hidden' name='total_amount' value='$totalAmount'>
                      <input type='hidden' name='total_profit' value='$totalProfit'>
                      
                      <label>Amount to Pay:</label>
                      <input type='number' name='amount_paid' step='0.01' max='$totalAmount' required>
                      <button type='submit'>Complete Purchase</button>
                  </form>";
            
            $conn->commit();
            
        } catch (Exception $e) {
            $conn->rollback();
            die("Error processing order: " . $e->getMessage());
        }
        
    }
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Book Purchase</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 20px auto; padding: 20px; }
        form { border: 1px solid #ccc; padding: 20px; border-radius: 5px; }
        div { margin: 10px 0; }
        input[type="number"], input[type="text"] { width: 200px; padding: 5px; }
    </style>
</head>
<body>
    <h2>Book Purchase Form</h2>
    <form method="POST">
        <div>
            <label>Customer ID:</label>
            <input type="number" name="customer_id" required>
        </div>
        
        <div>
            <label>Book Title:</label>
            <input type="text" name="book_title" required>
        </div>
        
        <div>
            <label>Quantity:</label>
            <input type="number" name="quantity" required>
        </div>
        
        <button type="submit">Calculate Total</button>
    </form>
</body>
</html>
