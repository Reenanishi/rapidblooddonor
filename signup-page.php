<!DOCTYPE html>

<html>

<head>

    <title>Sign Up</title>

    <link rel="stylesheet" type="text/css" href="style.css">

</head>

<script>

  let toggle = button => {

    let elementUserType = document.getElementById("usertype");
    var val = elementUserType.value;
    
    let element1 = document.getElementById("bloodgroup");
    let element2 = document.getElementById("bloodgrouplabel");
    let element3 = document.getElementById("orgname2");    

    if (val == 2) {
        element1.style.display = 'block';
        element2.style.display = 'block';
        element3.style.display = 'none';
    } else {
        element3.style.display = 'block';
        element1.style.display = 'none';
        element2.style.display = 'none';
    }
}
</script>

<body> 

    <div class="container">
      <header id="header">
         <?php include 'header.php';?>
     </header>
     <main id="main"> 
      
        <br><br><br><br>
        <form action="signup.php" method="post">

            <h2>Sign Up</h2>

            <?php if (isset($_GET['error'])) { ?>

            <p class="error"><?php echo $_GET['error']; ?></p>

            <?php } ?>

            <input type="text" name="uname" placeholder="Enter user id">

            <input type="password" name="password" placeholder="Enter password">

            <table>
              <tr> 
                <td> <label>User Type</label> </td>
                <td> <select name="usertype" id="usertype" onclick="toggle(this)">
                  <option value="1" selected>Individual</option>
                  <option value="2">Organization</option>
              </select> </td>


              <td> <label id="bloodgrouplabel">Blood Group</label> </td>
              <td>    <select name="bloodgroup" id="bloodgroup"> 
                  <option value="1">A-</option>
                  <option value="2">A+</option>
                  <option value="3">B-</option>
                  <option value="4">B+</option>
                  <option value="5">AB-</option>
                  <option value="6">AB+</option>
                  <option value="7">O-</option>
                  <option value="8">O+</option>
              </select> </td>

              <td style="display:none;" id="orgname2">  <input type="orgname" name="orgname" id="orgname" placeholder="Enter organization name"> </td>
          </tr>
      </table>

      <input type="zipcode" name="zipcode" placeholder="Enter zip code">

      <input type="latitude" name="latitude" placeholder="Enter latitude of home address">

      <input type="longitude" name="longitude" placeholder="Enter longitude of home address">
      
      <input type="email" name="email" placeholder="Enter email">

      <input type="phone" name="phone" placeholder="Enter phone nummber">

      <label>Contact Preference:</label> <br>
      <table>
        <tr> 
            <td> <input type="checkbox" id="allowemail" name="allowemail" value="1"> </td> 
            <td> <label for="allowemail">Allow Email</label> </td> 
            <td> <input type="checkbox" id="allowphonecall" name="allowphonecall" value="1"> </td> 
            <td> <label for="allowphonecall">Allow Phone Call</label> </td> 
            <td> <input type="checkbox" id="allowsms" name="allowsms" value="1"> </td> 
            <td> <label for="allowsms"> Allow SMS </label> </td> 
        </tr> 
    </table>
    
    <button type="submit">Sign-Up</button>


</form>

<br>
<button onclick="window.location.href='index.php';">
  Login
</button>
</main>
<footer id="footer">
  <?php include 'footer.php';?>
</footer>
</div>

</body>

</html>