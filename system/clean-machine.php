<?php
// This script prepares the virtual machine for being distributed.   It attempts to remove all private data and zero the available space so we can run the VDI compact command to reduce the size of the machine file.
// "C:\Program Files\Oracle\VirtualBox\VBoxManage.exe" modifyhd jetendo-server-os.vdi --compact

// run this before running this php /opt/jetendo-server/system/clean-machine.php
// killall -9 php


set_time_limit(300);

`/usr/sbin/service monit stop`;
`/usr/sbin/service railo_ctl forcequit`;
`/usr/sbin/service nginx stop`;
`/usr/sbin/service mysql stop`;
`/usr/sbin/service php5-fpm stop`;
`/usr/sbin/service cron stop`;
`/usr/sbin/service postfix stop`;
`/usr/sbin/service rsyslog stop`;
`/usr/sbin/service junglediskserver stop`;


// remove mysql passwords
$contents=file_get_contents("/etc/mysql/debian.cnf");
$pattern = '/password =(.*)/i';
$replace='password =';
$contents=preg_replace ($pattern , $replace , $contents);
file_put_contents("/etc/mysql/debian.cnf", $contents);

if ($handle = opendir('/home/')) {
    while (false !== ($entry = readdir($handle))) {
		if($entry != "." && $entry != ".." && is_dir("/home/".$entry) && file_exists("/home/".$entry."/.bash_history")){
			$f = @fopen("/home/".$entry."/.bash_history", "r+");
			if ($f !== false) {
				ftruncate($f, 0);
				fclose($f);
			}
		}
		// remove mailboxes
		if(file_exists("/home/".$entry."/mbox")){
			unlink("/home/".$entry."/mbox");
		}
    }
    closedir($handle);
}

// remove all mail
$cmd="/bin/rm -rf /var/mail/*";
`$cmd`;

// remove mysql database if it exists inside virtual machine
$cmd="/bin/rm -rf /var/lib/mysql";
`$cmd`;

// remove temp files
$cmd="/bin/rm -rf /tmp/*";
`$cmd`;

// remove the railo server admin password
$contents=file_get_contents("/opt/railo/lib/railo-server/context/railo-server.xml");
$pattern = '/railo-configuration pw="([^"]*)"/i';
$replace='railo-configuration pw=""';
$contents=preg_replace ($pattern , $replace , $contents, 1);
file_put_contents("/opt/railo/lib/railo-server/context/railo-server.xml", $contents);

# Removing smtp login between # jetendo-custom-smtp-begin and # jetendo-custom-smtp-end
if(file_exists("/etc/postfix/main.cf")){
	$main=file_get_contents("/etc/postfix/main.cf");
	$begin=strpos($main, "# jetendo-custom-smtp-begin");
	$end=strpos($main, "# jetendo-custom-smtp-end");
	if($begin !== FALSE && $end !== FALSE && $begin < $end){
		$end+=strlen("# jetendo-custom-smtp-end");
		$main=substr_replace($main, "", $begin, $end-$begin);
		file_put_contents("/etc/postfix/main.cf", $main);
	}
}
if(file_exists("/etc/postfix/sasl_passwd")){
	unlink("/etc/postfix/sasl_passwd");
}

echo `/usr/bin/apt-get clean all`;
echo `/usr/bin/apt-get autoremove`;

@unlink("/root/.gitconfig");
$cmd="/bin/rm -rf /home/*/.gitconfig";
`$cmd`;

`/usr/bin/git config --global user.name "Your Name Here"`;
`/usr/bin/git config --global user.email "your_email@example.com"`;

// remove older linux kernels
$cmd='/usr/bin/dpkg -l linux-image-* | /usr/bin/awk \'/^ii/{ print $2}\' | /bin/grep -v -e `uname -r | /usr/bin/cut -f1,2 -d"-"` | /bin/grep -e [0-9] | /usr/bin/xargs /usr/bin/apt-get -y purge';
echo `$cmd`;
$cmd='/usr/bin/dpkg -l linux-headers-* | /usr/bin/awk \'/^ii/{ print $2}\' | /bin/grep -v -e `uname -r | /usr/bin/cut -f1,2 -d"-"` | /bin/grep -e [0-9] | /usr/bin/xargs /usr/bin/apt-get -y purge';
echo `$cmd`;

`/bin/cp -f /opt/jetendo-server/system/railo/server.xml /opt/railo/tomcat/conf/server.xml`;
`/bin/rm -rf /opt/railo/lib/railo-server/context/cfclasses/*`;
`/bin/rm -rf /opt/railo/tomcat/*.log`;
`/bin/rm -rf /opt/railo/tomcat/logs/*`;
`/bin/rm -rf /opt/railo/tomcat/temp/*`;
`/bin/rm -rf /opt/railo/lib/railo-server/context/logs/*`;
`/bin/rm -rf /opt/railo/lib/railo-server/context/temp/*`;
`/bin/rm -rf /opt/railo/tomcat/conf/Catalina/*`;
`/bin/rm -rf /opt/railo/tomcat/webapps/ROOT/WEB-INF/*`;
`/bin/rm -rf /opt/jetendo/sites/WEB-INF/*`;
`/bin/rm -rf /opt/railo/tomcat/work/Catalina/*`;
`/bin/rm -rf /tmp/*`;
`/bin/rm -rf /var/log/*.log`;
`/bin/rm -rf /var/log/*.log.*.gz`;
`/bin/rm -rf /opt/nginx/logs/*`;
`/bin/rm -rf /etc/jungledisk/junglediskserver-license.xml`;

$f = @fopen("/root/.bash_history", "r+");
if ($f !== false) {
    ftruncate($f, 0);
    fclose($f);
}
@unlink("/root/mbox");
echo `/bin/umount -f /opt/jetendo-server/apache`;
echo `/bin/umount -f /opt/jetendo-server/coldfusion`;
echo `/bin/umount -f /opt/jetendo-server/railo`;
echo `/bin/umount -f /opt/jetendo-server/php`;
echo `/bin/umount -f /opt/jetendo-server/nginx`;
echo `/bin/umount -f /opt/jetendo-server/mysql`;

	// make sure those directories above are empty for security.

// compact filesystem to minimize size of VDI.
echo `/bin/dd if=/dev/zero of=/bigemptyfile bs=4096k`;
echo `/bin/rm -rf /bigemptyfile`;

`/sbin/poweroff`;
echo "done";
?>