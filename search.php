
<!-- <script src="https://smtpjs.com/v3/smtp.js"> -->
	<script>

		function phoneCall(phone, allowphone, bloodgroup) {
			if (allowphone == 1) {
				alert('Call to number ' + phone + ' for bloodgroup ' + bloodgroup);
			} else {
				alert('User has not enabled phone call');
			}
		}

		function sendSms(phone, allowsms, bloodgroup) {
			if (allowsms == 1) {
				alert('Sms has been send for bloodgroup ' + bloodgroup);
			} else {
				alert('User has not enabled sms');
			}
		}

		function sendEmail(email, allowemail, bloodgroup) {
			if (allowemail == 1) {
	    	// Email.send({
	     //    Host: "smtp.mailtrap.io",
	     //    Username: "reenannishi1994@gmail.com",
	     //    Password: "Syracuse2@",
	     //    To: email,
	     //    From: "reenannishi1994@gmail.com",
	     //    Subject: "Urgent: Please help to get bloodgroup " + bloodgroup,
	     //    Body: "Please help us to provide bloodgroup " + bloodgroup,
	     //  })
	     //    .then(function (message) {
	     //      alert("mail sent successfully to email " + email)
	     //    });
	     alert("Email has been send to "+ email +" for bloodgroup " + bloodgroup);
	 } else {
	 	alert('User has not enabled email');
	 }
	}

</script>

<?php 
session_start(); 
include "config.php";
include "util.php";

function homeDisplay($zipcode, $bloodgroup) { 
	echo '<script>alert("Welcome to Geeks for Geeks" + $bloodgroup + " "+ $zipcode)</script>';

	if ($zipcode && $bloodgroup) {

		if ($bloodgroup) {
			return "SELECT * FROM User u JOIN Blood_Report b ON u.User_Id = b.User_Id JOIN User_Preference p ON u.User_Id = p.User_Id WHERE Zipcode = $zipcode";
		} else {
			return "SELECT * FROM User u JOIN Blood_Report b ON u.User_Id = b.User_Id JOIN User_Preference p ON u.User_Id = p.User_Id WHERE Zipcode = $zipcode AND Blood_Group = $bloodgroup";	
		}
	} else if ($zipcode) {
		return "SELECT * FROM User u JOIN Blood_Report b ON u.User_Id = b.User_Id JOIN User_Preference p ON u.User_Id = p.User_Id WHERE Zipcode = $zipcode";

	} else if ($latitude && $longitude && $radius) {
		return "SELECT latitude, longitude, SQRT(POW(69.1 * (latitude - [$latitude]), 2) + POW(69.1 * ([$longitude] - longitude) * COS(latitude / 57.3), 2)) AS distance FROM User u JOIN Blood_Report b ON u.User_Id = b.User_Id JOIN User_Preference p ON u.User_Id = p.User_Id HAVING distance < $radius ORDER BY distance";
	} else {
		return "SELECT * FROM User u JOIN Blood_Report b ON u.User_Id = b.User_Id JOIN User_Preference p ON u.User_Id = p.User_Id";
	}
}

$zipcode = validate($_GET['zipcode']);

$latitude = $_GET['latitude'];
$longitude = $_GET['longitude'];
$radius = $_GET['radius'];

$bloodgroup = $_GET['bloodgroup'];



$query_string = homeDisplay($zipcode, $bloodgroup, $latitude, $longitude, $radius);
$result = mysqli_query($conn, $query_string);

while ($row = mysqli_fetch_assoc($result)) {
	$user_id = $row['User_Id'];
	$bloodgroup = $row['Blood_Group'];
	$phone = $row['Phone'];
	$email = $row['Email'];
	$usertype = $row['User_Type'];
	$allowemail = $row['Allow_Email'];
	$allowphone = $row['Allow_Phone'];
	$allowsms = $row['Allow_Sms'];

	echo "<div class='grid-item'>";
	echo "$bloodgroup ($usertype)";
	echo "   <table>";
	echo "        <tr>";
	echo "             <td><button onclick=\"sendEmail('$email', '$allowemail', '$bloodgroup')\">Send Email</button></td>";
	echo "             <td><button onclick=\"sendSms('$phone', '$allowsms', '$bloodgroup')\">Send Sms</button></td>";
	echo "             <td><button onclick=\"phoneCall('$phone', '$allowphone', '$bloodgroup')\">Phone Call</button></td>";
	echo "        </tr>";
	echo "  </table>";
	echo "</div>";
}

?>