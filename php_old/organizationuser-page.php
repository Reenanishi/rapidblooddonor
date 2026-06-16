<!DOCTYPE html>

<html>

<head>

    <title>Organization User</title>

    <link rel="stylesheet" type="text/css" href="style.css">

</head>

<body>

    <div class="container">
      <header id="loginheader">
         <?php include 'loginheader.php';?>
     </header>
     <main id="main"> 
      <br><br><br><br>
      <form action="organizationuser.php" method="post">

        <h2>Add myself to organization</h2>

        <?php if (isset($_GET['error'])) { ?>

            <p class="error"><?php echo $_GET['error']; ?></p>

        <?php } ?>

        <input type="text" name="uname" placeholder="Enter organization user id"><br>
        <button type="submit" name="addbutton">Add</button>
        <button type="submit" id="deletebutton" name="deletebutton">Delete</button>
    </form>

</main>
<footer id="footer">
  <?php include 'footer.php';?>
</footer>
</div>

</body>

</html>