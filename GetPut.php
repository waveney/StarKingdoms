<?php

// Library of get/puts for common resources

// Factions

function Get_Faction($id) {
  global $db;
  $res = $db->query("SELECT * FROM Factions WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Put_Faction(&$now) {
  $e=$now['id'];
  $Cur = Get_Faction($e);
  return Update_db('Factions',$Cur,$now);
}

function Get_Factions() {
  global $db,$GAMEID;
  $res = $db->query("SELECT * FROM Factions WHERE GameId=$GAMEID ORDER BY id ");
  $F = [];
  if ($res) {
    while ($ans = $res->fetch_assoc()) { $F[$ans['id']] = $ans; }
    }
  return $F;  
}

function Get_Faction_Names() {
  global $db,$GAMEID;
  $res = $db->query("SELECT * FROM Factions WHERE GameId=$GAMEID ORDER BY id ");
  $F = [];
  $F[0] = "None";
  if ($res) {
    while ($ans = $res->fetch_assoc()) { $F[$ans['id']] = $ans['Name']; }
    }
  return $F;  
}

function Get_Faction_Colours() {
  global $db,$GAMEID;
  $res = $db->query("SELECT * FROM Factions WHERE GameId=$GAMEID ORDER BY id ");
  $F = [];
  $F[0] = "white";
  if ($res) {
    while ($ans = $res->fetch_assoc()) { $F[$ans['id']] = $ans['MapColour']; }
    }
  return $F;  
}



// Systems

function Get_System($id) {
  global $db;
  $res = $db->query("SELECT * FROM Systems WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Put_System(&$now) {
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_System($e);
    return Update_db('Systems',$Cur,$now);
  } else {
    return $now['id'] = Insert_db ('Systems', $now );  
  }
}

function Get_SystemR($sysref) {
  global $db,$GAMEID;
  $res = $db->query("SELECT * FROM Systems WHERE GameId=$GAMEID AND Ref='$sysref'");
  if ($res) return $res->fetch_assoc();
  return [];
}

function Get_Systems() {
  global $db,$GAMEID;
  $res = $db->query("SELECT * FROM Systems WHERE GameId=$GAMEID ORDER BY Ref");
  $Systms = [];
  if ($res) {
    while ($ans = $res->fetch_assoc()) { $Systms[$ans['Ref']] = $ans; }
    }
  return $Systms;
}

function Get_SystemRefs() {
  global $db,$GAMEID;
  $res = $db->query("SELECT id,Ref FROM Systems WHERE GameId=$GAMEID ORDER BY Ref");
  $Systms = [];
  if ($res) {
    while ($ans = $res->fetch_assoc()) { $Systms[$ans['id']] = $ans['Ref']; }
    }
  return $Systms;
}

// Planets

function Get_Planet($id) {
  global $db;
  $res = $db->query("SELECT * FROM Planets WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Put_Planet(&$now) {
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_Planet($e);
    return Update_db('Planets',$Cur,$now);
  } else {
    return $now['id'] = Insert_db ('Planets', $now );  
  }
}

function Get_Planets($sysid) {
  global $db;
  $res = $db->query("SELECT * FROM Planets WHERE SystemId=$sysid ORDER BY OrbitalRadius");
  $Planets = [];
  if ($res) {
    while ($ans = $res->fetch_assoc()) { $Planets[] = $ans; };
    }
  return $Planets;
}

/// Links

function Get_Link($id) {
  global $db;
  $res = $db->query("SELECT * FROM Links WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Put_Link(&$now) {
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_Faction($e);
    return Update_db('Links',$Cur,$now);
  } else {
//var_dump($now); exit;
    return $now['id'] = Insert_db ('Links', $now );  
  }

}

function Get_Links($sysref) {
  global $db,$GAMEID;
  $res = $db->query("SELECT * FROM Links WHERE GameId=$GAMEID AND (System1Ref='$sysref' OR System2Ref='$sysref')");
  $links = [];
  if ($res) {
    while ($ans = $res->fetch_assoc()) { $links[] = $ans; };
    }
  return $links;
}

function Get_Links1end($sysref) {
  global $db,$GAMEID;
  $res = $db->query("SELECT * FROM Links WHERE GameId=$GAMEID AND System1Ref='$sysref'");
  $links = [];
  if ($res) {
    while ($ans = $res->fetch_assoc()) { $links[] = $ans; };
    }
  return $links;
}

// Messy not right avoid if poss
function Get_LinksAll($sysid) {
  global $db,$GAMEID;
  $res = $db->query("SELECT * FROM Links WHERE GameId=$GAMEID AND (System1=$sysid OR System2=$sysid)");
  $links = [];
  if ($res) {
    while ($ans = $res->fetch_assoc()) { $links[] = $ans; };
    }
  return $links;
}


// Link knnowledge

function Get_FactionLink($id) {
  global $db;
  $res = $db->query("SELECT * FROM FactionLink WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Get_FactionLinkFL($Fact, $Lid) {
  global $db;
  $res = $db->query("SELECT * FROM FactionLink WHERE FactionId=$Fact AND LinkId=$Lid");
  if ($res && ($ans = $res->fetch_assoc())) return $ans;
  return ['FactionId'=>$Fact, 'LinkId'=>$Lid, 'Known'=>0, 'NebScanned'=>0];
}

function Put_FactionLink(&$now) {
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_FactionLink($e);
    return Update_db('FactionLink',$Cur,$now);
  } else {
    return $now['id'] = Insert_db ('FactionLink', $now );
  }
}

function Get_Factions4Link($Lid) {
  global $db;
  $FL = [];
  $res = $db->query("SELECT * FROM FactionLink WHERE LinkId=$Lid");
  if ($res) while ($ans = $res->fetch_assoc()) $FL[$ans['FactionId']] = $ans;
  return $FL;
}

// FactionsSystems

function Get_FactionSystem($id) {
  global $db;
  $res = $db->query("SELECT * FROM FactionSystem WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Get_FactionSystemFS($Fact, $id) {
  global $db;
  $res = $db->query("SELECT * FROM FactionSystem WHERE FactionId=$Fact AND SystemId=$id");
  if ($res)  if ($ans=$res->fetch_assoc()) return $ans;
  return ['FactionId'=>$Fact, 'SystemId'=>$id];
}

function Get_FactionSystemFRef($Fact, $ref) { // DUFF CODE TODO no ref value
  global $db;
  $res = $db->query("SELECT * FROM FactionSystem WHERE FactionId=$Fact AND Ref='$ref'");
  if ($res)  if ($ans=$res->fetch_assoc()) return $ans;
  $N = Get_SystemR($ref);
  if ($N) return ['FactionId'=>$Fact, 'SystemId'=>$N['id']];
  echo "Unknown System Reference $ref<p>";
}

function Put_FactionSystem(&$now) {
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_FactionSystem($e);
    return Update_db('FactionSystem',$Cur,$now);
  } else {
    return $now['id'] = Insert_db ('FactionSystem', $now );
  }
}

function Get_Factions4Sys($id) {
  global $db;
  $FL = [];
  $res = $db->query("SELECT * FROM FactionSystem WHERE SystemId=$id");
  if ($res) while ($ans = $res->fetch_assoc()) $FL[$ans['FactionId']] = $ans;
  return $FL;
}


// Faction Planet

function Get_FactionPlanet($id) {
  global $db;
  $res = $db->query("SELECT * FROM FactionPlanet WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Get_FactionPlanetFS($Fact, $id) {
  global $db;
  $res = $db->query("SELECT * FROM FactionPlanet WHERE FactionId=$Fact AND Planet=$id");
  if ($res) return $ans = $res->fetch_assoc();
  return ['FactionId'=>$Fact, 'Planet'=>$id];
}

function Put_FactionPlanet(&$now) {
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_FactionPlanet($e);
    return Update_db('FactionPlanet',$Cur,$now);
  } else {
    return $now['id'] = Insert_db ('FactionPlanet', $now );
  }  
}


function Get_Factions4Plan($id) {
  global $db;
  $FL = [];
  $res = $db->query("SELECT * FROM FactionPlanet WHERE PlanetId=$id");
  if ($res) while ($ans = $res->fetch_assoc()) $FL[$ans['FactionId']] = $ans;
  return $FL;
}

// ??

function Get_LinkLevels() {
  global $db,$GAMEID;
  $Lvls = [];
  $res = $db->query("SELECT * FROM LinkLevel WHERE GameId=$GAMEID ORDER BY Level");
//  var_dump($res);
  if ($res) while ($ans = $res->fetch_assoc()) $Lvls[$ans['Level']] = $ans;
  return $Lvls;
}

// District Types

function Get_DistrictType($id) {
  global $db;
  $res = $db->query("SELECT * FROM DistrictTypes WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Put_DistrictType(&$now) {
  $e=$now['id'];
  $Cur = Get_DistrictType($e);
  return Update_db('DistrictTypes',$Cur,$now);
}

function Get_DistrictTypes() {
  global $db,$GAMEID;
  $Ts = [];
  $res = $db->query("SELECT * FROM DistrictTypes");
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[$ans['id']] = $ans;
  return $Ts;
}

function Get_DistrictTypeNames() {
  global $db,$GAMEID;
  $Ts = [];
  $res = $db->query("SELECT * FROM DistrictTypes");
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[$ans['id']] = $ans['Name'];
  return $Ts;
}

// Districts

function Get_District($id) {
  global $db;
  $res = $db->query("SELECT * FROM Districts WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Put_District(&$now) {
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_District($e);
    return Update_db('Districts',$Cur,$now);
  } else {
    return $now['id'] = Insert_db ('Districts', $now );
  }  
}

function Get_DistrictsP($Pid) {
  global $db,$GAMEID;
  $Ts = [];
  $res = $db->query("SELECT * FROM Districts WHERE HostType=0 AND HostId=$Pid ORDER BY Type");
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[$ans['Type']] = $ans;
  return $Ts;
}

function Get_DistrictsM($Mid) {
  global $db,$GAMEID;
  $Ts = [];
  $res = $db->query("SELECT * FROM Districts WHERE HostType=1 AND HostId=$Mid ORDER BY Type");
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[$ans['Type']] = $ans;
  return $Ts;
}

function Get_DistrictsT($Tid) {
  global $db,$GAMEID;
  $Ts = [];
  $res = $db->query("SELECT * FROM Districts WHERE HostType=2 AND HostId=$Tid ORDER BY Type");
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[$ans['Type']] = $ans;
  return $Ts;
}

//  Planet Types

function Get_PlanetType($id) {
  global $db;
  $res = $db->query("SELECT * FROM PlanetTypes WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Put_PlanetType(&$now) {
  $e=$now['id'];
  $Cur = Get_PlanetType($e);
  return Update_db('PlanetTypes',$Cur,$now);
}

function Get_PlanetTypes() {
  global $db,$GAMEID;
  $Ts = [];
  $res = $db->query("SELECT * FROM PlanetTypes");
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[$ans['id']] = $ans;
  return $Ts;
}

function Get_PlanetTypeNames() {
  global $db,$GAMEID;
  $Ts = [];
  $res = $db->query("SELECT * FROM PlanetTypes");
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[$ans['id']] = $ans['Name'];
  return $Ts;
}

//  Moons

function Get_Moon($id) {
  global $db;
  $res = $db->query("SELECT * FROM Moons WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Put_Moon(&$now) {
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_Moon($e);
    return Update_db('Moons',$Cur,$now);
  } else {
    return $now['id'] = Insert_db ('Moons', $now );
  }
}


function Get_Moons($Pid) {
  global $db,$GAMEID;
  $Ms = [];
  $res = $db->query("SELECT * FROM Moons WHERE PlanetId=$Pid ORDER BY OrbitalRadius");
  if ($res) while ($ans = $res->fetch_assoc()) $Ms[] = $ans;
  return $Ms;
}

//  

function Get_Thing($id) {
  global $db;
  $res = $db->query("SELECT * FROM Things WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Put_Thing(&$now) {
  global $db,$GAMEID;
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_Thing($e);
    return Update_db('Things',$Cur,$now);
  } else {
    $now['GameId'] = $GAMEID;
    return $now['id'] = Insert_db ('Things', $now );
  }
}

function Get_Things($Fact,$type=0) {
  global $db,$GAMEID;
  $Ts = [];
  $res = $db->query("SELECT * FROM Things WHERE GameId=$GAMEID AND Whose=$Fact " . ($type?" AND Type=$type":""));
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[] = $ans;
  return $Ts;
}

function Get_AllThings() {
  global $db,$GAMEID;
  $Ts = [];
  $res = $db->query("SELECT * FROM Things WHERE GameId=$GAMEID");
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[] = $ans;
  return $Ts;
}

function Get_AllThingsAt($id) {
  global $db,$GAMEID;
  $Ts = [];
  $res = $db->query("SELECT * FROM Things WHERE GameId=$GAMEID AND SystemId=$id ORDER By Whose");
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[] = $ans;
  return $Ts;
}

function Get_ThingsSys($Sid,$type=0,$Fid=0) {
  global $db,$GAMEID;
  $Ts = [];
  $res = $db->query("SELECT * FROM Things WHERE GameId=$GAMEID AND SystemId=$Sid " . ($type?" AND Type=$type":"") . ($Fid?" AND Whose=$Fid":""));
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[] = $ans;
  return $Ts;
}

function Get_Things_Cond($Fact,$Cond) {
  global $db,$GAMEID;
  $Ts = [];
  $res = $db->query("SELECT * FROM Things WHERE Whose=$Fact AND $Cond ");
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[] = $ans;
  return $Ts;
}


// Modules

function Get_Module($id) {
  global $db;
  $res = $db->query("SELECT * FROM Modules WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Put_Module(&$now) {
  global $db,$GAMEID;
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_Module($e);
    return Update_db('Modules',$Cur,$now);
  } else {
    return $now['id'] = Insert_db ('Modules', $now );
  }
}

function Get_Modules($Thing) {
  global $db,$GAMEID;
  $Ms = [];
  $res = $db->query("SELECT * FROM Modules WHERE ThingId=$Thing");
  if ($res) while ($ans = $res->fetch_assoc()) $Ms[] = $ans;
  return $Ms;
}

function Get_ModulesType($Thing,$Type) {
  global $db,$GAMEID;
  $Ms = [];
  $res = $db->query("SELECT * FROM Modules WHERE ThingId=$Thing AND Type=$Type");
  if ($res) while ($ans = $res->fetch_assoc()) $Ms[] = $ans;
  return $Ms;
}

// Module Types

function Get_ModuleType($id) {
  global $db;
  $res = $db->query("SELECT * FROM ModuleTypes WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Put_ModuleType(&$now) {
  global $db,$GAMEID;
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_ModuleType($e);
    return Update_db('ModuleTypes',$Cur,$now);
  } else {
    return $now['id'] = Insert_db ('ModuleTypes', $now );
  }
}

function Get_ModuleTypes() {
  global $db,$GAMEID;
  $Ms = [];
  $res = $db->query("SELECT * FROM ModuleTypes ORDER BY id");
  if ($res) while ($ans = $res->fetch_assoc()) $Ms[$ans['id']] = $ans;
  return $Ms;
}

// Module Formulae Types

function Get_ModFormula($id) {
  global $db;
  $res = $db->query("SELECT * FROM ModFormulae WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Put_ModFormula(&$now) {
  global $db,$GAMEID;
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_ModFormula($e);
    return Update_db('ModFormulae',$Cur,$now);
  } else {
    return $now['id'] = Insert_db ('ModFormulae', $now );
  }
}

function Get_ModFormulaes() {
  global $db,$GAMEID;
  $Ms = [];
  $res = $db->query("SELECT * FROM ModFormulae");
  if ($res) while ($ans = $res->fetch_assoc()) $Ms[] = $ans;
  return $Ms;
}

// Technologies

function Get_Tech($id) {
  global $db;
  $res = $db->query("SELECT * FROM Technologies WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Put_Tech(&$now) {
  global $db,$GAMEID;
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_Tech($e);
    return Update_db('Technologies',$Cur,$now);
  } else {
    return $now['id'] = Insert_db ('Technologies', $now );
  }
}

function Get_Techs($Fact=0) {
  global $db,$GAMEID;
  $Ms = [];
  if ($Fact == 0) {
    $res = $db->query("SELECT * FROM Technologies ORDER BY id");
    if ($res) while ($ans = $res->fetch_assoc()) $Ms[$ans['id']] = $ans;
    return $Ms;
  } else {
    $res = $db->query("SELECT t.* FROM Technologies t, FactionTechs ft WHERE (t.Cat<2 OR (t.Cat=3 AND ft.Tech_Id=t.id)) ORDER BY t.id");
    if ($res) while ($ans = $res->fetch_assoc()) $Ms[$ans['id']] = $ans;
    return $Ms;    
  }
}

function Get_TechsByCore($Fact=0,$All=0) {
  global $db,$GAMEID;
  $Ms = [];
  if ($Fact == 0 || $All) {
    $res = $db->query("SELECT * FROM Technologies ORDER BY PreReqTech, PreReqLevel, Name");
    if ($res) while ($ans = $res->fetch_assoc()) $Ms[] = $ans;
    return $Ms;
  } else {
    $res = $db->query("SELECT DISTINCT t.* FROM Technologies t, FactionTechs ft WHERE " .
           "(t.Cat<2 OR (t.Cat=2 AND ft.Faction_Id=$Fact AND ft.Tech_Id=t.id)) ORDER BY t.PreReqTech, t.PreReqLevel, t.Name");
    if ($res) while ($ans = $res->fetch_assoc()) $Ms[] = $ans;
    return $Ms;    
  }
}




function Get_CoreTechs() {
  global $db,$GAMEID;
  $Ms = [];
  $res = $db->query("SELECT * FROM Technologies WHERE Cat=0 ORDER BY id");
  if ($res) while ($ans = $res->fetch_assoc()) $Ms[] = $ans;
  return $Ms;
}

function Get_CoreTechsByName() {
  global $db,$GAMEID;
  $Ms = [];
  $res = $db->query("SELECT * FROM Technologies WHERE Cat=0 ORDER BY Name");
  if ($res) while ($ans = $res->fetch_assoc()) $Ms[] = $ans;
  return $Ms;
}

// Faction Techs

function Get_Faction_Tech($id) {
  global $db;
  $res = $db->query("SELECT * FROM FactionTechs WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Get_Faction_TechFT($Fid,$Tid) {
  global $db;
  $res = $db->query("SELECT * FROM FactionTechs WHERE Faction_Id=$Fid AND Tech_Id=$Tid");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return ['Tech_Id'=>$Tid, 'Faction_Id'=> $Fid, 'Level' => 0];
}

function Put_Faction_Tech(&$now) {
  global $db,$GAMEID;
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_Faction_Tech($e);
    return Update_db('FactionTechs',$Cur,$now);
  } else {
    return $now['id'] = Insert_db ('FactionTechs', $now );
  }
}

function Get_Faction_Techs($Fact) {
  global $db,$GAMEID;
  $Ms = [];
  $res = $db->query("SELECT * FROM  FactionTechs ft WHERE Faction_Id=$Fact");
  if ($res) while ($ans = $res->fetch_assoc()) $Ms[$ans['Tech_Id']] = $ans;
  return $Ms;
}

// Thing Types

function Get_ThingType($id) {
  global $db;
  $res = $db->query("SELECT * FROM ThingTypes WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Put_ThingType(&$now) {
  global $db,$GAMEID;
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_ThingType($e);
    return Update_db('ThingTypes',$Cur,$now);
  } else {
    return $now['id'] = Insert_db ('ThingTypes', $now );
  }
}

function Get_ThingTypes() {
  global $db,$GAMEID;
  $Ms = [];
  $res = $db->query("SELECT * FROM  ThingTypes ");
  if ($res) while ($ans = $res->fetch_assoc()) $Ms[$ans['id']] = $ans;
  return $Ms;
}

function Has_Tech($fid,$name,$Base=0) {// name can be id
  global $db,$GAMEID;
//echo "Has_Tech called with $fid, $name<p>";
  if (is_numeric($name)) {
    $res = $db->query("SELECT Level,Tech_Id FROM  FactionTechs WHERE Faction_Id=$fid AND Tech_Id=$name ");
  } else {
    $res = $db->query("SELECT ft.Level,ft.Tech_Id FROM  FactionTechs ft, Technologies t WHERE ft.Faction_Id=$fid AND ft.Tech_Id=t.id AND t.Name='$name' ");
  }
  if ($res) while ($ans = $res->fetch_assoc()) {
    $Blvl = $ans['Level'];
    if ($Base) return $Blvl;
    $tec = Get_Tech($ans['Tech_Id']);
    if ($tec['Cat'] == 0) return $Blvl;
    $Blvl = Has_Tech($fid,$tec['PreReqTech'],1);
    return $Blvl;
  }
//echo "Result is 0<p>";
  return 0;
}

// Get Game in sk.php

function Put_Game($now) {
  global $db,$GAMEID;
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_Game($e);
    return Update_db('Games',$Cur,$now);
  } else {
    return $now['id'] = Insert_db ('Games', $now );  
  }
}

// Deep Space Projects

function Get_DeepSpace($id) {
  global $db;
  $res = $db->query("SELECT * FROM DeepSpaceProjects WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Put_DeepSpace(&$now) {
  global $db,$GAMEID;
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_DeepSpace($e);
    return Update_db('ThingTypes',$Cur,$now);
  } else {
    return $now['id'] = Insert_db ('DeepSpaceProjects', $now );
  }
}

function Get_DeepSpaces() {
  global $db,$GAMEID;
  $Ms = [];
  $res = $db->query("SELECT * FROM  DeepSpaceProjects ");
  if ($res) while ($ans = $res->fetch_assoc()) $Ms[$ans['id']] = $ans;
  return $Ms;
}

function Get_FactionMoon($id) {
  global $db;
  $res = $db->query("SELECT * FROM FactionMoon WHERE id=$id");
  if ($res) return $res->fetch_assoc();
}

function Get_FactionMoonFS($Fact, $id) {
  global $db;
  $res = $db->query("SELECT * FROM FactionMoon WHERE FactionId=$Fact AND Moon=$id");
  if ($res) return $ans = $res->fetch_assoc();
  return ['FactionId'=>$Fact, 'Moon'=>$id];
}

function Put_FactionMoon(&$now) {
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_FactionMoon($e);
    return Update_db('FactionMoon',$Cur,$now);
  } else {
    return $now['id'] = Insert_db ('FactionMoon', $now );
  }  
}

/// Turns

function Get_Turn($id) {
  global $db,$GAME,$GAMEID;
  $res = $db->query("SELECT * FROM Turns WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return ['GameId'=>$GAMEID, 'Progress'=>0, 'ActivityLog'=>'', 'TurnNumber'=>$GAME['Turn']];
}

function Get_TurnNumber($id=0) {
  global $db,$GAME,$GAMEID;
  if ($id ==0) $id =$GAME['Turn'];
  $res = $db->query("SELECT * FROM Turns WHERE GameId=$GAMEID AND TurnNumber=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return ['GameId'=>$GAMEID, 'Progress'=>0, 'ActivityLog'=>'', 'TurnNumber'=>$GAME['Turn'] ];
}


function Put_Turn(&$now) {
  global $db,$GAMEID;
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_Turn($e);
    return Update_db('Turns',$Cur,$now);
  } else {
    return $now['id'] = Insert_db ('Turns', $now );
  }
}

// Projects

function Get_Project($id) {
  global $db;
  $res = $db->query("SELECT * FROM Projects WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Get_ProjectAt($Home,$Dist,$Start) {
  global $db;
  if ($Dist==0 || $Dist>5) return [];
  $Cat = [-1=>1,1=>64,2=>4,3=>2,4=>8,5=>1][$Dist]; // Convert from Distict numbers to category mask in project types
  $res = $db->query("SELECT p.* FROM Projects p ProjectTypes t WHERE p.Home=$Home AND p.TurnStart=$Start AND t.id=p.Type AND t.Category=$Cat");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}


function Put_Project(&$now) {
  global $db,$GAMEID;
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_Project($e);
    return Update_db('Projects',$Cur,$now);
  } else {
    return $now['id'] = Insert_db ('Projects', $now );
  }
}

function Get_Projects($home) {
  global $db;
  $Ts = [];
  $res = $db->query("SELECT * FROM Projects WHERE Home=$home ORDER BY TurnStart");
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[] = $ans;
  return $Ts;
}

function Get_Projects_Cond($Cond) {
  global $db;
  $Ts = [];
  $res = $db->query("SELECT * FROM Projects WHERE $Cond ORDER BY FactionId");
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[] = $ans;
  return $Ts;
}


// Project Types
function Get_ProjectType($id) {
  global $db;
  $res = $db->query("SELECT * FROM ProjectTypes WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Get_ProjectTypes() {
  global $db;
  $Ts = [];
  $res = $db->query("SELECT * FROM ProjectTypes");
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[$ans['id']] = $ans;
  return $Ts;
}

function Put_ProjectType(&$now) {
  global $db,$GAMEID;
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_ProjectType($e);
    return Update_db('ProjectTypes',$Cur,$now);
  } else {
    return $now['id'] = Insert_db ('ProjectTypes', $now );
  }
}

// Project Homes

function Get_ProjectHome($id) {
  global $db;
  $res = $db->query("SELECT * FROM ProjectHomes WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Get_ProjectHomes($who) {
  global $db,$GAMEID;
  $Ts = [];
  $res = $db->query("SELECT * FROM ProjectHomes WHERE Whose=$who");
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[$ans['id']] = $ans;
  return $Ts;
}

function Put_ProjectHome(&$now) {
  global $db,$GAMEID;
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_ProjectHome($e);
    return Update_db('ProjectsHomes',$Cur,$now);
  } else {
    return $now['id'] = Insert_db ('ProjectHomes', $now );
  }
}

// Project Turns

function Get_ProjectTurn($id) {
  global $db;
  $res = $db->query("SELECT * FROM ProjectTurn WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Get_ProjectTurnPT($pid,$Turn) {
  global $db;
  $res = $db->query("SELECT * FROM ProjectTurn WHERE ProjectId=$pid AND TurnNumber=$Turn");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return ['ProjectId'=>$pid, 'TurnNumber'=>$Turn ];
}

function Get_ProjectTurns($Proj) {
  global $db,$GAMEID;
  $Ts = [];
  $res = $db->query("SELECT * FROM ProjectTurn WHERE ProjectId=$Proj ORDER BY TurnNumber");
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[] = $ans;
  return $Ts;
}

function Put_ProjectTurn(&$now) {
  global $db,$GAMEID;
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_ProjectTurn($e);
    return Update_db('ProjectTurn',$Cur,$now);
  } else {
    return $now['id'] = Insert_db ('ProjectTurn', $now );
  }
}

// People

function Get_Person($id) {
  global $db;
  $res = $db->query("SELECT * FROM People WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Get_People() {
  global $db,$GAMEID;
  $Ts = [];
  $res = $db->query("SELECT * FROM People ");
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[] = $ans;
  return $Ts;
}

// Faction Faction Knowledge

function Get_FactionFaction($id) {
  global $db;
  $res = $db->query("SELECT * FROM FactionFaction WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Get_FactionFactions($who) {
  global $db,$GAMEID;
  $Ts = [];
  $res = $db->query("SELECT * FROM FactionFaction WHERE FactionId1=$who");
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[$ans['FactionId2']] = $ans;
  return $Ts;
}

function Put_FactionFaction(&$now) {
  global $db,$GAMEID;
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_FactionFaction($e);
    return Update_db('FactionFaction',$Cur,$now);
  } else {
    return $now['id'] = Insert_db ('FactionFaction', $now );
  }
}

// Credit Log

function Get_CreditLog($id) {
  global $db;
  $res = $db->query("SELECT * FROM CreditLog WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Put_CreditLog(&$now) {
  global $db,$GAMEID;
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_CreditLog($e);
    return Update_db('CreditLog',$Cur,$now);
  } else {
    return $now['id'] = Insert_db ('CreditLog', $now );
  }
}

function Get_CreditLogs($who,$From=0,$To=10000) {
  global $db,$GAMEID;
  $Ts = [];
  $res = $db->query("SELECT * FROM CreditLog WHERE Whose=$who AND Turn>=$From AND Turn<=$To");
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[] = $ans;
  return $Ts;
}


//  Get_FactionTurn($Fid,$Turn);

function Get_FactionTurn($id) {
  global $db;
  $res = $db->query("SELECT * FROM FactionTurn WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Get_FactionTurnFT($Fid,$Turn) {
  global $db;
  $res = $db->query("SELECT * FROM FactionTurn WHERE FactionId=$Fid AND Turn=$Turn");
  if ($res && ($ans = $res->fetch_assoc())) return $ans;
  return ['FactionId'=>$Fid,'Turn'=>$Turn];
}

function Put_FactionTurn(&$now) {
  global $db,$GAMEID;
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_FactionTurn($e);
    return Update_db('FactionTurn',$Cur,$now);
  } else {
    return $now['id'] = Insert_db ('FactionTurn', $now );
  }
}


// Banking
function Get_Banking($id) {
  global $db;
  $res = $db->query("SELECT * FROM Banking WHERE id=$id");
  if ($res) if ($ans = $res->fetch_assoc()) return $ans;
  return [];
}

function Get_BankingFT($Fid,$Turn) {
  global $db;
  $Ts = [];
  $res = $db->query("SELECT * FROM Banking WHERE " . ($Fid? " FactionId=$Fid AND ": "") . "(StartTurn=$Turn OR ( StartTurn<$Turn AND EndTurn >= $Turn ))");
  if ($res) while ($ans = $res->fetch_assoc()) $Ts[] = $ans;
  return $Ts;
}

function Put_Banking(&$now) {
  global $db,$GAMEID;
  if (isset($now['id'])) {
    $e=$now['id'];
    $Cur = Get_Banking($e);
    return Update_db('Banking',$Cur,$now);
  } else {
    return $now['id'] = Insert_db ('Banking', $now );
  }
}




