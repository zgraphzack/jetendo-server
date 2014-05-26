<?php
ini_set('default_socket_timeout', 100);
$d=realpath(dirname(__FILE__)."/../");
require($d."/config/server-mac-mapping.php");

if(!is_dir('/var/jetendo-server/shared/serverStatus/')){
	mkdir("/var/jetendo-server/shared/serverStatus/", 0700, true);
}
if(!is_dir('/var/jetendo-server/logs/')){
	mkdir("/var/jetendo-server/logs/", 0700, true);
}

function microtime_float(){
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}
$startTime=microtime_float();

function getConfigPath(){
	global $arrMacAddress;
	// find mac address for each ethernet adapter
	$result=`/sbin/ifconfig -a`;
	$arrSplit=explode("\n", $result);

	for($i=0;$i<count($arrSplit);$i++){
		$p=strpos($arrSplit[$i], "HWaddr");
		if($p===FALSE){
			continue;
		}
		$mac=strtoupper(trim(substr($arrSplit[$i], $p+6)));
		if(isset($arrMacAddress[$mac])){
			// compare them with jetendo server mac config file to determine which configuration to use
			$configPath=dirname(dirname(__FILE__))."/config/".$arrMacAddress[$mac]."/";
			return $configPath;
		}
	}
	echo "Missing mac address, Jetendo can't start.";
	exit;

}
function getProductionServers(){
	global $arrMacAddress;
	$arrProduction=array();
	for($i=0;$i<count($arrMacAddress);$i++){
		$isHostServer=false;
		require("/var/jetendo-server/config/".$arrMacAddress[$i]."/config.php");
		if($environment=="production" && $isHostServer){
			array_push($arrProduction, $machine);
		}

	}
	return $arrProduction;
}
function updateServerAvailability(){
	global $machineName;
	if(file_exists("/var/jetendo-server/logs/jetendo_server_down")){
		$down=true;
		$cmd="/bin/echo  1  > /proc/sys/net/ipv4/icmp_echo_ignore_all";
		echo $cmd."\n";
		//$r=`$cmd`;
		echo $r."\n";
	}else{
		$down=false;
		$cmd="/bin/echo  0  > /proc/sys/net/ipv4/icmp_echo_ignore_all";
		echo $cmd."\n";
		//$r=`$cmd`;
		echo $r."\n";
	}
	file_put_contents("/var/jetendo-server/shared/serverStatus/".$machineName, "0");
}

function dieWithError($error){
	global $hostname;
	$to      = get_cfg_var('jetendo_developer_email_to');
	$subject = 'Error occurred on '.$hostname;
		
	$headers = 'From: '.get_cfg_var('jetendo_developer_email_from')."\r\n" .
		'Reply-To: '.get_cfg_var('jetendo_developer_email_from')."\r\n" .
		'X-Mailer: PHP/' . phpversion();
	$message = 'Error occurred on '.$hostname."\n\nError: ".$error;

	mail($to, $subject, $message, $headers);
	echo $error;
	exit;
}

function checkAvailableServers(){
	return;
	// loop all the servers
	for($i=0;$i<count($arrServer);$i++){
		$configPath="";
		if($server_down){
		}else{
		}
	}
}

$isTestProductionServer=false;
$configPath=getConfigPath();
$memoryDumpURL="";
$environment="";
$jetendoAdminDomain="";
$disableKVM=false;
$host="";
$hostname="";
$machine=array();
$arrMount=array();
$arrService=array();
$arrCommand=array();
echo "Loading configuration: ".$configPath."config.php\n";
require_once($configPath."config.php");

$arrPath=explode("/", $configPath);
$serverName=$arrPath[1];
$serverPath="/var/jetendo-server/virtual-machines/".$serverName."/";

if(!$isHostServer){
	if(!$disableKVM && count($machine)==0){
		dieWithError('You must set the $machine variable in '.$configPath.'config.php.');
	}
}
if(array_key_exists("railo", $arrServiceMap) && $memoryDumpURL == ""){
	dieWithError('You must set the $memoryDumpURL variable to a valid url.');
}
if(count($arrServiceMap)==0){
	dieWithError('You must set the $arrServiceMap variable to enable at least one service.');
}
if($environment == ""){
	dieWithError('You must set the $environment variable to "production" or "development".');
}
if($hostname == ""){
	dieWithError('You must change the $hostname variable to be a unique domain.');
}

?>