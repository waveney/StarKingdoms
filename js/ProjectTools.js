var Dis;

function Get_DistData() {
  if (!Dis) Dis = JSON.parse(atob(document.getElementById('DistData').value));
  return Dis;
}

function Toggle(id) {
  $("." + id).toggle();
}

function TxoggleHome(id) {
debugger;
//  var x = Get_DistData();

//  var HostId = x[id][0]['HostId'];
  $(".Home" + id).toggle();
  x = 1;
  x = 2;
  
//  for (Hi in Dis) {
//    for (Di in Dis[Hi]) {
//    } 
//  }
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
    $('.Home' + id).toggle(true);
    HomeBut.classList.remove('Homehidden');
  } else {
    $('.Home' + id).toggle(false);
    HomeBut.classList.add('Homehidden');
  }
}

function RushChange(Turn,Projn,Homeindx,Distindx,MaxRush) {
debugger;
  AutoInput('Rush' + Turn + ':' + Projn);
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


