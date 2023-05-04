<?php
define("PAGE_TITLE", "Mitglied werden");


require_once("../inc/mitglieder.php");
$m = new Mitglied();

if(isset($_POST["updateMember"])){
  if(empty($_POST["vorname"]) || empty($_POST["nachname"]) || empty($_POST["geboren"])
      || empty($_POST["strasse"]) || empty($_POST["plz"]) || empty($_POST["ort"])
      || empty($_POST["email"]) || empty($_POST["telefon"])){
    echo "<p style='background: red; color: white'>Nicht alle erforderlichen Felder wurden ausgefüllt!</p>";
  }else{
    unset($_POST["updateMember"]);
    if(isset($_POST["datenschutz"])){ unset($_POST["datenschutz"]); }
    $m->update($_POST);
    header("Location: /join?status=success&memid=".$m->getId()."&secret=".Mitglied::generateMemberSecret($m->getId()));
    exit;
  }
}
$status = isset($_GET["status"]) ? $_GET["status"] : "";
?><link rel="stylesheet" href="/assets/css/mitglieder.css"> <?php
switch ($status) {
  case 'start':
    if(!$m->doesMemberExist()){
      ?><div class="join-wrap">
        <h1>Online-Antrag auf Mitgliedschaft</h1>
        <h2>im Eventverein Tambach-Dietharz e.V.</h2>
        <div class="message">
          <p>Danke für dein Interesse am Beitritt zum Eventverein! Wir benötigen zuerst einige Daten von dir:</p>
        </div>

      <?php
      $m->displayForm(true, "Weiter");
      ?></div><?php
    }else{
      header("Location: /join");
    }
    break;
  case 'success':
    if(isset($_GET["memid"], $_GET["secret"]) && $_GET["secret"] == Mitglied::generateMemberSecret($_GET["memid"])){
    ?>
    <div class="join-wrap">
      <h1>Online-Antrag auf Mitgliedschaft</h1>
      <h2>im Eventverein Tambach-Dietharz e.V.</h2>
      <div class="message">
        <p>Wichtig: dein Antrag auf Mitgliedschaft kann von uns aus rechtlichen Gründen
          erst bearbeitet werden, wenn er unterschrieben bei uns eingegangen ist.
          Ist das nach 4 Wochen noch nicht geschehen wird der Antrag sowie deine
          persönlichen Daten automatisch gelöscht.</p>
      </div>
      <div class="document-view">
        <div class="doc-preview">
          <iframe src="/mitgliedsantrag.php?fid=<?php echo $_GET["memid"]; ?>&secret=<?php echo $_GET["secret"]; ?>#view=FitV&scrollbar=0" width="100%" title="Document Preview" heigth="500px"></iframe>
        </div>
        <div class="info">
          <p><span class="icon done">check</span></p>
          <p>Deine Daten wurden erfolgreich gespeichert! <a href="mitgliedsantrag.php?fid=<?php echo $_GET["memid"]; ?>&secret=<?php echo $_GET["secret"]; ?>&forceDl" target="_blank" title="Antrag herunterladen">
            Lade dir jetzt deinen vorausgefüllten Mitgliedsantrag herunter</a>, ergänze fehlende Angaben und sende ihn unterschrieben inkl. aller Anhänge an:</p>
          <p class="address">Eventverein Tambach-Dietharz e.V.<br>Kirchstraße 32<br>99897 Tambach-Dietharz</p>
          <p><a class="button" href="mitgliedsantrag.php?fid=<?php echo $_GET["memid"]; ?>&secret=<?php echo $_GET["secret"]; ?>&forceDl" target="_blank" title="Antrag herunterladen"><span class="icon">download</span> Antrag herunterladen</a></p>
        </div>
      </div>


    </div>

    <?php
    }else{
      header("Location: /join");
    }
    break;
  default:
    ?>
    <div class="join-wrap">
      <img src="/assets/img/23-01-join-header.png" alt="Welcome Header" class="header">
      <div class="anforderungen">
        <div class="anforderung">
          <h2>Du bist...</h2>
          <p>
            <ul>
              <li>freundlich, hilfsbereit und respektvoll anderen gegenüber</li>
              <li>nett im Umgang mit deinen Mitmenschen</li>
              <li>ein Teamplayer</li>
              <li>mindestens 16 Jahre alt</li>
            </ul>
          </p>
        </div>
        <div class="anforderung">
          <h2>Du hast Lust...</h2>
          <p>
            <ul>
              <li>die Tambach-Dietharzer Veranstaltungs-Szene zu revolutionieren</li>
              <li>anzupacken, wenn alle anderen sich amüsieren</li>
              <li>auch an Wochenenden, Feiertagen und bei schlechtem Wetter zu ackern</li>
            </ul>
          </p>
        </div>
      </div>
      <div class="join-now">
        <h2>Dann trete jetzt dem Eventverein Tambach-Dietharz e.V. bei!</h2>
        <a href="/join?status=start" class="button"><span class="icon">login</span> Jetzt beitreten</a>
      </div>

      <p class="before-join"><strong>Wichtige Hinweise vor deinem Beitritt:</strong> Du solltest
        dir unbedingt unsere <a href="https://eventverein.de/satzung.pdf"
        title="Satzung downloaden" target="_blank" style="border-bottom: 1px dotted;">Satzung</a>
        durchlesen. Darin steht zum Beispiel, dass Mitglieder einen jährlichen Mitgliedsbeitrag zu
        entrichten haben. Die Höhe des Beitrags wurde in einer Mitgliederversammlung
        festgelegt und beträgt aktuell 40,00€. Außerdem ist in der Satzung
        verankert, dass Vereinsmitglieder verpflichtet sind, bei der Ausrichtung
        unserer Veranstaltungen zu helfen.</p>


    </div>

    <?php
    break;
}

?>
