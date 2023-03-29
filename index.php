<!DOCTYPE html>

<html>

<head>

    <title>LOGIN</title>

    <link rel="stylesheet" type="text/css" href="style.css">

</head>

<body>

    <div class="container">
      <header id="header">
         <?php include 'header.php';?>
     </header>
     <main id="main"> 
      
        <br><br><br><br>
        <form action="login.php" method="post">

            <h2>LOGIN</h2>

            <?php if (isset($_GET['error'])) { ?>

                <p class="error"><?php echo $_GET['error']; ?></p>

            <?php } ?>

            <label>User Id</label>

            <input type="text" name="uname" placeholder="Enter user id"><br>

            <label>Password</label>

            <input type="password" name="password" placeholder="Enter user password"><br> 

            <button type="submit">Login</button><br>
            
        </form>

        <br>
        <button onclick="window.location.href='signup-page.php';">
          Sign-Up
      </button>

  </main>
  <footer id="footer">
      <?php include 'footer.php';?>
  </footer>
</div>

</body>

</html>