<?php
if(get_cfg_var('jetendo_developer_email_to') == ""){
	echo "jetendo.ini must be configured and installed for this php script to function.\n";
	exit;
}

ini_set('default_socket_timeout', 100);
$d=realpath(dirname(__FILE__)."/../");
require($d."/config/server-mac-mapping.php");

if(!is_dir('/var/jetendo-server/shared/serverStatus/')){
	mkdir("/var/jetendo-server/shared/serverStatus/", 0700, true);
}
if(!is_dir('/var/jetendo-server/logs/')){
	mkdir("/var/jetendo-server/logs/", 0700, true);
}
function getMachineObject($serverName, $machineName){
	$machine=false;
	$p="/var/jetendo-server/config/".$serverName."/".$machineName."/config.php";
	require($p);
	if(gettype($machine) == "boolean"){
		echo "machine was not defined in ".$p."\n";
		exit;
	}
	return $machine;
}

function getDefinedMachines(){
	$arrReturn=array();
	$cmd="/usr/bin/virsh list --all";
	$r=`$cmd`;
	$arrMachine=explode("\n", trim($r));
	for($i=2;$i<count($arrMachine);$i++){
		$arr=explode(" ", trim($arrMachine[$i]));
		$arr2=array();
		for($n=0;$n<count($arr);$n++){
			$c=trim($arr[$n]);
			if($c != ""){
				array_push($arr2, $c);
			}
		}
		if(count($arr2) == 4){
			$arr2[2]=$arr2[2]." ".array_pop($arr2);
		}
		if(count($arr2) != 3){
			echo $arrMachine[$i]."\n";
			var_dump($arr2);
			echo "\nInvalid response for virsh list: ".$r."\n";
			exit;
		}else{
			$arrReturn[trim($arr2[1])]=array(
				"state"=>$arr2[2],
				"id"=>$arr2[0]
			);
		}
	}
	return $arrReturn;
}
function waitForMachineShutdown($arrMachineName, $timeoutInSeconds){
	$shutdownWasGraceful=false;
	// try to wait for graceful shutdown
	$time_start=microtime_float();
	while(true){
		if(microtime_float()-$time_start > $timeoutInSeconds){
			break;
		}
		$arrDefinedMachines=getDefinedMachines();
		$shutdownCount=0;
		for($i=0;$i<count($arrMachineName);$i++){
			$machineName=$arrMachineName[$i];
			if($arrDefinedMachines[$machineName]['state'] == "shut off"){
				$shutdownCount++;
			}
		}
		if($shutdownCount == count($arrMachineName)){
			$shutdownWasGraceful=true;
			break;
		}
		sleep(1);
	}
	if($shutdownWasGraceful == false && $arrDefinedMachines[$machineName]['state'] != "shut off"){
		// hard poweroff machine
		$cmd="/usr/bin/virsh destroy ".$machineName." 2>&1";
		echo $cmd."\n";
		$r=`$cmd`;
		echo $r."\n";
	}
}
function shutdownMachine($machineName){
	$shutdownWasGraceful=false;
	$arrDefinedMachines=getDefinedMachines();
	if(isset($arrDefinedMachines[$machineName])){
		if($arrDefinedMachines[$machineName]['state'] == "running"){
			// gracefully shutdown machine
			$cmd="/usr/bin/virsh shutdown ".$machineName." 2>&1";
			echo $cmd."\n";
			$r=`$cmd`;
			echo $r."\n";
		}
	}
}

