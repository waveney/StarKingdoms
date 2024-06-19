<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/phpmailer/phpmailer/src/Exception.php';
require 'vendor/phpmailer/phpmailer/src/PHPMailer.php';
require 'vendor/phpmailer/phpmailer/src/SMTP.php';

define('EMAIL_SYS',0);
define('EMAIL_DANCE',1);
define('EMAIL_TRADE',2);
define('EMAIL_INVOICE',3);
define('EMAIL_USER',4);
define('EMAIL_SIGNUP',5);
define('EMAIL_VOL',6);
define('EMAIL_MAILLIST',7);

function Pretty_Print_To($to) {
  $str = '';
  if (is_array($to)) {
    if (is_array($to[0])) {
      foreach ($to as $i=>$too) {
        if (is_array($too) && isset($too[1])) {
          $a = $too[1];
          $n = ((isset($too[2]) && $too[2])?("&lt;" . $too[2] . "&gt;"):'');
          switch ($too[0]) {
            case 'to':
              $str .= " to: $a $n, ";
              break;
            case 'cc':
              $str .= " cc: $a $n, ";
              break;
            case 'bcc':
              $str .= " bcc: $a $n, ";
              break;
            case 'replyto':
              $str .= " replyto: $a $n, ";
              break;
            case 'from':
              $str .= " from: $a $n, ";
              break;
          }
        } else {
          if (is_array($too)) {
            $str .= "to wierd array:" . implode(", ", $too);
          } else {
            $str .= "to: $too";
          }
        }
      }
    } else {
      $str .= "to: " . $to[0] . (isset($to[1])? " &lt;" . $to[1] . "&gt; ":'');
    }
  } else {
    $str .= "to: " . $to;
  }
  return $str;
}

  require 'vendor/autoload.php';


  use Html2Text\Html2Text;

function ConvertHtmlToText(&$body) {
  $body2 = preg_replace('/<p>/i',"</p>\n<p>",$body);
  $html = new \Html2Text\Html2Text($body2,['do_links' => 'inline']);
  return $html->getText();
}

//$to can be single address, a [address, name] or [[to,address,name],[cc,addr,name],bcc,addr,name],replyto,addr,name]...]
//$atts can be simple fie or [[file, name],[file,name]...]

