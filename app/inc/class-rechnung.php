<?php

class Rechnungen extends DB{
  function __construct(){
      parent::__construct();
  }

  public function display(){
    $rechnungen = $this->getFromDb();
    //var_dump($rechnungen);
    ?>
    <link rel="stylesheet" href="/assets/css/rechnungen.css">
    <div class="re-list">
      <?php
      foreach ($rechnungen as $rechnung) { ?>
        <a href="/rechnungen/edit?rid=<?php echo $rechnung->get("rid"); ?>">
          <div class="<?php echo $rechnung->getFormClasses(array("re-list-item")); ?>">
            <?php echo $rechnung->getStatusHtml(); ?>

            <div class="reli-zahlungsziel">
              <p>
                <?php
                $dtZz = new DateTime($rechnung->get("dtZahlungsziel"));
                if($rechnung->get("status") == "verschickt"){
                  $days = $rechnung->getZahlungszielTageVonJetzt();
                  if($days > 0){
                    echo("in ".$days." Tagen");
                  }else if($days < 0){
                    echo("vor ".abs($days)." Tagen");
                  }else if($days == 0){
                    echo("heute");
                  }
                }else{
                  echo $dtZz->format("d.m.Y");
                }
                ?>
              </p>
            </div>
            <div class="reli-nr">
              <?php echo $rechnung->getReNr(); ?>
            </div>
            <div class="reli-datum">
              <?php
              $dtRe = new DateTime($rechnung->get("dtRechnung"));
              echo $dtRe->format("d.m.Y");
              ?>
            </div>
            <div class="reli-kunde-betreff">
              <p><?php echo $this->getTransPartner($rechnung->get("kontaktId"))["name"]; ?></p>
              <p><?php echo $rechnung->get("betreff"); ?></p>
            </div>

            <?php $summen = $rechnung->getTotals(); ?>
            <div class="reli-sumNetto">
              <?php echo $this->euro($summen["sumNetto"]); ?>
            </div>
            <div class="reli-sumBrutto">
              <?php echo $this->euro($summen["sumBrutto"]); ?>
            </div>
          </div>
        </a>
      <?php } ?>
    </div>
    <?php
  }

  public function getFromDb(){
    $sql = $this->mysqli->prepare("SELECT rid FROM rechnungen;");
    $sql->execute();
    $result = $sql->get_result();
    $result = $result->fetch_all(MYSQLI_ASSOC);

    $rechnungen = array();
    foreach ($result as $res) {
      $rechnungen[] = new Rechnung($res["rid"]);
    }
    return $rechnungen;
  }
}

/**
 *
 */
class Rechnung extends DB {
  public $exist = false;
  private $rid = null;
  private $reData = array();
  private $statusTranslations = array(
    "entwurf" => "Entwurf",
    "verschickt" => "Versendet",
    "bezahlt" => "Bezahlt"
  );

  public function isLocked(){
    return (isset($this->reData["rnr"]) && $this->reData["status"] !== "entwurf");
  }

  public function getZahlungszielTageVonJetzt(){
    $now = time();
    $dtZz = new DateTime($this->reData["dtZahlungsziel"]);
    $then = $dtZz->getTimestamp();
    $days = ceil(($then - $now) /60 / 60 / 24);
    return $days;
  }
  public function publish(){
    global $lan;
    $rnr = $this->generateReNr();
    if($rnr === false){
      $lan->addError("Fehler beim Festschreiben der Rechnung: Rechnung bereits festgeschrieben.");
      return false;
    }
    $sql = $this->mysqli->prepare("UPDATE rechnungen SET rnr = ?, status = 'verschickt' WHERE rid = ?");
    $sql->bind_param("ii", $rnr , $this->rid);
    $sql->execute();

    if(empty($this->mysqli->error)){
      $lan->addSuccess("Rechnung festgeschrieben!");
    }else{
      $lan->addError("Fehler beim Festschreiben der Rechnung: ".$this->mysqli->error);
    }
  }

