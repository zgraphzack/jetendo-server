<?php
require("library.php");
$currentHost=$host;

$arrHost=array();
for($i=0;$i<count($arrMacAddress);$i++){
	$path="/var/jetendo-server/config/".$arrMacAddress[$i]."/";
	require($path."config.php");
	$arrHost=array(
		"host"=>$host,
		"hostname"=>$hostname,
		"isHostServer"=>$isHostServer
	);
}
$lastStatus="-1";
$arrStatus=array();

for($i=0;$i<70;$i++){
	$arrServerDown=array();
	for($i=0;$i<count($arrHost);$i++){
		if($arrHost['host'] == $currentHost){
			$files=scandir("/var/jetendo-server/shared/serverStatus/");
			if(count($files) > 2){
				// server is down
				if($lastStatus != "1"){
					$lastStatus="1";
					$cmd="/bin/echo  1  > /proc/sys/net/ipv4/icmp_echo_ignore_all";
					$r=`$cmd`;
				}
			}else{
				// server is up
				if($lastStatus != "0"){
					$lastStatus="0";
					$cmd="/bin/echo  0  > /proc/sys/net/ipv4/icmp_echo_ignore_all";
					$r=`$cmd`;
				}
			}
		}
		if($arrHost['isHostServer']){
			$cmd="/bin/ping -c 1 ".$arrHost['hostname'].".";
			$r=`$cmd`;
			$statusPath="/var/jetendo-server/shared/serverStatus/".$arrHost['hostname'];
			if(strpos($r, "bytes from") === FALSE){
				if(!isset($arrStatus[$arrHost['hostname']]) || !$arrStatus[$arrHost['hostname']]){
					$arrStatus[$arrHost['hostname']]=true;
					file_put_contents($statusPath, "1");
				}
				array_push($arrServerDown, $arrHost['hostname']);
			}else{
				if(isset($arrStatus[$arrHost['hostname']])){
					unset($arrStatus[$arrHost['hostname']]);
					unlink($statusPath);
				}
			}
		}
	}
	if(count($arrServerDown)){

	}
	$time=microtime_float()-$startTime;
	if($time > 59){
		exit;
	}
	sleep(1);
}