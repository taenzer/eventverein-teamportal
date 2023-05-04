<?php
define("PAGE_TITLE", "Transaktion bearbeiten");

if(!isset($_GET["tid"])){
  header("Location: /transaktionen");
}
require_once("../inc/transaction-single.php");

$t = new Transaction($_GET["tid"]);
if(!$t->isLoaded()){
  header("Location: /transaktionen");
  exit();
}

$ust = $t->get("ust");
$src = $t->get("src");
$beleg = $t->getBeleg();

if($db->isDateLocked($t->get("zeitstempel"))){
  $lan->addError("Diese Transaktion kann nicht mehr geändert werden, da sie abgeschlossen ist.");
  header("Location: /transaktionen/view?tid=".$_GET["tid"]);
  exit();
}

if(isset($_POST["action"], $_POST["zeitstempel"], $_POST["partner"], $_POST["zweck"], $_POST["betrag"], $_POST["ust"], $_POST["src"])){
  require_once("../inc/transactions.php");

  // Neuen Kontakt erstellen
  if(!isset($_POST["partner_exist"]) || empty($_POST["partner_exist"])){
    $kid = $db->neuerKontakt($_POST["partner"]);
  }else{
    $kid = $_POST["partner_exist"];
  }

  // Beleg ablegen
  if($beleg == false){
    $bid = "";
    if(isset($_FILES["beleg"])){
      $beleg = new Beleg();
      $beleg->upload($_FILES["beleg"], $_POST["beleg-name"]);
      if(empty($beleg->error)){
        $bid = $beleg->getBid();
      }else{
        foreach ($beleg->error as $err) {
          $lan->addError($err);
        }
      }
    }
  }else{
    $bid = $beleg->getBid();
  }

  $bnetto = round($_POST["betrag"] / ( 1 + ($_POST["ust"]/100) ), 2);

  $newData = array(
    "zeitstempel" => $_POST["zeitstempel"],
    "partner" => $kid,
    "zweck" => $_POST["zweck"],
    "ust" => $_POST["ust"]."%",
    "src" => $_POST["src"],
    "betragBrutto" => $_POST["betrag"],
    "betragNetto" => $bnetto,
    "beleg" => $bid
  );

  $t->update($newData);
  header("Location: /transaktionen/view?tid=".$_GET["tid"]);
}

?>
<link rel="stylesheet" href="/assets/css/transaktionen.css">
<h1>Transaktion #<?php echo $_GET["tid"]; ?> bearbeiten</h1>


<link rel="stylesheet" href="/assets/css/transaktionen.css">

<!-- Jquery UI Autocomplete Plugin for Partner Selection -->
<script src="//code.jquery.com/jquery-1.12.4.js"></script>
<script src="//code.jquery.com/ui/1.12.1/jquery-ui.js"></script>



 <form class="transaction-form " enctype="multipart/form-data" action="" method="post">
   <label for="zeitstempel">Zeitstempel</label>
   <input type="date" name="zeitstempel" id="zeitstempel" value="<?php echo($t->get("zeitstempelRaw"))?>">
   <label for="partner">Partner</label>
   <div class="partner-input ui-front">
     <input type="text" name="partner" data-id="" id="partner" value="<?php echo($t->getTransPartner($t->get("partner"))["name"])?>" required>
     <input type="hidden" id="partner_exist" name="partner_exist" value="<?php echo($t->get("partner"))?>">
   </div>
   <label for="zweck">Zweck</label>
   <input type="text" name="zweck" id="zweck" value="<?php echo($t->get("zweck"))?>" required>
   <label for="betrag">Betrag</label>
   <input type="number" name="betrag" id="betrag" step="0.01" value="<?php echo($t->get("betragBrutto"))?>" required>
   <label for="ust">Steuer</label>
   <select class="ust" name="ust" id="ust">
     <option value="19" <?php echo $ust == "19%" ? "selected" : ""; ?>>inkl. 19% Ust.</option>
     <option value="7" <?php echo $ust == "7%" ? "selected" : ""; ?>>inkl. 7% Ust.</option>
     <option value="0" <?php echo $ust == "0%" ? "selected" : ""; ?>>inkl. 0% Ust.</option>
   </select>
   <label for="src">Quelle</label>
   <select class="" name="src" id="src">
     <option value="vereinskonto" <?php echo $src == "vereinskonto" ? "selected" : ""; ?>>Vereinskonto</option>
     <option value="barkasse" <?php echo $src == "barkasse" ? "selected" : ""; ?>>Bar-Kasse</option>
   </select>
   <label for="beleg-name">Beleg</label>
   <?php
   if($beleg === false){ ?>
     <div class="beleg-input">
       <input type="text" name="beleg-name" id="beleg-name" value="" placeholder="Beleg Name">
       <input type="file" name="beleg" id="beleg" accept="application/pdf, image/jpeg, image/png">

     </div>
   <?php }else{ ?>
     <p style="padding: 20px 0;"><span class="icon">info</span> Am <?php echo $beleg->getDateUploaded(true); ?> wurde Beleg "<?php echo $beleg->getName(); ?>" hochgeladen und mit dieser Transaktion verknüpft.
     Das Entfernen/Ändern eines Belegs ist nicht möglich.</p>
   <?php } ?>
   <input type="submit" name="action" value="Speichern">
 </form>


<script>

  $(function() {
      function log( message ) {
        $( "<div>" ).text( message ).prependTo( "#log" );
        $( "#log" ).scrollTop( 0 );
      }

      $.widget( "custom.catcomplete", $.ui.autocomplete, {
      _create: function() {
        this._super();
        this.widget().menu( "option", "items", "> :not(.ui-autocomplete-category)" );
      },
      _renderMenu: function( ul, items ) {
        var that = this,
          currentCategory = "";
        $.each( items, function( index, item ) {
          var li;
          if ( item.category && item.category != currentCategory ) {
            ul.append( "<li class='ui-autocomplete-category'>" + item.category + "</li>" );
            currentCategory = item.category;
          }
          li = that._renderItemData( ul, item );
          if ( item.category ) {
            li.attr( "aria-label", item.category + " : " + item.label );
          }

          if(item.class){
            li.addClass(item.class);
          }
        });
      },
      _renderItem: function(ul, item){
        var build = $( "<li>" )
          .attr( "data-value", item.id )
          .append( item.label );

        if(item.subt){
          build.append("<span class='subt'>" + item.subt + "</span>");
        }

        return build.appendTo( ul );
      }
    });

      $( "#partner" ).catcomplete({
        source: function( request, response ) {
          console.log("getting data...");
          $.ajax({
            url: "https://team.eventverein.de/ajax",
            dataType: "json",
            data: {
              action: "getPartners",
              q: request.term,
              nonce: "nonce"
            },
            success: function( data ) {
              console.log(data);
              response( data );
            },
          });
        },
        minLength: 1,
        select: function( event, ui ) {
          $("#partner_exist").attr("value", ui.item.id ? ui.item.id : "");
        },
        open: function() {
          $( this ).removeClass( "ui-corner-all" ).addClass( "ui-corner-top" );
        },
        close: function() {
          $( this ).removeClass( "ui-corner-top" ).addClass( "ui-corner-all" );
        }
      });
    });

</script>
