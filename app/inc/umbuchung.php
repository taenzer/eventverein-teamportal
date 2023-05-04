<?php

/**
 *
 */
class Umbuchung extends DB
{

  function __construct($umbId = null){
    parent::__construct();

    if(isset($umbId)){
      $sql = $this->mysqli->prepare("SELECT * FROM umbuchungen WHERE umbId = ? LIMIT 1");
      $sql->bind_param("i", $umbId);
      $sql->execute();

      $result = $sql->get_result();
      $result = $result->fetch_all(MYSQLI_ASSOC);

      $this->data = $result[0];
      $this->exist = true;
    }else{
      $this->exist = false;
    }

  }

  public function isLoaded(){
    return isset($this->data);
  }

  public function getUmbId(){
    return $this->data["umbId"];
  }


  public function update($newdata){

    $this->mysqli->begin_transaction();

    if($this->exist){
      foreach ($newdata as $key => $value) {
        if($this->data[$key] != $value){
          $sql = $this->mysqli->prepare("UPDATE umbuchungen SET $key = ? WHERE tid = ?");
          if($sql == false){
            return $this->mysqli->error;
          }
          $sql->bind_param("si", $value, $this->data["tid"]);
          $sql->execute();
          $this->data[$key] = $value;
        }
      }
    }else{
      $nutzer = $_SESSION["user"];
      if($this->isDateLocked($newdata["zeitstempel"])){
        return false;
      }
      if(!isset($newdata["zeitstempel"], $newdata["quelle"], $newdata["ziel"], $newdata["betrag"], $newdata["partner"])){
        throw new \Exception("Umbuchung konnte nicht erzeugt werden. Grund: missing arguments", 1);
      }
      $sql = $this->mysqli->prepare("INSERT INTO umbuchungen (zeitstempel, quelle, ziel, betrag, partner, user) VALUES (?,?,?,?,?,(SELECT mnr FROM mitglieder WHERE login = ?))");
      $sql->bind_param("sssdss", $newdata["zeitstempel"], $newdata["quelle"], $newdata["ziel"], $newdata["betrag"], $newdata["partner"], $nutzer);
      $sql->execute();
    }

    $this->mysqli->commit();
    return true;

  }

  public function get($what){
    switch ($what) {
      case 'zeitstempel':
        $ret = new DateTime($this->data["zeitstempel"]);
        $ret = $ret->format("d.m.Y");
        return $ret;
        break;
      case 'zeitstempelRaw':
        return $this->data["zeitstempel"];

      default:
        return isset($this->data[$what]) ? $this->data[$what] : "";
        break;
    }
  }
}


 ?>
