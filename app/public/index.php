<?php
require_once("../inc/bootstrap.php");

define("URL_BASE", "/");
define("PATH_PAGES", __DIR__ . DIRECTORY_SEPARATOR . "pages" . DIRECTORY_SEPARATOR);
define("PUBLIC_PAGES", array("login", "activate", "logout", "404", "join"));

$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);

if (substr($path, 0, strlen(URL_BASE)) == URL_BASE) {
  $path = substr($path, strlen(URL_BASE));
}
$path = explode("/", rtrim($path, "/\\"));
if (count($path)==1) {
  $file = $path[0]=="" ? "index.php" : $path[0] . ".php";
} else {
  $file = implode("-", $path) . ".php";
}

if($file == "ajax.php"){
  require PATH_PAGES . $file;
  exit();
}


ob_start();

if(file_exists(PATH_PAGES . $file)){
  if(!in_array($path[0], PUBLIC_PAGES)){
    $auth->check();
  }
  $messagesHtml = $lan->printMessages();
  require PATH_PAGES . $file;
}else{
  http_response_code(404);
  $messagesHtml = $lan->printMessages();
  require PATH_PAGES . "404.php";
}

$content = ob_get_clean();



?>
<!DOCTYPE html>
<html lang="de" dir="ltr">
  <head>
    <!-- Meta -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eventverein Teamportal<?php echo(defined("PAGE_TITLE") ? " | ".PAGE_TITLE : ""); ?></title>

    <!-- Includes -->
    <link rel="stylesheet" href="/assets/css/master.css">
    <link rel="stylesheet" href="/assets/css/fonts.css">
    <script type="text/javascript" src="/assets/js/global.js"></script>

  </head>
  <body>
    <!-- Mobile Menu Trigger -->
    <div id="mobile-menu-trigger">
      <span class="icon open">menu</span>
      <span class="icon close">close</span>
    </div>
    <!-- Grid Wrapper -->
    <div class="wrapper">
      <section id="menu">
        <a href="/" title="Startseite"><img src="/assets/img/logo-light.png" alt="Logo Eventverein"></a>
        <?php if(!defined("HIDE_MENU") || HIDE_MENU == false){ ?>
        <nav>
          <ul>
            <?php
            $items = $auth->isUserLoggedIn() ? (defined("MENU_ITEMS_LOGGED_IN") ? MENU_ITEMS_LOGGED_IN : array()) : (defined("MENU_ITEMS_PUBLIC") ? MENU_ITEMS_PUBLIC : array());
            foreach ($items as $group_title => $group_items) {
              echo("<li class='menu-section-head'>$group_title</li>");
              foreach ($group_items as $item) {
                echo("<a class='nav-item' href='".$item["location"]."' title='".$item["text"]."'><li><span class='icon'>apps</span>".$item["text"]."</li></a>");
              }

            }

            ?>
          </ul>
        </nav>
      <?php } ?>
      </section>

      <section id="bottom">
        <h1 class="seo">Eventverein Tambach-Dietharz e.V.</h1>
        <h2>Teamportal</h2>
        <?php echo(defined("PAGE_TITLE") ? "<h3>" . PAGE_TITLE . "</h3>" : ""); ?>

        <div class="account-icon">
          <a href="/logout" title="Abmelden"><span class='icon outlined'>account_circle</span></a>
        </div>
      </section>

      <!-- Main Content -->
      <section id="content">
        <div class="notifications">
          <?php echo($messagesHtml); ?>
        </div>
        <?php  echo($content);  ?>
      </section>

    </div>
  </body>
</html>
