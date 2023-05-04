<?php

require_once("../inc/bootstrap.php");
$auth->check();

if(isset($_GET["doctype"], $_GET["docid"], $_GET["download"])){
  switch ($_GET["doctype"]) {
    case 'beleg':
      require_once("../inc/belege.php");
      $beleg = new Beleg();
      if($beleg->getFromDb($_GET["docid"])){
        $beleg->render($_GET["download"]);
      }else{
        echo("<p style='padding: 20px; font-family: monospace;'>Beleg nicht gefunden oder archiviert!</p>");
      }

      break;

    default:
      // code...
      break;
  }
}
 ?>
