<?php
define("PAGE_TITLE", "Mitgliederverwaltung");
require_once("../inc/mitglieder.php");

$m = new Mitglieder();
 ?>
<link rel="stylesheet" href="/assets/css/mitglieder.css">
<h1>Mitgliederverwaltung</h2>


  <?php $m->display(); ?>
