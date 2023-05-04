<?php

require_once("../inc/transactions.php");
require_once '../vendor/autoload.php';
use Dompdf\Dompdf;
use Dompdf\Options;
/**
 *
 */
class ReportEngine extends DB
{

  function __construct(){
    parent::__construct();
  }


  public function printOverview($year){

    $sql = $this->mysqli->prepare("SELECT * FROM berichte WHERE jahr = ? ORDER BY monat ASC");
    $sql->bind_param("i", $year);
    $sql->execute();

    $result = $sql->get_result();
    $result = $result->fetch_all(MYSQLI_ASSOC);

    $ordered = array();
    foreach ($result as $res) {
      $ordered[$res["monat"]] = $res;
    }

    $monate = array("Januar", "Februar", "März", "April", "Mai", "Juni", "Juli", "August", "September", "Oktober", "November", "Dezember");
    $now = new DateTime();
    $mcnt = $year == $now->format("Y") ? $now->format("m") : 12;
    $mcnt = $year > $now->format("Y") ? 0 : $mcnt;

    for($m=1; $m <= $mcnt; ++$m){
      $generated = isset($ordered[$m]);
      if($generated){
        $einnahmen = isset($ordered[$m]["einnahmen"]) ? $ordered[$m]["einnahmen"] : "-";
        $ausgaben = isset($ordered[$m]["ausgaben"]) ? $ordered[$m]["ausgaben"] : "-";
      }else{
        $stats = $this->calcPreviewStats($m, $year);
        $einnahmen = $stats["einnahmen"];
        $ausgaben = $stats["ausgaben"];
      }
      ?>
      <div class="report">
        <div class="main">
          <p class="titel"><?php echo $monate[$m-1]." ".$year; ?></p>
          <p class="stats">
            <span class="einnahmen"><span class="icon">savings</span> <?php echo $this->euro($einnahmen); ?></span>
            <span class="ausgaben"><span class="icon">receipt_long</span> <?php echo$this->euro($ausgaben); ?></span>
          </p>
        </div>
        <p class="aktionen">
          <?php
          if($generated){
              ?><a target="_blank"href="/report.php?bid=<?php echo $ordered[$m]["bid"]; ?>&zip"><span class="icon">archive</span> Herunterladen</a>
              <a target="_blank" href="/report.php?bid=<?php echo $ordered[$m]["bid"]; ?>"><span class="icon">preview</span> Vorschau</a><?php
          }else{
            if($this->canReportBeCreated($m,$year)){
              ?><a href="#" class="generate" data-month="<?php echo $m; ?>" data-year="<?php echo $year; ?>"><span class="icon">settings_suggest</span> Erstellen</a><?php
            }else{
              ?><a href="#" class="report-forbidden"><span class="icon">settings_suggest</span> Erstellen</a><?php
            }
            ?> <a target="_blank" href="/report.php?year=<?php echo $year; ?>&month=<?php echo $m; ?>"><span class="icon">preview</span> Vorschau</a><?php
          }
          ?>
        </p>
      </div>
    <?php }
  }

  private function calcPreviewStats($month, $year){
    $trans = $this->sammelTransaktionen($month, $year);
    $stats = array("einnahmen" => 0, "ausgaben" => 0);
    foreach ($trans["stats"] as $t) {
      $stats["einnahmen"] += isset($t["einnahmen"]) ? $t["einnahmen"] : 0;
      $stats["ausgaben"] += isset($t["ausgaben"]) ? $t["ausgaben"] : 0;
    }
    $stats["gewinn"] = $stats["einnahmen"] - $stats["ausgaben"];
    return $stats;
  }