  private function generateReNr(){
    if($this->isLocked()){
      return false;
    }
    $sql = $this->mysqli->prepare("SELECT rnr FROM rechnungen ORDER BY rnr DESC LIMIT 1");
    $sql->execute();
    $result = $sql->get_result();
    $result = $result->fetch_all(MYSQLI_ASSOC);

    if(!isset($result[0], $result[0]["rnr"])){
      return 1000;
    }else{
      return $result[0]["rnr"] + 1;
    }
  }

  public function __debugInfo(){
    $properties = get_object_vars($this);
    return $properties;
  }

  public function getStatus(){
    $status = $this->get("status");
    return array("key" => $status, "text" => $this->statusTranslations[$status]);
  }

  public function getTotals(){
    $totals = array("sumNetto" => 0.0, "sumBrutto" => 0.0);
    foreach ($this->reData["positionen"] as $pos) {
      if(!isset($totals[$pos["posUst"]])){
        $totals[$pos["posUst"]] = array("sum" => 0, "steuer" => 0);
      }
      $totals[$pos["posUst"]]["sum"] += $pos["posSum"];
    }
    foreach ($totals as $ust => $sum) {
      if(!in_array($ust, array("sumNetto", "sumBrutto"))){
        // Netto Summe addieren
        $totals["sumNetto"] += $sum["sum"];
        // Steuer von Netto Betrag berechnen
        $totals[$ust]["steuer"] = $sum["sum"] * ($ust/100);
        // Brutto Betrag addieren
        $totals["sumBrutto"] += $sum["sum"] * ((100+$ust)/100);
      }
    }
    return $totals;
  }

  public function formAction($data){
    global $lan;
    if(isset($data["save_draft"])){
      $this->updateData($data);
    }else if(isset($data["publish"])){
      $this->updateData($data);
      $this->publish();
    }else if(isset($data["pdf_preview"])){
      header("Location: /rechnung.php?rid=".$this->rid);
      die();
    }else if(isset($data["download"])){
      header("Location: /rechnung.php?dl=1&rid=".$this->rid);
      die();
    }else if(isset($data["create_transaction"])){
      $lan->addInfo("Diese Aktion ist noch nicht verfügbar.");
    }else if(isset($data["delete_invoice"])){
      $this->delete();
    }else{
      $lan->addError("Ungültige Aktion!");
      header("Location: /rechnungen");
      die();
    }
    header("Location: /rechnungen/edit?rid=".$this->rid);
    die();
  }

  private function delete(){
    global $lan;
    if($this->reData["status"] !== "entwurf"){
      $lan->addError("Diese Rechnung wurde bereits abgeschlossen und kann nicht mehr gelöscht werden!");
      return false;
    }
    $sql = $this->mysqli->prepare("DELETE FROM rechnungen WHERE rid = ?");
    $sql->bind_param("i", $this->rid);
    $sql->execute();
    if(empty($this->mysqli->error)){
      $lan->addSuccess("Rechnung wurde gelöscht!");
      header("Location: /rechnungen");
      die();
    }else{
      $lan->addError("Die Rechnung konnte nicht gelöscht werden: ".$this->mysqli->error);
      return false;
    }
  }

  private function completeInputData($data){
    // Neuen Kontakt erstellen, falls keine kontaktId gesetzt ist
    if(!isset($data["kontaktId"]) || empty($data["kontaktId"])){
      $data["kontaktId"] = $this->neuerKontakt($data["kontakt"]);
    }

    // Betrag Brutto ausrechnen
    if(!isset($data["positionen"])){
      $data["positionen"] = array();
    }

    $sumbrutto = 0;
    foreach ($data["positionen"] as $id => $pos) {
      $sumbrutto += round($pos["posSum"] * (((100 + $pos["posUst"]) / 100)), 2);
    }
    $data["summeBrutto"] = round($sumbrutto, 2);

    return $data;
  }

