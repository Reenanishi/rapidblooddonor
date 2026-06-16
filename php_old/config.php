<?php

$sname= "localhost";

$unmae= "root";

$password = "";

$db_name = "rapiddonor";

$conn = new mysqli($sname, $unmae, $password, $db_name);

if (!$conn) {

    echo "Connection failed!";

}
