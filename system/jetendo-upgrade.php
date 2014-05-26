<?php
set_time_limit(10000);
$r="";

echo "jetendo-server starting: ".date(DATE_RFC2822)."\n";
require_once("library.php");

if(!$isHostServer){
	echo "This script can only be executed on the host production machine.";
	exit;
}

if(!file_exists("/var/jetendo-server/current-base-image.txt")){
	file_put_contents("/var/jetendo-server/current-base-image.txt", "image1.qed");
}
$base=file_get_contents("/var/jetendo-server/current-base-image.txt");
if($base == "image1.qed"){
	$newBase="image2.qed";
}else{
	$newBase="image1.qed";
}

// copy test machine test.qed to imageVERSION.qed where VERSION is 1 or 2
$path="/var/jetendo-server/virtual-machines/";
if(!file_exists($path."test.qed")){
	echo $path."test.qed is missing and it is required for this script to function.\n";
	exit;
}
if(file_exists($path."image1.qed") && file_exists($path."image2.qed")){
	echo "Both ".$path."image1.qed and ".$path."image2.qed exist.  You must manually delete one of them and run this script again. Make sure no running machines are using the file you delete.\n";
	exit;
}
$cmd="/bin/cp -f /var/jetendo-server/virtual-machines/test.qed ".$path.$newBase;
echo $cmd."\n";
// $r=`$cmd`;
echo $r."\n";

function getMachineObject($serverName, $machineName){
	require("/var/jetendo-server/config/".$serverName."/"$machineName."/config.php");
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

function swapMachineImage($verify, $base, $newBase, $arrRunningMachines){
	$r="";
	// loop machines
	for($i=0;$i<count($arrMacAddress);$i++){
		$arrPath=explode("/", $arrMacAddress[$i]);
		$serverName=$arrPath[0];
		$machineName=$arrPath[1];
		$serverPath="/var/jetendo-server/virtual-machines/".$serverName;


		if(file_exists($serverPath."-image1.qed") && file_exists($serverPath."-image2.qed")){
			echo "Both ".$serverPath."-image1.qed and ".$serverPath."-image2.qed exist.  You must manually delete one of them and run this script again. Make sure no running machines are using the file you delete.\n";
			exit;
		}
		$machineImagePath=$serverPath."/".$machineName."-".$newBase.".qed";
		$imagePath=$serverPath.$newBase.".qed";

		// change image used
		echo "Generate new xml for ".$machineName."\n";
		$machine=getMachineObject($serverName, $machineName);
		$xml=getMachineXML($machine);
		$xmlPath="/var/jetendo-server/virtual-machines/".$machineName.".xml";
		file_put_contents($xmlPath, $xml);

		if(!isset($machine['varSize'])){
			echo "machine must have a key named varSize defined as the number of gigabytes for the /var partition, such as 20G";
			exit;
		}

		if(!$verify){
			continue;
		}

		$varImage="/var/jetendo-server/virtual-machines/".$machineName."-var.raw";
		if(!file_exists($varImage)){
		$cmd="/usr/bin/qemu-img create -f raw ".$varImage." ".$machine['varSize'];
		}

		// create new qed with $path as the backing file
		$cmd="/usr/bin/qemu-img create -b ".$imagePath." -f qed ".$machineImagePath;
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

	$cmd="/bin/rm -f ".$base;
	// $r=`$cmd`;
	echo $r."\n";
	exit;
}

$arrRunningMachines=getRunningMachines();
$result=swapMachineImage(true, $base, $newBase, $arrRunningMachines);

if($result){
	swapMachineImage(false);
	file_put_contents("/var/jetendo-server/current-base-image.txt", $newBase);
}
?>