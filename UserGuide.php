<?php

  include_once("sk.php");
  global $FACTION,$GAMEID,$USER;
  include_once("GetPut.php");
  include_once("PlayerLib.php");
  
  dostaffhead("User Guide");
  
?>
<H1>User Guide - Players</h1>

<h2>Basic Principles</h2>
Players don't need to login.  Once you have followed your faction's link a session cookie is used to identify you.<p>

There is far more than I describe here, but this is what you can do now/near term.<p>

<h2>Player States</h2>
There are four states:
<ul>
<li>Setup - what it says.  Not yet written
<li>Turn Planning - Do and see everything
<li>Turn Submitted - You think you are ready.  Players can't change anything in this state, but you can revert to Turn Planning.
<li>Turn Being Processed - GM's lockdown. Most features not accessible.
</ul>

<h2>Maps</h2>
See the node map of the systems you know about and links connecting them.<p>

Click on a system to get a survey report.  If you have something there with eyes/camera, you will get appropriate lists.<p>

Note in this game we have link numbers with universal meaning, likewise for systems. I had planned to make it unknown until you travelled along/to them.<p>

<h2>Technologies</h2>
List of all the built in technologies and their prerequisits.<p>

Click on the name to expand the full explanation.<p>

If you know of non-standard technologies they will also appear.<p>

<h2>What Can I See?</h2>
For each system where you have eyes/cameras - what they can see.<p>

<h2>Worlds and Colonies</h2>
List of your worlds  and their economies.<p>

You can name your worlds and add a text description.<p>

The Relative importance controls the order of display in the project list below (or will do).<P>

<h2>Worlds and Things with projects</h2>
To manage construction, and district based projects.  And in the near future Deep Space construction.<p>

You can switch between seeing 10 turns and 50+ turns - click on the right.<p>

There is a list of Worlds that are colour coded.   Click on one to toggle the showing of activity there.<p>

Click on the district type/construction to expand that area and actually change anything<p>

Click on a [+] button to start/change a project in that location on that turn.<p>

To rush a project change the rush number (or click up/down).<p>

<h2>List of Things</h2>
Everything you own from ships, armies, warp gates, Embassies etc.<p>

Click on the name to change names, crew, gadgets, notes, description, add an image if you like.<p>

Click on the <b>Move</b> to have that thing move in your next turn. (Not Written Yet)<p>

<h2>Plan a Thing</h2>
Design a Ship/Army/Agent/Space station etc.  You can design illegal things, you just won't be able to make them.<p>

<h2>Economy</h2>
Not Written<P>

<h2>Banking</h2>
Transfer credits to another player or for RP actions.  You can do one off transfers and standing orders - No direct debits.<p>

You can also see all credits in and out for as far back as you want to.<p>

<h2>Turn Actions Text</h2>
Access to automatically generated text for all turns covereying everything done automatically by the system. to see back to previous turns click appropriate turn top right.<p>

<h2>Submit Turn</h2>
Guess what that does?<p>

In the future it will include checking for errors and ommisions<p>

Once submitted, you can't do much, but can revert to Turn planning as long as you do it before the GMs start turn processing.<p>
<h2>Faction Information</h2>
Name, total credits, traits, a picture, science points.<P>


<h2>Why can't I do that?</h2>
Ask, I may not have realised you want to do it.<p>

Warning I have a terrible record of saying no, then doing it a few hours later.<p>

<h1>User Guide - GM</h1>
GM's do need to log in.  They have lots more features, and can also act as Players as well.<p>

It is hypothetically possible for a player in one game to be a GM in another (not tested).<p>

Mostly to be written<p>

<h1>Technical bits - if you are interested</h1>
Mostly written in php, some javascript, some css.  About 40 tables at time of writting of this paragraph.<p>

Graphics by GraphViz.<p>
Uses the star system generator at <a href=https://donjon.bin.sh/scifi/system/index.cgi>https://donjon.bin.sh/scifi/system/index.cgi</a> to initially populate the map.<p>

<?php
  dotail();
?>
