<?php
if (isset($_POST['book_title'])) {
    // 1) Connect to database
    $server   = 'localhost';
    $user     = 'root';
    $password = '';
    $database = 'inventory';
    $con = mysqli_connect($server, $user, $password, $database);

    if (!$con) {
        die('Connection failed: ' . mysqli_connect_error());
    }

    // 2) Sanitize inputs
    $book_title     = mysqli_real_escape_string($con, $_POST['book_title']);
    $seller_id      = (int)   ($_POST['seller_id']     ?? 0);
    $purchase_price = (float) ($_POST['purchase_price'] ?? 0);
    $selling_price  = (float) ($_POST['selling_price']  ?? 0);

    // 3) Check that the seller exists
    $check_sql = "
        SELECT 1
          FROM sellers
         WHERE seller_id = {$seller_id}
        LIMIT 1
    ";
    $check_res = mysqli_query($con, $check_sql);
    if (! $check_res) {
        die('Error checking seller: ' . mysqli_error($con));
    }
    if (mysqli_num_rows($check_res) === 0) {
        echo "Error: Seller ID {$seller_id} does not exist.";
        mysqli_free_result($check_res);
        mysqli_close($con);
        exit;
    }
    mysqli_free_result($check_res);

    // 4) Look for a duplicate book (same seller_id, book_title, purchase_price, selling_price)
    $dup_sql = "
        SELECT book_id, stock
          FROM books
         WHERE seller_id      = {$seller_id}
           AND book_title     = '{$book_title}'
           AND purchase_price = {$purchase_price}
           AND selling_price  = {$selling_price}
         LIMIT 1
    ";
    $dup_res = mysqli_query($con, $dup_sql);
    if (! $dup_res) {
        die('Error checking existing book: ' . mysqli_error($con));
    }

    $stock_to_insert = 0;
    if (mysqli_num_rows($dup_res) > 0) {
        $dup_row     = mysqli_fetch_assoc($dup_res);
        $old_book_id = (int)$dup_row['book_id'];
        $old_stock   = (int)$dup_row['stock'];
        mysqli_free_result($dup_res);

        // delete the old tuple
        $del_sql = "DELETE FROM books WHERE book_id = {$old_book_id}";
        if (! mysqli_query($con, $del_sql)) {
            die('Error deleting old book row: ' . mysqli_error($con));
        }

        // carry over its stock
        $stock_to_insert = $old_stock;
    } else {
        mysqli_free_result($dup_res);
    }

    // 5) Insert the new book row, using the carried-over stock if any
    $insert_sql = "
        INSERT INTO books
            (book_title, seller_id, purchase_price, selling_price, stock)
        VALUES
            (
                '{$book_title}',
                {$seller_id},
                {$purchase_price},
                {$selling_price},
                {$stock_to_insert}
            )
    ";
    if (mysqli_query($con, $insert_sql)) {
        echo "Successfully inserted new book record (stock={$stock_to_insert}).";
    } else {
        echo "Insert error: " . mysqli_error($con);
    }

    // 6) Close connection
    mysqli_close($con);
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

            <button type="submit">Add to Inventory</button>
        </form>
    </div>
</body>
</html>