  private function createInvoice($data){
    $sql = $this->mysqli->prepare("INSERT INTO rechnungen (betreff, summeBrutto,tageZahlungsziel, dtZahlungsziel, dtRechnung,kontaktId, kontaktAdr, ansprechpartnerId, positionen) VALUES (?,?,?,?,?,?,?,?,?)");
    $positionen = json_encode($data["positionen"]);
    $sql->bind_param("sdissssss",
      $data["betreff"],
      $data["summeBrutto"],
      $data["tageZahlungsziel"],
      $data["dtZahlungsziel"],
      $data["dtRechnung"],
      $data["kontaktId"],
      $data["kontaktAdr"],
      $data["ansprechpartnerId"],
      $positionen
    );
    $sql->execute();

    if(!empty($this->mysqli->error)){
      global $lan;
      $lan->addError("Fehler beim anlegen des Datensatzes: ".$this->mysqli->error);
      header("Location: /rechnungen");
      die();
    }
    return $this->mysqli->insert_id;
  }

  private function updateData($input){

    global $lan;

    // Check if data is locked
    if($this->isLocked()){
      return false;
    }

    // Validate all input data
    if(!$this->validateInputData($input)){
      return false;
    }
    // Calculate / Generate Missing Data
    $input = $this->completeInputData($input);


    $reData = $this->updateReData($input);

    // Check if Invoice exist
    if(!$this->exist){
      $rid = $this->createInvoice($reData);
      $lan->addSuccess("Rechnung erstellt");
      header("Location: /rechnungen/edit?rid=$rid");
      die();
    }

    // Update Database
    $this->updateDatabase($input);
    // Update Local Data
    $this->loadFromDatabase();

    $lan->addSuccess("Rechnung aktualisiert");
  }

  private function updateReData($input){
    foreach ($input as $key => $value) {
      if(isset($this->reData[$key]) && $this->reData[$key] != $value){
        $this->reData[$key] = $value;
      }
    }
    return $this->reData;
  }
  private function updateDatabase($input = array()){
    $this->updateReData($input);
    $positionen = json_encode($this->reData["positionen"]);
    $sql = $this->mysqli->prepare("UPDATE rechnungen SET betreff = ?, summeBrutto = ?,dtZahlungsziel = ?,dtRechnung = ?,kontaktId = ?, kontaktAdr = ?, ansprechpartnerId = ?, positionen = ?, tageZahlungsziel = ? WHERE rid = ?");
    echo($this->mysqli->error);
    $sql->bind_param("sdssssssii",
      $this->reData["betreff"],
      $this->reData["summeBrutto"],
      $this->reData["dtZahlungsziel"],
      $this->reData["dtRechnung"],
      $this->reData["kontaktId"],
      $this->reData["kontaktAdr"],
      $this->reData["ansprechpartnerId"],
      $positionen,
      $this->reData["tageZahlungsziel"],
      $this->rid
    );
    $sql->execute();
    echo($this->mysqli->error);
  }

  private function validateInputData($input){
    // Check if all data is set
    if(!isset($input["kontakt"], $input["kontaktAdr"], $input["betreff"], $input["dtRechnung"], $input["dtZahlungsziel"], $input["ansprechpartnerId"])){
      return false;
    }else{
      return true;
    }

    // TODO: More validation
  }

  function __construct($rid) {
    global $lan;
    parent::__construct();
    // Rechnungsid angegeben, Daten aus Datenbank ziehen
    if($rid !== false){
      if($this->invoiceExistInDb($rid)){
        $this->exist = true;
        $this->rid = $rid;
        $this->loadFromDatabase();
      }else{
        $lan->addError("Es wurde keine Rechnung mit dieser ID gefunden!");
        header("Location: /rechnungen");
        die();
      }
    }else{ // Falls keine Rechnungsid angegeben wurde soll eine neue Rechnung erstellt werdn
        $this->loadDefaults();
    }
  }

  private function invoiceExistInDb($rid){
    $sql = $this->mysqli->prepare("SELECT COUNT(1) AS reExist FROM rechnungen WHERE rid = ?");
    $sql->bind_param("i", $rid);
    $sql->execute();
    $result = $sql->get_result();
    $result = $result->fetch_all(MYSQLI_ASSOC);

    return $result[0]["reExist"] == 1;
  }

  private function loadFromDatabase(){
    $sql = $this->mysqli->prepare("SELECT * FROM rechnungen WHERE rid = ?");
    $sql->bind_param("i", $this->rid);
    $sql->execute();
    $result = $sql->get_result();
    $result = $result->fetch_all(MYSQLI_ASSOC);

    $this->reData = $result[0];
    $this->reData["positionen"] = json_decode($this->reData["positionen"], true);

  }

