<?php
// this is a placeholder for future automation

require("library.php");
$configPath=getConfigPath();


file_put_contents("/opt/jetendo-server/system/jetendo_server_down", "1");

// stop monit
if(!array_key_exists($arrService, "monit")){
	`/usr/sbin/service monit start`;
}

// stop cron
if(!array_key_exists($arrService, "cron")){
	`/usr/sbin/service cron start`;
}

// stop replication

// killall php

// stop nginx
if(!array_key_exists($arrService, "nginx")){
	`/usr/sbin/service nginx start`;
}

// stop railo with the memory dump request
$result=file_get_contents("memory-dump-url");
if(!array_key_exists($arrService, "railo")){
	`/usr/sbin/service railo_ctl start`;
}

// stop php5-fpm
if(!array_key_exists($arrService, "php")){
	`/usr/sbin/service php5-fpm start`;
}

// stop mysql
if(!array_key_exists($arrService, "mysql")){
	`/usr/sbin/service mysql start`;
}


?>