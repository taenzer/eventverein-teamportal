<?php
require_once("../inc/belege.php");

class Transaction extends DB{
  private $data;


  function __construct($tid){
    parent::__construct();

    $sql = $this->mysqli->prepare("SELECT * FROM v_kontouebersicht WHERE typ = 'transaktion' AND tid = ? lIMIT 1");
    $sql->bind_param("i", $tid);
    $sql->execute();

    $result = $sql->get_result();
    $result = $result->fetch_all(MYSQLI_ASSOC);

    $this->data = $result[0];
  }

  public function isLoaded(){
    return isset($this->data);
  }

  public function getTid(){
    return $this->data["tid"];
  }

  public function getBeleg(){
    $beleg = new Beleg();
    if($beleg->getFromDb($this->data["beleg"])){
      return $beleg;
    }else{
      return false;
    }

  }

  public function update($newdata){

    $this->mysqli->begin_transaction();
    foreach ($newdata as $key => $value) {
      if($this->data[$key] != $value){
        $sql = $this->mysqli->prepare("UPDATE transaktionen SET $key = ? WHERE tid = ?");
        if($sql == false){
          return $this->mysqli->error;
        }
        $sql->bind_param("si", $value, $this->data["tid"]);
        $sql->execute();
        $this->data[$key] = $value;
      }
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
