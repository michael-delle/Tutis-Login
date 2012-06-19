<?php
/*
Name:          Member Login
Author:        FireDart
License:       Creative Commons Attribution-ShareAlike 3.0 Unported License
                - http://creativecommons.org/licenses/by-sa/3.0/
*/
class captcha {
	/* Display Captcha */
	public function display($width, $height, $text) {
		/* Create Image */
		$image = imagecreate($width, $height);
		/* Set Background */
		$bg    = imagecolorallocate($image, 255, 255, 255);
		/* Set Text Color */
		$color  = imagecolorallocate($image, 0, 0, 0);
		/* Patch together Image */
		/* First character */
		$r = rand(-25, 25);
		imagettftext($image, 26, $r, 10, 33, $color, "fonts/arial.ttf", substr($text, 0, 1));
		/* Each character after that */
		for($i = 0; $i <= strlen($text); $i++) {
			$part = substr($text, $i + 1, 1);
			$r    = rand(-25, 25);
			$x    = 36 + (26 * $i);
			imagettftext($image, 26, $r, $x, 37, $color, "fonts/arial.ttf", $part);
			
		}
		
		/* Output the image */
		header('Content-type: image/png');
		
		imagepng($image);
		imagedestroy($image);
	}
}

$captcha = new captcha();
session_start();
echo $captcha->display("170", "50", $_SESSION['captcha']);
?>
