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

function startMover($options="") {
	global $vars, $cfg;
	
	if ( $options != "stop" ) {
		clearstatcache();
		$pid = @file_get_contents("/var/run/mover.pid");
		if ($pid) {
			logger("Mover already running");
			exit();
		}
	}
	if ( $options == "force") {
		$options = "";
		if ( $cfg['forceParity'] == "no" && $vars['mdResyncPos'] ) {
			logger("Parity Check / Rebuild in Progress.  Not running forced move");
			exit();
		}
	}
	exec("/usr/local/sbin/mover.old $options");
}

if ( $argv[2] ) {
	startMover(trim($argv[2]));
	exit();
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
if ( $cfg['threshold'] > $usedSpace ) {
	logger("Cache used space threshhold ({$cfg['threshold']}) not exceeded.  Used Space: $usedSpace.  Not moving files");
	exit();
}
logger("Starting Mover");
startMover();
?>