function NewSendEmail($SrcType,$SrcId,$to,$sub,&$letter,&$attachments=0,&$embeded=0,$from='') {
  global $CONF;

//  echo "Debug: XXX" .( UserGetPref('EmailDebug')?2:0) . "<p>";
//var_dump($sub,$attachments,$to);

/*if (Access('SysAdmin')) {
  echo "Calling NewSendEmail: ";
  var_dump($to); echo "<P>";
  var_dump($from); echo "<P> ";
}*/
  $Send = 1;
  if (!empty($CONF['testing'])){
    if (strstr($CONF['testing'],'@')) {
      $to = $CONF['testing'];
    } else {
      echo "<p>Would send email to " . Pretty_Print_To($to);
      if ($from) echo "From: " . Pretty_Print_To($from);
      echo " with subject: $sub<p>Content:<p>$letter<p>\n";
      debug_print_backtrace();

      echo "Text: " . ConvertHtmlToText($letter);
      if ($attachments) {
        if (!empty($CONF['testing'])) echo var_export($attachments);
        if (is_array($attachments)) {
          foreach ($attachments as $i=>$att) {
            if (is_array($att)) {
              if (isset($att[0])) {
                echo "Would attach " . $att[0] . " as " . $att[1] . "<p>";
              } else {
                echo "Would attach " . $att . "<p>";
              }
            } else {
              echo "Would Attach " . $att . "<p>";
            }
          }
        } else {
          echo "Would attach $attachments<p>";
        }
      }
      if ($embeded) {
        if (!empty($CONF['testing'])) echo var_export($embeded);
        if (is_array($embeded)) {
          foreach ($embeded as $i=>$att) {
            if (is_array($att)) {
              if (isset($att[0])) {
                echo "Would embed " . $att[0] . " as " . $att[1] . "<p>";
              } else {
                echo "Would embed " . $att . "<p>";
              }
            } else {
                echo "Would embed " . $att . "<p>";
            }
          }
        } else {
          echo "Would embed $embeded<p>";
        }
      }
    $Send = 0;
//    exit;  // Uncomment to test in depth
//    return;  // Under test this will then log, and not send
    }
  }
  $From = Feature('SMTPuser');
  $Atts = [];

  $EmailFrom = Feature('EmailFromAllowed',0);
  $EmailReplyTo = Feature('EmailReplyTo',0);

  $email = new PhpMailer(true);
  try {
    $email->SMTPDebug = ((Access('SysAdmin') && UserGetPref('EmailDebug'))?2:0);  // 2 general testing, 4 problems...
    $email->isSMTP();
    $mailserv = Feature('SMPTserver',Feature('HostURL'));
    if (Feature('SMTPsubdomain')) $mailserv = Feature('SMTPsubdomain') . "." . $mailserv;
    $email->Host = $mailserv;
    $email->SMTPAuth = true;
    $email->AuthType = 'LOGIN';
    $email->From = $email->Username = $From;
    $email->FromName = Feature('FestName');
    $email->Password = Feature('SMTPpwd');
    $email->SMTPSecure = 'tls';
    $email->Port = 587;

    if ($EmailFrom) {
      if ($from) {
        if (is_array($from)) {
          $email->setFrom($from[0],$from[1]);
        } else {
          $email->setFrom($from);
        }
      } else {
        $email->setFrom(Feature('DefaultFrom','No-Reply@' . Feature('HostURL')));
        if ($from) {
          $EmailReplyTo = $from;
        }
      }
    } else if ($from) {
      $EmailReplyTo = $from;
    }

    if (is_array($to)) {
      if (is_array($to[0])) {
        foreach ($to as $i=>$too) {
          if (!isset($too[0])) continue;
          $a = str_replace(' ','',$too[1]);
          $n = (isset($too[2])?$too[2]:'');
          switch ($too[0]) {
            case 'to':
              $email->addAddress($a,$n);
              if (empty($n)) {
                trigger_error("Email address missing - $a - $n" );
                exit;
              }
              $To = "$n <$a>";
              break;
            case 'cc':
              $email->addCC($a,$n);
              break;
            case 'bcc':
              $email->addBCC($a,$n);
              break;
            case 'replyto':
              $email->addReplyTo($a,$n);
              $EmailReplyTo = 0;
              break;
            case 'from':
              if ($EmailFrom) {
                $email->setFrom($a,$n);
                $From = "$n <$a>";
              }
              break;
          }
        }
      } else {
        $email->addAddress($to[0],(isset($to[1])?$to[1]:''));
        $To = $to[0];
      }
    } else {
      $email->addAddress($to);
    }
    $email->Subject = $sub;
    $email->isHTML(true);
    $email->Body = $letter; // HTML format
    $email->AltBody = ConvertHtmlToText($letter); // Text format

    if ($EmailReplyTo) $email->addReplyTo($EmailReplyTo,Feature('FestName'));


    if ($attachments) {
      if (is_array($attachments)) {
        foreach ($attachments as $i=>$att) {
          if (is_array($att)) {
            if (isset($att[0])) {
              $email->addAttachment($att[0],$att[1]);
              $Atts[] = [$att[0],$att[1],0];
            } elseif (isset($att['AttFileName'])) {
              $email->addAttachment($att['AttFileName']);
              $Atts[] = ["",$att['AttFileName'],0];
            } else {
              $email->addAttachment($att);
              $Atts[] = ["",$att,0];
            }
          } else {
            $email->addAttachment($att);
            $Atts[] = ["",$att,0];
          }
        }
      } else {
        $email->addAttachment($attachments);
        $Atts[] = ["",$attachments,0];
      }
    }
    if ($embeded) {
      if (is_array($embeded)) {
        foreach ($embeded as $i=>$att) {
          if (is_array($att)) {
            if (isset($att[0])) {
              $email->addEmbeddedImage($att[0],$att[1]);
              $Atts[] = [$att[0],$att[1],1];
            } else {
              $email->addEmbeddedImage($att['AttFileName']);
              $Atts[] = ["",$att['AttFileName'],1];
            }
          } else {
            $email->addEmbeddedImage($att);
            $Atts[] = ["",$att,1];
          }
        }
      } else {
        $email->addEmbeddedImage($embeded,0);
        $Atts[] = ["",$embeded,1];
      }
    }

    if ($Send) $email->Send();

  } catch (Exception $e) {
    echo 'Message could not be sent. Mailer Error: ', $email->ErrorInfo;
  }

  $EmLog = ['Type'=>$SrcType,'TypeId'=>$SrcId,'Subject'=>$sub,'FromAddr'=>json_encode($From),'ToAddr'=>json_encode($to), 'TextBody'=>$letter,'Date'=>time()];
  $logid = Insert_db('EmailLog', $EmLog);
  if ($Atts && $logid) {
    foreach ($Atts as $at) {
      $atc = ['EmailId'=>$logid,'AttName'=>$at[0],'AttFileName'=>$at[1],'AttType'=>$at[2]];
      Insert_db('EmailAttachments',$atc);
    }
  }
}

