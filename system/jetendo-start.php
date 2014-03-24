<?php
// this is a placeholder for future automation
exit;
require("library.php");

$configPath=getConfigPath();

// apply jetento-server configuration
require($configPath."config.php");

$cmd="/bin/echo 'hostname' > /etc/hostname";
`$cmd`;


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
			echo "Jetendo can't start because the virtualbox guest additions are not able to be loaded.  Make sure the guest additions ISO is mounted, install them with the following commands, and then reboot the machine:\n\n/bin/mount /dev/cdrom /media/cdrom\n/media/cdrom/VBoxLinuxAdditions.run";
			exit;
		}
	}
}

// mount partitions - via mount instead of fstab to avoid trouble booting due to kernel upgrades / virtualbox upgrades
for($i=0;$i<count($arrMount);$i++){
	$cmd=$arrMount[$i];
	$result=`$cmd`;
	if($result != ""){
		echo $cmd."\n";
		echo "Jetendo can't start because the above mount command returned the following information: ".$result."\n";
		exit;
	}
}

// copy all config files
for($i=0;$i<count($arrMount);$i++){
	$cmd=$arrCopy[$i];
	`$cmd`;
}

// load apparmor profiles
`/sbin/apparmor_parser -r /etc/apparmor.d/`;

// apply sysctl and other files and make sure they are processed
`/sbin/sysctl -p /etc/sysctl.conf`;

// start services in sequence
if(!array_key_exists($arrService, "ufw")){
	`/usr/sbin/service ufw restart`;
	`/usr/sbin/ufw enable`;
}
// service networking
if(!array_key_exists($arrService, "networking")){
	`/usr/sbin/service networking restart`;
}
// start dnsmasq
if(!array_key_exists($arrService, "dnsmasq")){
	`/usr/sbin/service dnsmasq restart`;
}
if(!array_key_exists($arrService, "ssh")){
	`/usr/sbin/service ssh restart`;
}
// start postfix
if(!array_key_exists($arrService, "postfix")){
	`/usr/sbin/service postfix restart`;
}
// start mysql
if(!array_key_exists($arrService, "mysql")){
	`/usr/sbin/service mysql restart`;
}
// start php5-fpm
if(!array_key_exists($arrService, "php5-fpm")){
	`/usr/sbin/service php5-fpm restart`;
}
// start railo
if(!array_key_exists($arrService, "railo_ctl")){
	`/usr/sbin/service railo_ctl restart`;
}

// verify railo's first jetendo request has completed
$status=`/usr/bin/php /opt/jetendo-server/system/jetendo-status.php`;
if($status !== "1"){
	echo "Jetendo status check failed.  You need to manually fix Railo / jetendo and then re-run this script.";
	exit;
}
// check availability of the other servers
checkAvailableServers();

// start replication
// doesn't exist yet

// verify replication state is less then 1 minute behind before coming back online.


// update nginx configuration to match server availability

// start nginx
if(!array_key_exists($arrService, "nginx")){
	`/usr/sbin/service nginx start`;
}

// start cron
if(!array_key_exists($arrService, "cron")){
	`/usr/sbin/service cron start`;
}

// start monit
if(!array_key_exists($arrService, "monit")){
	`/usr/sbin/service monit start`;
}

unlink($configPath."/jetendo_server_down");

// done!

?>