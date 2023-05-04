<?php
use setasign\Fpdi\Fpdi;

require_once('../inc/bootstrap.php');
require_once('../inc/mitglieder.php');
require_once('../vendor/autoload.php');


$secret = isset($_GET["secret"]) ? $_GET["secret"] : false;
$fid = isset($_GET["fid"]) ? $_GET["fid"] : false;

// initiate FPDI
$pdf = new Fpdi();
$pdf->SetAuthor("Eventverein Tambach-Dietharz e.V.");
$pdf->SetTitle("Mitgliedsantrag Eventverein");
// add a page
$pdf->AddPage();
// set the source file
$pdf->setSourceFile('../storage/dokumente/2023-01-Mitgliedsantrag-Uncompressed.pdf');

// import page 1
$tplIdx = $pdf->importPage(1);
// use the imported page and place it at position 10,10 with a width of 100 mm
$pdf->useTemplate($tplIdx, 0, 0);

$vsecret = Mitglied::generateMemberSecret($fid);

if($secret && $fid && $vsecret == $secret){
  $m = new Mitglied(null, $fid);
  // now write some text above the imported page
  $pdf->SetFont('Helvetica');

  $pdf->SetTextColor(150, 150, 150);
  $pdf->SetXY(10, 10);
  $pdf->Cell(0, 10, 'Online Antrags-Id: #'.strtoupper($secret)."/".$fid, 0, 0, 'C');

  $pdf->SetTextColor(0, 0, 0);
  $pdf->SetFont('Helvetica', '', 15);

  if($m->getRawData("mnr") !== false){
    $pdf->SetXY(144, 83.5);
    $mnr = str_split($m->getRawData("mnr"));
    $pdf->Write(0, $mnr[0]."  ".$mnr[1]."    ".$mnr[3]."  ".$mnr[4]."  ".$mnr[5]."  ".$mnr[6]);
  }


  $pdf->SetFont('Helvetica', '', 12);
  $pdf->SetXY(27, 121);
  $pdf->Write(0, $m->getRawData("vorname"));
  $pdf->SetXY(80, 121);
  $pdf->Write(0, $m->getRawData("nachname"));


  $dt = $m->getRawData("geboren") ? new DateTime($m->getRawData("geboren")) : false;
  $dt = $dt !== false ? $dt->format("d.m.Y") : "";

  $pdf->SetXY(27, 136);
  $pdf->Write(0, $dt);



  $adr = $db->split_street($m->getRawData("strasse"));

  $pdf->SetXY(27, 159);
  $pdf->Write(0, $adr["street"]);
  $pdf->SetXY(104, 159);
  $pdf->Write(0,  $adr["number"]. $adr["numberAddition"]);

  $pdf->SetXY(27, 174);
  $pdf->Write(0, $m->getRawData("plz"));
  $pdf->SetXY(104, 174);
  $pdf->Write(0, $m->getRawData("ort"));

  $pdf->SetXY(27, 189);
  $pdf->Write(0, $m->getRawData("telefon"));
  $pdf->SetXY(104, 189);
  $pdf->Write(0, $m->getRawData("email"));
}

if($secret && $fid && $vsecret == $secret){

}
// Page 2

$pdf->AddPage();
$tplIdx = $pdf->importPage(2);
$pdf->useTemplate($tplIdx, 0, 0);

if($secret && $fid && $vsecret == $secret){
  $pdf->SetTextColor(150, 150, 150);
  $pdf->SetXY(10, 10);
  $pdf->Cell(0, 10, 'Online Antrags-Id: #'.strtoupper($secret)."/".$fid, 0, 0, 'C');
  $pdf->SetTextColor(0, 0, 0);

  if($m->getRawData("mnr") !== false){
    $pdf->SetXY(27, 116);
    $pdf->Write(0, $m->getRawData("mnr"));
  }
  $pdf->SetXY(72, 116);
  $pdf->Write(0, $m->getRawData("vorname"));
  $pdf->SetXY(127, 116);
  $pdf->Write(0, $m->getRawData("nachname"));
}

// Page 3-5

for ($i=3; $i <= 5; $i++) {
  $pdf->AddPage();
  $tplIdx = $pdf->importPage($i);
  $pdf->useTemplate($tplIdx, 0, 0);

  if($secret && $fid && $vsecret == $secret){
    $pdf->SetTextColor(150, 150, 150);
    $pdf->SetXY(10, 10);
    $pdf->Cell(0, 10, 'Online Antrags-Id: #'.strtoupper($secret)."/".$fid, 0, 0, 'C');
    $pdf->SetTextColor(0, 0, 0);

    if($i == 3){
      if($m->getRawData("mnr") !== false){
        $pdf->SetXY(27, 93);
        $pdf->Write(0, $m->getRawData("mnr"));
      }
      $pdf->SetXY(72, 93);
      $pdf->Write(0, $m->getRawData("vorname"));
      $pdf->SetXY(127, 93);
      $pdf->Write(0, $m->getRawData("nachname"));
    }
  }
}
$filename = 'Mitgliedsantrag Eventverein';
$filename = ($secret && $fid && $vsecret == $secret) ? $filename.' #'.$fid : $filename;

// Output PDF
$pdf->Output(isset($_GET["forceDl"]) ? 'D' : 'I', $filename.'.pdf');
