<?php define("PAGE_TITLE", "Inventar");
require_once("../inc/inventar.php");
$i = new InventarVerwaltung();
?>
<!-- Style Sheet -->
<link rel="stylesheet" href="/assets/css/inventar.css">

<h1>Inventar</h1>
<a href="/inventar/neu" class="inv-new">Neuer Gegenstand</a>

<?php $i->print(); ?>
