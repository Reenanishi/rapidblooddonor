<!DOCTYPE html>

<html>

<head>

    <title>Setting</title>

    <link rel="stylesheet" type="text/css" href="style.css">

</head>

<body> 

    <div class="container">
      <header id="loginheader">
         <?php include 'loginheader.php';?>
     </header>
     <main id="main"> 

        <br><br><br><br>
        <form action="setting.php" method="post">

            <h2>Setting</h2>

            <?php if (isset($_GET['error'])) { ?>

            <p class="error"><?php echo $_GET['error']; ?></p>

            <?php } ?>
            <?php

                include "config.php";
                session_start(); 

                $user_name = $_SESSION['user_name'];

                $query_string = "SELECT * FROM User WHERE User_Id='$user_name'";
                
                $result = mysqli_query($conn, $query_string);
                $row = mysqli_fetch_assoc($result);
                
                $email = $row['Email'];
                $zipcode = $row['Zipcode'];
                $phone = $row['Phone'];
                $latitude = $row['Latitude'];
                $longitude = $row['Longitude'];

                echo "<input type='zipcode' name='zipcode' placeholder='Enter zip code' value='$zipcode'>";

                echo "<input type='latitude' name='latitude' placeholder='Enter latitude of home address' value='$latitude'>";

                echo "<input type='longitude' name='longitude' placeholder='Enter longitude of home address' value='$longitude'>";

                echo "<input type='email' name='email' placeholder='Enter email' value='$email'>";

                echo "<input type='phone' name='phone' placeholder='Enter phone number' value='$phone'>";

                $query_string = "SELECT * FROM User_Preference WHERE User_Id='$user_name'";
                $result = mysqli_query($conn, $query_string);

                if (mysqli_num_rows($result) == 1) {

                    $row = mysqli_fetch_assoc($result);
                    $allowemail = $row['Allow_Email'];
                    $allowphone = $row['Allow_Phone'];
                    $allowsms = $row['Allow_Sms'];

                    echo "<label>Contact Preference:</label> <br>";
                    echo "<table>";
                        echo "<tr>";
                            if ($allowemail) {
                                echo "<td> <input type='checkbox' id='allowemail' name='allowemail' value='1' checked> </td>";
                            } else {
                                echo "<td> <input type='checkbox' id='allowemail' name='allowemail' value='1'> </td>";
                            }
                            echo "<td> <label for='allowemail'>Allow Email</label> </td>";
                            if ($allowphone) {
                                echo "<td> <input type='checkbox' id='allowphonecall' name='allowphonecall' value='1' checked> </td>";
                            } else {
                                echo "<td> <input type='checkbox' id='allowphonecall' name='allowphonecall' value='1'> </td>";
                            }
                            echo "<td> <label for='allowphonecall'>Allow Phone Call</label> </td>";
                            if ($allowsms) {
                             echo "<td> <input type='checkbox' id='allowsms' name='allowsms' value='1' checked> </td>";
                         } else {
                             echo "<td> <input type='checkbox' id='allowsms' name='allowsms' value='1'> </td>";
                         }
                         echo "<td> <label for='allowsms'>Allow SMS</label> </td>";
                     echo "</tr>";
                 echo "</table>";
             } else {
                echo "<label>Contact Preference:</label> <br>";
                echo "<table>";
                    echo "<tr>";
                        echo "<td> <input type='checkbox' id='allowemail' name='allowemail' value='1'> </td>";
                        echo "<td> <label for='allowemail'>Allow Email</label> </td>";
                        echo "<td> <input type='checkbox' id='allowemail' name='allowemail' value='1'> </td>"; 
                        echo "<td> <label for='allowphonecall'>Allow Phone Call</label> </td>";
                        echo "<td> <input type='checkbox' id='allowsms' name='allowsms' value='1'> </td>";
                        echo "<td> <label for='allowsms'>Allow SMS</label> </td>";
                    echo "</tr>";
                echo "</table>";
            }       

        ?>


        <button type="submit" name="submmit">Update</button>

    </form>

</main>
<footer id="footer">
  <?php include 'footer.php';?>
</footer>
</div>

</body>

</html>