  public function getReNr(){
    if(isset($this->reData["rnr"])){
      return "RE-".str_pad($this->reData["rnr"], 4, '0', STR_PAD_LEFT);
    }
    return "RE-####";
  }

  private function loadDefaults(){
    $this->reData = array(
      "rid" => null,
      "rnr" => "",
      "betreff" => "Rechnung Nr {rnr}",
      "summeBrutto" => 0.0,
      "status" => "entwurf",
      "tageZahlungsziel" => 14,
      "dtZahlungsziel" => date("Y-m-d", time() + (60 * 60 * 24 * 14)),
      "dtRechnung" => date("Y-m-d"),
      "kontaktId" => "",
      "kontaktAdr" => "",
      "ansprechpartnerId" => "",
      "positionen" => array()
    );
  }

  // Create the HTML Body of the Invoice
  public function getBody(){
    $ansprechpartner = $this->getTransPartner($this->reData["ansprechpartnerId"]);
    $dtZahlungsziel = new DateTime($this->reData["dtZahlungsziel"]);
    $dtRechnung = new DateTime($this->reData["dtRechnung"]);
    ob_start();
    echo $this->getBodyStyles(); ?>
    <table class="reInfo">
      <tr>
        <td>Rechnungs-Nr.</td>
        <td><?php echo $this->getReNr(); ?></td>
      </tr>
      <tr>
        <td>Rechnungsdatum</td>
        <td><?php echo $dtRechnung->format("d.m.Y"); ?></td>
      </tr>
      <tr>
        <td>Fälligkeit</td>
        <td><?php echo $dtZahlungsziel->format("d.m.Y"); ?></td>
      </tr>
      <tr>
        <td>Ansprechpartner</td>
        <td><?php echo $ansprechpartner["name"]; ?></td>
      </tr>
    </table>
    <h4><?php echo str_replace('{rnr}', $this->getReNr(), $this->reData["betreff"]); ?></h4>

    <p class="anrede">Sehr geehrte Damen und Herren,</p>
    <p class="einleitung">vielen Dank für das entgegengebrachte Vertrauen. Wir erlauben uns die folgenden Positionen in Rechnung zu stellen:</p>

    <div class="positionen">
      <table class="pos">
        <thead>
          <tr>
            <td colspan="2" class="stretch">Position</td>
            <td>Menge</td>
            <td>Ust.</td>
            <td class="right">Einzelpreis<br><span class="netto">netto</span></td>
            <td class="right">Summe<br><span class="netto">netto</span></td>

          </tr>
        </thead>
        <tbody>
          <?php
          foreach ($this->reData["positionen"] as $pos) {
            ?>
            <tr>
              <td><?php echo $pos["posOrder"]+1; ?></td>
              <td><?php echo '<p class="title">'.$pos["posTitle"].'</p>';
              if(!empty($pos["posDesc"])){
                echo('<p class="desc">'.$pos["posDesc"]."</p>");
              }
              ?></td>
              <td><?php echo $pos["posAnz"]." ".$pos["posEinheit"]; ?></td>
              <td><?php echo $pos["posUst"]; ?>%</td>
              <td class="right"><?php echo $this->euro($pos["posEP"]); ?></td>
              <td class="right"><?php echo $this->euro($pos["posSum"]); ?></td>

            </tr>
            <?php
          }
          ?>
        </tbody>
      </table>
      <table class="summary">
        <?php $totals = $this->getTotals(); ?>
        <tbody>
          <tr class="sum">
            <td  class="sum-title">Summe netto</td>
            <td  class="sum-value"><?php echo $this->euro($totals["sumNetto"]); ?></td>
          </tr>
          <?php if(isset($totals[7])){ ?>
          <tr class="sum tax">
            <td  class="sum-title">zzgl. 7% MwSt.</td>
            <td  class="sum-value"><?php echo $this->euro($totals[7]["steuer"]); ?></td>
          </tr>
        <?php }
        if(isset($totals[19])){ ?>
          <tr class="sum tax">
            <td class="sum-title">zzgl. 19% MwSt.</td>
            <td class="sum-value"><?php echo $this->euro($totals[19]["steuer"]); ?></td>
          </tr>
        <?php } ?>
          <tr class="sum total">
            <td class="sum-title">Rechnungsbetrag</td>
            <td class="sum-value"><?php echo $this->euro($totals["sumBrutto"]); ?></td>
          </tr>
        </tbody>
      </table>
      <p class="fusszeile">Bitte überweisen Sie den Rechnungsbetrag innerhalb von <?php echo $this->reData["tageZahlungsziel"]; ?> Tagen (bis zum <?php echo $dtZahlungsziel->format("d.m.Y"); ?>) unter Angabe der Rechnungsnummer auf das unten angegebene Konto.</p>
      <p class="gruss">Viele Grüße,</p>
      <p class="unt"><?php echo $ansprechpartner["name"]; ?><br>Eventverein Tambach-Dietharz e.V.</p>
    </div>

    <?php return ob_get_clean();
  }

