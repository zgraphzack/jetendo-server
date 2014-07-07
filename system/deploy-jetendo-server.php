<?php
// dry-run:
// php /var/jetendo-server/system/deploy-jetendo-server.php preview=1

// final run:
// php /var/jetendo-server/system/deploy-jetendo-server.php preview=0

if(!isset($argv[1]) || $argv[1] != "preview=0"){
	$dryRun=' --dry-run ';
	$previewMessage="PREVIEW MODE ENABLED.  To disable preview, please re-run this script like this:\nphp /var/jetendo-server/system/deploy-jetendo-server.php preview=0\n";
}else{
	$dryRun='';
	$previewMessage="";
}
require_once("library.php");

echo "deploy jetendo-server: ".date(DATE_RFC2822)."\n";

if($environment == "production"){
	echo "Deploy can only be run on a development server.\n";
	exit;
}
if($rsyncKey == ""){
	echo "A valid key path for rsyncKey is required for deploy to work.\n";
	exit;
}

$cmd="/usr/bin/rsync -zrtLv --itemize-changes ".$dryRun." --delete --delay-updates --exclude='/.gitattributes' --exclude='/.gitignore' --exclude='/.git*' --exclude='.git/' --exclude='*/.git*' --exclude='/.git' --exclude='/mysql/' --exclude='/railo/' --exclude='/nginx/' --exclude='/railovhosts/' --exclude='/coldfusion/' --exclude='/apache/' --exclude='/backup/' --exclude='/php/' --exclude='/system/apr-build/' --exclude='/system/nginx-build/' --exclude='/system/railo/temp/' --exclude='/jetendo/' --exclude='/logs/' --exclude='virtual-machines/' -e 'ssh -i ".$rsyncKey."' /var/jetendo-server/ root@phoenix.farbeyondcode.com:/var/jetendo-server/";
echo $cmd."\n";
$result=`$cmd`;
echo $result."\n";
echo $previewMessage;
?>