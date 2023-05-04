<?php

function resp($data, $code = 200){
  echo json_encode($data);
  http_response_code($code);
  exit();
}
if(!isset($_GET["nonce"]) || $_GET["nonce"] != "nonce"){
  resp("Error: Invalid Request!", 400);
}
if(!isset($_GET["action"])){
  resp("Error: No action provided!", 400);
}

switch ($_GET["action"]) {
  case 'reNewPos':
    require_once("../inc/class-rechnung.php");
    $id = uniqid();
    $html = Rechnung::getPosHtml($id);
    resp($html);
    break;
  case 'createReport':
    if(!isset($_GET["month"], $_GET["year"])){
      resp("Error: Missing Report Params!", 400);
    }
    $monat = $_GET["month"];
    $jahr = $_GET["year"];
    require_once("../inc/ReportEngine.php");
    $re = new ReportEngine();
    try {
      if($re->generateReport($monat, $jahr, false) == true){
        resp("success");
      }else{
        resp("failed");
      }
    } catch (\Exception $e) {
      resp($e->getMessage(), 500);
    }
    break;
  case 'getPartners':
    if(!isset($_GET["q"])){
      resp("Error: Missing Params!", 400);
    }

    $mitglieder = $db->sucheMitglieder($_GET["q"]);
    $kontakte =  $db->sucheKontakte($_GET["q"]);

    $result = array();

    $result[] = array(
      "label" => $_GET["q"],
      "class" => "neuerKontakt",
      "subt" => "Neuen Kontakt erstellen");

    foreach ($kontakte as $kontakt) {
      $result[] = array(
        "label" => $kontakt["name"],
        "id" => $kontakt["kid"],
        "subt" => "Kontakt Id: ".$kontakt["kid"],
        "category" => "Kontakte");
    }

    foreach ($mitglieder as $mitglied) {
      $result[] = array(
        "label" => $mitglied["name"],
        "id" => $mitglied["mnr"],
        "subt" => "Mitglieds-Nr: ".$mitglied["mnr"],
        "category" => "Mitglieder");
    }

    resp($result);
    break;
  case 'getMembers':
    if(!isset($_GET["q"])){
      resp("Error: Missing Params!", 400);
    }

    $mitglieder = $db->sucheMitglieder($_GET["q"]);

    $result = array();

    foreach ($mitglieder as $mitglied) {
      $result[] = array(
        "label" => $mitglied["name"],
        "id" => $mitglied["mnr"],
        "subt" => "Mitglieds-Nr: ".$mitglied["mnr"]);
    }

    resp($result);
    break;
  case 'getTransactions':
    if(!isset($_GET["q"])){
      resp("Error: Missing Params!", 400);
    }
    require_once("../inc/transactions.php");
    $trans = new Transactions();
    $transaktionen = $trans->search($_GET["q"], "ausgaben");

    $result = array();


    foreach ($transaktionen as $t) {
      $dt = new DateTime($t["zeitstempel"]);
      $subt = $db->euro(abs($t["betragBrutto"]), false);
      $subt .= $t["betragBrutto"] < 0 ? " an " : " von ";
      $subt .= $trans->getTransPartner($t["partner"])["name"];
      $subt .= " (".$dt->format("d.m.Y").")";

      $result[] = array(
        "label" => $t["zweck"],
        "id" => $t["tid"],
        "subt" => $subt,
        "category" => $dt->format("m.Y"));
    }


    resp($result);
    break;

  default:
    resp("Error: Invalid action provided!", 400);
    break;
}


?>
