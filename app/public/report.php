<?php
require_once("../inc/bootstrap.php");
require_once("../inc/ReportEngine.php");
$auth->check();
$r = new ReportEngine();

// Check if Report should exist
if(isset($_GET["bid"])){
  $bid = $_GET["bid"];
  // Check if ZIP should be downloaded
  $path = $r->getZipDownloadPath($bid);
  if(isset($_GET["zip"])){
    header("Content-type: application/zip");
    header("Content-Disposition: attachment; filename=".$path["name"]);
    header("Content-length: " . filesize($path["path"].$path["name"]));
    header("Pragma: no-cache");
    header("Expires: 0");
    readfile($path["path"].$path["name"]);
    exit();
  }else{ // Just display PDF Report
    $filename = "transaktionen".explode(".", $path["name"])[0].".pdf";
    header("Content-type: application/pdf");
    header("Content-Disposition: inline; filename=".$filename);
    header('Content-Transfer-Encoding: binary');
    //header('Content-Length: ' . filesize($file));
    header('Accept-Ranges: bytes');
    echo file_get_contents("zip://".$path["path"].$path["name"]."#transaktionen.pdf");
    exit();
  }

}else if(isset($_GET["year"], $_GET["month"])){ // Just a Preview
  $jahr = $_GET["year"]; $monat = $_GET["month"];
  $r->generateReport($_GET["month"],$_GET["year"], true, true);
}else{
  header("Location: /");
}


 ?>
