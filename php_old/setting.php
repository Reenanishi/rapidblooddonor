<?php 

session_start(); 

include "config.php";
include "util.php";

if (isset($_POST['zipcode']) && isset($_POST['latitude']) && isset($_POST['longitude']) && isset($_POST['email']) && isset($_POST['phone'])) {

 $currentUser = $_SESSION['user_name'];
 $zipcode = validate($_POST['zipcode']);
 $latitude = $_POST['latitude'];
 $longitude = $_POST['longitude'];    
 $email = validate($_POST['email']);    
 $phone = validate($_POST['phone']);
 $allowemail  = $_POST['allowemail'] == 1 ? 1 : 0;
 $allowphonecall  = $_POST['allowphonecall'] == 1 ? 1 : 0;
 $allowsms  = $_POST['allowsms'] == 1 ? 1 : 0;

 if(empty($zipcode)){

    header("Location: setting-page.php?error=Zipcode is required");

    exit();

}else if(empty($latitude)){

    header("Location: setting-page.php?error=Latitude is required");

    exit();

}else if(empty($longitude)){

    header("Location: setting-page.php?error=Longitude is required");

    exit();

}else if(empty($email)){

    header("Location: setting-page.php?error=Email is required");

    exit();

}else if(empty($phone)){

    header("Location: setting-page.php?error=Phone is required");

    exit();

}else{
   echo "hi";
   $query_string = "UPDATE User SET Zipcode = '$zipcode', Phone = '$phone', Email = '$email', Latitude = $latitude, Longitude = $longitude WHERE User_Id='$currentUser';";
   $query_string .= "UPDATE User_Preference SET Allow_Email = $allowemail, Allow_Phone = $allowphonecall, Allow_Sms = $allowsms WHERE User_Id='$currentUser';";

   $result = $conn->multi_query($query_string);
   if ($result == 1) {
    echo "Setting Updated!";
    header("Location: setting-page.php?error=Successfully updated");
    exit();
}else{
    header("Location: setting-page.php?error=Error updating $result");
    exit();
}

}

}else{

    header("Location: setting-page.php");

    exit();

}