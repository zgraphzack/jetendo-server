<?php

if(zIsTestServer()){
	$verifyURL=get_cfg_var("jetendo_admin_domain")."/";
	$contents=file_get_contents($verifyURL);
}else{
	$verifyURL=get_cfg_var("jetendo_test_admin_domain")."/";
	$contents=file_get_contents($verifyURL);
}
if($contents == "OK"){
	echo "1";
}else{
	echo "0";
}
exit;
?>