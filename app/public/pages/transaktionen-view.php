<?php
define("PAGE_TITLE", "Transaktion bearbeiten");
require_once("../inc/transaction-single.php");


if(!isset($_GET["tid"])){
  header("Location: /transaktionen");
  exit();
}

$t = new Transaction($_GET["tid"]);
if(!$t->isLoaded()){
  header("Location: /transaktionen");
  exit();
}
$partner = $t->getTransPartner($t->get("partner"));

 ?>
  <link rel="stylesheet" href="/assets/css/transaktionen.css">
  <h1>Transaktion #<?php echo $t->getTid(); ?></h1>
  <p class="datum"><?php echo($t->get("zeitstempel")); ?></p>

  <div class="transaktion-detail <?php echo $t->get("betrag") < 0 ? "ausgabe" : "einnahme"; ?>">
    <div class="beleg">
      <?php $beleg = $t->get("beleg");
      if(!empty($beleg)){
      ?>
      <iframe title="Belegvorschau" src="https://team.eventverein.de/doc.php?doctype=beleg&docid=<?php echo $beleg; ?>&download=false" width="100%" height="100%"></iframe>
    <?php }else{ ?>
      <div class="kein-beleg">
        <p>Zu dieser Transaktion wurde noch kein Beleg hochgeladen!</p>
      </div>
    <?php } ?>
    </div>
    <div class="info">
      <p class="src"><?php echo($t->get("src")); ?></p>
      <p class="betrag"><?php echo($db->euro($t->get("betragBrutto"))); ?></p>
      <p class="src">inkl. <?php echo($t->get("ust")); ?> UST, <?php echo($db->euro($t->get("betragNetto"))); ?> Netto</p>


      <p class="zweck"><?php echo($t->get("zweck")); ?></p>

      <a href="<?php echo("/".$partner["type"]."/view?id=".$partner["id"]); ?>"><p class="partner"><?php echo($partner["name"]); ?></p></a>

      <?php if(!$db->isDateLocked($t->get("zeitstempel"))){ ?>
          <a href="/transaktionen/edit?tid=<?php echo $t->getTid(); ?>" class="button edit-trans"><span class="icon">edit_note</span> </a>
      <?php } ?>

    </div>
  </div>