  public function existiertBericht($jahr, $monat){
    return $this->isDateLocked($jahr."-".$monat);
  }
  public function generateReport($month, $year, $preview = true, $pdf = false){
    /*
    * 1. Saldo MA ermitteln
    * 2. Alle Transaktionen durchgehen
    *   2a) Ausgaben & Einnahmen addieren (Konten getrennt)
    *   2b) TransaktionsIds sammeln
    * 3. Umbuchungen durchgehen und zusammenfassen
    * 4. Saldo ME berechnen
    * 4.1 FailCheck über 1 ME
    * 5. Ausgaben & Einnahmen zusammenfassen
    * 6. Daten in Datenbank schreiben [*]
    * 7. PDF generieren
    * 8. Belege und PDF in ZIP Archiv packen [*]
    * 9. Datenbank Einträge in Archiv Tabelle verschieben [*]
    */
    $this->mysqli->begin_transaction();

    if($this->isDateLocked($year."-".$month) && !$preview){
      throw new \Exception("Für diesen Zeitraum existiert schon ein Bericht. Das Erstellen eines neuen ist nicht zulässig.", 1);
      return false;
    }

    if(!$this->canReportBeCreated($month, $year) && !$preview){
      throw new \Exception("Bericht kann nicht erstellt werden, da es noch keinen Bericht für den Vormonat gibt.", 1);
      return false;
    }

    $saldoMA = $this->berechneSaldoMA($month, $year);
    $trans = $this->sammelTransaktionen($month, $year);
    $umb = $this->sammelUmbuchungen($month, $year);
    $saldoME = $this->calcSaldoME($saldoMA, $trans, $umb);

    $nextM = new DateTime($year."-".$month);
    $nextM->modify("next month");
    if($saldoME !== $this->berechneSaldoMA($nextM->format("m"), $nextM->format("Y"))){
      throw new \Exception("Fehler beim berechnen der Kontostände (Check failed)", 1);
    }


    $saldos = array("MA" => $saldoMA, "ME" => $saldoME);
    $stats = array("einnahmen" => 0, "ausgaben" => 0);
    foreach ($trans["stats"] as $t) {
      $stats["einnahmen"] += isset($t["einnahmen"]) ? $t["einnahmen"] : 0;
      $stats["ausgaben"] += isset($t["ausgaben"]) ? $t["ausgaben"] : 0;
    }
    $stats["gewinn"] = $stats["einnahmen"] - $stats["ausgaben"];

    $html = $this->printMonthlyTransactionOverview($month, $year, $saldos, $stats, $preview);
    if($pdf){
      $this->generatePDF($html);
    }else if($preview){
      return $html;
    }

    if(!$preview){
      $rid = $this->writeToDB($month, $year, $saldos, $stats);
      $this->createZip($rid, $month, $year, $html, $trans["belege"]);
      $archivStatus = $this->lockAndArchiveData($month, $year);
    }
    $this->mysqli->commit();
    return true;
  }

  public function canReportBeCreated($month, $year){
    $dtNow = new DateTime();
    $dtReport = new DateTime($year."-".$month);
    $dtReport->modify("last day of");

    // Check if given month is over
    if($dtNow < $dtReport){
      return false;
    }

    $vormonat = new DateTime($year."-".$month);
    $vormonat->modify("last month");
    $m = $vormonat->format("m");
    $y = $vormonat->format("Y");

    $sql = $this->mysqli->prepare("SELECT bid FROM berichte WHERE monat = ? AND jahr = ?");
    $sql->bind_param("ii", $m, $y);
    $sql->execute();

    // Check if Report of last Month is already created
    if($sql->get_result()->num_rows == 0){
      return false;
    }

    return true;
  }

