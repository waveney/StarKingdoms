<?php
  include_once("sk.php");
  /* Remove any Participant overlay */

  A_Check('Player');

  $host= "https://" . $_SERVER['HTTP_HOST'];

  dohead("SK Pages", ["/js/jquery.typeahead.min.js", "/css/jquery.typeahead.min.css", "/js/Staff.js"]);

  global $GAME;

  function SKTable($Section,$Heading,$cols=1) {
    global $Heads;
    static $ColNum = 3;
    $txt = '';
    if ($Section != 'Any' && !Capability("Enable$Section")) return '';
    $Heads[] = $Heading;
    if ($ColNum+$cols > 3) {
      $txt .= "<tr>";
      $ColNum =0;
    }
    $hnam = preg_replace("/[^A-Za-z0-9]/", '', $Heading);
    $txt .= "<td class=Stafftd colspan=$cols >";
    $txt .= "<h2 id='Staff$hnam'>$Heading</h2>";
    $ColNum+=$cols;
    return $txt;
  }

  if (isset($ErrorMessage)) echo "<h2 class=ERR>$ErrorMessage</h2>";

//echo php_ini_loaded_file() . "<P>";

  echo "<h2>SK Pages - " . (isset($GAME['Name'])?$GAME['Name']:"Star Kingdoms" ) . "></h2>\n";
  
  $txt = "<div class=tablecont><table border width=100% class=Staff style='min-width:800px'>\n";
   
  if ($x = SKTable('Docs','Document Storage')) {
    $txt .= $x;
      $txt .= "<ul>\n";
      if (Access('Staff')) {
        $txt .= "<li><a href=Dir>View Document Storage</a>\n";
        $txt .= "<li><a href=Search>Search Document Storage</a>\n";
      }
      $txt .= "<p>";
//      $txt .= "<li><a href=ProgrammeDraft1.pdf>Programme Draft</a>\n";
      $txt .= "<li><a href=StaffHelp>General Help</a>\n";

      if (Access('SysAdmin')) {
        $txt .= "<p>";
        $txt .= "<li class=smalltext><a href=DirRebuild?SC>Scan Directories - Report File/Database discrepancies</a>";    
//      $txt .= "<li><a href=DirRebuild?FI>Rebuild Directorys - Files are YEARDATA</a>";
//      $txt .= "<li><a href=DirRebuild?DB>Rebuild Directorys - Database is YEARDATA</a>";
      }
      $txt .= "</ul>\n";
    }
    
// *********************** TIMELINE ****************************************************
  if ($x = SKTable('TLine','Timeline')) {
    $txt .= $x;
    $txt .= "<ul>\n";
    $txt .= "<li><a href=TimeLine?Y=$YEAR>Time Line Management</a>\n<p>";
    $txt .= "<li><a href=TLHelp>Timeline Help</a>\n";
//    $txt .= "<li>Timeline Stats\n";
    if (Access('SysAdmin')) {
      $txt .= "<p>";
//      $txt .= "<li class=smalltext><a href=TLImport1>Timeline Import 1</a>\n";
      }
    $txt .= "</ul><p>\n";
  }

// *********************** Users  **************************************************************
  if ($x = StaffTable('Any','Users')) {
    $txt .= $x;
    $txt .= "<ul>\n";
    $txt .= "<li><a href=Login?ACTION=NEWPASSWD>New Password</a>\n";
    if (Access('Committee','Users')) {
      $txt .= "<li><a href=AddUser>Add User</a>";
      $txt .= "<li><a href=ListUsers?FULL>List Committee/Group Users</a>";
      $txt .= "<li><a href=UserDocs>Storage Used</a>";
      $txt .= "<li><a href=ContactCats>Contact Categories</a>";      
    } else {
      $txt .= "<li><a href=ListUsers>List Committee/Group Users</a>";    
    }
    $txt .= "</ul><p>\n";
  }



// *********************** Misc *****************************************************************
  if ($x = SKTable('Misc','Misc')) {
    $txt .= $x;
    $txt .= "<ul>\n";
//    $txt .= "<li><a href=StewardView>Stewarding Applications (old)</a>\n";
    $txt .= "<li><a href=Volunteers?A=New>Volunteering Application Form</a>\n";
    $txt .= "<li><a href=Volunteers?A=List>Volunteers (new)</a>\n";
    if (Access('Staff','Photos')) {
      $txt .= "<li><a href=PhotoUpload>Photo Upload</a>";
      $txt .= "<li><a href=PhotoManage>Photo Manage</a>";
      $txt .= "<li><a href=GallManage>Gallery Manage</a>";
    }
    $txt .= "<p>";
    
//    $txt .= "<li><a href=LaughView?Y=$YEAR>Show Laugh Out Loud applications</a>";
    if (Access('Committee')) $txt .= "<li><a href=Campsite?Y=$YEAR>Manage Campsite Use</a>\n"; 
    if (Access('Staff')) $txt .= "<li><a href=CarerTickets?Y=$YEAR>Manage Carer / Partner Tickets</a>\n"; 
    if (Access('Staff','Sponsors')) $txt .= "<li><a href=TaxiCompanies>Manage Taxi Company List</a>\n"; 
    if (Access('SysAdmin')) $txt .= "<li><a href=ConvertPhotos>Convert Archive Format</a>";
//    $txt .= "<li><a href=ContractView>Dummy Music Contract</a>";
    $txt .= "</ul>\n";
  }


// *********************** GENERAL ADMIN *********************************************************
  if ($x = StaffTable('Any','General Admin')) {
    $txt .= $x;
    $txt .= "<ul>\n";

    if (Capability('EnableAdmin') && Access('Committee','News')) {
//      $txt .= "<li><a href=NewsManage>News Management</a>";
      $txt .= "<li><a href=ListArticles>Front Page Article Management</a>";
      $txt .= "<li><a href=LinkManage>Manage Other Fest Links</a>\n";
    }
    if (Access('Steward')) {
      $txt .= "<li><a href=AddBug>New Bug/Feature request</a>\n";
      $txt .= "<li><a href=ListBugs>List Bugs/Feature requests</a><p>\n";
    }

    if (Access('Staff')) $txt .= "<li><a href=TEmailProformas>EMail Proformas</a>";
    if (Access('Staff')) $txt .= "<li><a href=AdminGuide>Admin Guide</a> \n";
    if (Access('SysAdmin')) {
//      $txt .= "<li><a href=BannerManage>Manage Banners</a> \n";
      $txt .= "<li><a href=GameData?Y=$YEAR>Game Settings</a> \n";
      $txt .= "<li><a href=MasterData>Star Kingdoms System Data Settings</a> \n";
    }
    $txt .= "</ul>\n";
  }

  $txt .= "</table></div>\n";

  echo "<h3>Jump to: ";
  $d = 0;
  foreach ($Heads as $Hd) {
    $hnam = preg_replace("/[^A-Za-z0-9]/", '', $Hd);
    $Hd = preg_replace("/ /",'&nbsp;',$Hd);
//    if ($d++) echo ", ";
    echo "&gt;&nbsp;<a href='#Staff$hnam'>$Hd</a> ";
  }
  echo "</h3><br>";
  echo $txt;
  dotail();
?>

