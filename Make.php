<?php
chdir('Schema');
$skema = `skeema pull`;
echo $skema . "\n\n";
chdir('..');

$commits = `git shortlog -s -n`;
$lines = explode("\n",$commits);
$ctot = 0;
foreach ($lines as $line) {
	preg_match('/ *(\d+)/',$line,$ct);
	if ($ct) $ctot += 0+$ct[1];
}
file_put_contents("Version.php","<?php
\$VERSION=$ctot;
?>");