  private function lockAndArchiveData($month, $year){

    $status = array();
    $sql = $this->mysqli->prepare("INSERT INTO transaktionen_archiv SELECT * FROM transaktionen WHERE MONTH(zeitstempel) = ? AND YEAR(zeitstempel) = ? AND locked = 0");
    $sql->bind_param("ii", $month, $year);
    $status["CP"]["T"] = $sql->execute();

    $sql = $this->mysqli->prepare("UPDATE transaktionen_archiv SET locked = 1 WHERE locked = 0;");
    $status["LCK"]["T"] = $sql->execute();

    if($status["CP"]["T"] && $status["LCK"]["T"]){
      $sql->prepare("DELETE FROM transaktionen WHERE MONTH(zeitstempel) = ? AND YEAR(zeitstempel) = ? AND locked = 0");
      $sql->bind_param("ii", $month, $year);
      $status["DEL"]["T"] = $sql->execute();
    }

    $sql = $this->mysqli->prepare("INSERT INTO umbuchungen_archiv SELECT * FROM umbuchungen WHERE MONTH(zeitstempel) = ? AND YEAR(zeitstempel) = ? AND locked = 0");
    $sql->bind_param("ii", $month, $year);
    $status["CP"]["UMB"] = $sql->execute();

    $sql = $this->mysqli->prepare("UPDATE umbuchungen_archiv SET locked = 1 WHERE locked = 0;");
    $status["LCK"]["UMB"] = $sql->execute();

    if($status["CP"]["UMB"] && $status["LCK"]["UMB"]){
      $sql->prepare("DELETE FROM umbuchungen WHERE MONTH(zeitstempel) = ? AND YEAR(zeitstempel) = ? AND locked = 0");
      $sql->bind_param("ii", $month, $year);
      $status["DEL"]["UMB"] = $sql->execute();
    }

    foreach ($status as $op => $erg) {
      foreach ($erg as $key => $value) {
        if(!$value){
          return $status;
        }
      }
    }

    return true;

  }


  private function createZip($rid, $month, $year, $html, $belege){
    $zip = new ZipArchive;
    $storagePath = $_SERVER["DOCUMENT_ROOT"]."/storage";
    $zipPath = $storagePath."/reports/".$year;

    if(!is_dir($zipPath)){
      mkdir($zipPath, 0777, true);
    }
    $zipFileName = "/".$year."-".$month."-eventverein-report-".$rid.".zip";

    if ($zip->open($zipPath.$zipFileName, (ZipArchive::CREATE | ZipArchive::OVERWRITE)) !== TRUE) {
        exit("Unable to create ZIP Archive.");
    }

    $zip->addEmptyDir("belege");
    foreach ($belege as $bid) {
      $data = $this->getBelegData($bid);
      $zip->addFile($storagePath.$data["pfad"], "belege/".$bid."#".$data["filecode"]);
    }
    $zip->addFromString("transaktionen.pdf", $this->generatePDF($html, false));
    $zip->close();
  }

  public function getZipDownloadPath($rid){

    $sql = $this->mysqli->prepare("SELECT jahr, monat FROM berichte WHERE bid = ? LIMIT 1");
    $sql->bind_param("i", $rid);
    $sql->execute();
    $result = $sql->get_result();
    $result = $result->fetch_all(MYSQLI_ASSOC);

    if(count($result) !== 1){
      return false;
    }
    $result = $result[0];
    $storagePath = $_SERVER["DOCUMENT_ROOT"]."/storage";
    $zipPath = $storagePath."/reports/".$result["jahr"]."/";
    $zipFileName = $result["jahr"]."-".$result["monat"]."-eventverein-report-".$rid.".zip";
    return array("path" => $zipPath, "name" => $zipFileName);
  }

  private function generatePDF($html, $stream = true){
    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $dompdf = new Dompdf($options);

    $dompdf->loadHtml($html);
    // (Optional) Setup the paper size and orientation
    $dompdf->setPaper('A4', 'landscape');

    // Render the HTML as PDF
    $dompdf->render();

    // Output the generated PDF to Browser
    if($stream){
      $dompdf->stream("dompdf_out.pdf", array("Attachment" => false));
    }else{
      return $dompdf->output();
    }

  }