  private function getBodyStyles(){
    ob_start(); ?>
    <style>
      .content *{
        margin: 0;
      }
      .reInfo{
        position: absolute;
        z-index: 5;
        border: 1px solid;
        right: 0;
        top: -10mm;
        transform: translateY(-100%);
        padding: 5px 0;
        box-sizing: border-box;
        font-size: 0.9em;

      }
      .reInfo td{
        margin: 0;
        padding: 0 10px;
        max-width: 40mm;
      }
      .anrede{
        padding-top: 5mm;
        line-height: 1.5em;
      }
      .einleitung{
        padding-top: 2mm;
        line-height: 1.3em;
      }
      .pos{
        border-collapse: collapse;
        padding: 5mm 0;
        page-break-after: avoid;
        width: 100%;
        font-size: 1em;
      }

      .pos td{
        padding: 10px 10px;
        border-bottom: 2px solid #dedede;
        vertical-align: top;
      }



      .pos thead{
        background: #dedede;
      }

      .pos thead td{
        font-weight: bold;
        line-height: 1;
        vertical-align: middle;
      }

      .pos thead .netto{
        font-weight: lighter;
        font-size: 0.6em;
        font-style: italic;
        line-height: 1;
      }

      .pos .right{
        text-align: right;
      }

      .pos .desc{
        font-size: 0.8em;
        opacity: 0.7;
      }

      .summary{
        float: right;
        border-collapse: collapse;
        min-width: 80mm;
        page-break-before: avoid;
        page-break-inside: avoid;
        background: #f5f5f5;
      }
      .summary td{
        padding: 10px 10px;
        border-bottom: 2px solid #dedede;
        vertical-align: top;
      }

      .sum.total{
        font-weight: bold;
        background: #dedede;
      }

      .sum-value{
        text-align: right;
      }

      .fusszeile{
        clear:both;
        padding-top: 50px;
      }

      .gruss{
        padding: 30px 0 10px;
      }
    </style>
    <?php return ob_get_clean();
  }


