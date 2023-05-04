<?php

/**
 *
 */
class Mitglieder extends DB
{

  function __construct()
  {
    parent::__construct();
  }

  public function display(){
    $mitglieder = $this->get();

    foreach ($mitglieder as $m) {
      $antrag = empty($m["mnr"]);
      ?>
      <a href="<?php echo $antrag ? "/mitglieder/view?id=".$m["id"] : "/mitglieder/view?mnr=".$m["mnr"]; ?>">
        <div class="mitglied" id="m-<?php echo $m["mnr"]; ?>" data-mnr="<?php echo $m["mnr"]; ?>">
          <p class="icon <?php echo $antrag ? "pending" : "member"; ?>"><?php echo $antrag ? "pending_actions" : "badge"; ?></p>
          <p class="name"><?php echo $m["vorname"]." ".$m["nachname"]; ?></p>
          <p class="mnr">Mitgliedsnummer: <?php echo $m["mnr"]; ?></p>
        </div>
      </a>
      <?php
    }
  }

  public function get(){
    $sql = $this->mysqli->prepare("SELECT id, vorname, nachname, mnr, status FROM mitglieder");
    $sql->execute();
    $result = $sql->get_result();
    $result = $result->fetch_all(MYSQLI_ASSOC);

    return $result;
  }
}

class Mitglied extends DB{
  private $data = array();
  private $exist = false;

  function __construct($mnr = null, $memid = null){
    parent::__construct();
    if(isset($mnr)){

      $sql = $this->prep("SELECT *, !ISNULL(login) active FROM mitglieder WHERE mnr = ?");
      $sql->bind_param("s", $mnr);
      $sql->execute();
      $result = $sql->get_result();
      $result = $result->fetch_all(MYSQLI_ASSOC);

      $this->data = $result[0];
      $this->exist = true;

    }else if(isset($memid)){

      $sql = $this->prep("SELECT *, !ISNULL(login) active FROM mitglieder WHERE id = ?");
      $sql->bind_param("i", $memid);
      $sql->execute();
      $result = $sql->get_result();
      $result = $result->fetch_all(MYSQLI_ASSOC);

      $this->data = $result[0];
      $this->exist = true;
    }
  }

  public static function generateMemberSecret($memid){
    $tohash = "EVOA.$memid";
    return hash("crc32", $tohash);
  }

  public function doesMemberExist(){
    return $this->exist;
  }
  public function update($ndata){


    $this->mysqli->begin_transaction();
    $newuser = false;
    if(!$this->exist){
      $newuser = true;
      $sql = $this->mysqli->prepare("INSERT INTO mitglieder (status) VALUES ('wartet')");
      if($sql == false){
        return $this->mysqli->error;
      }
      $sql->execute();
      $this->data["id"] = $this->mysqli->insert_id;
      $this->exist = true;
    }

    foreach ($ndata as $key => $value) {
      if(in_array($key, array("id"))){
        continue;
      }



      if(array_key_exists($key,$this->data) || $newuser){
        if($newuser || $this->data[$key] != $value){
          echo("UDP");
          $sql = $this->mysqli->prepare("UPDATE mitglieder SET $key = ? WHERE id = ?");
          if($sql == false){
            return $this->mysqli->error;
          }
          $sql->bind_param("si", $value, $this->data["id"]);
          $sql->execute();
          $this->data[$key] = $value;
        }
      }
    }

    $this->mysqli->commit();
    return true;
  }

  public function isAcitivated(){
    if($this->exist){
        return $this->data["active"] == 1;
    }else{
      return false;
    }
  }

  public function getName(){
    if($this->exist){
      return $this->data["vorname"]." ".$this->data["nachname"];
    }else{
      return false;
    }
  }

  public function getRawData($key, $forPdf = true){
    $data = isset($this->data[$key]) ? $this->data[$key] : false;
    if($data !== false && $forPdf){
      $data = mb_convert_encoding($data, "Windows-1252", "UTF-8");
    }
    return $data;
  }

  public function getActivationLink(){
    if($this->exist){
      $link = "https://team.eventverein.de/activate?email=".$this->data["email"]."&mnr=".$this->data["mnr"]."&key=";
      $link .= $this->getActivationKey($this->data["mnr"], $this->data["email"]);
      return $link;
    }else{
      return false;
    }
  }

  public function displayActivationLink(){
    ?>
    <div class="account_activation">
      <p>Sende dem Mitglied diesen Link, damit es seinen Account im Teamportal aktivieren kann:</p>
      <p class="url click-copy"><?php echo($this->getActivationLink());?></p>
    </div>
    <?php
  }

  public function getId(){
    return isset($this->data["id"]) ? $this->data["id"] : false;
  }

  public function displayForm($required = false, $btnTitle = "Speichern"){
    $e = $this->exist;
    $d = $this->data;
    ?>

    <form class="member-form" method="post">
      <label for="vorname">Vorname</label>
      <input type="text" name="vorname" id="vorname"  value="<?php echo isset($d["vorname"]) ? $d["vorname"] : ""; ?>" <?php echo $required ? "required" : ""; ?>>

      <label for="nachname">Nachname</label>
      <input type="text" name="nachname" id="nachname" value="<?php echo isset($d["nachname"]) ? $d["nachname"] : ""; ?>" <?php echo $required ? "required" : ""; ?>>

      <label for="nachname">Geburtsdatum</label>
      <input type="date" name="geboren" id="geboren" value="<?php echo isset($d["geboren"]) ? $d["geboren"] : ""; ?>" <?php echo $required ? "required" : ""; ?>>

      <label for="strasse">Straße Nr</label>
      <input type="text" name="strasse" id="strasse" value="<?php echo isset($d["strasse"]) ? $d["strasse"] : ""; ?>" <?php echo $required ? "required" : ""; ?>>

      <label for="plz">PLZ</label>
      <input type="tel" pattern="[0-9]*" name="plz" id="plz" maxlength="5" value="<?php echo isset($d["plz"]) ? $d["plz"] : ""; ?>" <?php echo $required ? "required" : ""; ?>>

      <label for="ort">Ort</label>
      <input type="text" name="ort" id="ort" value="<?php echo isset($d["ort"]) ? $d["ort"] : ""; ?>" <?php echo $required ? "required" : ""; ?>>

      <label for="email">E-Mail</label>
      <input type="email" name="email" id="email" value="<?php echo isset($d["email"]) ? $d["email"] : ""; ?>" <?php echo $required ? "required" : ""; ?>>

      <label for="telefon">Telefon</label>
      <input type="tel" name="telefon" id="telefon" value="<?php echo isset($d["telefon"]) ? $d["telefon"] : ""; ?>" <?php echo $required ? "required" : ""; ?>>

      <?php if($e){ ?>
      <label for="beigetreten">Beitrittsdatum</label>
      <input type="date" name="beigetreten" id="beigetreten" value="<?php echo isset($d["beigetreten"]) ? $d["beigetreten"] : ""; ?>">
    <?php }else{ ?>
      <p style="padding: 10px 0;"><input type="checkbox" name="datenschutz" id="datenschutz" required><label for="datenschutz" style="font-weight: 400;">
        Ich bin damit einverstanden, dass meine persönlichen Daten zur Bearbeitung meines Mitgliedsantrags im Eventverein Teamportal gespeichert werden.</label></p>
    <?php }?>
      <input type="hidden" name="id" value="<?php echo isset($d["id"]) ? $d["id"] : ""; ?>">
      <input type="submit" name="updateMember" value="<?php echo $btnTitle; ?>">
    </form>

    <?php
  }
}

 ?>
