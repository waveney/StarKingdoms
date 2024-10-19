var Dis;

function Get_DistData() {
  if (!Dis) Dis = JSON.parse(atob(document.getElementById('DistData').value));
  return Dis;
}

function Toggle(id) {
  $("." + id).toggle();
}

function ToggleAllBut(id) {
  var x = Get_DistData();
  for (var h in x) {
    if (h != id) $(".Home" + h).toggle();
  }
}

function ToggleHome(id) {
debugger;
  var HomeBut = document.getElementById('PHome'+id);
  x =1;
  if (HomeBut.classList.contains('Homehidden')) {
    $('.PHName.Home' + id).toggle(true);
    HomeBut.classList.remove('Homehidden');
  } else {
    $('.Home' + id).toggle(false);
    HomeBut.classList.add('Homehidden');
  }
}

function ToggleCat(id) {
debugger;
  var HomeBut = document.getElementById('PCat'+id);
  x =1;
  if (HomeBut.classList.contains('Cathidden')) {
    $('.PHName.Cat' + id).toggle(true);
    HomeBut.classList.remove('Cathidden');
  } else {
    $('.Cat' + id).toggle(false);
    HomeBut.classList.add('Cathidden');
  }
}


function RushChange(Turn,Projn,Homeindx,Distindx,MaxRush,sfx='') {
debugger;
  AutoInput('Rush' + Turn + ':' + Projn,sfx);
  var costthis = document.getElementById("ProjC" + Turn + ':' + Homeindx + ':' + Distindx).innerHTML;
  if (costthis && costthis > 0) { 
    var Acts = document.getElementById("ProjP" + Turn + ':' + Homeindx +':' + Distindx).innerHTML;  
    var XofY = Acts.match(/(\d*)\/(\d*)/);
    var ProjActs = Number(XofY[2]);
    var TotProg = 0;
  } else {
    var Acts = document.getElementById("ProjP" + Number(Turn-1) + ':' + Homeindx + ":" + Distindx).innerHTML;
    var XofY = Acts.match(/(\d*)\/(\d*)/);
    var ProjActs = Number(XofY[2]);
    var TotProg = Number(XofY[1]);
  }
  
  var Project = document.getElementById("ProjN" + Turn + ':' + Homeindx + ":" + Distindx).innerHTML;
  var Level = document.getElementById("ProjL" + Turn + ':' + Homeindx + ":" + Distindx).innerHTML;
  var ETurn = Turn;

  while (TotProg < ProjActs) {
    var OrigProg = TotProg;
//debugger;
    var stuff = ETurn + ':' + Homeindx + ":" + Distindx;
    document.getElementById("ProjN" + stuff).innerHTML = Project;
    document.getElementById("ProjL" + stuff).innerHTML = Level;
    if (ETurn != Turn) document.getElementById("ProjC" + stuff).innerHTML = '';
    var CRush = document.getElementById("Rush" + ETurn + ':' + Projn);
    var ProgTurn = MaxRush + (CRush? Number(CRush.value): 0);
    TotProg += ProgTurn;
    if (TotProg > ProjActs) TotProg = ProjActs;
    document.getElementById("ProjP" + stuff).innerHTML = TotProg + '/' + ProjActs;
    if (ETurn != Turn) 
    document.getElementById("ProjT" + stuff).innerHTML = ( ((TotProg >= ProjActs)?"Completed": ((OrigProg==0)?"Started":"Ongoing")) );
    ETurn++;     
  }
  stuff = ETurn + ':' + Homeindx + ":" + Distindx;
  var nxtP = document.getElementById("ProjN" + stuff);
  if (nxtP && nxtP.innerHTML == Project) {
    document.getElementById("ProjC" + stuff).innerHTML = '';
    document.getElementById("ProjN" + stuff).innerHTML = '';
    document.getElementById("ProjL" + stuff).innerHTML = '';
    document.getElementById("ProjP" + stuff).innerHTML = '';
    document.getElementById("ProjT" + stuff).innerHTML = ''; 
  }
}

/*
function ModuleCheck() { // May be redundant
debugger;
  var max_modules = Number(document.getElementById("MaxModules").innerHTML);
  
  var highestMod = documnetgetElementById('HighestModule').value;
  var tot = 0;
  for (var mi=1, mi <= highestMod, mi++) {
    var c =  Number(document.getElementById("MaxModules").innerHTML); 
  }
}
*/

function Add_Bespoke() {
  $('.ProfButton').addClass('BespokeBorder');
  $('.ProfSmallButton').addClass('BespokeBorder');
  $('.Bespoke').toggle();
  $('.ProjHide').toggle();
}

function Remove_Bespoke() {
  $('.BespokeBorder').removeClass('BespokeBorder');
  $('.Bespoke').toggle();
  $('.ProjHide').toggle();
}

