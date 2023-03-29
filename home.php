<?php 

session_start();

if (isset($_SESSION['user_name'])) {

     ?>

     <!DOCTYPE html>

     <html>

     <head>

      <title>HOME</title>

      <link rel="stylesheet" type="text/css" href="style.css">

 </head>

 <body>


     <div class="container">
        <header id="loginheader">
             <?php include 'loginheader.php';?>
        </header>

        <main id="innermain"> 

          <br> <br> <br> <br> 
          <p>Hello, <?php echo $_SESSION['email']; ?></p>
          <div id="leftsplit">  

              <?php
              echo "<form action='home.php' method='get' id='searchform'>";
              echo "<h2>Search</h2>";

              echo "<input type='zipcode' name='zipcode' placeholder='Enter zip code'>";
              echo "<p> OR </p>";
              echo "<input type='latitude' name='latitude' placeholder='Enter latitude of home address'>";

              echo "<input type='longitude' name='longitude' placeholder='Enter longitude of home address'>";
              echo "<label>Select Radius in KM</label>";
              echo "<select name='radius' id='radius'>";
              echo "<option value='5'>5</option>";
              echo "<option value='10'>10</option>";
              echo "<option value='15'>15</option>";
              echo "<option value='20'>20</option>";
              echo "<option value='25'>25</option>";
              echo "<option value='30'>30</option>";
              echo "<option value='35'>35</option>";
              echo "<option value='40'>40</option>";
              echo "<option value='45'>45</option>";
              echo "<option value='50'>50</option>";
              echo "</select>";
              
              echo "<hr>";
              echo "<label>Blood Group</label>";
              echo "<select name='bloodgroup' id='bloodgroup' autofocus>";
              echo "<option value='0'>ALL</option>";
              echo "<option value='1'>A-</option>";
              echo "<option value='2'>A+</option>";
              echo "<option value='3'>B-</option>";
              echo "<option value='4'>B+</option>";
              echo "<option value='5'>AB-</option>";
              echo "<option value='6'>AB+</option>";
              echo "<option value='7'>O-</option>";
              echo "<option value='8'>O+</option>";
              echo "</select> <br><br><br>";
              echo "<button type='submit' name='search'>Search</button><br>";
              
              echo "</form>";
              ?>
         </div>  
         
         <div id="rightsplit">  
          <div class="grid-container">
             <?php include 'search.php';?>
        </div>
   </div>  


</main>
<footer id="footer">
    <?php include 'footer.php';?>
</footer>

</body>

</html>

<?php 

}else{

     header("Location: index.php");

     exit();

}

?>