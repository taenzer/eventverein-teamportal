<?php

/**
 *
 */
class EventvereinCI {

  public static function standardBrief($inner, $title = "Nachricht", $empf = "An alle Mitglieder des\nEventvereins", $before = ""){
    ob_start();
    $title = strlen($title) > 25 ? substr($title,0,25)."..." : $title;
    $empf = nl2br($empf, false);
    $empfCount = substr_count($empf, "<br>");
    ?>

    <head>
      <style>
        @page{
          margin: 50mm 17.5mm 30mm 17.5mm;
          font-family: "Helvetica", sans-serif;
          font-size: 14px;
        }
        body{
          position: relative;
        }

        img.docLogo{
          position: fixed;
          top: -27mm;
          right: -5mm;
          width: auto;
          height: 25mm;
          z-index: 10;
        }

        header{
          position: absolute;
          top: -50mm;
          left: -25mm;
          width: 100%;
          background: #dedede;
          padding: 25mm 25mm 0 25mm;
        }
        header img.briefkopf{
          position: absolute;
          top: 5mm;
          left: 0;
          width: 100%;
          height: auto;
        }
        header h1, header .pretitle{
          position: relative;
          z-index: 2;
          margin: 0;
        }
        header .pretitle{
          font-size: 0.8em;
          letter-spacing: 2px;
          font-style: italic;
        }
        header h1{
          margin: 10px 0;
          text-transform: uppercase;
        }
        header .empfaenger{
          position: relative;
          z-index: 2;
          display: inline-block;
          width: 80mm;
          margin: 8mm 0 0;
        }
        header .empfaenger p{
          margin: 0;
        }
        header .empfaenger .absender{
          font-size: 0.7em;
        }

        header .empfaenger .empf{
          margin: 15px 0 0 0;
        }

        footer{
          position: fixed;
          bottom: -30mm;
          left: 0;
          display: flex;
          font-size: 0.75em;
          width: 100%;
          padding: 2mm 0 10mm;
          border-top: 1px solid;
          color: #575757;
        }
        footer table{
          width: 100%;
        }
        footer table td{
          vertical-align: top;
        }

        footer p{
          margin: 0;

        }

        .page{
          text-align: right;
          margin-top: 1em;
          font-style: italic;
        }

        .content{
          margin: <?php echo $empfCount > 2 ? 45 + (3 * ($empfCount - 2)) : 45 ?>mm 0 0;
          position: relative;
          z-index: 2;
        }

        .marken{
          position: fixed;
          top: -50mm;
          left: -17.5mm;
          bottom: -30mm;
          width: 5mm;
        }

        .marken .falztop{
          width: 70%;
          height: 0.5mm;
          background: #575757;
          position: absolute;
          top: 87mm;
          left: 0;
        }
        .marken .falzbottom{
          width: 70%;
          height: 0.5mm;
          background: #575757;
          position: absolute;
          top: 192mm;
          left: 0;
        }
        .marken .loch{
          width: 100%;
          height: 0.5mm;
          background: #575757;
          position: absolute;
          top: 148.5mm;
          left: 0;
        }
      </style>

    </head>
    <body>
        <img src="https://team.eventverein.de/assets/img/logo-dark.png" alt="logo" class="docLogo">
        <header>
          <img src="https://team.eventverein.de/assets/img/briefkopf-hintergrund.png" alt="briefkopf" class="briefkopf">
          <p class="pretitle">EVENTVEREIN TAMBACH-DIETHARZ E.V.</p>
          <h1><?php echo $title; ?></h1>
          <div class="empfaenger">
            <p class="absender">Abs: Eventverein Tambach-Dietharz e.V., Kirchstraße 32, 99897 Tambach-Dietharz</p>
            <p class="empf"><?php echo $empf; ?></p>
          </div>
        </header>
        <section class="marken">
          <div class="falztop"></div>
          <div class="loch"></div>
          <div class="falzbottom"></div>
        </section>
        <footer>
          <table>
            <tr>
              <td class="col">
                <p><strong>Eventverein Tambach-Dietharz e.V.</strong></p>
                <p>vertreten durch Moritz Tanz, Julius Zink</p>
                <p>Kirchstraße 32, 99897 Tambach-Dietharz</p>
                <p>VR 141637 (Amtsgericht Gotha)</p>
              </td>
              <td class="col">
                <p><strong>Bankverbindung:</strong></p>
                <p>VR Bank Westthüringen eG</p>
                <p>IBAN: DE52 8206 4038 0000 3435 28</p>
                <p>BIC: GENODEF1MU2</p>
              </td>
              <td class="col">
                <p><strong>Ust-Id:</strong> DE357861782</p>
                <p><strong>Steuer-Nr:</strong> 156/140/01973</p>

              </td>
            </tr>
          </table>
        </footer>
        <?php echo $before; ?>
        <div class="content">
          <?php echo $inner; ?>
        </div>

        <script type="text/php">
          if ( isset($pdf) ) {
              $x = 509;
              $y = 799;
              $text = "Seite {PAGE_NUM}/{PAGE_COUNT}";
              $font = $fontMetrics->get_font("helvetica", "italic");
              $size = 9;
              $color = array(0.33725, 0.33725, 0.33725);
              $word_space = 0.0;  //  default
              $char_space = 0.0;  //  default
              $angle = 0.0;   //  default
              $pdf->page_text($x, $y, $text, $font, $size, $color, $word_space, $char_space, $angle);
          }
      </script>
    </body>
    <?php
    return ob_get_clean();
  }
}

 ?>
