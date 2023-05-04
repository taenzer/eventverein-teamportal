<?php
define("PAGE_TITLE", "Transaktionen");
require_once("../inc/transactions.php");

$t = new Transactions();

$year = date("Y");
$from = isset($_GET["von"]) ? $_GET["von"] : $year."-01-01";
$to = isset($_GET["bis"]) ? $_GET["bis"] : $year."-12-31";

$kontostand = $t->getKontostaende();
 ?>
<link rel="stylesheet" href="/assets/css/transaktionen.css">
<h1>Finanzübersicht</h1>




<div class="stats">
  <div class="stat">
    <p class="stat-title">Kontostand</p>
    <p class="stat-value"><?php echo($db->euro($kontostand["vereinskonto"])); ?></p>
    <p class="stat-info"></p>
  </div>
  <div class="stat">
    <p class="stat-title">Bar-Kasse</p>
    <p class="stat-value"><?php echo($db->euro($kontostand["barkasse"])); ?></p>
    <p class="stat-info"></p>
  </div>
</div>

<div class="filter-title">
  <h1>Transaktionen</h1>
  <form class="" action="" method="get">
    <input type="date" name="von" onChange="this.form.submit();" value="<?php echo($from);?>"> bis
    <input type="date" name="bis" value="<?php echo($to);?>">
  </form>
</div>

<div class="transaktionen">
  <a href="/transaktionen/neu" title="Neue Transaktion"><div class="transaktion" id="t-<?php echo $trans["tid"]; ?>" data-tid="<?php echo $trans["tid"]; ?>">
    <p class="new"><span class="icon">add</span> Neue Transaktion erfassen</p>
  </div></a>
  <?php $t->display($from, $to); ?>
</div>
