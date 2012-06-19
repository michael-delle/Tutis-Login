<?php
/*
Name:          Member Login
Author:        FireDart
License:       Creative Commons Attribution-ShareAlike 3.0 Unported License
                - http://creativecommons.org/licenses/by-sa/3.0/
*/
/* Include Class */
include("assets/member.inc.php");
$member->LoggedIn();

/* This is just for an example */
/* Load username from DB */
if(isset($_SESSION['member_id'])) {
	$user_id = $_SESSION['member_id'];
} else {
	$user_id = $_COOKIE['remember_me_id'];
}
$database->query('SELECT username FROM users WHERE id = :id', array(':id' => $user_id));
$user = $database->statement->fetch(PDO::FETCH_OBJ);
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>Secure Page</title>
	<!--CSS Files-->
	<link rel="stylesheet" type="text/css" href="assets/css/style.css" />
</head>
<body>
<div id="wrapper" class="group">
	<div id="header" class="group">
		<div id="member_name">Hello, <?php echo $user->username; ?><span id="member_message">Welcome back</span></div> <a href="member.php?action=logout" id="member_logout">Logout</a> <a href="member.php?action=change-password" id="change_password">Change Password</a>
	</div>
	<div id="body" class="group">
		<p>Hello, <br />This is a secure page.</p>
	</div>
</div>
</body>
</html>