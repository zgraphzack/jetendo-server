<?php
// this is ready to be tested

set_time_limit(10000);
$r="";

echo "jetendo-server upgrading: ".date(DATE_RFC2822)."\n";
require_once("library.php");

if(!$isHostServer){
	echo "This script can only be executed on the host production machine.";
	exit;
}
if($virtualMachineBaseImage == ""){
	echo "virtualMachineBaseImage must be set in the host config.";
	exit;
}

$base=file_get_contents($serverPath."current-base-image.txt");
if($base == "image1.qed"){
	$newBase="image2.qed";
}else{
	$newBase="image1.qed";
}

// copy test machine test.qed to imageVERSION.qed where VERSION is 1 or 2
$path="/var/jetendo-server/virtual-machines/";
if(!file_exists($path.$virtualMachineBaseImage)){
	echo $path.$virtualMachineBaseImage." is missing and it is required for this script to function.\n";
	exit;
}
if(file_exists($serverPath."image1.qed") && file_exists($serverPath."image2.qed")){
	echo "Both ".$serverPath."image1.qed and ".$serverPath."image2.qed exist.  You must manually delete one of them and run this script again. Make sure no running machines are using the file you delete.\n";
	exit;
}
$cmd="/bin/cp -f ".escapeshellarg($path.$virtualMachineBaseImage)." ".escapeshellarg($serverPath.$newBase);
echo $cmd."\n";
// $r=`$cmd`;
echo $r."\n";


$arrRunningMachines=getRunningMachines();
$result=swapMachineImage(true, $base, $newBase, $arrVirtualMachine, $arrRunningMachines);

if($result){
	swapMachineImage(false, $base, $newBase, $arrVirtualMachine, $arrRunningMachines);
	file_put_contents($serverPath."current-base-image.txt", $newBase);
}
?>