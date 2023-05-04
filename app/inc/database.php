<?php

/**
 *
 */
class DB{

  private $c = array(
    "db" => "eventverein",
    "host" => "192.168.100.10",
    "user" => "ev_webinterface",
    "pass" => "ryuR64qfrJ:wx-7ne25Zv_Znzx:_WR"
  );
  protected $mysqli;

  function __construct(){
    $this->mysqli = new mysqli($this->c["host"], $this->c["user"], $this->c["pass"], $this->c["db"]);
    $this->mysqli->set_charset("UTF8");
    if($this->mysqli->connect_errno){
      throw new \Exception("Fehler bei der Verbindung zur Datenbank", 1);
    }
  }
  public function __debugInfo(){
    $properties = get_object_vars($this);
    unset($properties['c']);
    return $properties;
  }

  public function getTransPartner($partner){
    $return = array();

    // Check if its member nr
    if(str_contains($partner, ".")){
      // Member
      $return["type"] = "mitglieder";
      $sqls = "SELECT concat(vorname, ' ', nachname) name, mnr, id FROM mitglieder WHERE mnr = ?";
    }else{
      // Contact
      $return["type"] = "kontakt";
      $sqls = "SELECT name, ktyp, kid id FROM kontakte WHERE kid = ?";
    }

    $sql = $this->mysqli->prepare($sqls);
    $sql->bind_param("s", $partner);
    $sql->execute();

    $result = $sql->get_result();
    $result = $result->fetch_all(MYSQLI_ASSOC);

    if(empty($result)){
      return false;
    }
    foreach ($result[0] as $key => $value) {
      $return[$key] = $value;
    }

    return $return;

  }

  public function getPartner($partner){
    return $this->getTransPartner($partner);
  }

  public function neuerKontakt($name){
    $sql = $this->mysqli->prepare("INSERT INTO kontakte (name) VALUES (?)");
    $sql->bind_param("s", $name);
    $sql->execute();

    return $this->mysqli->insert_id;
  }
  public function sucheMitglieder($name){
    $sql = $this->mysqli->prepare("SELECT mnr, concat(vorname, ' ',nachname) name FROM mitglieder WHERE concat(vorname, nachname) LIKE ? LIMIT 3");

    $name = "%$name%";
    $sql->bind_param("s", $name);
    $sql->execute();

    $result = $sql->get_result();
    $result = $result->fetch_all(MYSQLI_ASSOC);
    return $result;
  }

  /**
   * @brief Splits an address string containing a street, number and number addition
   *
   * @param $streetStr string An address string containing a street, number (optional) and number addition (optional)
   *
   * @return array Data array with the following keys: street, number and numberAddition.
   */
  public static function split_street($streetStr) {

    $aMatch         = array();
    $pattern        = '#^((?:[^\d\n]*)*)(\d{1,5})\s?([\w[:punct:]\-/]*)$#';
    $matchResult    = preg_match($pattern, $streetStr, $aMatch);

    $street = (isset($aMatch[1])) ? $aMatch[1] : $streetStr;
    $number         = (isset($aMatch[2])) ? $aMatch[2] : '';
    $numberAddition = (isset($aMatch[3])) ? $aMatch[3] : '';

    return array('street' => $street, 'number' => $number, 'numberAddition' => $numberAddition);

  }

  public function sucheKontakte($name){
    $sql = $this->mysqli->prepare("SELECT kid, name FROM kontakte WHERE name LIKE ? LIMIT 3");

    $name = "%$name%";
    $sql->bind_param("s", $name);
    $sql->execute();

    $result = $sql->get_result();
    $result = $result->fetch_all(MYSQLI_ASSOC);
    return $result;
  }

  public function getMySqli(){
    return $this->mysqli;
  }

  public function euro($input){
    if(!is_numeric($input)){
      return "XX.XX €";
    }
    return number_format($input, "2", ",", ".")." €";
  }

  protected function prep($sql){
    $statement = $this->mysqli->prepare($sql);
    return $statement;
  }

  public function getActivationKey($mnr, $email){
    	return hash("sha512", $mnr.$email);
  }

  public function isDateLocked($dateString){
    $dt = new DateTime($dateString);
    $sql = $this->mysqli->prepare("SELECT bid FROM berichte WHERE jahr = YEAR(?) AND monat = MONTH(?)");
    $date = $dt->format("Y-m-d");
    $sql->bind_param("ss", $date, $date);
    $sql->execute();
    return $sql->get_result()->num_rows > 0 ? true : false;
  }

  private function createViews(){

    /* v_kontostaende VIEW */

    // ID des aktuellsten Berichts
    $getBerichtId = "SELECT bid FROM berichte ORDER BY jahr DESC, monat DESC LIMIT 1";
    // Saldos des aktuellsten Berichts
    $getSaldos = "SELECT konto 'src', saldo 'betrag' FROM berichte_saldos WHERE zeitpunkt = 'ME' AND bid = (".$getBerichtId.")";
    // Summe aller Transaktionen (nicht archiviert)
    $getTransaktionsSumme = "SELECT src, SUM(betragBrutto) 'betrag' FROM transaktionen GROUP BY src";
    // Summe aller Umbuchungen (nicht archiviert) als betrag beim ziel und als betrag*-1 bei der quelle
    $getUmbuchungen = "SELECT quelle 'src', SUM(betrag*-1) 'betrag' FROM `umbuchungen` GROUP BY quelle UNION SELECT ziel 'src', SUM(betrag) 'betrag' FROM `umbuchungen` GROUP BY ziel";

    // Alles in einer Tabelle vereinen
    $union = $getSaldos." UNION ".$getTransaktionsSumme." UNION ".$getUmbuchungen;

    // Summe bilden
    $main = "SELECT src AS 'konto', ROUND(SUM(betrag),2) AS 'kontostand' FROM ( ". $union ." ) sub GROUP BY src";

    $sql = $this->mysqli->prepare("CREATE OR REPLACE VIEW v_kontostaende AS ".$main);
    //$sql->execute();


    /* v_kontouebersicht VIEW */
    $unionTrans = "SELECT * FROM transaktionen UNION SELECT * FROM transaktionen_archiv";
    $getTrans = "SELECT 'transaktion' AS 'typ', tid, zeitstempel, betragBrutto, betragNetto, ust, zweck, partner, src, beleg, NULL AS 'quelle', NULL AS 'ziel', erfasst, locked FROM (".$unionTrans.") a";

    $unionUmb = "SELECT * FROM umbuchungen UNION SELECT * FROM umbuchungen_archiv";
    $getUmb = "SELECT 'umbuchung' AS 'typ', umbId AS 'tid', zeitstempel, betrag AS 'betragBrutto', betrag AS 'betragNetto', '0%' AS 'ust', NULL AS 'zweck', partner, NULL AS 'src', NULL AS 'beleg', quelle, ziel, erfasst, locked FROM (".$unionUmb.") b";

    $mains = "CREATE OR REPLACE VIEW v_kontouebersicht AS SELECT * FROM (".$getTrans.") trans UNION (".$getUmb.") ORDER BY zeitstempel DESC";

    $sqls = $this->mysqli->prepare($mains);
    $sqls->execute();

  }


}


 ?>
