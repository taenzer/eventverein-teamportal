<?php
define("PAGE_TITLE", "Gegenstand hinzufügen");
require_once("../inc/inventar.php");

if(isset($_POST["sendForm"], $_POST["bezeichnung"], $_POST["ppe"])){
  $i = new InventarVerwaltung();

  if(isset($_POST["consumable"]) && $_POST["consumable"] == "yes"){
    $consumable = true;
    $stock = isset($_POST["stock"]) ? $_POST["stock"] : 1;
  }else{
    $consumable = false;
    $stock = 1;
  }

  $tid = isset($_POST["transaction_link"]) ? $_POST["transaction_link"] : 0;
  $inr = $i->createItem($_POST["bezeichnung"], $consumable, $_POST["angeschafft"], $_POST["info"], $stock, $_POST["ppe"], $tid);
  //$inr = 101;

  if($inr !== false){
    $inr = str_pad($inr, 4, "0", STR_PAD_LEFT);
    if(isset($_FILES['bild']) && is_uploaded_file($_FILES['bild']['tmp_name'])){
      $filename = "item-".$inr.".jpg";
      $uploadfile = $_SERVER["DOCUMENT_ROOT"]."/storage/bilder/inventar/".$filename;
      if(move_uploaded_file($_FILES['bild']['tmp_name'], $uploadfile)){
        echo("Upload done");
        header("Location: /inventar/view?inr=".$inr);
      }
    }
    header("Location: /inventar/view?inr=".$inr);
  }
}


 ?>
 <!-- Style Sheet -->
<link rel="stylesheet" href="/assets/css/inventar.css">

<!-- Custom Script: Transaction Link -->
<script src="https://code.jquery.com/jquery-1.12.4.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
<script src="/assets/js/transactionselect.js"></script>
<h1>Neuer Inventar Gegenstand</h1>

<form class="inventar-edit" method="post" enctype="multipart/form-data">

  <label for="bezeichnung">Bezeichnung*</label>
  <input type="text" name="bezeichnung" id="bezeichnung" value="" required>
  <label for="info">Info</label>
  <input type="text" name="info" id="info" value="">

  <p class="label">Art des Gegenstands*</p>
  <p><input type="radio" class="radioButtons" onChange="showHideStock()" name="consumable" id="consum-yes" value="yes" required><label for="consum-yes"><span class="icon">storefront</span> <i>Verbrauch</i>sgut</label>
    <input type="radio" class="radioButtons" onChange="showHideStock()" name="consumable" id="consum-no" value="no" required><label for="consum-no"><span class="icon">construction</span><i>Gebrauch</i>sgut</label></p>

  <p id="stockWrap"><label for="stock">Menge*</label>
  <input type="number" name="stock" id="stock" min="1" value="1">
  </p>

  <label for="angeschafft">Angeschafft am*</label>
  <input type="date" name="angeschafft" id="angeschafft" value="<?php echo date("Y-m-d"); ?>" required>

  <label for="ppe">Anschaffungskosten (ggf je Einheit)*</label>
  <input type="number" name="ppe" id="ppe" step="0.01" required>
  <div class="partner-input ui-front">
    <input style="width: 100%;" type="text" name="transaction" data-id="" id="transaction" value="" placeholder="Transaktion verknüpfen">
    <input type="hidden" id="transaction_link" name="transaction_link" value="">
  </div>

  <label for="bild">Bild (.jpg)</label>
  <input type="file" name="bild" id="bild" value="" accept="image/jpeg, image/jpg">

  <input type="submit" name="sendForm" value="Hinzufügen">
</form>

<script type="text/javascript">
  function showHideStock(){
    let val = document.querySelector("input[name='consumable']:checked").value;
    document.getElementById("stockWrap").style.display = val == "yes" ? "flex" : "none";
  }
</script>
