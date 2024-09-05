<?php

function Recalc_Offices() { // Recount offices for each org
  global $GAMEID;
  $Offs = Gen_Get_All('Offices', " WHERE GameId=$GAMEID");
  $Orgs = Gen_Get_All('Organisations', " WHERE GameId=$GAMEID");
  $OCount = [];

  foreach ($Orgs as &$O) $O['OfficeCount'] = 0;

  foreach ($Offs as $oi=>$O) {
    $Org = $O['Organisation'];
    if (isset($Orgs[$Org])) {
      $Orgs[$Org]['OfficeCount']++;
      if ($O['OrgType'] != $Orgs[$Org]['OrgType']) {
        $O['OrgType'] = $Orgs[$Org]['OrgType'];
        Gen_Put('Offices',$O);
      }
    } else {
      echo "Office $oi does not match any current Organisation - needs fixing<p>";
    }
  }

  foreach ($Orgs as &$O) Gen_Put('Organisations',$O);
}

function SocPrinciples($Fid) {
  $SocPs = Gen_Get_Cond('SocialPrinciples',"Whose=$Fid");
  $A = [];
  foreach ($SocPs as $i=>$P) $A[$i] = $P['Principle'];
  return $A;
}