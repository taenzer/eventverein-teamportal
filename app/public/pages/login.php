<?php

require_once("../inc/bootstrap.php");

define("PAGE_TITLE", "Login");

if(!$auth->isUserLoggedIn() && isset($_POST["login"], $_POST["username"], $_POST["pass"])){
  $auth->login($_POST["username"], $_POST["pass"]);
}


if($auth->isUserLoggedIn()){

  if(isset($_GET["redirect_to"])){
    header('Location: '.$_GET["redirect_to"]);
    die();
  }else if(isset($_POST["redirect_to"]) && !empty($_POST["redirect_to"])) {
    header('Location: '.$_POST["redirect_to"]);
    die();
  }
  header('Location: /');

  die();
}

 ?>
    <div class="login-wrp">
      <div class="content">
        <form class="login" method="post">

          <label for="user">Benutzername</label>
          <input type="text" name="username" id="username" value="" required>
          <label for="pass">Passwort</label>
          <input type="password" name="pass" id="pass" value="" required>
          <input type="submit" name="login" value="Anmelden">

          <input type="hidden" name="redirect_to" value="<?php echo isset($_GET["redirect_to"]) ? $_GET["redirect_to"] : ""; ?>">
        </form>
      </div>
</div>
