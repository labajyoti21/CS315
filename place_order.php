<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// --- DATABASE CONNECTION ---
$server   = 'localhost';
$user     = 'root';
$password = '';
$database = 'inventory';

$con = mysqli_connect($server, $user, $password, $database);
if (!$con) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Connection failed: ' . mysqli_connect_error()]);
    exit;
}
// --- AJAX: GET AMOUNT ---
if (isset($_GET['action']) && $_GET['action'] === 'get_amount') {
    header('Content-Type: application/json');

    $seller_id  = (int)($_GET['seller_id']  ?? 0);
    $book_title = mysqli_real_escape_string($con, $_GET['book_title'] ?? '');
    $quantity   = (int)($_GET['quantity']   ?? 0);

    $sql    = "SELECT selling_price 
               FROM books 
               WHERE seller_id = {$seller_id} 
                 AND book_title = '{$book_title}' 
               ORDER BY book_id DESC 
               LIMIT 1";
    $result = $con->query($sql);

    if ($result && $row = $result->fetch_assoc()) {
        $amount = $row['selling_price'] * $quantity;
        echo json_encode(['amount' => $amount]);
    } else {
        echo json_encode(['error' => 'Book not found for this seller.']);
    }
    $con->close();
    exit;
}
$message = '';
// --- FORM SUBMISSION: PLACE ORDER ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' 
    && isset($_POST['action']) 
    && $_POST['action'] === 'submit_order'
) {
    // 1. Sanitize inputs
    $seller_id      = (int) ($_POST['seller_id']     ?? 0);
    $book_title     = mysqli_real_escape_string($con, $_POST['book_title'] ?? '');
    $quantity       = (int) ($_POST['quantity']      ?? 0);
    $amount_paying  = (float) ($_POST['amount_to_pay'] ?? 0);
    

    // 2. Fetch the book row (grabbing BOTH selling_price and its book_id)
    $sql    = "SELECT book_id, selling_price
                FROM books
                WHERE seller_id  = {$seller_id}
                    AND book_title = '{$book_title}'
                ORDER BY book_id DESC
                LIMIT 1
    ";
    $result = $con->query($sql);

    if ($result && $row = $result->fetch_assoc()) {
        $book_id       = (int)   $row['book_id'];
        $selling_price = (float) $row['selling_price'];

        // 3. Calculate total amount
        $amount = $selling_price * $quantity;

        // 4. Update the stock on that SAME book_id
        $stmt = $con->prepare("UPDATE books
                                    SET stock = stock + ?
                                WHERE book_id = ?
        ");
        $stmt->bind_param('ii', $quantity, $book_id);

        if (! $stmt->execute()) {
            // handle update error
            error_log("Stock update failed: " . $stmt->error);
            echo json_encode(['error' => 'Could not update stock.']);
            $stmt->close();
            $con->close();
            exit;
        }
        $stmt->close();
        $reminder = $amount - $amount_paying;
        $upd = $con->prepare("UPDATE sellers
                                SET balance = balance + ?
                            WHERE seller_id = ?
        ");
        // balance is probably DECIMAL, so we bind as "d" (double) then "i"
        $upd->bind_param('di', $reminder, $seller_id);

        if (! $upd->execute()) {
            error_log("Seller balance update failed: " . $upd->error);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Could not update seller balance.']);
            $upd->close();
            $con->close();
            exit;
        }
        $upd->close();
        $ins = $con->prepare("INSERT INTO transactions
                                (seller_id, transaction_date, total_amount, paid_amount)
                            VALUES
                                 (?, NOW(), ?, ?)
        ");
        $ins->bind_param(
            'idd',
            $seller_id,    // i = integer
            $amount,       // d = double (total_amount)
            $amount_paying // d = double (paid_amount)
        );
        if (! $ins->execute()) {
            error_log("Transaction insert failed: " . $ins->error);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Could not record transaction.']);
            $ins->close();
            $con->close();
            exit;
        }
        $ins->close();
        // 5. Return JSON to caller
        header('Content-Type: application/json');
        echo json_encode(['paid' => $amount_paying]);
    } else {
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Book not found for this seller.']);
    }
    $con->close();
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Place Seller Order</title>
</head>
<body>
    <?php if ($message): ?>
        <p><strong><?php echo htmlspecialchars($message); ?></strong></p>
    <?php endif; ?>

    <h2>Purchase Order Form</h2>
    <form id="orderForm" action="place_order.php" method="POST">
        <label for="seller_id">Seller ID:</label><br>
        <input type="number" id="seller_id" name="seller_id" required><br><br>

        <label for="book_title">Book Title:</label><br>
        <input type="text" id="book_title" name="book_title" required><br><br>

        <label for="quantity">Quantity:</label><br>
        <input type="number" id="quantity" name="quantity" required><br><br>

        <button type="button" onclick="fetchAmount()">Get Amount</button>
        <div id="amount_display" style="margin:10px 0;"></div>

        <label for="amount_to_pay">Amount to Pay:</label><br>
        <input type="number"
               id="amount_to_pay"
               name="amount_to_pay"
               step="0.01"
               required
               disabled><br><br>

        <button type="submit" id="submit_btn" disabled>Place Order</button>
        <input type="hidden" name="action" value="submit_order">
    </form>

    <script>
    async function fetchAmount() {
        const sellerId  = document.getElementById('seller_id').value;
        const bookTitle = document.getElementById('book_title').value;
        const quantity  = document.getElementById('quantity').value;

        if (!sellerId || !bookTitle || !quantity) {
            alert('Please enter Seller ID, Book Title, and Quantity.');
            return;
        }

        let data;
        try {
            const response = await fetch(
                `place_order.php?action=get_amount
                 &seller_id=${sellerId}
                 &book_title=${encodeURIComponent(bookTitle)}
                 &quantity=${quantity}`
                .replace(/\s+/g, '')
            );
            if (!response.ok) {
                throw new Error(`Server returned status ${response.status}`);
            }
            data = await response.json();
        } catch (err) {
            console.error('Fetch/JSON error:', err);
            document.getElementById('amount_display').innerText = 'Server response error';
            return;
        }

        if (data.error) {
            document.getElementById('amount_display').innerText = data.error;
            document.getElementById('amount_to_pay').disabled = true;
            document.getElementById('submit_btn').disabled    = true;
        } else {
            document.getElementById('amount_display').innerText = 'Amount to be paid: ₹' + data.amount;
            document.getElementById('amount_to_pay').max       = data.amount;
            document.getElementById('amount_to_pay').value     = data.amount;
            document.getElementById('amount_to_pay').disabled  = false;
            document.getElementById('submit_btn').disabled     = false;
        }
    }
    </script>
</body>
</html>