  private function writeToDB($month, $year, $saldos, $stats){
    $sql = $this->mysqli->prepare("
      INSERT INTO berichte
      (monat, jahr, einnahmen, ausgaben)
      VALUES (?,?,?,?);");
    $einnahmen = round($stats["einnahmen"],2 );
    $ausgaben = round($stats["ausgaben"], 2);
    $sql->bind_param("iidd", $month, $year, $einnahmen, $ausgaben);
    $sql->execute();

    $bid = $this->mysqli->insert_id;

    $bsql = $this->mysqli->prepare("INSERT INTO berichte_saldos (bid, zeitpunkt, konto, saldo) VALUES (?,?,?,?)");
    foreach ($saldos as $zeitpunkt => $values) {
      foreach ($values as $src => $saldo) {
        $bsql->bind_param("issd", $bid, $zeitpunkt, $src, $saldo);
        $bsql->execute();
      }
    }
    return $bid;
  }
  private function calcSaldoME($saldoMA, $trans, $umb){

    // Ändere alle Eingangs Kontostände je nach Bewegung
    foreach ($saldoMA as $konto => $saldo) {
      $saldoME[$konto] = $saldo;
      // Prüfe ob es Kontobewegungen gibt
      if(isset($trans["stats"][$konto])){
        // Addiere / Subtrahiere Einnahmen / Ausgaben auf diesem Konto
        foreach ($trans["stats"][$konto] as $type => $value) {
          $saldoME[$konto] += $type == "einnahmen" ? $value : $value * -1;
        }
      }
    }

    // Wende alle Umbuchungen an
    foreach ($umb as $umbuchung) {
      if(!isset($saldoME[$umbuchung["quelle"]])){
        $saldoME[$umbuchung["quelle"]] = 0;
      }else if(!isset($saldoME[$umbuchung["ziel"]])){
        $saldoME[$umbuchung["ziel"]] = 0;
      }
      $saldoME[$umbuchung["quelle"]] = round($saldoME[$umbuchung["quelle"]] - $umbuchung["umbuchung"], 2);
      $saldoME[$umbuchung["ziel"]] = round($saldoME[$umbuchung["ziel"]] + $umbuchung["umbuchung"], 2);
    }
    return $saldoME;
  }

  private function sammelUmbuchungen($month, $year){
    $sql = $this->mysqli->prepare("
      SELECT t.quelle, t.ziel, round(sum(t.betrag), 2) 'umbuchung' FROM
        (SELECT betrag, quelle, ziel FROM umbuchungen WHERE MONTH(zeitstempel) = ? AND YEAR(zeitstempel) = ?) t
      GROUP BY t.quelle, t.ziel;
    ");
    $sql->bind_param("ii", $month, $year);
    $sql->execute();

    $umbuchungen = $sql->get_result();
    $umbuchungen = $umbuchungen->fetch_all(MYSQLI_ASSOC);
    return $umbuchungen;
  }

  private function sammelTransaktionen($month, $year){
    $sql = $this->mysqli->prepare("SELECT tid, src, betragBrutto, beleg FROM transaktionen WHERE MONTH(zeitstempel) = ? AND YEAR(zeitstempel) = ?;");
    $sql->bind_param("ii", $month, $year);
    $sql->execute();
    $result = $sql->get_result();
    $result = $result->fetch_all(MYSQLI_ASSOC);

    $trans = array();
    $belege = array();
    foreach ($result as $t) {
      $belege[] = $t["beleg"];
      $type = $t["betragBrutto"] > 0 ? "einnahmen" : "ausgaben";
      if(!isset($trans[$t["src"]][$type])){
        $trans[$t["src"]][$type] = 0;
      }
      $trans[$t["src"]][$type] += abs($t["betragBrutto"]);
    }

    return array("belege" => $belege, "stats" => $trans);
  }
  private function berechneSaldoMA($month, $year){
    $dt = new DateTime($year."-".$month);
    $firstday = $dt->format("Y-m-01");
    $sql = $this->mysqli->prepare("
      SELECT t.src, round(sum(t.betragBrutto), 2) 'saldoVorUmbuchungen' FROM
        (SELECT betragBrutto, src FROM v_kontouebersicht WHERE typ = 'transaktion' AND zeitstempel < ?) t
      GROUP BY t.src;
    ");
    $sql->bind_param("s", $firstday);
    $sql->execute();
    $results = $sql->get_result();
    $results = $results->fetch_all(MYSQLI_ASSOC);

    $saldos = array();

    foreach ($results as $result) {
      $saldos[$result["src"]] = $result["saldoVorUmbuchungen"];
    }

    $sql = $this->mysqli->prepare("
      SELECT t.quelle, t.ziel, round(sum(t.betragBrutto), 2) 'umbuchung' FROM
        (SELECT betragBrutto, quelle, ziel FROM v_kontouebersicht WHERE typ = 'umbuchung' AND zeitstempel < ?) t
      GROUP BY t.quelle, t.ziel;
    ");
    $sql->bind_param("s", $firstday);
    $sql->execute();

    $umbuchungen = $sql->get_result();
    $umbuchungen = $umbuchungen->fetch_all(MYSQLI_ASSOC);


    foreach ($umbuchungen as $umbuchung) {
      $saldos[$umbuchung["quelle"]] = round($saldos[$umbuchung["quelle"]] - $umbuchung["umbuchung"], 2);
      $saldos[$umbuchung["ziel"]] = round($saldos[$umbuchung["ziel"]] + $umbuchung["umbuchung"], 2);
    }

    return $saldos;
  }

  public function generateTransOverview($month, $year){


    exit(0);
  }

  public function printMonthlyTransactionOverview($month, $year, $saldos, $stats, $preview = true){
    $dt = new DateTime($year."-".$month);
    $dtfrom = $dt->format("d.m.Y");
    $dtto = $dt->format("t.m.Y");
    $trans = new Transactions();

    $dates = $trans->get($dt->format("Y-m-d"), $dt->format("Y-m-t"), "ASC");
    ob_start();
    if($preview){
      echo("<p class='preview'>VORSCHAU</p>");
    }
    ?>
    <h1 style="margin: 0 0 10px 0; font-size: 1.2em;">TRANSAKTIONSÜBERSICHT</h1>
    <h2 style="margin: 0 0 10px 0;  font-size: 1em;"><?php echo($this->dtToMonthYear($dt)); ?> (<?php echo("$dtfrom - $dtto"); ?>)</h2>
    <img style="position: absolute; top: 0px; right: 0px; width: 100px; height: auto;"src="https://team.eventverein.de/assets/img/logo-dark.png" alt="">
    <table style="page-break-after:auto;">
      <thead>
        <tr>
          <td colspan="6">Transaktionsinfo</td>
          <td colspan="2">Beleg</td>
        </tr>
        <tr>
          <td>Datum</td>
          <td>ID</td>
          <td>Zweck</td>
          <td>SRC</td>
          <td>Betrag Brutto</td>
          <td>Partner</td>
          <td>ID</td>
          <td>Datei</td>
        </tr>
      </thead>
      <tbody>
        <?php
        foreach ($dates as $date => $ts) {
          $first = true;
          foreach ($ts as $t) {
            $d = new DateTime($t["zeitstempel"]);            ?>
            <tr>
              <td class="date" rowspan="1"><?php echo $d->format("d.m.Y"); ?></td>
              <?php
              if($t["typ"] == "transaktion"){ ?>
                <td class="tid"><?php echo($t["tid"]); ?></td>
                <td class="zweck"><?php echo($t["zweck"]); ?></td>
                <td class="src"><?php echo($t["src"]); ?></td>
              <?php }else{ ?>
                <td colspan="3">UMBUCHUNG VON <i class="src"><?php echo$t["quelle"]; ?></i> NACH <i class="src"><?php echo$t["ziel"]; ?></i></td>
              <?php } ?>
              <td class="betragBrutto <?php echo $t["betragBrutto"] < 0 ? "minus" : "plus"; ?>"><?php echo($trans->euro($t["betragBrutto"])); ?></td>
              <?php
              $partner = $trans->getTransPartner($t["partner"])["name"];



              ?>
              <td class="partner"><?php echo( $partner); ?></td>

              <?php if(isset($t["beleg"])){
                $beleg = $this->getBelegData($t["beleg"]);
                ?>
                <td class="belegId"><?php echo($beleg !== false ? $t["beleg"] : ""); ?></td>
                <td class="belegfile"><?php echo($beleg !== false ? $beleg["filecode"] : $t["beleg"]); ?></td>
              <?php }else{
                echo('<td class="nobeleg" colspan="2">--- Kein Beleg ---</td>');
              }?>

            </tr>
          <?php } ?>

        <?php }
        if(count($dates) == 0){
          echo('<tr><td colspan="8" style="padding: 20px; text-align: center;">Keine Transaktionen!</td></tr>');

        }


        ?>
      </tbody>
    </table>
    <?php
    $sortedSaldos = array();
    foreach ($saldos as $ts => $values) {
      foreach ($values as $src => $value) {
        $sortedSaldos[$src][$ts] = $value;
      }
    }
     ?>
    <table style="page-break-inside: avoid;">
      <thead>
        <tr>
          <td>Konto</td>
          <td>Saldo Monatsanfang</td>
          <td>Saldo Monatsende</td>
        </tr>
      </thead>
      <tbody >
        <?php
        foreach ($sortedSaldos as $src => $values) { ?>
          <tr>
            <td class="src"><?php echo $src; ?></td>
            <td><?php echo isset($values["MA"]) ? $this->euro($values["MA"]) : "-"; ?></td>
            <td><?php echo isset($values["ME"]) ? $this->euro($values["ME"]) : "-"; ?></td>
          </tr>
        <?php }
        ?>
      </tbody>
    </table>
    <table style="page-break-inside: avoid;">
      <thead>
        <tr>
          <td>Einnahmen</td>
          <td>Ausgaben</td>
          <td>Gewinn/Verlust</td>
        </tr>
      </thead>
      <tbody >
        <tr>
          <td><?php echo $this->euro($stats["einnahmen"]); ?></td>
          <td><?php echo $this->euro($stats["ausgaben"]); ?></td>
          <td><?php echo $this->euro($stats["gewinn"]); ?></td>
        </tr>
      </tbody>
    </table>
    <p style="page-break-before: avoid; font-size:0.7em; opacity: 0.5;">Alle Beträge inkl. Mehrwertsteuer. Bericht generiert am <?php $d = new DateTime(); echo $d->format("d.m.Y H:i:s"); ?> Uhr</p>
    <style>
      *{
        font-family: 'Helvetica';

      }
      table{
        border-collapse: collapse;
        width: 100%;
        margin: 20px 0;
        font-size: 0.8em;
      }
      thead td{
        font-weight: bold;
        background: #c7c5c5;
      }
      table tr td {
        border: 1px solid black;
        padding: 5px 10px;
      }

      tbody tr:nth-child(2n) td{
        background: #ededed;
      }
      .nobeleg{
        text-align: center;
      }
      .minus{
        color: red;
      }

      .preview{
        position: fixed;
        top: 50%;
        margin: 0;
        padding: 0;
        left: 50%;
        transform: translate(-50%, -50%) rotate(-20deg);
        font-size: 10em;
        font-weight: bold;
        text-transform: uppercase;
        opacity: 0.2;
        line-height: 1;
      }

      .src{
        text-transform: capitalize;
        }
      </style>
    <?php
    return ob_get_clean();
  }

  public function getBelegData($bid){
    $sql = $this->mysqli->prepare("SELECT dateityp, pfad FROM belege WHERE bid = ?");
    $sql->bind_param("i", $bid);
    $sql->execute();
    $result = $sql->get_result();
    $result = $result->fetch_all(MYSQLI_ASSOC);

    if(count($result) != 1){
      return false;
    }

    $result[0]["filecode"] = $this->extractBelegFileName($result[0]["pfad"]);
    return $result[0];
  }

  private function extractBelegFileName($path){
    $parts = explode("/", $path);
    $filename = $parts[4];
    return $filename;
  }


  function dtToMonthYear($dt){
    switch ($dt->format("m")) {
      case '01': $o = 'Januar'; break;
      case '02': $o = 'Februar'; break;
      case '03': $o = 'März'; break;
      case '04': $o = 'April'; break;
      case '05': $o = 'Mai'; break;
      case '06': $o = 'Juni'; break;
      case '07': $o = 'Juli'; break;
      case '08': $o = 'August'; break;
      case '09': $o = 'September'; break;
      case '10': $o = 'November'; break;
      case '11': $o = 'Dezember'; break;
      case '12': $o = 'Februar'; break;
    }
    $o .= " ".$dt->format("Y");
    return $o;
  }
}
 ?>