function swapMachineImage($base, $newBase, $arrVirtualMachine, $verify){
	global $machineShutDownTimeoutInSeconds;
	$r="";
	// loop machines
	$arrShutdown=array();
	$arrDefinedMachines=getDefinedMachines();
	foreach($arrVirtualMachine as $machinePath=>$autoStart){
		$arrPath=explode("/", $machinePath);
		$machineName=array_pop($arrPath);
		$serverName=implode("/", $arrPath);
		if(isset($arrDefinedMachines[$machineName])){
			shutdownMachine($machineName);
			array_push($arrShutdown, $machineName);
		}
	}
	waitForMachineShutdown($arrShutdown, $machineShutDownTimeoutInSeconds);

	foreach($arrVirtualMachine as $machinePath=>$autoStart){
		$arrPath=explode("/", $machinePath);
		$machineName=array_pop($arrPath);
		$serverName=implode("/", $arrPath);
		$serverPath="/var/jetendo-server/virtual-machines/".$arrPath[0]."/";
		$serverMachinePath="/var/jetendo-server/virtual-machines/".$serverName."/";
		if(!is_dir($serverPath)){
			mkdir($serverPath, 0770, true);
		}


		if(file_exists($serverPath."image1.qed") && file_exists($serverPath."image2.qed")){
			echo "Both ".$serverPath."image1.qed and ".$serverPath."image2.qed exist.  You must manually delete one of them and run this script again. Make sure no running machines are using the file you delete.\n";
			exit;
		}

		$machine=getMachineObject($serverName, $machineName);
		if(!isset($machine['name'])){
			var_dump($machine);
			echo "machine['name'] was undefined for machineName=$machineName and it is required.\n";
			exit;
		}
		$xmlPath=$serverMachinePath.$machineName.".xml";
		if($verify){
			foreach($machine["drives"] as $drive=>$driveObj){
				if(isset($driveObj["baseImage"])){
					$basePath=$driveObj["baseImage"]."-".$newBase;
					if(file_exists($basePath)){
						echo "Base image copy already exists and must be manually deleted before this process can continue. Path: ".$driveObj["baseImage"]."-".$newBase."\n";
						exit;
					}
				}
			}
		}

		foreach($machine["drives"] as $drive=>$driveObj){
			if($driveObj["type"] == "cdrom"){
				if(isset($driveObj["file"])){
					if(!file_exists($driveObj["file"])){
						echo "Virtual cdrom media is missing: ".$driveObj["file"]."\n";
						exit;
					}else{
						echo "Virtual media exists: ".$driveObj["file"]."\n";
					}
				}
				continue;
			}
			if(isset($driveObj["baseImage"])){
				$basePath=$driveObj["baseImage"]."-".$newBase;
				if(!file_exists($basePath)){
					echo "Copying baseImage\n";
					$cmd="/bin/cp -f ".escapeshellarg($driveObj["baseImage"])." ".escapeshellarg($basePath);
					echo $cmd."\n";
					$r=`$cmd`;
					echo $r."\n";
				}
				// create new qed with $path as the backing file
				$cmd="/usr/bin/qemu-img create -b ".escapeshellarg($basePath)." -f ".$driveObj["type"]." ".escapeshellarg($driveObj["file"]);
				echo $cmd."\n";
				$r=`$cmd`;
				echo $r."\n";
			}else{
				if(!file_exists($driveObj["file"])){
					echo "Creating missing virtual media: ".$driveObj["file"]."\n";
					$cmd="/usr/bin/qemu-img create -f ".$driveObj["type"]." ".escapeshellarg($driveObj["file"])." ".escapeshellarg($driveObj['size']);
					echo $cmd."\n";
					$r=`$cmd`;
					echo $r."\n";
				}else{
					echo "Virtual media exists: ".$driveObj["file"]."\n";
				}
			}
		}

		if(isset($arrDefinedMachines[$machineName])){
			$cmd="/usr/bin/virsh undefine ".$machineName;
			echo $cmd."\n";
			$r=`$cmd`;
			echo $r."\n";
		}

		if($base != $newBase){
			foreach($machine["drives"] as $drive=>$driveObj){
				if(isset($driveObj["baseImage"])){
					$basePath=$driveObj["baseImage"]."-".$base;
					if(file_exists($basePath)){
						unlink($basePath);
					}
				}
			}
		}

		echo "Generate new xml for ".$machineName."\n";
		$xml=getMachineXML($machine);
		file_put_contents($xmlPath, $xml);
		
		$cmd="/usr/bin/virsh define ".$xmlPath;
		echo $cmd."\n";
		$r=`$cmd`;
		echo $r."\n";

		// temporarily disabled autoStart for debugging purposes
		if(false && $autoStart){
			$cmd="/usr/bin/virsh start ".$machineName;
			echo $cmd."\n";
			$r=`$cmd`;
			echo $r."\n";
		}
	}
	return true;
}

