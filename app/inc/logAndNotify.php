<?php

/**
 *
 */
class LogAndNotify
{

  function __construct(){
    if(!isset($_SESSION["msg"])){
      $_SESSION["msg"] = array();
    }
  }

  public function printMessages( $echo = false, $limit = -1){
    ob_start();
    foreach ($_SESSION["msg"] as $id => $message) { ?>
      <div class="notification <?php echo $message["type"]; ?>" data-id="<?php echo $id; ?>" onclick="this.style.display = 'none'; ">
        <div class="icon-wrp">
          <span class="icon error">crisis_alert</span>
          <span class="icon info">info</span>
          <span class="icon success">task_alt</span>
        </div>
        <div class="notification-content">
          <p><?php echo $message["msg"]; ?></p>
        </div>
      </div>
    <?php
    if($message["forceClose"] == false){
      $_SESSION["msg"][$id]["printed"]++;
      unset($_SESSION["msg"][$id]);
    }
    }
    $buffered = ob_get_clean();
    if($echo){
      echo $buffered;
      return;
    }else{
      return $buffered;
    }
  }

  public function addError($msg, $forceClose = false){
    $this->addToSessionStorage("error", $msg, $forceClose);
  }
  public function addInfo($msg, $forceClose = false){
    $this->addToSessionStorage("info", $msg, $forceClose);
  }
  public function addSuccess($msg, $forceClose = false){
    $this->addToSessionStorage("success", $msg, $forceClose);
  }

  private function addToSessionStorage($type, $msg, $forceClose){
    $id = uniqid();
    $protection = 100;
    while(isset($_SESSION["msg"][$id]) && $protection > 0){
      $id = uniqid();
    }
    $_SESSION["msg"][$id] = array(
      "type" => $type,
      "msg" => $msg,
      "printed" => 0,
      "forceClose" => $forceClose,
      "created" => date("Y-m-d H:i:s")
    );
  }
}


 ?>
