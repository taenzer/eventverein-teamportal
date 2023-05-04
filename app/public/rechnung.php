<?php
require_once("../inc/bootstrap.php");
require_once("../inc/class-rechnung.php");
require_once("../inc/class-eventvereinCI.php");
$auth->check();

require_once '../vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;


// Check if Report should exist
if(isset($_GET["rid"])){
  $rechnung = new Rechnung($_GET["rid"]);
  ob_start();
  ?>
  <style>

  </style>

  <?php
  $before = ob_get_clean();
  $html = EventvereinCI::standardBrief($rechnung->getBody(), "Rechnung", $rechnung->get("kontaktAdr"), $before);
  $stream = !isset($_GET["dl"]);

  $options = new Options();
  $options->set('isRemoteEnabled', true);
  $options->set("isPhpEnabled", true);
  $dompdf = new Dompdf($options);

  $dompdf->loadHtml($html);
  $dompdf->setPaper('A4', 'portrait');

  // Render the HTML as PDF
  $dompdf->render();

  // Output the generated PDF to Browser
  if($stream){
    $dompdf->stream("dompdf_out.pdf", array("Attachment" => false));
  }else{
    header("Content-type: application/pdf");
    header("Content-Disposition: attachment; filename=".$rechnung->getReNr()."-Eventverein.pdf");
    header('Content-Transfer-Encoding: binary');
    echo $dompdf->output();
  }


}else{
  //header("Location: /");
}


 ?>
