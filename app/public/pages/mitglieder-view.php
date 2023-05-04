<?php define("PAGE_TITLE", "Mitglied verwalten");

require_once("../inc/mitglieder.php");

if(isset($_GET["mnr"]) && !empty($_GET["mnr"])){
  $m = new Mitglied($_GET["mnr"]);
  $antrag = false;
}else if(isset($_GET["id"]) && !empty($_GET["id"])){
  $m = new Mitglied(null, $_GET["id"]);
  $antrag = true;
}else{
  header("Location: .");
}



if(isset($_POST["updateMember"])){
  unset($_POST["updateMember"]);
  $m->update($_POST);
  header("Refresh:0");
}
?>

<link rel="stylesheet" href="/assets/css/mitglieder.css">
<h1><?php echo $antrag ? "Mitgliedsantrag #".$m->getId() : $m->getName(); ?></h1>
<?php echo $antrag ? "<h2>Antragsteller: ".$m->getName()."</h2>" : "";?>

<p class="msg error">Achtung: Es wurde noch keine Rechteverwaltung implementiert. Deshalb kann jeder aktivierte Account alles einsehen und verwalten. Mitgliederaccounts
sollten daher nur aktiviert werden, wenn das unbedingt nötig ist.</p>
<?php

// Activation Link
if(!$m->isAcitivated()){
  $m->displayActivationLink();
}

$m->displayForm();

 ?>