function NewProjectCheck(Turn,Hi,Di) {
debugger;
  var stuff = Turn + ':' + Hi + ":" + Di;
  var CanPro = document.getElementById("ProjN" + stuff).innerText;
  if (!confirm("Do you want to cancel " + CanPro + "?")) return false;
//  return true;
  window.location.pathname = "ProjNew.php?t=" + Turn + "&Hi=" + Hi + "&Di=" + Di;
}

var ListFaction = 0;
var ListGM = 0;
var ListType = 0;
var ListBuild = 0;

function ThingListFilter() {
debugger;
  var Show = $("input[name=ThingShow]:checked").val();
  var dbg = document.getElementById('Debug');
  if (Show != ListType && ListFaction) {
    $.post("setfield.php", {'T':'Factions', 'I':ListFaction, 'F':(ListGM?'GMThingType':'ThingType'), 'V':Show} , function( data ) { 
      if (dbg) { 
        $('#Debug').html( data)
        }
      }
    );
  }
  var Build = $("input[name=BuildShow]:checked").val();
  if (Build != ListBuild && ListFaction) {
    $.post("setfield.php", {'T':'Factions', 'I':ListFaction, 'F':(ListGM?'GMThingBuild':'ThingBuild'), 'V':Build}, function( data ) { 
      if (dbg) { 
        $('#Debug').html( data);
        }
      }
    );  
  }
  $(".ThingList").each(function() {
    if (Show == 0 && Build==0) { $(this).show(); return }
    var hide = 1;
    if (Show ==0) hide =0;
    if (Show ==1 && $(this).hasClass("Thing_Ship")) hide = 0;
    if (Show ==2 && $(this).hasClass("Thing_Army")) hide = 0;
    if (Show ==3 && $(this).hasClass("Thing_Agent")) hide = 0;
    if (Show ==4 && $(this).hasClass("Thing_Chars")) hide = 0;
    if (Show ==5 && $(this).hasClass("Thing_Other")) hide = 0;
    if (Show ==6 && $(this).hasClass("Thing_Prisoner")) hide = 0;
    
    if ((Build > 0 && ! $(this).hasClass("Thing_Build" + (Build-1))) ||
        (Build == 0 && $(this).hasClass("Thing_Build4")))  hide =1;
    if (hide) {
      $(this).hide(); return
    } else {
      $(this).show(); return
    }
  })
}

function ListThingSetup (Fact,GM,TType,TBuild) {
debugger;
  ListFaction = Fact;
  ListGM = GM;
  ListType = TType;
  ListBuild = TBuild;
  ThingListFilter();
}

// This is old code

function ThingListShow() {
debugger;
  var Show = $("input[name=ThingShow]:checked").val();
  switch (Show) {
  case "0":
    $(".Thing_Ship").show();
    $(".Thing_Army").show();
    $(".Thing_Agent").show();
    $(".Thing_Other").show();
    return;
  case "1":
    $(".Thing_Ship").show();
    $(".Thing_Army").hide();
    $(".Thing_Agent").hide();
    $(".Thing_Other").hide();
    return;
  case "2":
    $(".Thing_Ship").hide();
    $(".Thing_Army").show();
    $(".Thing_Agent").hide();
    $(".Thing_Other").hide();
    return;
  case "3":
    $(".Thing_Ship").hide();
    $(".Thing_Army").hide();
    $(".Thing_Agent").show();
    $(".Thing_Other").hide();
    return;
  case "4":
    $(".Thing_Ship").hide();
    $(".Thing_Army").hide();
    $(".Thing_Agent").hide();
    $(".Thing_Other").show();
    return;
  }  
}

function ThingListBuild() {
debugger;
  var Build = $("input[name=BuildShow]:checked").val();
  switch (Build) {
  case "0":
    $(".Thing_Build0").show();
    $(".Thing_Build1").show();
    $(".Thing_Build2").show();
    $(".Thing_Build3").show();
    $(".Thing_Build4").hide();
    return;
  case "1":
    $(".Thing_Build0").show();
    $(".Thing_Build1").hide();
    $(".Thing_Build2").hide();
    $(".Thing_Build3").hide();
    $(".Thing_Build4").hide();
    return;
  case "2":
    $(".Thing_Build0").hide();
    $(".Thing_Build1").show();
    $(".Thing_Build2").hide();
    $(".Thing_Build3").hide();
    $(".Thing_Build4").hide();
    return;
  case "3":
    $(".Thing_Build0").hide();
    $(".Thing_Build1").hide();
    $(".Thing_Build2").show();
    $(".Thing_Build3").hide();
    $(".Thing_Build4").hide();
    return;
  case "4":
    $(".Thing_Build0").hide();
    $(".Thing_Build1").hide();
    $(".Thing_Build2").hide();
    $(".Thing_Build3").show();
    $(".Thing_Build4").hide();
    return;
  case "5":
    $(".Thing_Build0").hide();
    $(".Thing_Build1").hide();
    $(".Thing_Build2").hide();
    $(".Thing_Build3").hide();
    $(".Thing_Build4").show();
    return;
  }  
}

