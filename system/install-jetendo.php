<?php
// if you wish to install a different version of jetendo, change the configuration here:
$gitCloneURL="https://github.com/jetendo/jetendo.git";
$gitBranch="master";

@mkdir("/var/jetendo-server/jetendo/", 0755);
chdir("/var/jetendo-server/jetendo/");
// install jetendo source from git repository:
if(!is_dir("/var/jetendo-server/jetendo/.git/")){
	echo("Cloning git repository at: ".$gitCloneURL."\n");
	$r=`/usr/bin/git clone $gitCloneURL /var/jetendo-server/jetendo/`;
}else{
	$r=`/usr/bin/git remote add origin $gitCloneURL`;
	echo("Pulling git repository at: ".$gitCloneURL.", branch: ".$gitBranch."\n");
	$r=`/usr/bin/git pull origin $gitBranch`;
}
echo("Git Checkout branch: ".$gitBranch."\n");
$r=`/usr/bin/git checkout $gitBranch`;
$status=`/usr/bin/git status`;
echo("Git status: ".$status."\n");


@mkdir("/var/jetendo-server/jetendo/sites", 0777);
@mkdir("/var/jetendo-server/jetendo/sites-writeable", 0777);

echo "Jetendo core source code has been downloaded and verified.\n\n";
echo "Please continue installation by following the README instructions for installing Jetendo CMS in the jetendo-server project.\n\n";
echo "If you need additional assistance, visit: https://www.jetendo.com/\n\n";
?>