<?php

echo "Trying to bring jetendo-server up: ".date(DATE_RFC2822)."\n";
require("library.php");

// start replication

// verify replication state is less then 1 minute behind before coming back online.

// check availability of the other servers

// update nginx configuration

// start nginx

// start monit

unlink("/var/jetendo-server/logs/jetendo_server_down");
updateServerAvailability();

?>