<?php 

session_start(); 

include "config.php";
include "util.php";

if (isset($_POST['uname'])) {

 $uname = validate($_POST['uname']);
 $currentUser = $_SESSION['user_name'];
 $addbutton= isset($_POST['addbutton']) ? 1: 0;

 if (empty($uname)) {
    header("Location: organizationuser-page.php?error=Organization name is required");
    exit();
}else{

    $query_string = "SELECT * FROM Organization WHERE Org_Id='$uname'";
    $result = mysqli_query($conn, $query_string);
    if (mysqli_num_rows($result) === 1) {

        if ($addbutton) {

            $query_string2 = "INSERT INTO Organization_Users (Org_Id, User_Id) VALUES ('$uname', '$currentUser');";
            $result2 = $conn->multi_query($query_string2);

            if ($result2 == 1) {
                header("Location: organizationuser-page.php?error=Successfully register to new organization $uname");
                exit();
            }else{
                header("Location: organizationuser-page.php?error=Error registering to new organization $uname");
                exit();
            }
        } else {

         $query_string3 = "DELETE FROM Organization_Users WHERE Org_Id = '$uname' AND User_Id ='$currentUser';";
         $result3 = $conn->multi_query($query_string3);

         if ($result3 == 1) {
            header("Location: organizationuser-page.php?error=Successfully unregister from organization $uname");
            exit();
        }else{
            header("Location: organizationuser-page.php?error=Error unregister from organization $uname");
            exit();
        }
    }

}else{
    header("Location: organizationuser-page.php?error=Incorect Organization name");
    exit();
}
} 
}else{
    header("Location: organizationuser-page.php");
    exit();
}