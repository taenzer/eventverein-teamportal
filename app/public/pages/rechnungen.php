<?php
require_once("../inc/class-rechnung.php");
define("PAGE_TITLE", "Rechnungen");

 ?>
<h1>Rechnungen</h1>
<a href="/rechnungen/edit" class="button">Neue erstellen</a>
<?php $rechnungen = new Rechnungen();
$rechnungen->display();
 ?>