function Get_Email_Proformas() {
  global $db;
  $full = [];
  $res = $db->query("SELECT * FROM EmailProformas ORDER BY SN ");
  if ($res) while ($typ = $res->fetch_assoc()) $full[$typ['id']] = $typ;
  return $full;
}

function Get_Email_Proformas_By_Name() {
  global $db;
  $full = [];
  $res = $db->query("SELECT * FROM EmailProformas ORDER BY SN ");
  if ($res) while ($typ = $res->fetch_assoc()) $full[$typ['SN']] = $typ;
  return $full;
}

function Get_Email_Proforma($id) {
  global $db;

  if (is_numeric($id)) {
    $res=$db->query("SELECT * FROM EmailProformas WHERE id=$id");
  } else {
    $res=$db->query("SELECT * FROM EmailProformas WHERE SN='$id'");
  }
  if ($res) {
    $ans = $res->fetch_assoc();
    return $ans;
  }
  return 0;
}

function Put_Email_Proforma(&$now) {
  $e=$now['id'];
  $Cur = Get_Email_Proforma($e);
  return Update_db('EmailProformas',$Cur,$now);
}

function Parse_Proforma(&$Mess,$helper='',$helperdata=0,$Preview=0,&$attachments=0,&$embeded=[]) {
  global $PLANYEAR,$YEARDATA,$USERID,$USER;
  static $attnum = 0;
  $Reps = [];
  $Limit = 0;
//echo "Passing <p>";

  while (preg_match('/\*(.*?)\*/',$Mess)) {
//echo "<p>Here<p>";
    if ($Limit++ > 10) break; // Limit recursion to 10 deep
    $Matches = [];
    $mtch = [];
    if (preg_match_all('/\*(\S*)\*/',$Mess,$Matches)) {
      foreach($Matches[1] as $key) {
//echo "Doing $key<br>";
        if (!isset($Reps[$key])) {
          switch ($key) {
          case 'PLANYEAR':
          case 'THISYEAR': // For historic proformas should be removed in time
          case 'YEAR':
            $rep = $PLANYEAR;
            break;
          case 'NEXTYEAR':
            $rep = $PLANYEAR+1;
            break;
          case 'DATES':
            $rep = FestDate($YEARDATA['FirstDay']) . " to " . FestDate($YEARDATA['LastDay'],'M') ;
            break;
          case 'FESTDAY0':
            $rep = FestDate($YEARDATA['FirstDay'],'L') ;
            break;
          case 'FESTIVAL':
            $rep = Feature('FestName');
            break;
          case 'HOST':
            $rep = Feature('HostURL');
            break;
          case 'TOWN':
            $rep = Feature('FestHomeName');
            break;
          case 'ID':
            $rep = ($helperdata['id'] ?? 0);
            break;
          case (preg_match('/MAILTO_(.*)/',$key,$mtch)?true:false):
            $rep = "<a href='mailto:" . $mtch[1] . "@" . Feature('HostURL') . "'>" . $mtch[1] . "@" . Feature('HostURL') . "</a>";
            break;
          case (preg_match('/WEBINT(:.*)/',$key,$mtch)?true:false):
            $bits = preg_split('/:/',$mtch[1],3);
            $url = '';
            if (isset($bits[1])) $url = $bits[1];
            if (isset($bits[2])) { $txt = $bits[2]; $txt = preg_replace('/_/',' ',$txt); }
            $rep = "<a href='https://" . $_SERVER['HTTP_HOST'] . ($url? "/$url" : "") . "'>$txt</a>";
            break;
          case (preg_match('/WEB(:.*)/',$key,$mtch)?true:false):
            $bits = preg_split('/:/',$mtch[1],3);
            $url = '';
            $txt = Feature('HostURL');
            if (isset($bits[1])) $url = $bits[1];
            if (isset($bits[2])) { $txt = $bits[2]; $txt = preg_replace('/_/',' ',$txt); }
            $url = preg_replace_callback('/\%(\S*?)\%/',function ($matches) use ($helper,$helperdata,$Preview,$attachments,$embeded) {
                $rtxt = "*" . $matches[1] . "*";
                Parse_Proforma($rtxt,$helper,$helperdata,$Preview,$attachments,$embeded);
                return $rtxt;
              }, $url);
            $rep = "<a href='https://" . Feature('HostURL') . ($url? "/$url" : "") . "'>$txt</a>";
            break;
          case (preg_match('/URL(:.*)/',$key,$mtch)?true:false):
            $bits = preg_split('/:/',$mtch[1],3);
            $url = '';
            if (isset($bits[1])) $url = $bits[1];
            if (isset($bits[2])) { $txt = $bits[2]; $txt = preg_replace('/_/',' ',$txt); }
            $rep = "<a href='https://$url'>$txt</a>";
            break;
          case (preg_match('/READFILE_(.*)/',$key,$mtch)?true:false):
            $file = file_get_contents($mtch[1]);
            if ($file) {
              $rep = $file;
            } else {
              $rep = "File " . $mtch[1] . " not Found.<p>";
            }
            break;
          case (preg_match('/IMAGE_(.*)/',$key,$mtch)?true:false):
            if (!file_exists($mtch[1])) { $rep = "Image " . $mtch[1] . " Not found<p>"; break;  };
            $sfx = pathinfo($mtch[1],PATHINFO_EXTENSION );
            $embeded[] = [$mtch[1],"img_$attnum.$sfx"];

            if ($Preview) {
              Set_User();
              if (!$attnum) system("rm Temp/$USERID.*");
              $tf = $USERID . "." . $attnum . "." . time() . ".$sfx";
              copy($mtch[1],"Temp/$tf");
              $rep = "<img src='Temp/$tf'>";
            } else {
              $rep = "<img src=cid:img_$attnum.$sfx>";
            }
            $attnum++;
            break;
          case (preg_match('/COPY_(.*)/',$key,$mtch)?true:false):
            $Prof = Get_Email_Proforma($mtch[1]);
            $rep = ($Prof?$Prof:("Unknown Email Proforma " . $mtch[1] . "<p>"));
            break;
          case (preg_match('/FEAT:(.*)/',$key,$mtch)?true:false):
            $bits = explode(':',$mtch[1]);
            $rep = Feature($bits[0],($bits[1]??''));
            break;
          case 'SENDER':
            $rep = (empty($USER['SN'])?"The *FESTNAME*":$USER['SN']);
            break;
          case (preg_match('/DAY(.*)/',$key,$mtch)?true:false):
            $day = $mtch[1];
            $rep = FestDate($day,'L');
            break;
          case (preg_match('/ATTACH_(.*)/',$key,$mtch)?true:false):
            include_once("DocLib.php");
            $fn = "Store" . File_FullName($mtch[1]);
//            var_dump($fn);
            $attachments[]= $fn;
            $rep = ''; //$fn for testing
            break;

          default:
// var_dump($helper,$helperdata,$attachments,$embeded);
            $rep = ($helper?$helper($key,$helperdata,$attachments,$embeded):"@@$key@@");
            break;
          }
        $Reps[$key] =$rep;
        }
      }
      foreach ($Reps as $k=>$v) {
        $qk = preg_quote($k,'/');
//var_dump($qk,$v);
        $Mess = preg_replace("/\*$qk\*/",$v,$Mess);
      }

    }
  }

  $Mess = preg_replace('/(?<!<p>)\n\s*\n+\s*/mi',"\n\n<p>",$Mess);
}


