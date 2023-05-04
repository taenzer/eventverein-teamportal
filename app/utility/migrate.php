<?php
$mysql = $db->getMySqli();
$file_to_read = fopen(__DIR__.'/../assets/eventverein_bartrans_export.csv', 'r');
if($file_to_read !== FALSE){

  $mysql->begin_transaction();
  try{
      while(($data = fgetcsv($file_to_read, 600, ';')) !== FALSE){
        $get = $mysql->prepare("SELECT kid FROM kontakte WHERE name = ?");
        $get->bind_param("s", $data[1]);
        $get->execute();
        $contact = $get->get_result();
        $contact = $contact->fetch_all(MYSQLI_ASSOC);

        if(count($contact) == 1){
          $contact = $contact[0]["kid"];
        }else if(count($contact) == 0){
          $create = $mysql->prepare("INSERT INTO kontakte (name) VALUES (?)");
          $create->bind_param("s", $data[1]);
          $create->execute();
          $contact = $mysql->insert_id;
        }else{
          echo("Error migrating Payment: ". $data[1] . " -> " . $data[2]."\n");
          continue;
        }
        var_dump($data[0]);
        $dt = new DateTime(preg_replace('/[^A-Za-z0-9.\-]/', '', $data[0]));
        $dt = $dt->format("Y-m-d H:i");
        $betrag = (double)filter_var(str_replace(".", "", $data[3]), FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        $betrag = $betrag/100;
        $betrag = str_replace(",", ".", $data[3]);
        var_dump($betrag);
        $insert = $mysql->prepare("INSERT INTO transaktionen (zeitstempel, nutzer, src, zweck, partner, betrag, beleg) VALUES (?,'22.0001','barkasse',?,?,?,?)");
        $insert->bind_param("sssds",
                            $dt,
                            $data[2],
                            $contact,
                            $betrag,
                            $data[4]);

        //$insert->execute();
        echo($mysql->error);
        echo("INSERT DONE! #".$mysql->insert_id."\n");
      }
      /* Wenn der Code bis hierher fehlerfrei ist, die Daten in der Datenbank bestätigen */
      $mysql->commit();
    } catch (mysqli_sql_exception $exception) {
      $mysql->rollback();

      throw $exception;
    }

    fclose($file_to_read);
}
?>
