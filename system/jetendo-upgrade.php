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

$base=file_get_contents($serverPath."current-base-image.txt");
if($base == "image1.qed"){
	$newBase="image2.qed";
}else{
	$newBase="image1.qed";
}

// copy test machine test.qed to imageVERSION.qed where VERSION is 1 or 2
$path="/var/jetendo-server/virtual-machines/";

$result=swapMachineImage($base, $newBase, $arrVirtualMachine, true);

if($result){
	file_put_contents($serverPath."current-base-image.txt", $newBase);
}
?>