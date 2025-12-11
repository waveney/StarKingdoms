<?php
chdir('Schema');
$skema = `skeema pull`;
echo $skema . "\n\n";
chdir('..');
$Ignore = `git add -A`;

$commits = `git shortlog -s -n`;
$lines = explode("\n",$commits);
$ctot = 0;
$ct = '';
foreach ($lines as $line) {
	preg_match('/ *(\d+)/',$line,$ct);
	if ($ct) $ctot += 0+$ct[1];
}
$txt = "\$VERSION=\"" . gmdate('Y') . ".$ctot" . '"';
file_put_contents("Version.php","<?php
$txt;
?>");
$Ignore = `git add -A`;
