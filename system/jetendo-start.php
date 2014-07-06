<?php
// this is a placeholder for future automation
/*
before this script can be used to boot the server, we need to prepare the system by disabling services, removing some of the mounts from fstab
update-rc.d apache2 disable
update-rc.d coldfusion disable
update-rc.d railo_ctl disable
update-rc.d monit disable
update-rc.d postfix disable
update-rc.d dnsmasq disable
echo "manual" > /etc/init/dnsmasq.override
echo "manual" > /etc/init/postfix.override
echo "manual" > /etc/init/monit.override
echo "manual" > /etc/init/railo_ctl.override
echo "manual" > /etc/init/coldfusion.override
echo "manual" > /etc/init/apache2.override
echo "manual" > /etc/init/php5-fpm.override
echo "manual" > /etc/init/nginx.override
echo "manual" > /etc/init/networking.override
echo "manual" > /etc/init/cron.override
echo "manual" > /etc/init/ssh.override

Disable all /etc/fstab jetendo-server/jetendo mounts except /var/jetendo-server/system and /var/jetendo-server/config

To install this script to automatically run at boot, you must run these commands:
	/bin/cp -f /var/jetendo-server/system/jetendo-server /etc/init.d/jetendo-server
	/bin/chmod 755 /etc/init.d/jetendo-server
	update-rc.d jetendo-server defaults

on production, make sure you have a copy of the /etc/hosts file stored here:
	/var/jetendo-server/jetendo/share/hosts

To run this script manually, run this command:
	/usr/bin/php /var/jetendo-server/system/jetendo-start.php

*/
echo "jetendo-server starting: ".date(DATE_RFC2822)."\n";
require_once("library.php");


$currentDir=dirname(__FILE__);

$cmd="/bin/echo '".$hostname."' > /etc/hostname";
$r=`$cmd`;
echo $r."\n";

// mount partitions - via mount instead of fstab to avoid trouble booting due to kernel upgrades / virtualbox upgrades
for($i=0;$i<count($arrMount);$i++){
	$mount=$arrMount[$i];
	if(!is_dir($mount['path'])){
		echo "Creating directory: ".$mount['path']."\n";
		$cmd="/bin/mkdir ".$mount['path'];
		$r=`$cmd`;
		echo $r."\n";
	}
	if($environment == "production"){
		echo "Mounting 9p virtio directory: ".$mount['path']."\n";
		$cmd="/bin/mount -t 9p -o trans=virtio,version=9p2000.L hostshare /tmp/host_files ".$mount['options']." ".$mount['mountName']." ".$mount['path'];
		$result=`$cmd`;
		if($result != ""){
			echo $cmd."\n";
			dieWithError("Jetendo can't start because the above mount command returned the following information: ".$result."\n");
		}
	}else{
		echo "Mounting vboxsf directory: ".$mount['path']."\n";
		$cmd="/bin/mount -t vboxsf -o ".$mount['options']." ".$mount['mountName']." ".$mount['path'];
		$result=`$cmd`;
		if($result != ""){
			echo $cmd."\n";
			dieWithError("Jetendo can't start because the above mount command returned the following information: ".$result."\n");
		}
	}
}

// copy all config files
for($i=0;$i<count($arrCommand);$i++){
	$cmd=$arrCommand[$i];
	echo "Running command: ".$cmd."\n";
	$r=`$cmd 2>&1`;
	echo $r."\n";
}

// load apparmor profiles
echo "Install all apparmor profiles\n";
if(!is_dir($configPath."/apparmor.d/")){
	echo "Installed shared apparmor profiles\n";
	array_push($arrCommand, "/bin/cp -rf ".$currentDir."/apparmor.d/".$environment."/* /etc/apparmor.d/");
}
$r=`/sbin/apparmor_parser -r /etc/apparmor.d/`;
echo $r."\n";

// apply sysctl and other files and make sure they are processed
echo "Update sysctl\n";
$r=`/sbin/sysctl -p /etc/sysctl.conf`;
echo $r."\n";


