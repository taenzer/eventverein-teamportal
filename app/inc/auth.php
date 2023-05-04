<?php
class Auth{

  private $mysqli;
  private $user = "admin";
  private $pass = "$2y$10$7NjEYMjrtBLdgqWCwYRt3ujpQIZ2rDZVyo1DFpios6WakNea.wWem";
  private $noredir = array("/", "/login", "/logout");

  function __construct($mysqli){
    session_start();
    $this->mysqli = $mysqli;
  }

  public function check(){
    if(!$this->isUserLoggedIn()){
      $redirect = $_SERVER['REQUEST_URI'];
      $redirect = !in_array($redirect, $this->noredir) ? $redirect : "";
      $this->goToLogin($redirect);
    }
  }

  public function goToLogin($redTo = ""){
    $header = "Location: /login";
    if(!empty($redTo)){
      $header.= "?".http_build_query(array("redirect_to" => $redTo));
    }
    header($header);
    die();
  }


  public function login($user, $passw){
    if($this->isUserLoggedIn()){
      return false;
    }

    if($this->checkPassword($user, $passw)){
      $this->newSession($user);
      return true;
    }else{
      return false;
    }
  }

  public function checkPassword($user, $passw){
    $sql = $this->mysqli->prepare("SELECT passwort, mnr FROM mitglieder WHERE login = ? LIMIT 1");
    $sql->bind_param("s", $user);
    $sql->execute();
    $result = $sql->get_result();
    $result = $result->fetch_all(MYSQLI_ASSOC);

    if(count($result) != 1){
      return false;
    }

    $mnr = $result[0]["mnr"];

    // Salt & Hash Password
    $passw .= $mnr."€VTI)";
    $passw = hash("sha512", $passw);

    return $passw === $result[0]["passwort"];
  }

  public function logout(){
    $this->clearSession();
  }

  public function activate($mnr, $login, $password){
    if($this->isUserLoggedIn()){
      return false;
    }
    // Check if User exists and is not activated already
    $sql = $this->mysqli->prepare("SELECT * FROM mitglieder WHERE mnr = ? AND passwort IS NULL");
    $sql->bind_param("s", $mnr);
    $sql->execute();
    $result = $sql->get_result();
    $result = $result->fetch_all(MYSQLI_ASSOC);

    if(count($result) != 1){
      return false;
    }

    // Check if username already exists
    if(!$this->checkUsername($login)){
      return false;
    }

    // Salt & Hash Password
    $password .= $mnr."€VTI)";
    $password = hash("sha512", $password);

    // Store Password in Database
    return $this->updateDbUser($mnr, array("login" => $login, "passwort" => $password));

  }

  private function updateDbUser($mnr, $args){

    $this->mysqli->begin_transaction();
    foreach ($args as $key => $value) {
      $sql = $this->mysqli->prepare("UPDATE mitglieder SET $key = ? WHERE mnr = ?");
      $sql->bind_param("ss", $value, $mnr);
      $sql->execute();
    }
    $this->mysqli->commit();

  }

  /*
  * Checks if a username is already in use.
  * @return true if username can be used, false if not
  */
  private function checkUsername($uname){
    $sql = $this->mysqli->prepare("SELECT login FROM mitglieder WHERE login = ?");
    $sql->bind_param("s", $uname);
    $sql->execute();

    $result = $sql->get_result();
    $result = $result->fetch_all(MYSQLI_ASSOC);

    if(count($result) != 0){
      return false;
    }else{
      return true;
    }
  }


  public function isUserLoggedIn(){

    $sid = isset($_SESSION["sid"]) ? $_SESSION["sid"] : false;
    $skey = isset($_SESSION["skey"]) ? $_SESSION["skey"] : false;

    if($sid === false || $skey === false){
      $sid = isset($_COOKIE["evtd-session-sid"]) ? $_COOKIE["evtd-session-sid"] : false;
      $skey = isset($_COOKIE["evtd-session-skey"]) ? $_COOKIE["evtd-session-skey"] : false;
    }

    if($sid === false || $skey === false){
      return false;
    }
    return $this->checkSession($sid, $skey);
  }


  private function newSession($user){
    $this->clearSession();
    $skey = hash('sha256', uniqid()."evtd#4X01bfmI%&#8");
    $sid = $this->createLoginSession($skey, $user);

    if($sid === false){
      new Exception("Session Error: New Session could not be initialized.");
    }
    $this->updateSessionData($sid, $skey, $user);
  }

  private function clearSession(){
    $this->updateSessionData("","","");
    return true;
  }

  private function checkSession($sid, $skey){
    $session = $this->checkLoginSession($sid, $skey);

    if($session !== false){
      if(!headers_sent()){
        $this->updateSessionData($sid, $skey, $session);
      }
      return true;
    }else{
      return false;
    }
  }

  private function updateSessionData($sid, $skey, $uid){
    $_SESSION["sid"] = $sid;
    $_SESSION["skey"] = $skey;
    $_SESSION["user"] = $uid;

    setcookie("evtd-session-sid", $sid, time()+60*60*24*30, "/");
    setcookie("evtd-session-skey", $skey, time()+60*60*24*30, "/");
  }

  private function getSessionData(){
    $data = array(
      "sid" => $_SESSION["sid"],
      "skey" => $_SESSION["skey"],
      "user" => $_SESSION["user"]
    );
    return $data;
  }


    public function createLoginSession($skey, $user){
      $sql = $this->mysqli->prepare("INSERT INTO login_sessions (skey, user, expires) VALUES (?,?,?)");
      $exp = new DateTime();
      $exp->modify("+30 days");
      $exp = $exp->format("Y-m-d H:i");
      $sql->bind_param("sss", $skey, $user, $exp);
      $sql->execute();
      if($sql->errno == 0){
        return $this->mysqli->insert_id;
      }else{
        return false;
      }
    }

    public function checkLoginSession($sid, $skey){
      $sql = $this->mysqli->prepare("SELECT user FROM login_sessions WHERE sid = ? AND skey = ? and expires > NOW()");
      $sql->bind_param("is", $sid, $skey);
      $sql->execute();
      $result = $sql->get_result();
      $result = $result->fetch_all(MYSQLI_ASSOC);

      if(count($result) == 1){
        return $result[0]["user"];
      }else{
        return false;
      }
    }



    public function getLoggedInUser(){
      $sessionData = $this->getSessionData();
      $sid = $sessionData["sid"];
      $skey = $sessionData["skey"];

      $sql = $this->mysqli->prepare("SELECT mnr, vorname, nachname, status FROM mitglieder WHERE login = (SELECT user FROM login_sessions WHERE sid = ? AND skey = ? AND expires > NOW())");
      $sql->bind_param("is", $sid, $skey);
      $sql->execute();
      $result = $sql->get_result();
      $result = $result->fetch_all(MYSQLI_ASSOC);


      return isset($result[0]) ? $result[0] : false;

    }



}

 ?>
