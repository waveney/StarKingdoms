function ToggleGroup(id) {
  $(".Group" + id).toggle();
}

function RushChange(Turn,Projn,Homeindx,Distindx,MaxRush) {
debugger;
  AutoInput('Rush' + Turn + ':' + Projn);
  var costthis = document.getElementById("ProjC" + Turn + ':' + Homeindx + ':' + Distindx).innerHTML;
  if (costthis) { 
    var Acts = document.getElementById("ProjP" + Turn + ':' + Homeindx +':' + Distindx).innerHTML;  
    var XofY = Acts.match(/(\d*)\/(\d*)/);
    var ProjActs = XofY[2];
    var TotProg = 0;
  } else {
    var Acts = document.getElementById("ProjP" + Number(Turn-1) + ':' + Homeindx + ":" + Distindx).innerHTML;
    var XofY = Acts.match(/(\d*)\/(\d*)/);
    var ProjActs = XofY[2];
    var TotProg = XofY[1];
  }
  
  var Project = document.getElementById("ProjN" + Turn + ':' + Homeindx + ":" + Distindx).innerHTML;
  var Level = document.getElementById("ProjL" + Turn + ':' + Homeindx + ":" + Distindx).innerHTML;
  var ETurn = Turn;

  while (TotProg < ProjActs) {
    var OrigProg = TotProg;
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
  if (document.getElementById("ProjN" + stuff).innerHTML == Project) {
    document.getElementById("ProjC" + stuff).innerHTML = '';
    document.getElementById("ProjN" + stuff).innerHTML = '';
    document.getElementById("ProjL" + stuff).innerHTML = '';
    document.getElementById("ProjP" + stuff).innerHTML = '';
    document.getElementById("ProjT" + stuff).innerHTML = ''; 
  }
}
