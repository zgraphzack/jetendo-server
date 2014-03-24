<?php
require("config/server-mac-mapping.php");

function getConfigPath(){
	global $arrMacAddress;
	// find mac address for each ethernet adapter
	$result=`/sbin/ifconfig -a`;
	$arrSplit=explode("\n", $result);

	$arrMac=array();
	for($i=0;$i<count($arrSplit);$i++){
		$p=strpos($arrSplit[$i], "HWaddr");
		if($p===FALSE){
			continue;
		}
		$mac=str_replace(":", "", trim(substr($arrSplit[$i], $p)));
		array_push($arrMac, $mac);
	}

	if(!array_key_exists($arrMacAddress, $mac)){
		echo "Missing mac address, Jetendo can't start.";
		exit;
	}

	// compare them with jetendo server mac config file to determine which configuration to use
	$configPath="/config/".$arrMacAddress[$mac]."/";
	return $configPath;
}

function checkAvailableServers(){
	// loop all the servers
	for($i=0;$i<count($arrServer);$i++){
		$configPath=...;
		if(server_down){
			file_put_contents($configPath."jetendo_server_down", "1");
		}else{
			unlink($configPath."jetendo_server_down");
		}
	}
}

?>