#!/usr/bin/php
<?
require_once("/usr/local/emhttp/plugins/dynamix/include/Wrappers.php");

$cfg = parse_plugin_cfg("ca.mover.tuning");
$vars = @parse_ini_file("/var/local/emhttp/var.ini");
$cron = ( $argv[1] == "crond" );

function logger($string) {
	global $cfg;
	
	if ( $cfg['logging'] == 'yes' ) {
		exec("logger ".escapeshellarg($string));
	}
}

function startMover() {
	$descriptorspec = array(
		0 => array("pipe", "r"),  // stdin is a pipe that the child will read from
		1 => array("pipe", "w"),  // stdout is a pipe that the child will write to
		2 => array("file", "/tmp/error-output.txt", "a") // stderr is a file to write to
	);
	proc_open("/usr/local/sbin/mover.old",$descriptorspec,$pipes);
}

if ( ! $cron && $cfg['moveFollows'] != 'follows') {
	logger("Manually starting mover");
	startMover();
	exit();
}

if ( $cron && $cfg['moverDisabled'] == 'yes' ) {
	logger("Mover schedule disabled");
	exit();
}

if ( $cfg['parity'] == 'no' && $vars['mdResyncPos'] ) {
	logger("Parity Check / rebuild in progress.  Not running mover");
	exit();
}

$usedSpace = trim(exec("df --output=pcent /mnt/cache | tail -n 1 | tr -d '%'"));
if ( (100-$cfg['threshold']) < $usedSpace ) {
	logger("Cache used space threshhold ({$cfg['threshold']}) not exceeded.  Free Space: $usedSpace.  Not moving files");
	exit();
}
logger("Starting Mover");
startMover();
?>