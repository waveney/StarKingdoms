<?php
require __DIR__ . '/vendor/autoload.php';


/*
 * We need to get a Google_Client object first to handle auth and api calls, etc.
 */
$client = new \Google_Client();
$client->setApplicationName('My PHP App');
$client->setScopes([\Google_Service_Sheets::SPREADSHEETS]);
$client->setAccessType('offline');
$client->setAuthConfig("cache/starkingdoms-ca1278acb7bb.json");

$sheets = new \Google_Service_Sheets($client);

$Factions = ['181ISkhyn218Q6-3bYfZLFYXf7q7f2uPh8GqP6MlHIQ4', // Blank
             '1hoT-rzRxAyKxqUbSkef8pSJ9O3S6GbrkMFCsWm_FQ7s', // Zabania
             '1-U9hdUP7IOfKXKm4FPG7r4kBCBFGsF78seEbGxgdyJI', // Wombles
             '114-XBfOgYYqRoPYPmPNqtqiTX0zr9OegC4xpMAj03-A', // Fongari
             '1MGtwAguxFUxF74i4FN7Yk2tHDIB0xSQ_0F2WtXB9g_M', // Haxan
             '1Ocnth43LRBUZ0YA04DISFax4OdxuOJPPm4gLoZn2jvM', // Ratfolk
             '1vlr7GhkC28Bye4k6px7COMgqP5S6Yy2nyUBLF9qI8dA',  // Sedrana
             '1q1IBZUNndSFa8MI9BUjuOAu_KGfTSAyDgpaWNRmyKoM', // Arachni
             '1MyYTJKms6WRRktiYkSKKAvfEdiOraZvmoiv1HYRnkV8', // Kalipolis
             '1SMMNBY_jZDoIUoMeLjqguJRtVUrRmIqShRU2wG8ih3A']; // Mammots

$SheetIds = ['Faction'=> 0, 'Setup'=> 1104884901, 'Main'=>1067465833, 'Colony1'=>1459791190, 'Colony2'=>1000668192, 'Colony3'=>1470733151,
             'Ships'=> 272145940, 'Armies'=>1086939544, 'Agents'=>1573267898, 'Techs'=>1573267898, 'Economy'=>1568340317];
             
           
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");
  include_once("PlayerLib.php");
  include_once("SystemLib.php");
  include_once("ProjLib.php");
  include_once("HomesLib.php");

  if (isset($_REQUEST['F'])) {
  
  
  
$rows = $sheets->spreadsheets_values->get($SpreadsheetID,"A1:Z99");

  $Fid = $_REQUEST['F'];
  $Stage = $_REQUEST['S'];

  switch ($Stage) {
  case 'Faction':
  
  
  case 'Setup': 
  
  
  case 'Main':
  
  
  case 'Colony1':
  
  
  case 'Colony2':
  
  
  case 'Colony3':
  
  
  case 'Ships':
  
  
  case 'Armies':
  
  
  case 'Agents':
  
  
  case 'Techs':
  
  
  case 'Economy':
  
  }
  
  }
  
  echo "<h1>Import from a spreadsheet</h1>

