<?php
define("PAGE_TITLE", "Neue Transaktion");


if(isset($_POST["action"], $_POST["zeitstempel"], $_POST["partner"], $_POST["zweck"], $_POST["betrag"], $_POST["ust"], $_POST["src"], $_POST["type"])){
  require_once("../inc/transactions.php");


  // Neuen Kontakt erstellen
  if(!isset($_POST["partner_exist"]) || empty($_POST["partner_exist"])){
    $kid = $db->neuerKontakt($_POST["partner"]);
  }else{
    $kid = $_POST["partner_exist"];
  }

  // Beleg ablegen
  $bid = "";

  if(isset($_FILES["beleg"])){
    $beleg = new Beleg();
    $beleg->upload($_FILES["beleg"], $_POST["beleg-name"]);
    if(empty($beleg->error)){
      $bid = $beleg->getBid();
    }
  }

  $betrag = $_POST["type"] == "einnahme" ? $_POST["betrag"] : $_POST["betrag"] * -1;

  $t = new Transactions();
  if(!$t->new($_POST["zeitstempel"], $kid, $_POST["zweck"], $betrag, $_POST["ust"], $_POST["src"], $bid)){
    $lan->addError("Transaktion konnte nicht erstellt werden: für den angegebenen Zeitbereich wurde schon ein Bericht erstellt.");
  }
  header("Location: /transaktionen");

}else if(isset($_POST["type"])
  && $_POST["type"] == "umbuchung"
  && isset($_POST["action"], $_POST["zeitstempel"], $_POST["partner"], $_POST["betrag"], $_POST["quelle"], $_POST["ziel"])){

  require_once("../inc/umbuchung.php");
  $u = new Umbuchung();
  // Neuen Kontakt erstellen
  if(!isset($_POST["partner_exist"]) || empty($_POST["partner_exist"])){
    $kid = $db->neuerKontakt($_POST["partner"]);
  }else{
    $kid = $_POST["partner_exist"];
  }

  $data = $_POST;
  $data["partner"] = $kid;
  $u->update($data);

}

?>
<link rel="stylesheet" href="/assets/css/transaktionen.css">
<!-- Jquery UI Autocomplete Plugin for Partner Selection -->
<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script src="/assets/js/partnerselect.js"></script>

<h1>Transaktion erfassen</h1>

<?php if(isset($_GET["type"]) && $_GET["type"] != "umbuchung"){ ?>

<link rel="stylesheet" href="/assets/css/transaktionen.css">



<h2>Neue <?php echo($_GET["type"]); ?></h2>

 <form class="transaction-form " enctype="multipart/form-data" action="" method="post">
   <label for="zeitstempel">Zeitstempel</label>
   <input type="date" name="zeitstempel" id="zeitstempel" value="<?php echo(date("Y-m-d"))?>">
   <label for="partner">Partner</label>
   <div class="partner-input ui-front">
     <input type="text" name="partner" data-id="" id="partner" value="" required>
     <input type="hidden" id="partner_exist" name="partner_exist" value="">
   </div>
   <label for="zweck">Zweck</label>
   <input type="text" name="zweck" id="zweck" value="" required>
   <label for="betrag">Betrag</label>
   <input type="number" name="betrag" id="betrag" min="0" step="0.01" value="" required>
   <label for="ust">Steuer</label>
   <select class="ust" name="ust" id="ust">
     <option value="19">inkl. 19% Ust.</option>
     <option value="7">inkl. 7% Ust.</option>
     <option value="0">inkl. 0% Ust.</option>
   </select>
   <label for="src">Quelle</label>
   <select class="" name="src" id="src">
     <option value="vereinskonto">Vereinskonto</option>
     <option value="barkasse">Bar-Kasse</option>
   </select>
   <label for="beleg-name">Beleg</label>
   <div class="beleg-input">
     <input type="text" name="beleg-name" id="beleg-name" value="" placeholder="Beleg Name">
     <input type="file"  name="beleg" id="beleg" accept="application/pdf, image/jpeg, image/png">
   </div>

   <input type="submit" name="action" value="Speichern">
   <input type="hidden" name="type" value="<?php echo in_array($_GET["type"], array("einnahme", "ausgabe")) ? $_GET["type"] : "invalid";?>">
 </form>

 <style media="screen">
   .testNew{
     color: red;
   }
 </style>

<script>

  // Auto Fill Name Input
  document.getElementById("beleg").addEventListener("change", function(e){
    if (e.target.files[0]) {
      document.getElementById("beleg-name").value = e.target.files[0].name.split('.')[0];
    }
  });

</script>


<?php }else if(isset($_GET["type"]) && $_GET["type"] == "umbuchung"){
?>
<h2>Neue Umbuchung</h2>
<form class="" method="post">

  <div><label for="amount">Umbuchung</label></div>
  <div class="umbuchung-wrap">
    <select class="" name="quelle">
      <option value="vereinskonto">Vereinskonto</option>
      <option value="barkasse">Bar-Kasse</option>
    </select>


    <div class="arrow-amount">
      <input type="number" name="betrag" id="amount" value="" min="0" step="0.1">
      <div class="arrow">
        <div class="tip"></div>
        <div class="shaft"></div>
      </div>
    </div>


    <select class="" name="ziel">
      <option value="barkasse">Bar-Kasse</option>
      <option value="vereinskonto">Vereinskonto</option>
    </select>


  </div>
  <div class="partner-input ui-front">
    <label for="partner">Partner</label>
    <input type="text" name="partner" data-id="" id="partner" value="" required>
    <input type="hidden" id="partner_exist" name="partner_exist" value="">
  </div>
  <label for="zeitstempel">Zeitstempel</label>
  <input type="date" name="zeitstempel" id="zeitstempel" value="<?php echo(date("Y-m-d"))?>">

  <input type="hidden" name="type" value="umbuchung">
  <div>
    <input type="submit" name="action" value="Speichern">
  </div>



</form>

<?php
}else{ ?>
  <h2>Art der Buchung auswählen:</h2>

  <div class="trans-select">
    <a href="?type=einnahme">
      <div class="trans-type">
        <span class="icon">savings</span>
        <p class="trans-title">Einnahme</p>
      </div>
    </a>
    <a href="?type=ausgabe">
      <div class="trans-type">
        <span class="icon">payments</span>
        <p class="trans-title">Ausgabe</p>
      </div>
    </a>
    <a href="?type=umbuchung">
      <div class="trans-type">
        <span class="icon">currency_exchange</span>
        <p class="trans-title">Umbuchung</p>
      </div>
    </a>
  </div>




<?php }

?>
