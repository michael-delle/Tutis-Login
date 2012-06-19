<?php
/*
**********
Member Page
**********
*/
/* Include Class */
include("database.class.php");
include("member.class.php");
/* Start an instance of the Database Class */
$database = new database("hostname name here", "database name here", "username here", "password here");
/* Create an instance of the Member Class */
$member = new member();
?>