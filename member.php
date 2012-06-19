<?php
/*
**********
Member Page
**********
*/
/* Include Code */
include("assets/member.inc.php");
/* Is an Action set? */
if(isset($_GET['action'])) {
	$action = $_GET['action'];
} else {
	$action = null;
}
if($action == 'logout') {
	echo $member->logout();
	$title = 'Logging user out';
	$content = '
<div id="logout" class="group">
	<div class="notice info">You are being logged out...</div>
</div>';
} elseif($action == 'change-password') {
	$member->LoggedIn();
	$title = 'Chnage Password';
	$content = '
<div id="change-password" class="group">
	<h1>Change Password</h1>
	' . $member->changePassword() . '
</div>';
} elseif($action == 'register') {
	$title = 'Create an account';
	$content = '
<div id="register" class="group">
	<h1>Create an account</h1>
	' . $member->register() . '
</div>';
} elseif($action == 'recover-password') {
	$title = 'Recover your password';
	$content = '
<div id="recover-password" class="group">
	<h1>Recover your password</h1>
	' . $member->recoverPassword() . '
</div>';
} elseif($action == 'reset-password') {
	$member->LoggedIn();
	$title = 'Reset your password';
	$content = '
<div id="reset-password" class="group">
	<h1>Reset your password</h1>
	' . $member->resetPassword() . '
</div>';
} elseif($action == 'verification') {
	$title = 'Your account has been verified';
	$content = '
<div id="verification" class="group">
	<h1>Account Verification</h1>
	' . $member->verification() . '
</div>';
} else {
	$title = 'Please authenticate your self';
	$content = '
<div id="login" class="group">
	<h1>Login</h1>
	' . $member->login() . '
</div>';
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title><?php echo $title; ?></title>
	<!--CSS Files-->
	<link rel="stylesheet" type="text/css" href="assets/css/style.css" />
</head>
<body>
<?php echo $content; ?>
</body>
</html>