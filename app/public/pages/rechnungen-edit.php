<?php
define("PAGE_TITLE", "Rechnung bearbeiten");
require_once("../inc/class-rechnung.php");

$rechnung = new Rechnung(isset($_GET["rid"]) ? $_GET["rid"] : false);
if(!empty($_POST)){
  $rechnung->formAction($_POST);
}

$lock = $rechnung->isLocked();

?>
 <script src="https://code.jquery.com/jquery-1.12.4.js"></script>
 <script src="https://code.jquery.com/ui/1.12.1/jquery-ui.js"></script>
 <script src="/assets/js/partnerselect.js"></script>
 <script src="/assets/js/rechnungen.js"></script>

 <link rel="stylesheet" href="/assets/css/rechnungen.css">
<h1>Rechnung bearbeiten</h1>
<form class="<?php echo $rechnung->getFormClasses(array("re-form")); ?>" method="post">

  <div class="re-actions">
    <input type="submit" class="re-action save_draft" name="save_draft" value="Entwurf speichern">
    <input type="submit" class="re-action pdf_preview" name="pdf_preview" value="Vorschau">
    <input type="submit" class="re-action delete_invoice" name="delete_invoice" value="Rechnung löschen">
    <input type="submit" class="re-action publish" name="publish" value="Fertigstellen">
    <input type="submit" class="re-action download" name="download" value="Herunterladen">
    <input type="submit" class="re-action create_transaction" name="create_transaction" value="Einnahme erfassen">
  </div>
  <?php echo $rechnung->getStatusHtml(); ?>
  <div class="re-body">
    <div class="re-data">
      <div class="partner">
        <label for="partner">Kunde</label>
        <div class="partner-input ui-front">
          <?php $kontakt = $rechnung->getPartner($rechnung->get("kontaktId")); ?>
          <input type="text" name="kontakt" data-id="" id="kontakt" value="<?php echo isset($kontakt["name"]) ? $kontakt["name"] : ""; ?>" required <?php echo $lock ? "disabled" : ""; ?>>
          <input type="hidden" id="kontaktId" name="kontaktId" value="<?php echo $rechnung->get("kontaktId"); ?>">
        </div>
        <label for="anschrift">Anschrift</label>
        <textarea name="kontaktAdr" rows="8" cols="80" <?php echo $lock ? "disabled" : ""; ?>><?php echo $rechnung->get("kontaktAdr"); ?></textarea>
      </div>

      <div class="additionals">
        <div class="form-row">
          <label for="betreff">Betreff</label>
          <input type="text" name="betreff" value="<?php echo $rechnung->get("betreff"); ?>" required <?php echo $lock ? "disabled" : ""; ?>>
        </div>
        <div class="form-row">
          <label for="dtRechnung">Rechnungsdatum</label>
          <input type="date" name="dtRechnung" value="<?php echo $rechnung->get("dtRechnung"); ?>" required <?php echo $lock ? "disabled" : ""; ?>>
        </div>
        <div class="form-row">
          <label for="tageZahlungsziel">Zahlungsziel</label>
          <p>

            <input type="number" min="0" max="365" name="tageZahlungsziel" value="<?php echo $rechnung->get("tageZahlungsziel"); ?>" <?php echo $lock ? "disabled" : ""; ?>>
            <input type="text" value="Tage"  style="width: 4em;" disabled>
            <input type="date" name="dtZahlungsziel" value="<?php echo $rechnung->get("dtZahlungsziel"); ?>" required <?php echo $lock ? "disabled" : ""; ?>>
          </p>

        </div>
        <div class="form-row">
          <label for="ansprechpartner">Ansprechpartner</label>
          <div class="partner-input ui-front">
            <?php $ap = $rechnung->getPartner($rechnung->get("ansprechpartnerId")); ?>
            <input type="text" name="ansprechpartner" data-id="" id="ansprechpartner" value="<?php echo isset($ap["name"]) ? $ap["name"] : ""; ?>" required <?php echo $lock ? "disabled" : ""; ?>>
            <input type="hidden" id="ansprechpartnerId" name="ansprechpartnerId" value="<?php echo $rechnung->get("ansprechpartnerId"); ?>">
          </div>
        </div>
      </div>
    </div>

    <div class="re-pos">
      <h2>Positionen</h2>
      <div id="posWrap">
        <?php
        $positionen = $rechnung->get("positionen");
        foreach ($positionen as $id => $pos) {
          echo $rechnung->getPosHtml($id, $pos, $lock);
        }
         ?>
      </div>
       <div class="pos pos-neu" id="posNeu">
         <p>+ Neue Position</p>
       </div>
    </div>
    <?php
    $totals = $rechnung->getTotals();

    ?>
    <div class="re-summary">
      <table>
        <tr class="sum">
          <td class="sum-title">Summe Netto</td>
          <td class="sum-value"><?php echo $rechnung->euro($totals["sumNetto"]); ?></td>
        </tr>
        <?php if(isset($totals[7])){ ?>
        <tr class="sum tax">
          <td class="sum-title">zzgl. 7% MwSt.</td>
          <td class="sum-value"><?php echo $rechnung->euro($totals[7]["steuer"]); ?></td>
        </tr>
      <?php }
      if(isset($totals[19])){ ?>
        <tr class="sum tax">
          <td class="sum-title">zzgl. 19% MwSt.</td>
          <td class="sum-value"><?php echo $rechnung->euro($totals[19]["steuer"]); ?></td>
        </tr>
      <?php } ?>
        <tr class="sum">
          <td class="sum-title">Summe Brutto</td>
          <td class="sum-value"><?php echo $rechnung->euro($totals["sumBrutto"]); ?></td>
        </tr>
      </table>
    </div>
  </div>
</form>
