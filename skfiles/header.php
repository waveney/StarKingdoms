<?php
  global $GAME,$GAMESYS,$CALYEAR;

  echo "<meta name=description content='Star Kingdoms Software'>\n";
  echo "<meta name=keywords content=''>\n";
  echo '<meta charset="UTF-16">';

  $V = $GAMESYS['V'];
//  if (isset($FESTSYS['Analytics'])) echo "<script>" . $FESTSYS['Analytics'] . "</script>";
  echo "<link href=/skfiles/style.css?V=$V type=text/css rel=stylesheet />";
  echo "<link href=/skfiles/dropdown.css?V=$V type=text/css rel=stylesheet />\n";

?>

    <script src="/js/jquery-3.2.1.min.js"></script>
<?php    
//    <link href="/files/themes.css" type="text/css" rel="stylesheet" />
//    <script src="/js/lightbox.js"></script>
//    <link href="/css/lightbox.css" rel="stylesheet" />
//    <script src="/js/responsiveslides.js"></script>
//    <link href="/css/responsiveslides.css" rel="stylesheet" />
?>
    <link rel='stylesheet' href='https://fonts.googleapis.com/css?family=Montserrat%3A300%2C400%2C600%2C700' type='text/css' media='all' />
<?php
  echo "<script src=/js/tablesort.js?V=$V></script>\n";
  echo "<script src=/js/Tools.js?V=$V></script>\n";
?>
