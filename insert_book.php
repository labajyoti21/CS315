<?php
if(isset($_POST['name'])){
    $server = "localhost";
    $user = "root";
    $password = "";
    $con = mysqli_connect($server, $user, $password);

    if (!$con) {
        die("Connection failed due to: ". mysqli_connect_error());
    }
    //echo "Connection established";
    $book_title=$_POST['book_title'];
    $seller_id=$_POST['seller_id'];
    $purchase_price=$_POST['purchase_price'];
    $selling_price=$_POST['selling_price'];
    $sql = "INSERT INTO `inventory`.`books` (`book_id`, `book_title`, `seller_id`, `purchase_price`, `selling_price`,`stock`) 
    VALUES (NULL, '$book_title', '$seller_id', '$purchase_price', '$selling_price', '0');";
    if($con->query($sql) == true){
        echo "Successfully inserted";}
    else{
        echo "Error:  $sql <br> $con->error";
    }
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

            <div class="form-group">
                <label>Quantity:</label>
                <input type="number" name="stock" required>
            </div>

            <button type="submit">Add to Inventory</button>
        </form>
    </div>
</body>
</html>