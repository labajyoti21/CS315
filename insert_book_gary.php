<?php
if (isset($_POST['book_title'])) {
    // 1) connect
    $server       = "localhost";
    $username     = "root";
    $password     = "";
    $dbname       = "inventory";
    $con = new mysqli($server, $username, $password, $dbname);
    if ($con->connect_error) {
        die("Connection failed: " . $con->connect_error);
    }

    // 2) grab POSTed values
    $book_title     = $_POST['book_title'];
    $seller_id      = $_POST['seller_id'];
    $purchase_price = $_POST['purchase_price'];
    $selling_price  = $_POST['selling_price'];

    // 3) begin transaction
    $con->begin_transaction();
    try {
        // a) fetch old stock (or default to 0)
        $stmt = $con->prepare(
            "SELECT stock
               FROM books
              WHERE book_title     = '$book_title'
                AND seller_id      = '$seller_id'
                AND purchase_price = '$purchase_price'
                AND selling_price  = '$selling_price'
              LIMIT 1"
        );
        $stmt->bind_param($book_title, $seller_id, $purchase_price, $selling_price);
        $stmt->execute();
        $stmt->bind_result($old_stock);
        $stmt->fetch();
        $stmt->close();
        $old_stock = $old_stock ?? 0;

        // b) delete any existing row
        $stmt = $con->prepare(
            "DELETE
               FROM books
              WHERE book_title     = '$book_title'
                AND seller_id      = '$seller_id'
                AND purchase_price = '$purchase_price'
                AND selling_price  = '$selling_price'"
        );
        $stmt->bind_param("sidd", $book_title, $seller_id, $purchase_price, $selling_price);
        $stmt->execute();
        $stmt->close();

        // c) insert new row with carried-over stock
        $stmt = $con->prepare(
            "INSERT INTO books
                (book_title, seller_id, purchase_price, selling_price, stock)
             VALUES ('$book_title', '$seller_id', '$purchase_price', '$selling_price', '$old_stock')"
        );
        $stmt->bind_param("siddi", $book_title, $seller_id, $purchase_price, $selling_price, $old_stock);
        $stmt->execute();
        $stmt->close();

        // 4) commit and report success
        $con->commit();
        echo "Successfully inserted with stock = $old_stock.";

    } catch (Exception $e) {
        // on any error, roll back
        $con->rollback();
        echo "Transaction failed: " . $e->getMessage();
    }

    // 5) close connection
    $con->close();
}
?>



<!DOCTYPE html>
<html>
<head>
    <title>Add Book Inventory</title>
    <style>
        .form-container { max-width: 500px; margin: 20px auto; padding: 20px; }
        .form-group { margin-bottom: 15px; }
        label { display: block; margin-bottom: 5px; }
        input { width: 100%; padding: 8px; }
        button { padding: 10px 20px; background: #4CAF50; color: white; border: none; }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Add New Book Stock</h2>
        <form action="insert_book.php" method="post">
            <div class="form-group">
                <label>Book Title:</label>
                <input type="text" name="book_title" required>
            </div>
            
            <div class="form-group">
                <label>Seller ID:</label>
                <input type="number" name="seller_id" required>
            </div>

            <div class="form-group">
                <label>Purchase Price (Rs.):</label>
                <input type="number" step="0.01" name="purchase_price" required>
            </div>

            <div class="form-group">
                <label>Selling Price (Rs.):</label>
                <input type="number" step="0.01" name="selling_price" required>
            </div>

            <!-- <div class="form-group">
                <label>Quantity:</label>
                <input type="number" name="stock" required>
            </div> -->

            <button type="submit">Add to Inventory</button>
        </form>
    </div>
</body>
</html>