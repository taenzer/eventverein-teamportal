<?php
define("PAGE_TITLE", "Gegenstand verwalten");
if(!isset($_GET["inr"]) || empty($_GET["inr"]) ){
  header("Location: /inventar");
  die();
}else{
  $inr = $_GET["inr"];
}
require_once("../inc/inventar.php");
$inv = new InventarVerwaltung();

$itemData = $inv->getItemData($inr);

 ?>
 <!-- Style Sheet -->
<link rel="stylesheet" href="/assets/css/inventar.css">

 <h1>Gegenstand verwalten</h1>
 <h2><?php echo $itemData["name"]; ?></h2>

 <div class="inventar-view">
   <div class="inv-left">
     <div class="inv-item-data">
       <div class="inv-preview">
         <img src="/assets/img/preview.php?type=inventar&id=<?php echo $itemData["inr"]; ?>" alt="Vorschau">
       </div>
       <div class="inv-details">
         <p class="inr">#<?php echo $itemData["inr"]; ?></p>
         <?php if(!empty($itemData["info"])){ ?>
           <p class="info"><?php echo $itemData["info"]; ?></p>
         <?php  } ?>
         <p class="angeschafft"><?php
         $ange = new DateTime($itemData["angeschafft"]);
         if($itemData["consumable"] == true){
           echo "erstmalig ";
         }
         echo "am ".$ange->format("d.m.Y");
         echo " für ".$db->euro($itemData["ppe"]);
         if($itemData["consumable"] == true){
           echo " (je Einheit)";
         }
         ?></p>
         <?php
         if($itemData["consumable"] == true){?>
           <p class="stock"><?php echo $itemData["stock"]; ?></p>
         <?php }

         if($itemData["transaktion"] !== 0){
           require_once("../inc/transaction-single.php");
           $trans = new Transaction($itemData["transaktion"]);

           ?>
           <p class="transaction">
             <a class="twrp" target="_blank" href="/transaktionen/view/?tid=<?php echo $trans->get("tid"); ?>">
               <span class="tid">[#<?php echo $trans->get("tid"); ?>]</span>
               <?php echo $db->euro(abs($trans->get("betragBrutto"))); ?>  an <?php echo $trans->getTransPartner()["name"]; ?>
             </a>
           </p>
         <?php } ?>

       </div>
     </div>
     <div class="inv-actions">

     </div>

   </div>
   <div class="inv-right">
     <div class="inv-notes">

     </div>
   </div>


 </div>
