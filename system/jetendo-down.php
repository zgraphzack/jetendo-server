<?php

echo "Trying to bring jetendo-server down: ".date(DATE_RFC2822)."\n";
require("library.php");

file_put_contents("/var/jetendo-server/logs/jetendo_server_down", "1");

// stop monit
if(array_key_exists("monit", $arrServiceMap)){
	echo "Stop monit\n";
	$r=`/usr/sbin/service monit stop`;
	echo $r."\n";
}

// stop replication

// update nginx to force down state for external monitoring
updateServerAvailability();

?>