createDir(
echo "Create symbolic links to configuration files\n";
# symbolic link configuration
if(array_key_exists("mysql", $arrServiceMap)){
	if(!is_dir('/var/jetendo-server/mysql')){
		mkdir('/var/jetendo-server/mysql', 0755);
	}
	if(!is_dir('/var/jetendo-server/mysql/data')){
		mkdir('/var/jetendo-server/mysql/data', 0700);
		chown('/var/jetendo-server/mysql/data', 'mysql');
		chgrp('/var/jetendo-server/mysql/data', 'mysql');
	}
	if(!is_dir('/var/jetendo-server/mysql/logs')){
		mkdir('/var/jetendo-server/mysql/logs', 0700);
		chown('/var/jetendo-server/mysql/logs', 'mysql');
		chgrp('/var/jetendo-server/mysql/logs', 'mysql');
	}
	if(file_exists($configPath."mysql/my.cnf")){
		$cmd="/bin/ln -sfn ".$configPath."mysql/my.cnf /etc/mysql/conf.d/jetendo.cnf";
	}else{
		$cmd="/bin/ln -sfn /var/jetendo-server/system/jetendo-mysql-".$environment.".cnf /etc/mysql/conf.d/jetendo.cnf";
	}
	$r=`$cmd`;
	echo $r."\n";
}
if(array_key_exists("nginx", $arrServiceMap)){
	$r=`/bin/ln -sfn /var/jetendo-server/system/jetendo-nginx-init /etc/init.d/nginx`;
	echo $r."\n";
	if(is_dir($configPath."nginx/nginx.conf")){
		$cmd="/bin/ln -sfn ".$configPath."nginx/nginx.conf /var/jetendo-server/nginx/conf/nginx.conf";
	}else{
		$cmd="/bin/ln -sfn /var/jetendo-server/system/nginx-conf/nginx-".$environment.".conf /var/jetendo-server/nginx/conf/nginx.conf";
	}
	$r=`$cmd`;
	echo $r."\n";
}
if(is_dir($configPath."sysctl.conf")){
	$cmd="/bin/ln -sfn ".$configPath."sysctl.conf /etc/sysctl.d/jetendo.conf";
}else{
	$cmd="/bin/ln -sfn /var/jetendo-server/system/jetendo-sysctl-".$environment.".conf /etc/sysctl.d/jetendo.conf";
}
$r=`$cmd`;
echo $r."\n";
if(array_key_exists("apache", $arrServiceMap)){
	if(is_dir($configPath."apache/sites-enabled")){
		$cmd="/bin/ln -sfn ".$configPath."apache/sites-enabled /etc/apache2/sites-enabled";
	}else{
		$cmd="/bin/ln -sfn /var/jetendo-server/system/apache-conf/".$environment."-sites-enabled /etc/apache2/sites-enabled";
	}
	$r=`$cmd`;
	echo $r."\n";
}
if(array_key_exists("php", $arrServiceMap)){
	if(!is_dir('/var/jetendo-server/php')){
		mkdir('/var/jetendo-server/php', 0755);
		chown('/var/jetendo-server/php', 'root');
		chgrp('/var/jetendo-server/php', 'root');
	}
	if(!is_dir('/var/jetendo-server/php/run')){
		mkdir('/var/jetendo-server/php/run', 0770);
		chown('/var/jetendo-server/php/run', 'www-data');
		chgrp('/var/jetendo-server/php/run', 'www-data');
	}
	if(!is_dir('/var/jetendo-server/php/session')){
		mkdir('/var/jetendo-server/php/session', 0770);
		chown('/var/jetendo-server/php/session', 'www-data');
		chgrp('/var/jetendo-server/php/session', 'www-data');
	}
	if(!is_dir('/var/jetendo-server/php/temp')){
		mkdir('/var/jetendo-server/php/temp', 0770);
		chown('/var/jetendo-server/php/temp', 'www-data');
		chgrp('/var/jetendo-server/php/temp', 'www-data');
	}
	if(is_dir($configPath."php/pool")){
		$cmd="/bin/ln -sfn ".$configPath."php/pool /etc/php5/fpm/pool.d";
	}else{
		$cmd="/bin/ln -sfn /var/jetendo-server/system/php/".$environment."-pool /etc/php5/fpm/pool.d";
	}
	$r=`$cmd`;
	echo $r."\n";
}


// if using virtualbox
$result=`/usr/sbin/dmidecode  | /bin/grep -i product`;
if(strpos($result, "Virtualbox") !== FALSE){
	// using virtualbox - check for module being loaded
	$result=`/sbin/lsmod | /bin/grep -i vboxguest`;
	if($result == ""){
		// reinstall modules
		`/bin/mount /dev/cdrom /media/cdrom`;
		`/media/cdrom/VBoxLinuxAdditions.run`;
		
		// check again
		$result=`/sbin/lsmod | /bin/grep -i vboxguest`;
		if($result == ""){
			// fail - may require rebooting.
			dieWithError("Jetendo can't start because the virtualbox guest additions are not able to be loaded.  Make sure the guest additions ISO is mounted, install them with the following commands, and then reboot the machine:\n\n/bin/mount /dev/cdrom /media/cdrom\n/media/cdrom/VBoxLinuxAdditions.run");
		}
	}
}

// stop monit before restarting services to avoid conflicts & failures
if(array_key_exists("monit", $arrServiceMap)){
	echo "Stop monit\n";
	$r=`/usr/sbin/service monit stop`;
	echo $r."\n";
}

// start services in sequence
if(array_key_exists("ufw", $arrServiceMap)){
	echo "Start ufw\n";
	$r=`/usr/sbin/service ufw restart`;
	echo $r."\n";
	echo "Enable ufw\n";
	$r=`/bin/echo "y" | /usr/sbin/ufw enable`;
	echo $r."\n";
}
// service networking
if(array_key_exists("networking", $arrServiceMap)){
	echo "Start networking\n";
	$r=`/usr/sbin/service networking restart`;
	echo $r."\n";
}
if(array_key_exists("ssh", $arrServiceMap)){
	echo "Start ssh\n";
	$r=`/usr/sbin/service ssh restart`;
	echo $r."\n";
}
// start dnsmasq
if(array_key_exists("dnsmasq", $arrServiceMap)){
	echo "Start dnsmasq\n";
	$r=`/usr/sbin/service dnsmasq restart`;
	echo $r."\n";
}
// start cron
if(array_key_exists("cron", $arrServiceMap)){
	echo "Start cron\n";
	$r=`/usr/sbin/service cron restart`;
	echo $r."\n";
}
// start postfix
if(array_key_exists("postfix", $arrServiceMap)){
	echo "Start postfix\n";
	$r=`/usr/bin/newaliases`;
	echo $r."\n";
	$r=`/usr/sbin/service postfix restart`;
	echo $r."\n";
}
// start php5-fpm
if(array_key_exists("php", $arrServiceMap)){
	echo "Start php5-fpm\n";
	$r=`/usr/sbin/service php5-fpm start`;
	echo $r."\n";
}
// start mysql
if(array_key_exists("mysql", $arrServiceMap)){
	echo "Start mysql\n";
	$r=`/usr/sbin/service mysql restart`;
	echo $r."\n";
}

// start railo
if(array_key_exists("railo", $arrServiceMap)){
	if(!is_dir('/var/jetendo-server/railovhosts')){
		mkdir('/var/jetendo-server/railovhosts', 0770);
		chown('/var/jetendo-server/railovhosts', 'www-data');
		chgrp('/var/jetendo-server/railovhosts', 'www-data');
	}
	if(!is_dir('/var/jetendo-server/railovhosts/server')){
		mkdir('/var/jetendo-server/railovhosts/server', 0770);
		chown('/var/jetendo-server/railovhosts/server', 'www-data');
		chgrp('/var/jetendo-server/railovhosts/server', 'www-data');
		`/bin/cp -rf /var/jetendo-server/railo/* /var/jetendo-server/railovhosts/server/`;
		`/bin/chown -R www-data:www-data /var/jetendo-server/railovhosts/server/`;
		`/bin/chmod -R 770 /var/jetendo-server/railovhosts/server/`;
	}
	if(!is_dir('/var/jetendo-server/railovhosts/tomcatlogs')){
		mkdir('/var/jetendo-server/railovhosts/tomcatlogs', 0770);
		chown('/var/jetendo-server/railovhosts/tomcatlogs', 'www-data');
		chgrp('/var/jetendo-server/railovhosts/tomcatlogs', 'www-data');
	}
	echo "Start railo\n";
	$r=`/usr/sbin/service railo_ctl restart`;
	echo $r."\n";
	// setup logs
	$cmd="/bin/cp -f ".$currentDir."/railo/logrotate.txt /etc/logrotate.d/tomcat";
	$r=`$cmd`;
	echo $r."\n";

	echo "Start jetendo\n";
	// verify railo's first jetendo request has completed
	ob_start();
	require_once("jetendo-status.php");
	$status=ob_get_clean();
	if($status !== "1"){
		dieWithError("Jetendo status check failed.  You need to manually fix Railo / jetendo and then re-run this script.");
	}
}
// check availability of the other servers
checkAvailableServers();

// start replication
// doesn't exist yet

// verify replication state is less then 1 minute behind before coming back online.


// update nginx configuration to match server availability



// start nginx
if(array_key_exists("nginx", $arrServiceMap)){
	echo "Start nginx\n";
	$r=`/usr/sbin/service nginx restart`;
	echo $r."\n";
}

// start apache
if(array_key_exists("apache", $arrServiceMap)){
	echo "Start apache\n";
	$r=`/usr/sbin/service apache2 restart`;
	echo $r."\n";
}


// start coldfusion
if(array_key_exists("coldfusion", $arrServiceMap)){
	echo "Start coldfusion\n";
	$r=`/usr/sbin/service coldfusion restart`;
	echo $r."\n";
}


// start junglediskserver
if(array_key_exists("junglediskserver", $arrServiceMap)){
	echo "Start junglediskserver\n";
	$r=`/usr/sbin/service junglediskserver restart`;
	echo $r."\n";
}

// start monit
if(array_key_exists("monit", $arrServiceMap)){
	echo "Start monit\n";
	$r=`/usr/sbin/service monit restart`;
	echo $r."\n";
}

if(file_exists($configPath."/jetendo_server_down")){
	unlink($configPath."/jetendo_server_down");
}

if(array_key_exists("postfix", $arrServiceMap)){
	echo $hostname.' has been started.';
	$cmd='/bin/echo "'.$hostname.' has been started." | /usr/bin/mailx -s "'.$hostname.' has been started." root@localhost';
	$r=`$cmd`;
	echo $r."\n";
}else{
	echo $hostname.' has been started. Can\'t send an email because postfix is not enabled.';
}




if($isHostServer){

	startHost($serverPath, $arrVirtualMachine, $virtualMachineBaseImage);
}

// done!
echo "\n===========\n";
?>