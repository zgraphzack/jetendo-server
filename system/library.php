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
function getMachineObject($serverName, $machineName){
	require("/var/jetendo-server/config/".$serverName."/".$machineName."/config.php");
	return $machine;
}

function getRunningMachines(){
	$arrRunning=array();
	$cmd="/usr/bin/virsh list | /bin/grep 'running'";
	$r=`$cmd`;
	$arrMachine=explode("\n", $r);
	for($i=0;$i<count($arrMachine);$i++){
		$arr=explode(" ", $arrMachine[$i]);
		$arr2=array();
		for($n=0;$n<count($arr);$n++){
			$c=trim($arr[$n]);
			if($c == ""){
				array_push($arr2, $c);
			}
		}
		if(count($arr2) != 3){
			echo "Invalid response for virsh list: ".$r."\n";
			exit;
		}else{
			$arrRunning[trim($arr2[1])]=trim($arr2[0]);
		}
	}
	return $arrRunning;
}

function swapMachineImage($verify, $base, $newBase, $arrVirtualMachine, $arrRunningMachines){
	$r="";
	// loop machines
	foreach($arrVirtualMachine as $machinePath){
		$arrPath=explode("/", $machinePath);
		$machineName=array_pop($arrPath);
		$serverName=implode("/", $arrPath);
		$serverPath="/var/jetendo-server/virtual-machines/".$arrPath[0]."/";
		if(!is_dir($serverPath)){
			mkdir($serverPath, 0770);
		}


		if(file_exists($serverPath."image1.qed") && file_exists($serverPath."image2.qed")){
			echo "Both ".$serverPath."image1.qed and ".$serverPath."image2.qed exist.  You must manually delete one of them and run this script again. Make sure no running machines are using the file you delete.\n";
			exit;
		}
		$machineImagePath=$serverPath.$machineName.".qed";
		$imagePath=$serverPath.$newBase.".qed";

		// change image used
		echo "Generate new xml for ".$machineName."\n";
		$machine=getMachineObject($serverName, $machineName);
		$xml=getMachineXML($machine);
		$xmlPath=$serverPath.$machineName.".xml";
		file_put_contents($xmlPath, $xml);

		if(!isset($machine['varSize'])){
			echo "machine must have a key named varSize defined as the number of gigabytes for the /var partition, such as 20G";
			exit;
		}

		if($verify){
			continue;
		}

		$varImage=$serverPath.$machineName."-var.raw";
		if(!file_exists($varImage)){
			$cmd="/usr/bin/qemu-img create -f raw ".escapeshellarg($varImage)." ".escapeshellarg($machine['varSize']);
		}

		if(file_exists($machineImagePath)){
			unlink($machineImagePath);
		}
		// create new qed with $path as the backing file
		$cmd="/usr/bin/qemu-img create -b ".escapeshellarg($imagePath)." -f qed ".escapeshellarg($machineImagePath);
		echo $cmd."\n";
		// $r=`$cmd`;
		echo $r."\n";

		if(isset($arrRunningMachines[$machineName])){
			// shutdown machine
			$cmd="/usr/bin/virsh stop ".$machineName;
			echo $cmd."\n";
			// $r=`$cmd`;
			echo $r."\n";
		}

		$cmd="/usr/bin/virsh undefine ".$xmlPath;
		echo $cmd."\n";
		// $r=`$cmd`;
		echo $r."\n";
		$cmd="/usr/bin/virsh define ".$xmlPath;
		echo $cmd."\n";
		// $r=`$cmd`;
		echo $r."\n";


		$cmd="/usr/bin/virsh start ".$machineName;
		echo $cmd."\n";
		// $r=`$cmd`;
		echo $r."\n";
	}
	if($base != $newBase){
		$cmd="/bin/rm -f ".$base;
		// $r=`$cmd`;
		echo $r."\n";
	}
	exit;
}

function getMachineXML($machine){
	global $disableKVM;
	$xml='<domain type="kvm" id="1">
	<name>'.$machine['name'].'</name>
	<os>
	<type>hvm</type>
	<boot dev="hd"/>
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
		<graphics type="spice" port="'.$machine['spicePort'].'" autoport="no" listen="127.0.0.1"/>
		 <video>
	      <model type="qxl" vram="32768" heads="1"/>
	      <address type="pci" domain="0x0000" bus="0x00" slot="0x02" function="0x0"/>
	    </video>';
	foreach($machine['shared_folders'] as $name=>$folder){
		// consider using accessmode "passthrough" (lets guest change permissions) or "squashed" (ignores some failures but acts like passthrough)
		// Mapped doesn't let guest change permissions at all.
		$xml.='<filesystem type="mount" accessmode="mapped"> 
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

function startHost($serverPath, $arrVirtualMachine, $virtualMachineBaseImage){
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
	if(!file_exists($path.$virtualMachineBaseImage)){
		echo $path.$virtualMachineBaseImage." is missing and it is required for this script to function.\n";
		exit;
	}
	if(file_exists($serverPath."image1.qed") && file_exists($serverPath."image2.qed")){
		echo "Both ".$serverPath."image1.qed and ".$serverPath."image2.qed exist.  You must manually delete one of them and run this script again. Make sure no running machines are using the file you delete.\n";
		exit;
	}
	if(!file_exists($serverPath.$base)){
		$cmd="/bin/cp -f ".escapeshellarg($path.$virtualMachineBaseImage)." ".escapeshellarg($serverPath.$base);
		echo $cmd."\n";
		// $r=`$cmd`;
		echo $r."\n";
	}
	$arrRunningMachines=getRunningMachines();
	$result=swapMachineImage(true, $base, $base, $arrVirtualMachine, $arrRunningMachines);

	if($result){
		swapMachineImage(false, $base, $base, $arrVirtualMachine, $arrRunningMachines);
		file_put_contents($serverPath."current-base-image.txt", $newBase);
	}
}
function stopHost($arrVirtualMachine){
	$arrRunningMachines=getRunningMachines();

	foreach($arrVirtualMachine as $machinePath){
		$arrPath=explode("/", $machinePath);
		$machineName=array_pop($arrPath);
		$serverName=implode("/", $arrPath);
		if(isset($arrRunningMachines[$machineName])){
			// shutdown machine
			$cmd="/usr/bin/virsh stop ".$machineName;
			echo $cmd."\n";
			// $r=`$cmd`;
			echo $r."\n";
		}
	}
}

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

$arrPath=explode("/", $configPath);
$machineName=array_pop($arrPath);
$serverName=implode("/", $arrPath);
$serverPath="/var/jetendo-server/virtual-machines/".$arrPath[0]."/";

if(!$isHostServer){
	if(!$disableKVM && count($machine)==0){
		dieWithError('You must set the $machine variable in '.$configPath.'config.php.');
	}
}else{
	if(count($arrVirtualMachine) && $virtualMachineBaseImage == ""){
		dieWithError('You must set the $virtualMachineBaseImage variable to a valid qed image.');
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