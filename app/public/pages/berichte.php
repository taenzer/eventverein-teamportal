<?php
define("PAGE_TITLE", "Berichte");
require_once("../inc/ReportEngine.php");

$re = new ReportEngine();
$dt = new DateTime();
$startjahr = 2023;

$jahr = isset($_GET["jahr"]) ? $_GET["jahr"] : $dt->format("Y");
if($jahr < $startjahr){
  $jahr = $startjahr;
}

$diesjahr = $dt->format("Y");

 ?>

<h1>Berichte</h1>
<link rel="stylesheet" href="/assets/css/reports.css">
<link rel="stylesheet" href="/assets/css/popup.css">
<script src="//code.jquery.com/jquery-1.12.4.js"></script>
<script src="/assets/js/popup.js" charset="utf-8"></script>
<script src="/assets/js/reports.js" charset="utf-8"></script>
<div class="report-year">
<?php

while ($diesjahr >= $startjahr) {
  $class = $jahr == $diesjahr ? "selected" : "";
  ?>
  <a href="?jahr=<?php echo $diesjahr; ?>" class="year-select <?php echo $class; ?>"><?php echo $diesjahr; ?></a>
  <?php
  $diesjahr--;
} ?>
</div>
<div class="reports">
  <?php $re->printOverview($jahr); ?>
</div>