// helper is a function that takes (THING,helperdata,atts) to return THING - not needed for generic fields typical THINGs are DETAILS, DEPOSIT...
// if mescat > 40 chars it is assumed to be the proforma itself
function Email_Proforma($Src,$SrcId,$to,$mescat,$subject,$helper='',$helperdata=0,$logfile='',&$attachments=0,$embeded=0,$from='') {
  global $PLANYEAR,$YEARDATA,$CONF;

  if (strlen($mescat) < 40) {
    $Prof = Get_Email_Proforma($mescat);
    $Mess = ($Prof? $Prof['Body'] : "Unknown message $mescat ");
    if (!$Prof && !empty($CONF['testing'])) debug_print_backtrace();
  } else {
    $Mess = $mescat;
  }
  Parse_Proforma($Mess,$helper,$helperdata,0,$attachments,$embeded);
//echo "<p>After Pass:"; var_dump($attachments);
  NewSendEmail($Src,$SrcId,$to,$subject,$Mess,$attachments,$embeded,$from);
//echo "<p>After Send:"; var_dump($attachments);
  if (isset($Prof)) {
    $Callback = $helper . "_Callback";
//    echo "Got Here";
    if (function_exists($Callback)) {
      $Callback($mescat,$helperdata);
    }
  }

  if ($logfile) {
    $logf = fopen("LogFiles/$logfile","a");
    fwrite($logf,"\n\nOn:" . date('j M Y  H:i:s') . "\nEmail to : " . Pretty_Print_To($to) . "Subject:$subject\n");
    if ($from) fwrite($logf,"From: " . Pretty_Print_To($from));
    fwrite($logf,"\n\n$Mess");

    if ($attachments) {
      fwrite($logf,"\n\nAttachments:\n\n");
      fwrite($logf, var_export($attachments));
      if (is_array($attachments)) {
        foreach ($attachments as $i=>$att) {
          if (is_array($att)) {
            fwrite($logf," With attachment: " . $att[0] . " as " . (empty($att[1])?"Unknown Attachment":$att[1]) . "\n\n");
          } else {
            fwrite($logf," With attachment: " . $att . "\n\n");
          }
        }
      } else {
        fwrite($logf," With attachment $attachments\n\n");
      }
    }
    if ($embeded) {
      fwrite($logf,"\n\Embeded:\n\n");
      fwrite($logf, var_export($embeded));
      if (is_array($embeded)) {
        foreach ($embeded as $i=>$att) fwrite($logf," With embeded: " . $att[0] . " as " . (empty($att[1])?"Unknown embeded":$att[1]) . "\n\n");
      } else {
        fwrite($logf," With embeded $embeded\n\n");
      }
    }

    fclose($logf);
  }
  return $Mess;
}

