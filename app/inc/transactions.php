<?php
require_once("../inc/belege.php");
/**
 *
 */
class Transactions extends DB
{

  function __construct()
  {
    parent::__construct();
  }


  public function getKontostaende(){
    $sql = $this->mysqli->prepare("SELECT * FROM v_kontostaende");
    $sql->execute();
    $result = $sql->get_result();
    $result = $result->fetch_all(MYSQLI_ASSOC);

    $kontostd = array();
    foreach ($result as $ks) {
      $kontostd[$ks["konto"]] = $ks["kontostand"];
    }
    return $kontostd;
  }

  public function new($zeitstempel, $partner, $zweck, $betrag, $ust, $src, $beleg = ""){
    $nutzer = $_SESSION["user"];
    if($this->isDateLocked($zeitstempel)){
      return false;
    }
    $betragNetto = round($betrag / (1 + $ust/100), 2);
    $ust .= "%";
    $sql = $this->mysqli->prepare("INSERT INTO transaktionen (zeitstempel, nutzer, src, zweck, partner, betragBrutto, betragNetto, ust, beleg) VALUES (?, (SELECT mnr FROM mitglieder WHERE login = ?),?,?,?,?,?,?,?)");
    $sql->bind_param("sssssddss",
        $zeitstempel,
        $nutzer,
        $src,
        $zweck,
        $partner,
        $betrag,
        $betragNetto,
        $ust,
        $beleg);
    $sql->execute();
    return true;
  }

  public function display($from, $to){
    $timestamps = $this->get($from, $to);

    foreach ($timestamps as $timestamp => $transactions) {
      $dt = new DateTime($timestamp);
      ?>
      <div class="time-head" id="day-<?php echo($dt->format("Y-m-d")); ?>">
        <?php echo($dt->format("d.m.Y")); ?>
      </div>
      <?php
      unset($dt);

      foreach ($transactions as $trans) {
        $partner = $this->getTransPartner($trans["partner"]);
        if($trans["typ"] == "transaktion"){
          ?>
            <div
              class="transaktion <?php echo $trans["betragBrutto"] < 0 ? "ausgabe" : "einnahme"; echo !isset($trans["beleg"]) ? " kein-beleg" : ""; echo $trans["locked"] ? " locked" : ""; ?>"
              id="t-<?php echo $trans["tid"]; ?>"
              data-tid="<?php echo $trans["tid"]; ?>"
              >

              <p class="partner"><a href="<?php echo "/".$partner["type"]."/view?id=".$partner["id"]; ?>"><?php echo $partner["name"]; ?></a></p>
              <p class="zweck"><a href="/transaktionen/view?tid=<?php echo $trans["tid"]; ?>"><?php echo $trans["zweck"]; ?></a></p>
              <p class="betrag"><?php echo $this->euro($trans["betragBrutto"]); ?></p>
              <p class="src"><?php echo $trans["src"]; ?></p>
            </div>
          <?php
        }else{
          ?>
            <div
              class="umbuchung <?php echo $trans["locked"] ? " locked" : ""; ?>"
              id="umb-<?php echo $trans["tid"]; ?>"
              data-tid="<?php echo $trans["tid"]; ?>"
              >
              <p class="partner"><a href="<?php echo "/".$partner["type"]."/view?id=".$partner["id"]; ?>"><?php echo $partner["name"]; ?></a></p>
              <div class="umbuchung-wrap">
                <p class="quelle"><?php echo $trans["quelle"]; ?></p>
                <div class="arrow-amount">
                  <p class="betrag"><?php echo $this->euro($trans["betragBrutto"]); ?></p>
                  <div class="arrow">
                    <div class="tip"></div>
                    <div class="shaft"></div>
                  </div>
                </div>
                <p class="ziel"><?php echo $trans["ziel"]; ?></p>
              </div>
            </div>
          <?php
        }

      }
    }

  }

  public function get($from, $to, $orderby = "DESC"){


    $sql = $this->mysqli->prepare("SELECT * FROM v_kontouebersicht WHERE zeitstempel BETWEEN ? AND ? ORDER BY zeitstempel $orderby, erfasst ");
    $sql->bind_param("ss", $from, $to);
    $sql->execute();
    $result = $sql->get_result();
    $result = $result->fetch_all(MYSQLI_ASSOC);

    $ordered = array();

    foreach ($result as $data) {
      $ordered[$data["zeitstempel"]][] = $data;
    }

    return $ordered;
  }

  public function search($term, $type = "all"){

    switch ($type) {
      case 'all':
        $sql = $this->mysqli->prepare("SELECT tid, zeitstempel, zweck, partner, betragBrutto FROM transaktionen WHERE zweck LIKE ? ORDER BY zeitstempel DESC LIMIT 5");
        break;
      case 'ausgaben':
        $sql = $this->mysqli->prepare("SELECT tid, zeitstempel, zweck, partner, betragBrutto FROM transaktionen WHERE betragBrutto < 0 AND zweck LIKE ? ORDER BY zeitstempel DESC LIMIT 5");
        break;
      case 'einnahmen':
        $sql = $this->mysqli->prepare("SELECT tid, zeitstempel, zweck, partner, betragBrutto FROM transaktionen WHERE betragBrutto >= 0 AND zweck LIKE ? ORDER BY zeitstempel DESC LIMIT 5");
        break;
    }

    $term = "%$term%";
    $sql->bind_param("s", $term);
    $sql->execute();
    $result = $sql->get_result();
    $result = $result->fetch_all(MYSQLI_ASSOC);
    return $result;
  }




}

 ?>
