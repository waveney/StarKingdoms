<?php

  include_once("sk.php");
  global $FACTION,$GAMEID,$USER;
  include_once("GetPut.php");
  include_once("PlayerLib.php");
  
  dostaffhead("User Guide");
  
?>
<div style=max-width:800>
<H1>User Guide - Players</h1>

<h2>Basic Principles</h2>
Players don't need to login.  Once you have followed your faction's link a session cookie is used to identify you.<p>

There is far more than I describe here, but this is what you can do now/near term.<p>

Anywhere there is a table, you can click on a column to sort by that column.  Click again to sort the other way.<p>

<h2>Player States</h2>
There are four states:
<ul>
<li>Setup - what it says.  Not yet written
<li>Turn Planning - Do and see everything
<li>Turn Submitted - You think you are ready.  Players can't change anything in this state, but you can easily revert to Turn Planning.
<li>Turn Being Processed - GM's lockdown.  Most features not accessible.
</ul>

<h2>Maps</h2>
See the node map of the systems you know about and links connecting them.<p>

Click on a system to get a survey report.  If you have something there with eyes/camera, you will get appropriate lists.<p>

Note in this game we have link numbers with universal meaning, likewise for systems. I had planned to make it unknown until you travelled along/to them.<p>

<h2>Technologies</h2>
List of all the built in technologies and their pre-requisits.<p>

Click on the name to expand the full explanation.<p>

If you know of non-standard technologies they will also appear - even if you are yet to research them.<p>

<h2>What Can I See?</h2>
For each system where you have eyes/cameras - what they can see.<p>

You can always see everything of yours, but not necessarily everything of others.<P>

<h2>Worlds and Colonies</h2>
List of your worlds  and their economies.<p>

You can name your worlds and add a text description.<p>

The Relative importance controls the order of display in the project list below (or will do).<P>

<h2>Name Places</h2>
Name systems, planets and moons you have visited.<p>

Give the System Reference, the current names of Planets/moons to identify the one you want to change.  Then give the name.<p>

If you control the system they are public names, otherwise they only appear to you.<p>

<h2>Anomalies that have been seen</h2>
A list of all known anomalies and where you are on analysing them.<p>

Note: Analysing Anomalies is an instruction see below.<p>


<h2>Worlds and Things with Projects</h2>
To manage construction, and district based projects. <p>

You can switch between seeing 10 turns and 50+ turns - click on the right.<p>

There is a list of Worlds that are colour coded.   Click on one to toggle the showing of activity there.<p>

Click on the district type/construction to expand that area and actually change anything<p>

Click on a [+] button to start/change a project in that location on that turn.<p>

To rush a project change the rush number (or click up/down).<p>

The display sometimes gets confused. If that happens go to your Faction Menu and select Projects again.<P>

Note: This does not <b>YET</b> handle techs, districts etc changing over the period you are planning projects.<p>

On the right is a ROUGH idea to your forward credits.  It does not allow for other uses of credits (such as for Deep space and paying other factions).  
It does not allow for income from other factions and the building of districts, thing and technologies that gain you income.<p>


<h2>List of Things</h2>
Everything you own from Ships, Armies, Warp Gates, Embassies, Named Characters etc.<p>

Click on the name to change names, crew, gadgets, notes, description, add an image if you like.<p>

Click on the <b>Move</b> to have that thing move in your next turn.  It shows a mini map - click on the link you want<p>

Movement from the list only works for Ships and Agents at the moment.<p>

Militia will only be created for a world that is under attack.<p>

If you click on the name of the thing, for lots more detail:
<h3>Instructions</h3>
You can do special actions such as colonisation, dissasembly, warping home and deep space projects.<p>

Only those actions you can actually do at that location are shown.<p>

Some actions ask supplimentary questions such as the district type to make on colonisation.<p>

One Instruction of note is for <b>Analyse Anomaly</b>.  You must be where the anomaly is to issue the instruction.<p>

If there are multiple anomalies, you will be asked to pick the one you want to study.<p>

Other ships in the same location can also help study the same anomaly, you need to give each their own instruction.<p>

There is no automation covering Anomaly Collaboration at present - if used notify the GMs.<p>

<h3>Deep Space Construction</h3>
Almost all Deep Space construction projects are built in.<p>

Your ships need to be in the location at the start of the turn to do a DSC project.

There is no automation covering multiple ships working together on DSC, if used please notify the GMs.<p>

<h3>Moving armies and named characters</h3>
You can load/unload named characters to and from things and armies on to transport ships.  These can normally be done both now and in turn.<p>

Named characters take no space and can be part of (almost) anything.  Armies take space, you will not be able to overload a ship.<p>

If you want to move troops to another system you need to unload on your turn (which is after movement).<p>

Under conflict conditions, you are only allowed to do this on your turn.<p>

You can also load/unload on to things of other factions, as long as they have given permission to load.  This can be for one turn or ongoing.<p>

If you are loaded on someone elses ship they can unload you any time and place they like (including deep space).


<h2>Plan a Thing</h2>
Design a Ship/Army/Agent/Space station etc.  You can design illegal things, you just won't be able to make them.<p>

<h2>Economy</h2>
Current credits and this turns expenditure and expected income.<p>

This also shows any science points and Adianite you have.<p>

Please use turn text to cover any use of science points or Adianite.<p>

<h2>Banking</h2>
Transfer credits to another player or for RP actions.  You can do one off transfers and standing orders - No direct debits.<p>

You can also see all credits in and out for as far back as you want to.<p>

Adianite is also tradeable (if you have any).<p>

<h2>Turn Actions Text</h2>
Access to automatically generated text for all turns covereying everything done automatically by the system. to see back to previous turns click appropriate turn top right.<p>

<h2>Submit Turn</h2>
Guess what that does?<p>

It does some limited checking for errors and ommisions<p>

Once submitted, you view everything but not do changes.  You can revert to Turn planning as long as you do it before the GMs start turn processing.<p>
<h2>Faction Information</h2>
Name, total credits, traits, a picture, science points.<P>

If a trait is automated in the system it will indicate that it is automated, for all other cases you need to do the RP yourself and tell GMs if it affects underlying mechanics.<p>

<h2>Allow Others Control</h2>
Enable your things to carry things of other factions.  Allow others to use your Warp gates.  Allow others to repair your ships.<p>

Controls can be for one turn or ongoing.<p>

<h2>Why can't I do that?</h2>
Ask, I may not have realised you want to do it.<p>

Warning - I have a terrible record of saying no, then doing it a few hours later.<p>

<h1>User Guide - GM</h1>
GM's do need to log in.  They have lots more features, and can also act as Players as well.<p>

It is hypothetically possible for a player in one game to be a GM in another (not tested).<p>

Most User Guide to be written for this<p>

<h1>Technical bits - if you are interested</h1>
Mostly written in php (20K lines), some javascript (1700 lines), some css.  About 44 tables at time of writting of this paragraph.<p>

Graphics by GraphViz.<p>
Uses the star system generator at <a href=https://donjon.bin.sh/scifi/system/index.cgi>https://donjon.bin.sh/scifi/system/index.cgi</a> to initially populate the map.<p>
</div>
<?php
  dotail();
?>