function Replace_Help($Area='',$Right=0) {
  $Reps = [
  ['*WHO*','First name of contact','All'],
  ['*BIZ*','Business name','Trade,Sponsors'],
  ['*PLANYEAR*/*NEXTYEAR*','Year for the booking, Planyear+1','All'],
  ['*DATES*','Dates of Festival From - to','All'],
  ['*LOCATION*','Location(s) of Pitches','Trade'],
  ['*PRICE*','Total Price quoted','Trade'],
  ['*LINK*','Personal Link for Participants','Trade, Volunteers, Performers'],
  ['*REMOVE*','Remove Request','Trade'],
  ['*FESTLINK*','Link for Committee members direct to that Trader/Volunteer/Performer etc','Trade, Volunteers'],
  ['*DEPOSIT*','Deposit Required','Trade, LNL, BB'],
  ['*BALANCE*','Balance Required','Trade'],
  ['*DETAILS*','Full details of booking etc','Trade, BB, LOL, LNL, Volunteers, Invoices'],
  ['*FINANCIAL*','Trade financial statement','Trade'],
  ['*STATE*','Decsription of application state','Trade'],
  ['*PAIDSOFAR*','Total payments so far','Trade'],
  ['*FESTIVAL*','Name of Festival','All'],
  ['*HOST*','Host URL for festival','All'],
  ['*MAILTO_name*','Inserts a mailto link to name@festival.org','All'],
  ['*BBREF*/*LNLREF*','Unique reference for payments','BB, LNL'],
  ['*PROG*','Programme for performer','Dance (will be all performers)'],
  ['*WEB:*/*WEB:URL:TEXT*','Website for Festival, URL - to follow website, TEXT - To be displayed (NO SPACES - any _ will appear as spaces)','All'],
  ['*WEBINT:URL:TEXT*','Website for the festival back end, URL/TEXT as above.  WEB is the same when they are part of the same server','All'],
  ['*MISSING*','Important information missing from a dance side','Dance'],
  ['*PERF*','Name of performer','Dance,Music,Family,Ceilidh'],
  ['*SIDE*','Name of side','Dance,Music,Family,Ceilidh'],
  ['*TICKBOX:b:TEXT*','Direct link to click a box, b=num(1-4)|Rec(eived)|..., TEXT to be displayed (NO SPACES - any _ will appear as spaces)','Dance,Music,Trade'],
  ['*TRADEMAP*','Trade location and Map info','Trade'],
  ['*WEBSITESTUFF*','Traders photo and product description prompt','Trade'],
  ['*READFILE_file*','Read file as body of message - only use for VERY large messages, contact Richard to use','All'],
  ['*IMAGE_file*','Embed image from file - contact Richard to use','All'],
  ['*DEPCODE*/*BALCODE*/*OTHERCODE*','Payment codes for Deposit, Balance(All), Other Payment','Trade'],
  ['*DUEDATE*','Date Invoice/Payment is Due','Trade, Invoices'],
  ['*PAYCODES*','Details of payment to be made, ammount, account and code to be used','Trade'],
  ['*COPY_name*','Copy Email Proforma name into the current message','All'],
  ['*PAYDAYS*','Days to pay an Invoice','Trade,Invoices'],
  ['*PAIDSOFAR*','Total Money Actually paid so far: Deposit and Balance','Trade'],
  ['*URL:URL:Text*','URL (https:// is prepended, TEXT - To be displayed (NO SPACES - any _ will appear as spaces)','All'],
  ['*DANCEORG*','Sign of for dance messages','Dance'],
  ['*SENDER*','Name of sender - person clicking the email','All'],
  ['*CONTRACT*','Attach Contract to the message','Performers'],
  ['*COLLECTINFO*','Collection Information','Dance, Volunteers'],
  ['*FEAT:Name:Default*','Include value of Feature(Name,Default)','All'],
  ['*TRADEORG*','Organiser(s) of Trade','Trade'],
  ['*DAYnn*','Date of Day nn of festival 0=Friday','All'],
  ['*ATTACH_nn*','Attach File Number nn from the document store','All'],
  ];

  echo "<span " . ($Right?' class=floatright':'') . " id=largeredsubmit onclick=($('.HelpDiv').toggle()) >Click to toggle Standard Replacements Help</span>";
  echo "<div class=HelpDiv hidden>";

  echo "<div class=Scrolltable><table border>\n";
  echo "<tr><td>Code<td>What it does<td>Areas \n";

  foreach($Reps as $r) {
    if ($Area =='' || preg_match("/(All)|($Area)/",$r[2])) echo "<tr><td>" . $r[0] . "<td>" . $r[1] . "<td>" . $r[2] . "\n";
  }
  echo "</table></div></div>\n";
}

function Get_Email_Logs($src,$srcid) {
  global $db;
  $Log = [];
  $Qry = "SELECT * FROM EmailLog WHERE Type=$src AND TypeId=$srcid ORDER BY Date DESC";
//  var_dump($Qry);
  $Recs = $db->query($Qry);
  if ($Recs) while ($Msgs = $Recs->fetch_assoc()) $Log[] = $Msgs;
  return $Log;
}

function Get_Email_Attachments($id) {
  global $db;
  $Atts = [];
  $Qry = "SELECT * FROM EmailAttachments WHERE EmailId=$id";
//  var_dump($Qry);
  $ans = $db->query($Qry);
  if ($ans) while ($Att = $ans->fetch_assoc()) $Atts[] = $Att;
  return $Atts;
}

function Get_Email_Log($id) {
  return db_get("EmailLog","id=$id");
}
