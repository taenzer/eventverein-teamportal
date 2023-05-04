<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
setlocale(LC_TIME, "de_DE");
date_default_timezone_set("Europe/Berlin");
require_once("config.php");

require_once("database.php");
$db = new DB();

require_once("auth.php");
$auth = new Auth($db->getMySqli());

require_once("logAndNotify.php");
$lan = new LogAndNotify();
 ?>
