<?php

/**
 *
 */
class Beleg extends DB
{

  private $file;
  private $name;
  private $fileType;
  private $path;
  private $bid = -1;
  public $error = array();

  function __construct(){
    parent::__construct();
  }
  public function getBid(){
    return $this->bid;
  }
  public function getName(){
    return isset($this->name) ? $this->name : "";
  }

  public function getDateUploaded($formatted = false){
    if(!isset($this->date)){
      return "";
    }
    $date = $this->date;

    if($formatted){
      $date = new DateTime($date);
      $date = $date->format("d.m.Y");
    }
    return $date;
  }

  public function render($dl = false){
    if($this->fileType == "pdf"){
      header("Content-type: application/pdf");
    }else if($this->fileType == "jpg"){
      header("Content-type: image/jpeg");
    }else if($this->fileType == "png"){
      header("Content-type: image/png");
    }
    if($dl == "true"){
      header('Content-Disposition: attachement; filename="'.$this->name.'.'.$this->fileType.'"');
    }else{
      header('Content-Disposition: inline; filename="'.$this->name.'.'.$this->fileType.'"');
    }

    readfile(dirname(__DIR__)."/storage".$this->path);
    exit();
  }

  public function getFromDb($bid){
    $sql = $this->mysqli->prepare("SELECT * FROM belege WHERE bid = ? LIMIT 1");
    $sql->bind_param("i", $bid);
    $sql->execute();

    $result = $sql->get_result();
    $result = $result->fetch_all(MYSQLI_ASSOC);

    if(count($result) == 1){
      $result = $result[0];
      $this->bid = $result["bid"];
      $this->fileType = $result["dateityp"];
      $this->path = $result["pfad"];
      $this->name = $result["name"];
      $this->date = $result["hochgeladen"];
      return true;
    }else{
      return false;
    }

  }

  public function upload($file, $name = ""){
    $this->file = $file;
    $this->name = $name !== "" ? $name : basename($file["name"]);
    if($this->validateUpload()){
      $this->bid = $this->prepareDatabase();
      $short_path = $this->generatePath();
      $full_path = dirname(__DIR__)."/storage".$short_path;

      if(!is_dir($full_path)){
        mkdir($full_path, 0777, true);
      }

      $filename = $this->generateFileName();

      if (move_uploaded_file($file["tmp_name"], $full_path.$filename)) {
        $this->updateDbPath($short_path.$filename);
      }else{
        $this->error[] = "Datei konnte nicht ins Archiv verschoben werden";
        echo dirname(__DIR__).$path;
      }
    }
  }

  private function updateDbPath($path){
    $sql = $this->mysqli->prepare("UPDATE belege SET pfad = ? WHERE bid = ?");
    $sql->bind_param("si", $path, $this->bid);
    $sql->execute();
  }

  private function prepareDatabase(){
    if($this->bid != -1){
      return $this->bid;
    }

    $sql = $this->mysqli->prepare("INSERT INTO belege (name, dateityp) VALUES (?,?)");
    $sql->bind_param("ss", $this->name, $this->fileType);
    $sql->execute();

    return $this->mysqli->insert_id;
  }

  private function generatePath(){
    $path = "/belege/".date("Y/m")."/";
    return $path;
  }

  private function generateFileName(){
    $name = "B-".date("Y-m-d");
    $name .= "-".$this->bid;
    $name .= "-".substr(uniqid(), -4);
    $name .= ".".$this->fileType;
    return $name;
  }

  private function validateUpload(){
    return $this->checkSize() && $this->checkFileType();
  }

  private function checkSize(){
    $mb = 2;
    if($this->file["size"] > $mb * 1000000){
      $this->error[] = "Die Datei ist zu groß (".$this->file["size"].")";
      return false;
    }
    return true;
  }

  private function checkFileType(){
    $imageFileType = strtolower(pathinfo($this->file["name"],PATHINFO_EXTENSION));
    $this->fileType = $imageFileType;
    if($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg"
      && $imageFileType != "pdf" ) {
      $this->error[] = "Ungültiges Dateiformat";
      return false;
    }
    return true;
  }
}


 ?>
