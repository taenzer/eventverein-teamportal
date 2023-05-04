<?php

require_once("../inc/bootstrap.php");

define("PAGE_TITLE", "Account Aktivierung");

if(!$auth->isUserLoggedIn() && isset($_POST["activate"], $_POST["login"], $_POST["email"], $_POST["mnr"], $_POST["pass"], $_POST["pass2"], $_POST["key"])){

  if(hash("sha512", $_POST["mnr"].$_POST["email"]) == $_POST["key"]){
    if($_POST["pass"] === $_POST["pass2"]){
      var_dump($auth->activate($_POST["mnr"], $_POST["login"], $_POST["pass"]));
    }else{
      echo "pass not mathc";
    }

  }else{
    echo "key wrong";
  }

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
<!DOCTYPE html>
<html lang="de" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title>ACCOUNT AKTIVIERUNG | EVENTVEREIN TAMBACH-DIETHARZ</title>

  </head>
  <body class="login">
    <div class="login-wrp">
      <div class="content">
        <form class="login" method="post">
          <h1>Account Aktivierung</h1>
          <p class="welcome" style="margin: 20px 0;">Hallo {{Vorname}}! Aktiviere jetzt deinen Account um das Eventverein Teamportal nutzen zu können.</p>

          <p><label for="login">Benutzername</label><br>
          <input type="text" name="login" id="login" maxlength="15" value="" required></p>

          <p><label for="pass">Passwort</label><br>
          <input type="password" name="pass" id="pass" value="" required></p>

          <p><label for="pass">Passwort wiederholen</label><br>
          <input type="password" name="pass2" id="pass2" value="" required></p>

          <input type="submit" name="activate" value="Konto aktivieren">

          <input type="hidden" name="key" value="<?php echo isset($_GET["key"]) ? $_GET["key"] : ""; ?>">
          <input type="hidden" name="mnr"  value="<?php echo isset($_GET["mnr"]) ? $_GET["mnr"] : ""; ?>">
          <input type="hidden" name="email"  value="<?php echo isset($_GET["email"]) ? $_GET["email"] : ""; ?>">
        </form>
      </div>

    </div>
  </body>
</html>