function getMachineXML($machine){
	global $disableKVM;
	$xml='<domain type="kvm" id="1">
	<name>'.$machine['name'].'</name>
	<os>
	<type>hvm</type>';
	if(isset($machine['enableNetworkBoot']) && $machine['enableNetworkBoot']){
		$xml.='<boot dev="network"/>';
	}
	if(isset($machine['enableCdromBoot']) && $machine['enableCdromBoot']){
		$xml.='<boot dev="cdrom"/>';
	}
	$xml.='<boot dev="hd"/>
	</os>
	<cpu mode="host-passthrough"/>
	<features>
    <acpi/>
    <apic/>
    <pae/>
      <hyperv>
        <relaxed state="on"/> 
      </hyperv>
	</features>
	<memory>'.$machine['memory'].'</memory>
	<vcpu>8</vcpu>
	<devices>';
	foreach($machine['drives'] as $key=>$drive){
		if($drive['type']=='cdrom'){
			$xml.='<disk type="file" device="cdrom">
				<source file="'.$drive['file'].'"/>
				<target dev="'.$key.'"/>
				<readonly/>
			</disk>'."\n";
		}else{
			$xml.='<disk type="file" snapshot="external">'."\n";
			if($drive['type']=='raw'){
				$xml.='<driver name="qemu" type="raw" cache="none" io="native" />'."\n";
			}else{
				$xml.='<driver name="qemu" type="'.$drive['type'].'" cache="none" />'."\n";
			}
			if(!$disableKVM && !file_exists($drive['file'])){
				echo $drive['file']." doesn't exist. You can't define a machine with a missing image path.\n";
				exit;
			}
			$xml.='<source file="'.$drive['file'].'"/>'."\n";
			$xml.='<target dev="'.$key.'" bus="virtio"/>
			</disk>'."\n";
		}
	}
	$xml.='	<interface type="bridge">
			<mac address="'.$machine['mac'].'"/>
			<source bridge="virbr0"/>
			<model type="virtio"/>
		</interface>
		<graphics type="spice" port="'.$machine['spicePort'].'" autoport="no" listen="127.0.0.1">
		<clipboard copypaste="yes"/>
		</graphics>

		 <video>
	      <model type="qxl" vram="32768" heads="1"/>
	      <address type="pci" domain="0x0000" bus="0x00" slot="0x02" function="0x0"/>
	    </video>';
	foreach($machine['shared_folders'] as $name=>$folder){
		// consider using accessmode "passthrough" (lets guest change permissions) or "squashed" (ignores some failures but acts like passthrough)
		// Mapped doesn't let guest change permissions at all.
		$xml.='<filesystem type="mount" accessmode="';
		if(isset($folder['securityMode'])){
			$xml.=$folder['securityMode'];
		}else{
			$xml.='mapped';
		}
		$xml.='"> 
		   <source dir="'.$folder['path'].'"/>
		   <target dir="'.$name.'"/>'."\n";
			if(isset($folder['readonly']) && $folder['readonly']){
				$xml.='<readonly />'."\n";
			}
		$xml.='</filesystem>'."\n";
	}
	$xml.='</devices>
</domain>
';
/*
private network xml:
<network>
<name>default</name>
<bridge name="virbr0" />
<forward mode="nat"/>
<ip address="192.168.48.1" netmask="255.255.255.0">
  <dhcp>
	<range start="192.168.48.2" end="192.168.48.254" />
  </dhcp>
</ip>
</network>
*/
	return $xml;
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

function startHost($serverPath, $arrVirtualMachine){
	$r="";
	if(!file_exists($serverPath."current-base-image.txt")){
		file_put_contents($serverPath."current-base-image.txt", "image1.qed");
	}
	$base=file_get_contents($serverPath."current-base-image.txt");
	// copy test machine test.qed to imageVERSION.qed where VERSION is 1 or 2
	$path="/var/jetendo-server/virtual-machines/";
	if(!is_dir($path)){
		mkdir($path, 0770);
	}
	$arrDefinedMachines=getDefinedMachines();
	$result=swapMachineImage($base, $base, $arrVirtualMachine, false);
}
function stopHost($arrVirtualMachine){
	global $machineShutDownTimeoutInSeconds;
	$arrShutdown=array();
	foreach($arrVirtualMachine as $machinePath){
		$arrPath=explode("/", $machinePath);
		$machineName=array_pop($arrPath);
		$serverName=implode("/", $arrPath);
		if(isset($arrDefinedMachines[$machineName])){
			shutdownMachine($machineName);
			array_push($arrShutdown, $machineName);
		}
	}
	waitForMachineShutdown($arrShutdown, $machineShutDownTimeoutInSeconds);
}


$rsyncKey="";
$copyVarDirectory=false;
$updateVarDirectory=false;
$isTestProductionServer=false;
$configPath=getConfigPath();
$memoryDumpURL="";
$environment="";
$jetendoAdminDomain="";
$disableKVM=false;
$isHostServer=false;
$hostname="";
$machine=array();
$arrMount=array();
$arrServiceMap=array();
$arrVirtualMachine=array();
$virtualMachineBaseImage="";
$arrCommand=array();
echo "Loading configuration: ".$configPath."config.php\n";
require_once($configPath."config.php");

$arrPath=explode("/", str_replace('/var/jetendo-server/config/', '', $configPath));
$machineName=array_pop($arrPath);
$serverName=implode("/", $arrPath);
$serverPath="/var/jetendo-server/virtual-machines/".$arrPath[0]."/";

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