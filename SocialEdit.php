<?php
include_once("sk.php");
include_once("GetPut.php");
include_once("ThingLib.php");
include_once("SystemLib.php");
include_once("OrgLib.php");
global $FACTION,$GAMEID;

// var_dump($_REQUEST);

dostaffhead("Social Principle editing and creation");

$GM = Access('GM');
$FactNames = Get_Faction_Names();

echo "<form method=post>";
$Sid = ($_REQUEST['id'] ??0);
$Pwi = ($_REQUEST['Pwi'] ??0);

if (isset($_REQUEST['Action'])) {
  switch ($_REQUEST['Action']) {
    case 'Add':
      $Wid = $_REQUEST['W'];
      $World = Get_World($Wid);
      $Prins = Gen_Get_Cond('SocialPrinciples',"GameId=$GAMEID");
      $PrinNames = NamesList($Prins,'Principle');
      asort($PrinNames);
      echo fm_hidden('Action','Create') . fm_hidden('W',$Wid);
      echo "Select Principle: " . fm_select($PrinNames,$_POST,'id',1," onchange=this.form.submit()") . "</form>\n";

      dotail();

    case 'Create':
      $SP = ['World'=>($_REQUEST['W']??0), 'Principle'=>$Sid, 'Value'=>1];
      Gen_Put('SocPsWorlds',$SP);
      echo "Social Principle recorded<p>";
      break;

    case 'Delete':
      if ($Sid) {
        db_delete('SocialPrinciples',$Sid);
        echo "Social Principle Deleted";
      } else {
        echo 'No Social Principle Specified';
      }
      dotail();

    case 'Remove': // SP from World
      if ($Pwi) {
        db_delete('SocPsWorlds',$Pwi);
        echo "Social Principle Removed from World";
      } else {
        echo 'No Social Principle Specified';
      }
      dotail();

    case 'Edit':
      // drop though
  }
}

$Prin = Gen_Get('SocialPrinciples',$Sid);
$SocPWs = Gen_Get_Cond('SocPsWorlds',"Principle=$Sid");

if ($GM) {
  $Fid = 0;
  if (!empty($FACTION)) $Fid = $FACTION['id'];
} else {
  $Fid = $FACTION['id'];
  if ($FACTION['TurnState'] > 2) Player_Page();
  Check_MyThing($Prin,$Fid);
}

$Force = (isset($_REQUEST['FORCE'])?1:0);

echo "<table border>";
Register_AutoUpdate('Generic', 0);
if ($GM) {
  echo "<tr><td>id: $Sid";
  echo "<tr><td>Whose:<td>" . fm_select($FactNames,$Prin,'Whose');
  echo "<tr>" . fm_text('Principle Name',$Prin,'Principle',2,'',"SocialPrinciples:Principle:$Sid");
  echo "<tr>" . fm_textarea("Description",$Prin,3,3,'',"SocialPrinciples:Description:$Sid");
} else {
  echo "<tr><td>" . $Prin['Principle'];
  echo "<td>" .  $Prin['Description'];
//  echo "<tr>" . fm_textarea("Description",$Prin,3,3,'',"SocialPrinciples:Description:$Sid");
}

if ($SocPWs ) {
  echo "<tr><td>System<td>World<td>Adherence<td>Actions";

  foreach ($SocPWs as $pwi=>$Spw) {
    $W = Get_World($Spw['World']);
    switch ($W['ThingType']) {
      case 1: // Planet
        $P = Get_Planet($W['ThingId']);
        $Sys = Get_System($P['SystemId']);
        $Where = $P['Name'];
        break;
      case 2: // Moon
        $M = Get_Moon($W['ThingId']);
        $P = Get_Planet($M['PlanetId']);
        $Sys = Get_System($P['SystemId']);
        $Where = $M['Name'] . " a moon of " . $P['Name'];
        break;
      case 3:// Thing
        $T = Get_Thing($W['ThingId']);
        $Sys = Get_System($T['SystemId']);
        $Where = $T['Name'];
        break;
    }
    echo "<tr><td>" . System_Name($Sys,$Fid) . "<td>$Where";
    if ($GM) {
       echo fm_number1('',$Spw,'Value','','min=0 max=100', "SocPsWorlds:Value:$pwi");
       echo "<td><a href=SocialEdit.php?Action=Remove&Pwi=$pwi>Remove</a>";
    } else {
      echo "<td>" . $Spw['Value'];
    }
  }

} else {
  if ($GM) echo "<tr><td colspan=3>Social Principle not followed anywhere... <a href=SocialEdit.php?Action=Delete>Delete?</a>";
}

echo "</table><p>";
if ($GM) echo "To add a principle to a new world, go the world and follow the <b>Add Social Principle</b> link at the bottom of the page.<p>";

dotail();