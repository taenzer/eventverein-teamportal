<?php


define("MENU_ITEMS_LOGGED_IN", array(
  "Allgemein" => array(
    0 => array(
      "icon" => "null",
      "text" => "Dashboard",
      "location" => "/"
    ),

  ),
  "Inventar" => array(
    0 => array(
      "icon" => "null",
      "text" => "Inventarliste",
      "location" => "/inventar"
    ),
    1 => array(
      "icon" => "null",
      "text" => "Ausleihe",
      "location" => "/inventar/ausleihe"
    ),
  ),
  "Finanzen" => array(
    0 => array(
      "icon" => "null",
      "text" => "Transaktionen",
      "location" => "/transaktionen"
    ),
    3 => array(
      "icon" => "null",
      "text" => "Rechnungen",
      "location" => "/rechnungen"
    ),
    4 => array(
      "icon" => "null",
      "text" => "Berichte",
      "location" => "/berichte"
    )
  ),
  "Verwaltung" => array(
    0 => array(
      "icon" => "null",
      "text" => "Mitglieder",
      "location" => "/mitglieder"
    ),
    1 => array(
      "icon" => "null",
      "text" => "Kontakte",
      "location" => "/kontakte"
    ),
  )

));

define("MENU_ITEMS_PUBLIC", array(
  "Bitte melde dich an" => array(
    0 => array(
      "icon" => "null",
      "text" => "Login",
      "location" => "/login"
    ),
    1 => array(
      "icon" => "null",
      "text" => "Mitglied werden",
      "location" => "/join"
    ),
  )
)
)
 ?>
