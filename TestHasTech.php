<?php
  include_once("sk.php");
  include_once("GetPut.php");
  include_once("ThingLib.php");

  A_Check('GM');

  dostaffhead("Test Has Tech");

  $Techs = Get_Techs();
  $TNs = [];
  foreach ($Techs as $T) $TNs[$T['id']] = $T['Name'];

  $FactNames = Get_Faction_Names();
  [$Fact_Colours,$Fact_Text_Colours] = Get_Faction_Colours();

  if (isset($_REQUEST['Test'])) {
    if ($v = Has_Tech($_REQUEST['Whose'], $Techs[$_REQUEST['Tech']]['Name'])) {
      echo "Yes $v<p>";
    } else {
      echo "No<p>";
    }
  }

  $t = [];
  echo "<form method=post action=TestHasTech.php><table>";
  echo "<tr>" . fm_radio('Whose',$FactNames ,$t,'Whose','',1,'colspan=6','',$Fact_Colours,0,'','',$Fact_Text_Colours);
  echo "<tr><td>Tech<td>" . fm_select($TNs,$t,'Tech');
  echo "<tr><td><td><input type=submit name=Test value=Test></table></form>\n";

  dotail();

?>

