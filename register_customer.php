<?php
if(isset($_POST['first_name'])){
    $server = "localhost";
    $user = "root";
    $password = "";
    $con = mysqli_connect($server, $user, $password);

    if (!$con) {
        die("Connection failed due to: ". mysqli_connect_error());
    }
    //echo "Connection established";
    $first_name=$_POST['first_name'];
    $last_name=$_POST['last_name'];
    $email=$_POST['email'];
    $phone=$_POST['phone'];
    $balance=$_POST['balance'];
    $sql = "INSERT INTO `inventory`.`customers` (`customer_id`, `first_name`, `last_name`, `email`, `phone`, `registration_date`, `balance`) 
    VALUES (NULL, '$first_name', '$last_name', '$email', '$phone', CURRENT_TIME(), '$balance');";
    echo $sql;
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
    <title>Register New Customer</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 40px; }
        form { max-width: 400px; margin: auto; }
        label { display: block; margin-top: 15px; }
        input[type="text"], input[type="email"], input[type="number"], input[type="tel"] {
            width: 100%; padding: 8px; margin-top: 5px; box-sizing: border-box;
        }
        button { margin-top: 20px; padding: 10px 20px; }
    </style>
</head>
<body>
    <h2>Register New Customer</h2>
    <form action="register_customer.php" method="POST">
        <label for="first_name">First Name<span style="color:red">*</span>:</label>
        <input type="text" id="first_name" name="first_name" required>

        <label for="last_name">Last Name<span style="color:red">*</span>:</label>
        <input type="text" id="last_name" name="last_name" required>

        <label for="email">Email<span style="color:red">*</span>:</label>
        <input type="email" id="email" name="email" required>

        <label for="phone">Phone:</label>
        <input type="tel" id="phone" name="phone">

        <label for="balance">Balance:</label>
        <input type="number" id="balance" name="balance" step="0.01" placeholder="0">

        <button type="submit">Register Customer</button>
    </form>
</body>
</html>