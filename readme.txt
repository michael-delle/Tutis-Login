=== Login Script using PDO/OOP ===
Description: A login script that is written in OOP and uses PHP5's PDO.
Creator: FireDart
Site: http://www.FireDartStudios.com/

== Updates ==
= Version 1.3.2 (May 22, 2012) =
 - Fixed Bcrypt Security Hole, upgraded to $2y$

= Version 1.3.1 (May 19, 2012) =
 - Added "Change Password" feature on login
 - Fixed a few bugs in member.class.php

= Version 1.3 (May 12, 2012) =
 - Bcrypt Encrypted Passwords

= Version 1.2.1 (March 15, 2012)  =
 - Performance; Increased performance by only selected need columns from the db
 - Security; SESSION Hijacking Prevention (Thanks wide_load)
 - Security; Uses now MUST be logged in to see change password screen (before you could see it but needed the session id/valid so one could have used an existing session to reset your password, am sorry I missed this)
 - Registration; Check if user exist in inactive db before approving new user
 - Registration; If inactive user is older than 24 hours replace user
 - Logs; New Logs on users recovering and resetting password

= Version 1.2 (March 03, 2012) =
 - Recover Password (With feature to force users to change password on next login)
 - Option to user "Remember Me?" feature
 - "Remember Me?" Feature with sha256 Encryption on cookies
 - "Remember Me?" Feature Changes cookie very time a user visits
 - "Remember Me?" Feature verifies with db for active logins
 - Option to send user a welcome e-mail on registration
 - Option to send user an activation link on registration

= Version 1.1 (March 03, 2012) =
 - Add Captcha Support

= Version 1.0 (February 25, 2012) =
 - Simple Login Script
 - Registration Form
 - Captcha Support on Registration
 - 128 Character Encrypt Password using PHP's sha256 Hash
 - Option to send user a welcome e-mail on registration
 - Option to send user an activation link on registration
 - Example usage in the included zip (Simple example of the script in action)
 - Comes with a simplistic css style (Just for fun)