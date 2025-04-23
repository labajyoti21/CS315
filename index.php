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
    $age=$_POST['age'];
    $phone=$_POST['phone'];
    $desc=$_POST['desc'];
    $sql = "INSERT INTO `inventory`.`main` ( `name`, `age`, `phone`, `desc`, `time`) VALUES ('$name', '$age', '$phone', '$desc', CURRENT_TIME());";
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
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inventory Management System</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <h3>Form</h3>
        <p>Enter details</p>
        <p class="submitMsg">Success!</p>
        <form action="index.php" method="post">
            <input type="text" name="name" id="name" placeholder="Enter your name">
            <input type="text" name="age" id="age" placeholder="Enter your age">
            <input type="phone" name="phone" id="phone" placeholder="Enter your phone">
            <textarea name="desc" id="desc" cols="30" rows="10" placeholder="Enter any other information here"></textarea>
            <button class="btn">Submit</button>
        </form>
    </div>
    <script src="index.js"></script>
</body>

</html>