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
             
foreach ( $Factions as $i=>$SSID) {
  echo "Doing faction $i\n";
  $Sheet = $sheets->spreadsheets->get($SSID);

  $Sheets = $Sheet->getSheets();
  
  $FF = fopen("cache/Faction$i","w");
  fwrite($FF,"$SSID\n");
  foreach ($Sheets as $S) {
    fwrite($FF, $S->properties->sheetId . " - " . $S->properties->title . "\n" );
  };
  
  fclose($FF);
}

echo "Done!";
















// ZZZZ$SpreadsheetID ="181ISkhyn218Q6-3bYfZLFYXf7q7f2uPh8GqP6MlHIQ4";

/*
$range= "A1:C15";
$rows = $sheets->spreadsheets_values->get($SpreadsheetID,"A1:Z99");

//var_dump($rows);

print_r($rows);



$Sheet = $sheets->spreadsheets->get($SpreadsheetID);

$Sheets = $Sheet->getSheets();

foreach ($Sheets as $S) echo $S->properties->sheetId . " - " . $S->properties->title . "\n";
//var_dump($rows);

//print_r($Sheets);
*/
?>
