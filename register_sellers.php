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
    $name=$_POST['name'];
    $contact_email=$_POST['contact_email'];
    $phone=$_POST['phone'];
    $address=$_POST['address'];
    $balance=$_POST['balance'];
    $sql = "INSERT INTO `inventory`.`sellers` (`seller_id`, `name`, `contact_email`, `phone`, `address`,`balance`) 
    VALUES (NULL, '$name', '$contact_email', '$phone', '$address', '$balance');";
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
    <title>Register New Seller</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        form { max-width: 400px; margin: auto; }
        label { display: block; margin-top: 15px; }
        input[type="text"], input[type="email"], input[type="number"], input[type="tel"], textarea {
            width: 100%; padding: 8px; margin-top: 5px; box-sizing: border-box;
        }
        button { margin-top: 20px; padding: 10px 20px; }
    </style>
</head>
<body>
    <h2>Register New Seller</h2>
    <form action="register_sellers.php" method="POST">
        <label for="name">Seller Name<span style="color:red">*</span>:</label>
        <input type="text" id="name" name="name" required>

        <label for="contact_email">Email:</label>
        <input type="email" id="contact_email" name="contact_email">

        <label for="phone">Phone:</label>
        <input type="tel" id="phone" name="phone">

        <label for="address">Address:</label>
        <textarea id="address" name="address" rows="3"></textarea>

        <label for="balance">Balance:</label>
        <input type="number" id="balance" name="balance" step="0.01" placeholder="0">

        <button type="submit">Register Seller</button>
    </form>
</body>
</html>