  public static function getPosHtml($id, $posData = array(), $lock = false){
    ob_start();
    ?>
    <div class="pos <?php echo $lock ? "locked" : ""; ?>" data-posId="<?php echo $id; ?>">
      <!-- Position auf der Rechnung (1 ganz oben) -->
      <input type="hidden" class="posOrder" name="positionen[<?php echo $id; ?>][posOrder]" value="<?php echo isset($posData["posOrder"]) ? $posData["posOrder"] : ""; ?>">
      <p class="posNr"><?php echo isset($posData["posOrder"]) ? ($posData["posOrder"]+1)."." : "-1"; ?></p>

      <!-- Titel der Position -->
      <div class="posColTitle" data-content="Position"><input class="posTitle" type="text" name="positionen[<?php echo $id; ?>][posTitle]" value="<?php echo isset($posData["posTitle"]) ? $posData["posTitle"] : ""; ?>" placeholder="Bezeichnung" <?php echo $lock ? "disabled" : ""; ?> ></div>

      <!-- Anzahl und Einheit -->
      <div class="posQty posColTitle" data-content="Menge">
        <input class="posAnz" type="number" step="0.1" min="1" max="999" name="positionen[<?php echo $id; ?>][posAnz]" value="<?php echo isset($posData["posAnz"]) ? $posData["posAnz"] : ""; ?>" <?php echo $lock ? "disabled" : ""; ?>>
        <input class="posEinheit" type="text" name="positionen[<?php echo $id; ?>][posEinheit]" value="<?php echo isset($posData["posEinheit"]) ? $posData["posEinheit"] : ""; ?>" style="width: 5em;" maxlength="5" <?php echo $lock ? "disabled" : ""; ?>>
      </div>

      <!-- Preis -->
      <div class="posPrice moneyInput posColTitle" data-content="Einzelpreis (Netto)">
        <input class="posEP" type="number" step="0.01" min="-99999" max="99999" name="positionen[<?php echo $id; ?>][posEP]" value="<?php echo isset($posData["posEP"]) ? $posData["posEP"] : ""; ?>" <?php echo $lock ? "disabled" : ""; ?>>
      </div>


      <!-- Steuer -->
      <div class="posQty posColTitle" data-content="Ust.">
        <select class="posUst" name="positionen[<?php echo $id; ?>][posUst]" <?php echo $lock ? "disabled" : ""; ?>>
          <option value="19" <?php echo isset($posData["posUst"]) && $posData["posUst"] == 19 ? "selected" : ""?>>19%</option>
          <option value="7" <?php echo isset($posData["posUst"]) && $posData["posUst"] == 7 ? "selected" : ""?>>7%</option>
          <option value="0" <?php echo isset($posData["posUst"]) && $posData["posUst"] == 0 ? "selected" : ""?>>0%</option>
        </select>
      </div>

      <!-- Summe -->
      <div class="posPrice moneyInput posColTitle" data-content="Summe (Netto)">
        <input readonly class="posSum" step="0.01" type="number" min="-99999999" max="99999999" name="positionen[<?php echo $id; ?>][posSum]" value="<?php echo isset($posData["posSum"]) ? $posData["posSum"] : 0; ?>" <?php echo $lock ? "disabled" : ""; ?>>
      </div>

      <!-- Pos löschen -->
      <p class="posDel"><span class="icon">delete</span></p>

      <!-- Beschreibung -->
      <p class="posDesc"><textarea class="posDescr" name="positionen[<?php echo $id; ?>][posDesc]" rows="2" cols="80" placeholder="Beschreibung" <?php echo $lock ? "disabled" : ""; ?>><?php echo isset($posData["posDesc"]) ? $posData["posDesc"] : ""; ?></textarea></p>
    </div>
    <?php
    return ob_get_clean();
  }

  public function getStatusHtml(){
    ob_start(); ?>
    <div class="<?php echo $this->getFormClasses(array("re-status")); ?>">
      <div class="status-icon">
        <span class="new icon">add</span>
        <span class="draft icon">draw</span>
        <span class="open icon">lock</span>
        <span class="paid icon">done</span>
        <span class="overdue icon">crisis_alert</span>
      </div>
      <div class="status-text">
        <p class="new">Neu</p>
        <p class="draft">Entwurf</p>
        <p class="open">Offen</p>
        <p class="paid">Bezahlt</p>
        <p class="overdue">Überfällig</p>
      </div>
    </div>
    <?php return ob_get_clean();
  }

  public function get($key){
    // Spezialfall: Anprechpartner Id wenn Rechnung neu erstellt wird
    // Standard: Angemeldeter Benutzer
    if($key == "ansprechpartnerId" && !$this->exist){
      global $auth;
      return  $auth->getLoggedInUser()["mnr"];
    }
    return isset($this->reData[$key]) ? $this->reData[$key] : "";
  }
  public function getFormClasses($classes = array()){
    if(!$this->exist){
      $classes[] = "new";
    }else{
      $classes[] = $this->reData["status"];
    }
    if($this->isLocked()){
      $classes[] = "locked";
    }
    if($this->reData["status"] == "verschickt" && $this->getZahlungszielTageVonJetzt() < 0){
      $classes[] = "overdue";
    }
    return implode(" ", $classes);
  }
}


 ?>
