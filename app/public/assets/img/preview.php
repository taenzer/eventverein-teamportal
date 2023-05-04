<?php

$base = "/storage";

if(!isset($_GET["id"], $_GET["type"])){
  $folder = "/bilder/default/";
  $file = "placeholder.jpg";
}else{
  switch ($_GET["type"]) {
    case 'inventar':
      $id = $inr = str_pad($_GET["id"], 4, "0", STR_PAD_LEFT);
      $folder = "/bilder/inventar/";
      $file = "item-".$id.".jpg";
      break;

    default:
      $folder = "/bilder/default/";
      $file = "placeholder.jpg";
      break;
  }
}


if(!file_exists($_SERVER["DOCUMENT_ROOT"].$base.$folder.$file)){
  $folder = "/bilder/default/";
  $file = "placeholder.jpg";
}

header("Content-type: image/jpeg");
readfile($_SERVER["DOCUMENT_ROOT"].$base.$folder.$file);

 ?>
