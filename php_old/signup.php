<?php 

include "config.php";
include "util.php";

if (isset($_POST['uname']) && isset($_POST['password'])) {

 $uname = validate($_POST['uname']);

 $pass = validate($_POST['password']);

 $usertype = $_POST['usertype'];

 $bloodgroup = $_POST['bloodgroup'];

 $zipcode = validate($_POST['zipcode']);

 $latitude = $_POST['latitude'];

 $longitude = $_POST['longitude'];    

 $email = validate($_POST['email']);    

 $phone = validate($_POST['phone']);

 $allowemail  = $_POST['allowemail'] == 1 ? 1 : 0;
 $allowphonecall  = $_POST['allowphonecall'] == 1 ? 1 : 0;
 $allowsms  = $_POST['allowsms'] == 1 ? 1 : 0;

 $orgname = validate($_POST['orgname']);

 echo $orgname."\n";

 if (empty($uname)) {

    header("Location: signup-page.php?error=User Name is required");

    exit();

}else if(empty($pass)){

    header("Location: signup-page.php?error=Password is required");

    exit();

}else if(empty($zipcode)){

    header("Location: signup-page.php?error=Zipcode is required");

    exit();

}else if(empty($latitude)){

    header("Location: signup-page.php?error=Latitude is required");

    exit();

}else if(empty($longitude)){

    header("Location: signup-page.php?error=Longitude is required");

    exit();

}else if(empty($email)){

    header("Location: signup-page.php?error=Email is required");

    exit();

}else if(empty($phone)){

    header("Location: signup-page.php?error=Phone is required");

    exit();

}else if ($usertype == 1){
    $query_string = "INSERT INTO User (User_Id, Password, User_Type, Email, Zipcode, Phone, Latitude, Longitude) VALUES ('$uname', '$pass', $usertype, '$email', '$zipcode', '$phone', $latitude, $longitude);";
    $query_string .= "INSERT INTO Blood_Report (User_Id, Blood_Group) VALUES ('$uname', $bloodgroup);";
    $query_string .= "INSERT INTO User_Preference (User_Id, Allow_Email, Allow_Phone, Allow_Sms) VALUES ('$uname', $allowemail, $allowphonecall, $allowsms);";
    $result = $conn->multi_query($query_string);
    if ($result == 1) {
        echo "Sign Up!";
        header("Location: signup-page.php?error=Successfully inserted new user");
        exit();
    }else{
        header("Location: signup-page.php?error=Error inserting new user $result");
        exit();

    }

}else{

    $query_string = "INSERT INTO User (User_Id, Password, User_Type, Email, Zipcode, Phone, Latitude, Longitude) VALUES ('$uname', '$pass', 2, '$email', '$zipcode', '$phone', $latitude, $longitude);";
    $query_string .= "INSERT INTO Blood_Report (User_Id, Blood_Group) VALUES ('$uname', $bloodgroup);";
    $query_string .= "INSERT INTO Organization (Org_Id, Org_Name) VALUES ('$uname', '$orgname');";
    $query_string .= "INSERT INTO User_Preference (User_Id, Allow_Email, Allow_Phone, Allow_Sms) VALUES ('$uname', $allowemail, $allowphonecall, $allowsms);";

    $result = $conn->multi_query($query_string);

    if ($result == 1) {
        echo "Sign Up!";
        header("Location: signup-page.php?error=Successfully inserted new organization");
        exit();
    }else{
        header("Location: signup-page.php?error=Error inserting new organization $result");
        exit();

    }

}

}else{

    header("Location: signup-page.php");

    exit();

}