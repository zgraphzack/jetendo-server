
LC_ALL=C.UTF-8 add-apt-repository ppa:ondrej/php
apt-get update
apt-get install php7.0
apt-get install php7.0-mysql php7.0-cli php7.0-fpm php7.0-gd php7.0-curl php7.0-dev php7.0-sqlite3

updated apparmor, monit, jetendo-start, jetendo-stop, nginx conf production, php conf in /var/jetendo-server/system/
made php paths work php7.0 enmod see readme.txt

ln -sfn /var/jetendo-server/system/php/jetendo.ini /etc/php/7.0/mods-available/jetendo.ini
ln -sfn /var/jetendo-server/system/php/production-pool /etc/php/7.0/fpm/pool.d
phpenmod jetendo
apparmor_parser -r /etc/apparmor.d/
service php7.0-fpm restart

afterwards:
apt-get remove dh-php5 php5-apcu php5-cgi php5-cli php5-common php5-curl php5-dev php5-fpm php5-gd php5-json php5-mysql php5-readline php5-sqlite
apt-get autoremove

check for other php5 stuff and remove:
dpkg --get-selections | grep php

remove the ppa php5 manually if one exists
remove /etc/init.d/php5-fpm

service nginx restart
service php7.0-fpm restart
service monit restart

check aa-logprof
check php is working
php /var/jetendo-server/jetendo/scripts/zqueue/queue-check-running.php
