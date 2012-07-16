<?php
/*
Name:          Member Login
Author:        FireDart
License:       Creative Commons Attribution-ShareAlike 3.0 Unported License
                - http://creativecommons.org/licenses/by-sa/3.0/
*/
/* Member Class */
class member {
	/* Simple Variables */
	private $remember = true;
	private $captcha = true;
	private $email_master = null;
	private $email_welcome = true;
	private $email_verification = true;
	private $bcryptRounds = 12;
	/* Needed Member Stuff */
	function __construct() {
		/* Prevent JavaScript from reaidng Session cookies */
		ini_set('session.cookie_httponly', true);
		
		/* Start Session */
		session_start();
		
		/* Check if last session is fromt he same pc */
		if(!isset($_SESSION['last_ip'])) {
			$_SESSION['last_ip'] = $_SERVER['REMOTE_ADDR'];
		}
		if($_SESSION['last_ip'] !== $_SERVER['REMOTE_ADDR']) {
			/* Clear the SESSION */
			$_SESSION = array();
			/* Destroy the SESSION */
			session_unset();
			session_destroy();
		}
	}
	/*
	User Authentication
	*/
	/* Current Path */
	public function currentPath($type = 0) {
		$currentPath  = 'http';
		if(isset($_SERVER["HTTPS"]) == "on") {$currentPage .= "s";}
		$currentPath .= "://";
		$currentPath .= dirname($_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"]) . '/';
		return $currentPath;
	}
	
	/* Current Page */
	public function currentPage() {
		/* Current Page */
		$currentPage  = 'http';
		if(isset($_SERVER["HTTPS"]) == "on") {$currentPage .= "s";}
		$currentPage .= "://";
		$currentPage .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
		return $currentPage;
	}
	
	/* Gen Salt */
	public function genSalt() {
		/* openssl_random_pseudo_bytes(16) Fallback */
		$seed = '';
		for($i = 0; $i < 16; $i++) {
			$seed .= chr(mt_rand(0, 255));
		}
		/* GenSalt */
		$salt = substr(strtr(base64_encode($seed), '+', '.'), 0, 22);
		/* Return */
		return $salt;
	}
	
	/* Gen Password */
	public function genHash($salt, $password) {
		/* Explain '$2y$' . $this->rounds . '$' */
			/* 2a selects bcrypt algorithm */
			/* $this->rounds is the workload factor */
		/* GenHash */
		$hash = crypt($password, '$2y$' . $this->bcryptRounds . '$' . $this->genSalt());
		/* Return */
		return $hash;
	}
	
	/* Verify Password */
	public function verify($password, $existingHash) {
		/* Hash new password with old hash */
		$hash = crypt($password, $existingHash);
		/* Do Hashs match? */
		if($hash === $existingHash) {
			return true;
		} else {
			return false;
		}
	}
	
	/* Login */
	public function login() {
		global $database;
		/* User Rember me feature? */
		if($this->remember == true) {
			$remember = '<div class="clearer"> </div><p class="remember_me"><input type="checkbox" name="remember_me" value="1" /> Remember me?</p>';
		} else {
			$remember = "";
		}
		/* Login Form */
		$form = '
<form name="login" action="' . $this->currentPage() . '" method="post" class="group">
	<label>
		<span>Username</span>
		<input type="text" name="username" />
	</label>
	<label>
		<span>Password</span>
		<input type="password" name="password" />
	</label>
	' . $remember . '
	<input name="login" type="submit" value="Login" />
</form>
<p class="options group"><a href="member.php?action=register">Register</a> &bull; <a href="member.php?action=recover-password">Recover Password</a></p>
		';
		/* Check if Login is set */
		if(isset($_POST['login'])) {
			/* Set username and password */
			if(empty($_POST['username'])) {
				$username = null;
			} else {
				$username = $_POST['username'];
			}
			if(empty($_POST['password'])) {
				$password = null;
			} else {
				$password = $_POST['password'];
			}
			/* Is both Username and Password set? */
			if($username && $password) {
				/* Get User data */
				$database->query('SELECT id, password FROM users WHERE username = :username', array(':username' => $username));
				/* Check if user exist */
				if($database->count() >= '1') {
					/* Get the users info */
					$user = $database->statement->fetch(PDO::FETCH_OBJ);
					/* Check hash */
					if($this->verify($password, $user->password) == true) {
						/* If correct create session */
						session_regenerate_id();
						$_SESSION['member_id'] = $user->id;
						$_SESSION['member_valid'] = 1;
						/* User Rember me feature? */
						$this->createNewCookie($user->id);
						/* Log */
						$this->userLogger($user->id, 0);
						/* Report Status */
						$message = "Authentication Success";
						$message_type = 2;
						$return_form = 0;
						/* Redirect */
						echo '<meta http-equiv="refresh" content="2;url=index.php" />';
					} else {
						/* Report Status */
						$message = "Authentication Failed";
						$message_type = 1;
						$return_form = 1;
					}
				} else {
					/* Report Status */
					$message = "Authentication Failed";
					$message_type = 1;
					$return_form = 1;
				}
			} else {
				/* Report Status */
				$message = "Authentication Failed";
				$message_type = 1;
				$return_form = 1;
			}
		} else {
			/* Report Status */
			$message = "Please authenticate your self";
			$message_type = 0;
			$return_form = 1;
		}
		/* What type of message? */
		switch($message_type) {
			case 0:
				$type = "info";
				break;
			case 1:
				$type = "error";
				break;
			case 2:
				$type = "success";
				break;
		}
		/* Combine Data */
		$data = '<div class="notice ' . $type . '">' . $message . '</div>';
		/* We need the login form? */
		if($return_form == 1) {
			$data .= $form;
		}
		/* Return data */
		return $data;
	}
	
	/* Is the user Logged In? */
	public function LoggedIn() {
		global $database;
		/* Is a SESSION set? */
		if(isset($_SESSION['member_valid']) && $_SESSION['member_valid']) {
			/* Return true */
			$status = true;
			
			/* Check if user needs account reset */
			$database->query('SELECT reset FROM users WHERE id = :id', array(':id' => $_SESSION['member_id']));
			$user = $database->statement->fetch(PDO::FETCH_OBJ);
			/* Is a password Reset needed? */
			if($user->reset == 1) {
				$reset = true;
			} else {
				$reset = false;
			}
		/* Is a COOKIE set? */
		} elseif(isset($_COOKIE['remember_me_id']) && isset($_COOKIE['remember_me_hash'])) {
			/* If so, find the equivilent in the db */
			$database->query('SELECT id, hash FROM users_logged WHERE id = :id', array(':id' => $_COOKIE['remember_me_id']));
			/* Does the record exist? */
			if($database->count() >= '1') {
				/* If so load the data */
				$user = $database->statement->fetch(PDO::FETCH_OBJ);
				/* Do the hashes match? */
				if($user->hash == $_COOKIE['remember_me_hash']) {
					/* If so Create a new cookie and mysql record */
					$this->createNewCookie($user->id);
					/* Return true */
					$status = true;
					
					/* Check if user needs account reset */
					$database->query('SELECT reset FROM users WHERE id = :id', array(':id' => $_COOKIE['remember_me_id']));
					$user = $database->statement->fetch(PDO::FETCH_OBJ);
					/* Is a password Reset needed? */
					if($user->reset == 1) {
						$reset = true;
					} else {
						$reset = false;
					}
					
				} else {
					/* Return false */
					$status = false;
					$reset = false;
				}
			}
		} else {
			/* Return false */
			$status = false;
			$reset = false;
		}
		
		/* */
		if($status != true) {
			header("Location: member.php?action=login");
		} else {
			if($reset == true && basename($_SERVER["REQUEST_URI"]) != "member.php?action=reset-password") {
				header("Location: member.php?action=reset-password");
			}
		}
	}
	
	/* Logout */
	public function logout() {
		/* Log */
		if(isset($_SESSION['member_id'])) {
			$user_id = $_SESSION['member_id'];
		} else {
			$user_id = $_COOKIE['remember_me_id'];
		}
		$this->userLogger($user_id, 1);
		/* Clear the SESSION */
		$_SESSION = array();
		/* Destroy the SESSION */
		session_unset();
		session_destroy();
		/* Delete all old cookies and user_logged */
		if(isset($_COOKIE['remember_me_id'])) {
			$this->deleteCookie($_COOKIE['remember_me_id']);
		}
		/* Redirect */
		header('Refresh: 2; url=index.php');
	}
	
	/* Is the user Logged In? */
	public function createNewCookie($id) {
		global $database;
		/* User Rember me feature? */
		if($this->remember == true) {
			/* Gen new Hash */
			$hash = $this->genHash($this->genSalt(), $_SERVER['REMOTE_ADDR']);
			/* Set Cookies */
			setcookie("remember_me_id", $id, time() + 31536000);  /* expire in 1 year */
			setcookie("remember_me_hash", $hash, time() + 31536000);  /* expire in 1 year */
			/* Delete old record, if any */
			$database->query('DELETE FROM users_logged WHERE id = :id', array(':id' => $id));
			/* Insert new cookie */
			$database->query('INSERT INTO users_logged(id, hash) VALUES(:id, :hash)', array(':id' => $id, ':hash' => $hash));
		}
	}
	
	/* Delete Cookies? */
	public function deleteCookie($id) {
		global $database;
		/* User Rember me feature? */
		if($this->remember == true) {
			/* Destroy Cookies */
			setcookie("remember_me_id", "", time() - 31536000);  /* expire in 1 year */
			setcookie("remember_me_hash", "", time() - 31536000);  /* expire in 1 year */
			/* Clear DB */
			$database->query('DELETE FROM users_logged WHERE id = :id', array(':id' => $id));
		}
	}
	
	/* Logger */
	public function userLogger($userid, $action) {
		global $database;
		/* What type of action? */
		switch($action) {
			case 0:
				$action = "Logged In";
				break;
			case 1:
				$action = "Logged Out";
				break;
			case 2:
				$action = "Recover Password";
				break;
			case 2:
				$action = "Reset Password";
				break;
		}
		/* Get User's IP */
		$ip = $_SERVER['REMOTE_ADDR'];
		/* Date */
		$timestamp = date("Y-m-d H:i:s", time());
		$database->query('INSERT INTO users_logs(userid, action, time, ip) VALUES(:userid, :action, :time, :ip)', array(':userid' => $userid, ':action' => $action, ':time' => $timestamp, ':ip' => $ip));
	}
	
	/* Register */
	public function register() {
		global $database;
		/* Set Message Array */
		$message = array();
		/* Check if Login is set */
		if(isset($_POST['register'])) {
			/* Check Username */
			if(!empty($_POST['username'])) {
				$check_username = strtolower($_POST['username']);
				/* Check the username length */
				$length = strlen($check_username);
				if($length >= 5 && $length <= 25) {
					/* Is the username Alphanumeric? */
					if(preg_match('/[^a-zA-Z0-9_]/', $check_username)) {
						$error[] = "Please enter a valid alphanumeric username";
						$username = null;
					} else {
						$database->query('SELECT id FROM users WHERE username = :username', array(':username' => $check_username));
						/* Check if user exist in database */
						if($database->count() == 0) {
							/* Require use to validate account */
							if($this->email_verification == true) {
								/* Check if user exist in inactive database */
								$database->query('SELECT date FROM users_inactive WHERE username = :username', array(':username' => $check_username));
								/* If user incative is older than 24 hours */
								$user = $database->statement->fetch(PDO::FETCH_OBJ);
								if($database->count() == 0 or time() >= strtotime($user->date) + 86400) {
									/* If user incative is older than 24 hours */
									$username = $_POST['username'];
								} else {
									$error[] = "Username already in use";
									$username = $check_username;
								}
							} else {
								$username = $_POST['username'];
							}
						} else {
							$error[] = "Username already in use";
							$username = $check_username;
						}
					}
				} else {
					$error[] = "Please enter a username between 5 to 25 characters";
					$username = $check_username;
				}
			} else {
				$error[] = "Please enter a username";
				$username = null;
			}
			/* Check Password */
			if(!empty($_POST['password'])) {
				/* Do passwords match? */
				if(isset($_POST['password_again']) && $_POST['password_again'] == $_POST['password']) {
					/* Is the password long enough? */
					$length = strlen($_POST['password']);
					if($length >= 8) {
						$password = $_POST['password'];
					} else {
						$error[] = "Passwords must be atleast than 8 characters";
					}
				} else {
					$error[] = "Passwords must match";
				}
			} else {
				$error[] = "Please enter a password";
			}
			/* Check E-Mail */
			if(!empty($_POST['email'])) {
				$check_email = strtolower($_POST['email']);
				$check_email_again = strtolower($_POST['email_again']);
				/* Do E-Mails match? */
				if(isset($check_email_again) && $check_email_again == $check_email) {
					$length = strlen($check_email);
					/* Is the E-Mail really an E-Mail? */
					if(filter_var($check_email, FILTER_VALIDATE_EMAIL) == true) {
						$database->query('SELECT id FROM users WHERE email = :email', array(':email' => $check_email));
						/* Check if user exist with email */
						if($database->count() == 0) {
							/* Require use to validate account */
							if($this->email_verification == true) {
								/* Check if user exist with email in inactive */
								$database->query('SELECT date FROM users_inactive WHERE email = :email', array(':email' => $check_email));
								/* If user incative is older than 24 hours */
								$user = $database->statement->fetch(PDO::FETCH_OBJ);
								if($database->count() == 0 or time() >= strtotime($user->date) + 86400) {
									$email = $check_email;
									$email_again = $check_email_again;
								} else {
									$error[] = "E-Mail already in use";
									$email = null;
									$email_again = null;
								}
							} else {
								$email = $check_email;
								$email_again = $check_email_again;
							}
						} else {
							$error[] = "E-Mail already in use";
							$email = null;
							$email_again = null;
						}
					} else {
						$error[] = "Invalid E-Mail";
						$email = $check_email;
						$email_again = $check_email_again;
					}
				} else {
					$error[] = "E-Mails must match";
					$email = $check_email;
					$email_again = $check_email_again;
				}
			} else {
				$error[] = "Please enter an E-Mail";
				$email = null;
				$email_again = null;
			}
			
			/* Captcha? */
			if($this->captcha == true) {
				/* Check E-Mail */
				if(!empty($_POST['captcha'])) {
					if($_POST['captcha'] != $_SESSION['captcha']) {
						$error[] = "Invalid Captcha";
					}
				} else {
					$error[] = "Please fill in the Captcha";
				}
			}
			
			/* Is both Username and Password set? */
			if(!isset($error)) {
				$return_form = 0;
				/* Final Format */
				$password = $this->genHash($this->genSalt(), $password);
				/* Send the user a welcome E-Mail */
				if($this->email_welcome == true) {
					/* Send the user an E-Mail */
					/* Can we send a user an E-Mail? */
					if(function_exists('mail') && $this->email_master != null) {
						$subject = "Thank you for creating an account, " . $username;
						$body_content = "Hi " . $username . ",<br />Thanks for signing-up!<br /><br /><i>-Admin</i>";
						/* E-Mail body */
						$body = '<html lang="en"><body style="margin: 0; padding: 0; width: 100% !important;" bgcolor="#eeeeee"><table bgcolor="#eeeeee" cellpadding="0" cellspacing="0" border="0" align="center" width="100%" topmargin="0"><tr><td align="center" style="margin: 0; padding: 0;"><table cellpadding="0" cellspacing="0" border="0" align="center" width="100%" style="padding: 20px 0;"><tr><td width="600" style="font-size: 0px;">&nbsp;</td></tr></table><table width="600" cellspacing="0" cellpadding="0" border="0" align="center" class="header" style="border-bottom: 1px solid #eeeeee; font-family: Helvetica, Arial, sans-serif; background:#ffffff;"><tbody><tr><td width="20" style="font-size: 0px;">&nbsp;</td><td width="580" align="left" style="padding: 5px 0 10px;"><h1 style="color: #444444; font: bold 32px Helvetica, Arial, sans-serif; margin: 0; padding: 0; line-height: 40px; border: none;">Your accound has been created</h1></td></tr></tbody></table></td></tr><tr><td align="center" style="margin: 0; padding: 0;"><table align="center" border="0" style="background-color:#ffffff;" width="600" height="100" cellpadding="3" cellspacing="3"><tr><td style="color: #222222; font: normal 16px Helvetica, Arial, sans-serif; margin: 0; padding: 10px; line-height: 18px;">' . $body_content . '</td></tr></table></td></tr><tr><td align="center" style="margin: 0; padding: 0;"><table cellpadding="0" cellspacing="0" border="0" align="center" width="100%" style="padding: 20px 0;"><tr><td width="600" style="font-size: 0px;">&nbsp;</td></tr></table></td></tr></table></body></html>';
						/* Headers */
						$headers = "From: " . strip_tags($this->email_master) . "\r\n";
						$headers .= "Reply-To: ". strip_tags($this->email_master) . "\r\n";
						$headers .= "MIME-Version: 1.0\r\n";
						$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
						/* Send it */
						mail($email, $subject, $body, $headers);
					}
				}
				/* Require use to validate account */
				if($this->email_verification == true) {
					/* Send the user an E-Mail */
					/* Can we send a user an E-Mail? */
					if(function_exists('mail') && $this->email_master != null) {
						$verCode = md5(uniqid(rand(), true) . md5(uniqid(rand(), true)));
						$subject = "Thank you for creating an account, " . $username;
						$body_content = 'Hi ' . $username . ',<br />Thanks for signing-up!<br />To activate your account please click the link below, or copy past it into the address bar of your web browser<hr /><a href="' . $this->currentPath() . 'member.php?action=verification&vercode=' . $verCode . '">' . $this->currentPath() . 'member.php?action=verification&vercode=' . $verCode . '</a><br /><br /><i>-Admin</i>';
						/* E-Mail body */
						$body = '<html lang="en"><body style="margin: 0; padding: 0; width: 100% !important;" bgcolor="#eeeeee"><table bgcolor="#eeeeee" cellpadding="0" cellspacing="0" border="0" align="center" width="100%" topmargin="0"><tr><td align="center" style="margin: 0; padding: 0;"><table cellpadding="0" cellspacing="0" border="0" align="center" width="100%" style="padding: 20px 0;"><tr><td width="600" style="font-size: 0px;">&nbsp;</td></tr></table><table width="600" cellspacing="0" cellpadding="0" border="0" align="center" class="header" style="border-bottom: 1px solid #eeeeee; font-family: Helvetica, Arial, sans-serif; background:#ffffff;"><tbody><tr><td width="20" style="font-size: 0px;">&nbsp;</td><td width="580" align="left" style="padding: 5px 0 10px;"><h1 style="color: #444444; font: bold 32px Helvetica, Arial, sans-serif; margin: 0; padding: 0; line-height: 40px; border: none;">Your accound has been created</h1></td></tr></tbody></table></td></tr><tr><td align="center" style="margin: 0; padding: 0;"><table align="center" border="0" style="background-color:#ffffff;" width="600" height="100" cellpadding="3" cellspacing="3"><tr><td style="color: #222222; font: normal 16px Helvetica, Arial, sans-serif; margin: 0; padding: 10px; line-height: 18px;">' . $body_content . '</td></tr></table></td></tr><tr><td align="center" style="margin: 0; padding: 0;"><table cellpadding="0" cellspacing="0" border="0" align="center" width="100%" style="padding: 20px 0;"><tr><td width="600" style="font-size: 0px;">&nbsp;</td></tr></table></td></tr></table></body></html>';
						/* Headers */
						$headers = "From: " . strip_tags($this->email_master) . "\r\n";
						$headers .= "Reply-To: ". strip_tags($this->email_master) . "\r\n";
						$headers .= "MIME-Version: 1.0\r\n";
						$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
						/* Send it */
						if(mail($email, $subject, $body, $headers)) {
							/* Insert Data */
							$date = date("Y-m-d H:i:s", time());
							$database->query('INSERT INTO users_inactive(verCode, username, password, email, date) VALUES (?, ?, ?, ?, ?)', array($verCode, $username, $password, $email, $date));
							$success[] = "You account has been created!";
							$info[] = "Please check your e-mail to activate your account";
							/* Redirect */
							echo '<meta http-equiv="refresh" content="2;url=index.php" />';
						} else {
							$error[] = "Could not send e-mail!<br />Please contact the site admin.";
						}
					} else {
						$error[] = "It seems this server cannot send e-mails!<br />Could not send e-mail!<br />Please contact the site admin.";
					}
				} else {
					/* Insert Data */
					$date = date("Y-m-d", time());
					$database->query('INSERT INTO users(username, password, email, date) VALUES (?, ?, ?, ?)', array($username, $password, $email, $date));
					$success[] = "You account has been created!";
					/* Redirect */
					echo '<meta http-equiv="refresh" content="2;url=index.php" />';
				}
			} else {
				if($this->captcha == true) {
					/* If an error recreate captcha */
					$this->randomString();
				}
				$return_form = 1;
			}
		} else {
			/* Report Status */
			$info[] = "Please fill in all the information";
			$return_form = 1;
			$username = null;
			$email = null;
			$email_again = null;
			if($this->captcha == true) {
					/* If an error recreate captcha */
					$this->randomString();
				}
		}
		/* Register Form */
		/* Captcha? */
		if($this->captcha == true) {
			$captcha_input = '
<label>
		<span>Captcha</span>
		<span id="captcha">
			<input type="text" name="captcha" value="" />
			<img alt="Captcha" src="' . $this->currentPath() . 'assets/captcha.php" />
		</span>
	</label>
			';
		} else {
			$captcha_input = null;
		}
		
		$form = '
<form name="register" action="' . $this->currentPage() . '" method="post">
	<label>
		<span>Username</span>
		<input type="text" name="username" value="' . $username . '" />
	</label>
	<label>
		<span>Password</span>
		<input type="password" name="password" />
	</label>
	<label>
		<span>Password Again</span>
		<input type="password" name="password_again" />
	</label>
	<label>
		<span>E-Mail</span>
		<input type="text" name="email" value="' . $email . '" />
	</label>
	<label>
		<span>E-Mail Again</span>
		<input type="text" name="email_again" value="' . $email_again . '" />
	</label>
	' . $captcha_input . '
	<input name="register" type="submit" value="Register" />
</form>
		';
		/* Combine Data */
		$data = "";
		/* Report any Info */
		if(isset($info)) {
			foreach($info as $message) {
				$data .= '<div class="notice info">' . $message . '</div>';
			}
		}
		/* Report any Errors */
		if(isset($error)) {
			foreach($error as $message) {
				$data .= '<div class="notice error">' . $message . '</div>';
			}
		}
		/* Report any Success */
		if(isset($success)) {
			foreach($success as $message) {
				$data .= '<div class="notice success">' . $message . '</div>';
			}
		}
		/* Do we need the login form? */
		if($return_form == 1) {
			$data .= $form;
		}
		/* Return data */
		return $data;
	}
	
	/* Random String */
	public function randomString() {
		$chars = '1234567890AaBbCcDdEeFfGgHhIiJjKkLlMmNnOoPpQqRrSsTtUuVvWwXxYyZz';
		$string = "";
		for($i = 0; $i < 6; $i++) {
			$string .= ($i%2) ? $chars[mt_rand(10, 23)] : $chars[mt_rand(0, 18)];
		}
		$_SESSION['captcha'] = $string;
		return $_SESSION['captcha'];
	}
	
	/* Recover Password */
	public function recoverPassword() {
		global $database;
		/* Recover Password Form */
		$form = '
<form name="recover" action="' . $this->currentPage() . '" method="post" class="group">
	<input type="text" name="email" />
	<input name="recover" type="submit" value="Recover" />
</form>
		';
		if(isset($_POST['recover'])) {
			$database->query('SELECT username, email FROM users WHERE email = :email', array(':email' => $_POST['email']));
			/* Check if user exist */
			if($database->count() >= '1') {
				/* Get the users info */
				$user = $database->statement->fetch(PDO::FETCH_OBJ);
				/* Create a random password */
				$chars = '1234567890AaBbCcDdEeFfGgHhIiJjKkLlMmNnOoPpQqRrSsTtUuVvWwXxYyZz!@#$%^&*';
				$temp_password = "";
				for($i = 0; $i < 10; $i++) {
					$temp_password .= ($i%2) ? $chars[mt_rand(10, 23)] : $chars[mt_rand(0, 18)];
				}
				/* Can we send a user an E-Mail? */
				if(function_exists('mail') && $this->email_master != null) {
					/* E-Mail */
					$subject = "You requested a password reset";
					$body_content = "Hi " . $user->username . ",<br />Your password has been temporarily set to <b>" . $temp_password . "</b>.<br />Please change your password once your are logged in.<br /><br /><i>-Admin</i>";
					$body = '<html lang="en"><body style="margin: 0; padding: 0; width: 100% !important;" bgcolor="#eeeeee"><table bgcolor="#eeeeee" cellpadding="0" cellspacing="0" border="0" align="center" width="100%" topmargin="0"><tr><td align="center" style="margin: 0; padding: 0;"><table cellpadding="0" cellspacing="0" border="0" align="center" width="100%" style="padding: 20px 0;"><tr><td width="600" style="font-size: 0px;">&nbsp;</td></tr></table><table width="600" cellspacing="0" cellpadding="0" border="0" align="center" class="header" style="border-bottom: 1px solid #eeeeee; font-family: Helvetica, Arial, sans-serif; background:#ffffff;"><tbody><tr><td width="20" style="font-size: 0px;">&nbsp;</td><td width="580" align="left" style="padding: 5px 0 10px;"><h1 style="color: #444444; font: bold 32px Helvetica, Arial, sans-serif; margin: 0; padding: 0; line-height: 40px; border: none;">Your accound has been created</h1></td></tr></tbody></table></td></tr><tr><td align="center" style="margin: 0; padding: 0;"><table align="center" border="0" style="background-color:#ffffff;" width="600" height="100" cellpadding="3" cellspacing="3"><tr><td style="color: #222222; font: normal 16px Helvetica, Arial, sans-serif; margin: 0; padding: 10px; line-height: 18px;">' . $body_content . '</td></tr></table></td></tr><tr><td align="center" style="margin: 0; padding: 0;"><table cellpadding="0" cellspacing="0" border="0" align="center" width="100%" style="padding: 20px 0;"><tr><td width="600" style="font-size: 0px;">&nbsp;</td></tr></table></td></tr></table></body></html>';
					/* Headers */
					$headers = "From: " . strip_tags($this->email_master) . "\r\n";
					$headers .= "Reply-To: ". strip_tags($this->email_master) . "\r\n";
					$headers .= "MIME-Version: 1.0\r\n";
					$headers .= "Content-Type: text/html; charset=ISO-8859-1\r\n";
					/* Send it */
					if(mail($user->email, $subject, $body, $headers)) {
						/* Upadte password only if you can mail them it! */
						$database->query('UPDATE users SET password = :password WHERE email = :email', array(':password' => $this->genHash($this->genSalt(), $temp_password), ':email' => $_POST['email']));
						$this->userLogger($user_id, 2);
						$success[] = 'Please check your e-mail';
						$return_form = 0;
					} else {
						$error[] = 'Could not send e-mail!<br />Contact the site admin.';
					$return_form = 0;
					}
				} else {
					$error[] = 'Could not send e-mail!<br />Contact the site admin.';
					$return_form = 0;
				}
			} else {
				$error[] = 'Sorry that e-mail does not exist in our database';
				$return_form = 1;
			}
		} else {
			$info[] = 'Please enter your e-mail';
			$return_form = 1;
		}
		$data = "";
		/* Report any Info */
		if(isset($info)) {
			foreach($info as $message) {
				$data .= '<div class="notice info">' . $message . '</div>';
			}
		}
		/* Report any Errors */
		if(isset($error)) {
			foreach($error as $message) {
				$data .= '<div class="notice error">' . $message . '</div>';
			}
		}
		/* Report any Success */
		if(isset($success)) {
			foreach($success as $message) {
				$data .= '<div class="notice success">' . $message . '</div>';
			}
		}
		/* We need the login form? */
		if($return_form == 1) {
			$data .= $form;
		}
		return $data;
	}
	
	/* Reset Password */
	public function resetPassword() {
		global $database;
		if(isset($_POST['reset-password'])) {
			/* Check Password */
			if(!empty($_POST['password'])) {
				/* Do passwords match? */
				if(isset($_POST['password_again']) && $_POST['password_again'] == $_POST['password']) {
					/* Is the password long enough? */
					$length = strlen($_POST['password']);
					if($length >= 8) {
						$password = $_POST['password'];
					} else {
						$error[] = "Passwords must be atleast than 8 characters";
					}
				} else {
					$error[] = "Passwords must match";
				}
			} else {
				$error[] = "Please enter a password";
			}
			if(!isset($error)) {
				if(isset($_SESSION['member_valid'])) {
					$id = $_SESSION['member_id'];
				} else {
					$id = $_COOKIE['remember_me_id'];
				}
				$password = $this->genHash($this->genSalt(), $password);
				$database->query('UPDATE users SET password = :password, reset = 0 WHERE id = :id', array(':password' => $password, ':id' => $id));
				$this->userLogger($user_id, 3);
				/* Report Status */
				$success[] = "Password has been updated!";
				$return_form = 0;
				/* Redirect */
				echo '<meta http-equiv="refresh" content="2;url=index.php" />';
			} else {
				$return_form = 1;
			}
		} else {
			/* Report Status */
			$info[] = "Please choose a new password";
			$return_form = 1;
		}
		
		/* Reset Password Form */
		$form = '
<form name="reset-password" action="' . $this->currentPage() . '" method="post">
	<label>
		<span>Password</span>
		<input type="password" name="password" />
	</label>
	<label>
		<span>Password Again</span>
		<input type="password" name="password_again" />
	</label>
	<input name="reset-password" type="submit" value="Reset Password" />
</form>
		';
		/* Combine Data */
		$data = "";
		/* Report any Info */
		if(isset($info)) {
			foreach($info as $message) {
				$data .= '<div class="notice info">' . $message . '</div>';
			}
		}
		/* Report any Errors */
		if(isset($error)) {
			foreach($error as $message) {
				$data .= '<div class="notice error">' . $message . '</div>';
			}
		}
		/* Report any Success */
		if(isset($success)) {
			foreach($success as $message) {
				$data .= '<div class="notice success">' . $message . '</div>';
			}
		}
		/* Do we need the login form? */
		if($return_form == 1) {
			$data .= $form;
		}
		/* Return data */
		return $data;
	}
	
	/* Change Password */
	public function changePassword() {
		global $database;
		if(isset($_POST['change-password'])) {
			/* Check Password */
			if(!empty($_POST['password'])) {
				/* Do passwords match? */
				if(isset($_POST['password_again']) && $_POST['password_again'] == $_POST['password']) {
					/* Is the password long enough? */
					$length = strlen($_POST['password']);
					if($length >= 8) {
						$password = $_POST['password'];
					} else {
						$error[] = "Passwords must be atleast than 8 characters";
					}
				} else {
					$error[] = "Passwords must match";
				}
			} else {
				$error[] = "Please enter a password";
			}
			if(!isset($error)) {
				if(isset($_SESSION['member_valid'])) {
					$id = $_SESSION['member_id'];
				} else {
					$id = $_COOKIE['remember_me_id'];
				}
				$password = $this->genHash($this->genSalt(), $password);
				$database->query('UPDATE users SET password = :password WHERE id = :id', array(':password' => $password, ':id' => $id));
				$this->userLogger($id, 3);
				/* Report Status */
				$success[] = "Password has been updated!";
				$return_form = 0;
				/* Redirect */
				//echo '<meta http-equiv="refresh" content="2;url=index.php" />';
			} else {
				$return_form = 1;
			}
		} else {
			/* Report Status */
			$info[] = "Please choose a new password";
			$return_form = 1;
		}
		
		/* Reset Password Form */
		$form = '
<form name="change-password" action="' . $this->currentPage() . '" method="post">
	<label>
		<span>New Password</span>
		<input type="password" name="password" />
	</label>
	<label>
		<span>New Password Again</span>
		<input type="password" name="password_again" />
	</label>
	<input name="change-password" type="submit" value="Change Password" />
</form>
		';
		/* Combine Data */
		$data = "";
		/* Report any Info */
		if(isset($info)) {
			foreach($info as $message) {
				$data .= '<div class="notice info">' . $message . '</div>';
			}
		}
		/* Report any Errors */
		if(isset($error)) {
			foreach($error as $message) {
				$data .= '<div class="notice error">' . $message . '</div>';
			}
		}
		/* Report any Success */
		if(isset($success)) {
			foreach($success as $message) {
				$data .= '<div class="notice success">' . $message . '</div>';
			}
		}
		/* Do we need the login form? */
		if($return_form == 1) {
			$data .= $form;
		}
		/* Return data */
		return $data;
	}
	
	/* E-Mail Verification */
	public function verification() {
		global $database;
		if(isset($_GET['vercode'])) {
			$verCode = $_GET['vercode'];
			$database->query('SELECT username, password, email FROM users_inactive WHERE verCode = :verCode', array(':verCode' => $verCode));
			$user = $database->statement->fetch(PDO::FETCH_OBJ);
			/* Insert Data */
			$database->query('INSERT INTO users(username, password, email, date) VALUES (?, ?, ?, ?)', array($user->username, $user->password, $user->email, date('Y-m-d')));
			/* Clear Inactive */
			$database->query('DELETE FROM users_inactive WHERE verCode = :verCode', array(':verCode' => $verCode));
			/* Message */
			$success[] = "You account has been verified!";
			/* Redirect */
			echo '<meta http-equiv="refresh" content="2;url=index.php" />';
		} else {
			$info[] = '<div class="notice info">No verCode (Verification Code)!</div>';
		}
		/* Combine Data */
		$data = "";
		/* Report any Info */
		if(isset($info)) {
			foreach($info as $message) {
				$data .= '<div class="notice info">' . $message . '</div>';
			}
		}
		/* Report any Errors */
		if(isset($error)) {
			foreach($error as $message) {
				$data .= '<div class="notice error">' . $message . '</div>';
			}
		}
		/* Report any Success */
		if(isset($success)) {
			foreach($success as $message) {
				$data .= '<div class="notice success">' . $message . '</div>';
			}
		}
		/* Return data */
		return $data;
	}
}
?>
