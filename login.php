<?php 

session_start(); 

include "config.php";
include "util.php";

if (isset($_POST['uname']) && isset($_POST['password'])) {

 $uname = validate($_POST['uname']);

 $pass = validate($_POST['password']);

 if (empty($uname)) {

    header("Location: index.php?error=User Name is required");

    exit();

}else if(empty($pass)){

    header("Location: index.php?error=Password is required");

    exit();

}else{
    $query_string = "SELECT * FROM User WHERE User_Id='$uname'";
    $result = mysqli_query($conn, $query_string);

    if (mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_assoc($result);
            // echo "$row['user_name']";
        if ($row['User_Id'] === $uname && $row['Password'] === $pass) {

            echo "Logged in!";

            $_SESSION['user_name'] = $row['User_Id'];
            $_SESSION['email'] = $row['Email'];
            $_SESSION['zipcode'] = $row['Zipcode'];
            $_SESSION['user_type'] = $row['User_Type'];
            $_SESSION['phone'] = $row['Phone'];
            $_SESSION['latitude'] = $row['Latitude'];
            $_SESSION['longitude'] = $row['Longitude'];


            header("Location: home.php");

            exit();

        }else{
            header("Location: index.php?error=Incorect User name or password 2");
            exit();
        }

    }else{
        header("Location: index.php?error=Incorect User name or password 4");
        exit();

    }

}

}else{

    header("Location: index.php");

    exit();

}