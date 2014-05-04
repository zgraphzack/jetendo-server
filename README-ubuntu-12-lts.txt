Jetendo Server Installation Documentation
OS: Ubuntu Server 12.04 LTS 

This readme is for users that want to install Jetendo Server and Jetendo from scratch.
If you downloaded the pre-configured virtual machine from https://www.jetendo.com/ , please use README-FOR-VIRTUAL-MACHINE.txt to get started with it.
If you don't have README-FOR-VIRTUAL-MACHINE.txt, please download it from https://github.com/jetendo/jetendo-server/ 

Download Jetendo Server 
	If you're reading this readme and haven't cloned or downloaded a release of the Jetendo Server project to your host system, please do so now.
	
	You can grab the latest development version from https://github.com/jetendo/jetendo-server/ or a release version from https://www.jetendo.com/
	
	The Jetendo Server project holds most of the configuration required by the virtual machine.

Virtualbox initial setup
	Ubuntu Linux x64
	Minimum requirements: 2048mb ram, 5gb hard drive, 1gb 2nd hard drive for swap, 1 NAT network adapter
	NAT Advanced Settings -> Port forwarding
		Name: SSH, Host Ip: 127.0.0.2: Host Port: 3222, Guest Ip: 10.0.2.15, Guest Port: 22
		Name: Nginx, Host Ip: 127.0.0.2: Host Port: 80, Guest Ip: 10.0.2.15, Guest Port: 80
		Name: Nginx SSL, Host Ip: 127.0.0.2: Host Port: 443, Guest Ip: 10.0.2.15, Guest Port: 443
		Name: Apache, Host Ip: 127.0.0.3: Host Port: 80, Guest Ip: 10.0.2.16, Guest Port: 80
		Name: Apache SSL, Host Ip: 127.0.0.3: Host Port: 443, Guest Ip: 10.0.2.16, Guest Port: 443
		Name: Railo, Host Ip: 127.0.0.2: Host Port: 8888, Guest Ip: 10.0.2.15, Guest Port: 8888
	Setup Shared Folders - The following names must point to the directory with the same name on your host system.  By default, they are a subdirectory of this README.txt file, however, you may relocate the paths if you wish.
		host-nginx
		host-mysql
		host-coldfusion
		host-system
		host-railo
		host-php
		host-apache
		jetendo
	Download and mount Ubuntu 12.04 LTS ISO to cdrom on first boot
	
	At Install Prompt, Press F4 (modes) and select Minimal Virtual Machine Install if this is virtual machine
		use defaults for all options - except don't encrypt your home directory.
	The username created during install will not be used later.
	Don't select any packages when prompted because this is a minimal install and they will be configured with shell afterwards.
	
	After finishing the rest of this guide, you'll be able to access:
		SSH/SFTP with:
			127.0.0.2 port 22
		Apache web sites with:
			www.your-site.com.127.0.0.3.xip.io
		Nginx web sites with:
			www.your-site.com.127.0.0.2.xip.io
		Railo administrator:
			http://127.0.0.2:8888/railo-context/admin/server.cfm
		Jetendo Administrator:
			https://jetendo.your-company.com.127.0.0.2.xip.io/z/server-manager/admin/server-home/index
			
	To run other copies of the virtual machine, just update the IP addresses to be unique.  You can use any ips on 127.x.x.x for this without reconfiguring your host system.
			
After OS is installed:		

# change vi default so insert mode is easier to use.  Type these commands:
	vi /root/.vimrc
	set nocompatible
	Press escape key
	:quit
	Now vi insert mode is easier to use.

# force grub screen to NOT wait forever for keypress on failed boot:
	vi /etc/default/grub
		GRUB_RECORDFAIL_TIMEOUT=2
	update-grub
	
# force ubuntu to boot after 2 second delay on grub menu
	vi /etc/grub.d/00_header
		# put this below ALL the other "set timeout" records in make_timeout
		set timeout=2
	upgrade-grub

# Initial kernel & OS update
	sudo apt-get update
	sudo apt-get upgrade
	sudo apt-get dist-upgrade
	sudo reboot
	
# setup ssh
	sudo apt-get install -qqy --force-yes openssh-server
	
# setup ufw
	sudo apt-get install ufw
	
	# allow web traffic:
	sudo ufw allow 80/tcp
	sudo ufw allow 443/tcp
	
	# allow ssh from specific ip, replace YOUR_STATIC_IP with a real IP address.
	ufw allow from YOUR_STATIC_IP to any port 22
	
	# don't have a static ip? Then allow from any IP (less secure)
	sudo ufw allow 22/tcp
	
	# Add connection limiting on a production server
	
		add to /etc/ufw/before.rules after the "drop INVALID packets" configuration lines
		
			# Limit to 30 concurrent connections on port 80 per IP
			-A ufw-before-input -p tcp --syn --dport 80 -m connlimit --connlimit-above 30 -j REJECT
			-A ufw-before-input -p tcp --syn --dport 443 -m connlimit --connlimit-above 30 -j REJECT

			# Limit to 20 connections on port 80 per 1 seconds per IP
			-A ufw-before-input -p tcp --dport 80 -i eth0 -m state --state NEW -m recent --set
			-A ufw-before-input -p tcp --dport 80 -i eth0 -m state --state NEW -m recent --update --seconds 1 --hitcount 20 -j REJECT
			-A ufw-before-input -p tcp --dport 443 -i eth0 -m state --state NEW -m recent --set
			-A ufw-before-input -p tcp --dport 443 -i eth0 -m state --state NEW -m recent --update --seconds 1 --hitcount 20 -j REJECT
	service ufw restart
	
# Enable empty password autologin for root on development server
	sudo passwd - enter temporary password
	vi /etc/pam.d/common-auth
		change nullok_secure to nullok
	vi /etc/ssh/sshd_config
		PermitEmptyPasswords Yes
	vi /etc/shadow
		delete the password hash for root between the 2 colons so it appears like "root::" on the first line.
	service ssh restart
	
Log out and login as root using ssh for the rest of the instructions.

# If server is a virtual machine
	apt-get install linux-virtual
	apt-get install linux-headers-`uname -r` build-essential
	reboot

	# If using virtualbox, install Virtualbox Guest Additions
		Mount guest additions iso to virtualbox guest
		apt-get install make gcc
		mount /dev/cdrom /media/cdrom
		/media/cdrom/VBoxLinuxGuestAdditions.run
		reboot

# Add the contents of /jetendo/system/jetendo-fstab.conf and copy the file to /etc/fstab, then run
	mount -a
	
Add Prerequisite Repositories
	apt-get install python-software-properties
	apt-key adv --recv-keys --keyserver hkp://keyserver.ubuntu.com:80 0xcbcb082a1bb943db
	add-apt-repository 'deb http://mirror.jmu.edu/pub/mariadb/repo/5.5/ubuntu precise main'
	add-apt-repository ppa:ondrej/php5
	add-apt-repository ppa:stebbins/handbrake-releases
	add-apt-repository ppa:webupd8team/java
	apt-get update

Install Required Packages
	apt-get install apache2 apt-show-versions monit rsyslog ntp cifs-utils mailutils samba fail2ban libsasl2-modules postfix opendkim opendkim-tools oracle-java7-installer p7zip-full handbrake-cli dnsmasq imagemagick ffmpeg git libpcre3-dev libssl-dev build-essential  libpcre3-dev unzip apparmor-utils rng-tools
	# accept defaults when postfix installer prompts you, i.e. OK, Internet Site
	
	apt-get install php5-fpm php5-cli php5-cgi php5-mysql php5-gd php-apc php5-curl php5-dev php-pear php5-apcu
	
	# MariaDB install results in held packages due to mysql release being newer - it is safe to ignore this.
	sudo aptitude install mariadb-server libmysqlclient18=5.5.34+maria-1~precise mysql-common=5.5.34+maria-1~precise
	
	# Force usage of mariadb packages
	apt-get remove php5-mysql rsyslog-mysql
	apt-get install php5-mysql rsyslog-mysql
	
	# Don't auto-configure database when the rsyslog utility app asks you.

Configure MariaDB
	service mysql stop
	
	# to begin with a fresh database, run this command to overwrite your mysql/data folder. WARNING:  If you existing mysql data files on your host system already, don't run this command.
	cp -rf /var/lib/mysql/* /opt/jetendo-server/mysql/data/
	
	disable the /root/.mysql_history file
	export MYSQL_HISTFILE=/dev/null

# add hosts to system to force dns resolution to work for more loopback ips:
	vi /etc/hosts
		127.0.0.2 nginx
		127.0.0.3 apache

Configure Apache2 (Note: Jetendo CMS uses Nginx exclusive, Apache configuration is optional)
	# enable modules
		a2enmod ssl rewrite proxy proxy_html xml2enc
	Change apache2 ip binding
		vi /etc/apache2/ports.conf
			ServerName dev
			Listen 127.0.0.3:80
			Listen 127.0.0.3:443
	
	service apache2 reload
	
	If you don't need Apache, it is recommended to disable it from starting with the following command:
		update-rc.d apache2 disable
	To re-enable:
		update-rc.d apache2 enable
		service apache2 start
	
Install Required Software From Source
	Nginx
		mkdir /opt/jetendo-server/system/nginx-build
		cd /opt/jetendo-server/system/nginx-build
		wget http://nginx.org/download/nginx-1.5.9.tar.gz
		tar xvfz nginx-1.5.9.tar.gz
		adduser --system --no-create-home --disabled-login --disabled-password --group nginx
		
		Put "sendfile off;" in nginx.conf on test server when using virtualbox shared folders
		
		#download and unzip nginx modules
			cd /opt/jetendo-server/system/nginx-build/
			wget https://github.com/simpl/ngx_devel_kit/archive/master.zip
			unzip master.zip -d /opt/jetendo-server/system/nginx-build/
			rm master.zip
			wget https://github.com/agentzh/set-misc-nginx-module/archive/master.zip
			unzip master.zip -d /opt/jetendo-server/system/nginx-build/
			rm master.zip
		
		cd /opt/jetendo-server/system/nginx-build/nginx-1.5.9/
		./configure --with-http_realip_module  --with-http_spdy_module --prefix=/opt/nginx --user=nginx --group=nginx --with-http_ssl_module --with-http_gzip_static_module  --with-http_flv_module --with-http_mp4_module --with-http_stub_status_module  --add-module=/opt/jetendo-server/system/nginx-build/ngx_devel_kit-master --add-module=/opt/jetendo-server/system/nginx-build/set-misc-nginx-module-master
		apt-get install make
		make
		make install
		
		# service is not running until symbolic link and reboot steps are followed below

	add mime-types to /opt/nginx/conf/mime.types
		application/x-font-ttf             ttf;
		font/opentype                      otf;
	
Install Railo
	Compile and Install Apache APR Library
		cd /opt/jetendo-server/system/apr-build/
		# get the newest apr unix gz here: http://apr.apache.org/download.cgi
		wget http://apache.mirrors.pair.com//apr/apr-1.5.0.tar.gz
		tar -xvf apr-1.5.0.tar.gz
		cd apr-1.5.0
		./configure
		make && make install	
	Compile and Install Tomcat Native Library
		JAVA_HOME=/usr/lib/jvm/java-7-oracle
		export JAVA_HOME
		cd /opt/jetendo-server/system/apr-build/
		wget http://apache.spinellicreations.com//tomcat/tomcat-connectors/native/1.1.29/source/tomcat-native-1.1.29-src.tar.gz
		tar -xvzf tomcat-native-1.1.29-src.tar.gz
		cd tomcat-native-1.1.29-src/jni/native/
		./configure --with-apr=/usr/local/apr/bin/ --with-ssl=/usr/include/openssl --with-java-home=/usr/lib/jvm/java-7-oracle && make && make install
		
	Install Railo from newest tomcat x64 binary release on www.getrailo.org
		/opt/jetendo-server/system/railo/railo-4.1.2.005-pl0-linux-x64-installer.run
		When it asks for the user to run Railo as, type in: www-data
		Start railo at boot time: Y
		Don't allow installation of apache connectors: n
		Remember to write down password for Tomcat/Railo administrator.
		
	Put newest JRE In Railo:
		service railo_ctl stop
		rm -rf /opt/railo/jdk/jre
		mkdir /opt/railo/jdk/jre
		/bin/cp -rf /usr/lib/jvm/java-7-oracle/jre/* /opt/railo/jdk/jre
		chown -R www-data. /opt/railo/jdk/jre
	
	# install the server.xml for production or development
		# development
		cp /opt/jetendo-server/railo/server-development.xml /opt/railo/tomcat/conf/server.xml
		# production
		cp /opt/jetendo-server/railo/server-production.xml /opt/railo/tomcat/conf/server.xml
	
	/opt/railo/tomcat/conf/web.xml
		Add the init-param xml below this line:
			<servlet-class>railo.loader.servlet.CFMLServlet</servlet-class>
		init-param xml:
		   <init-param>
				<param-name>railo-web-directory</param-name> 
				<param-value>/opt/jetendo-server/railo/vhosts/{web-context-hash}/</param-value>
				
				<description>Railo Web Directory directory</description> 
			</init-param>
	service railo_ctl start
	
	vi /etc/logrotate.d/tomcat
	/opt/railo/tomcat/logs/catalina.out {
		copytruncate
		daily
		rotate 7
		compress
		missingok
		size 5M
	}
	
	
Install Coldfusion 9.0.2 (Jetendo CMS uses Railo exclusively, Coldfusion installation is optional)
	apt-get install libstdc++5
	download coldfusion 9 developer editing linux 64-bit from adobe: http://www.adobe.com/support/coldfusion/downloads_updates.html#cf9
	/opt/jetendo-server/system/coldfusion/install/ColdFusion_9_WWEJ_linux64.bin
	http://127.0.0.2:8500/CFIDE/administrator/index.cfm
	
Download Install Newest Intel Ethernet Adapter Driver If Production Server Use Intel Device
	lspci | grep -i eth
	
Install Optional Packages If You Want Them:
	# provide KVM virtual machines on production server
		apt-get install cpu-checker qemu-kvm libvirt-bin virtinst bridge-utils ubuntu-virt-server python-vm-builder
	# provide regular ftp
		apt-get install vsftpd
	# provides ab (apachebench) benchmarking utility
		apt-get install apache2-utils
	# provides hard drive smart status utilities
		apt-get install smartmontools
	# utilities for monitoring network and hard drive performance
		apt-get install sysstat iftop
	


# dev server manually modified files
	
# development server symbolic link configuration
	ln -sfn /opt/jetendo-server/system/jetendo-mysql-development.cnf /etc/mysql/conf.d/jetendo-mysql-development.cnf
	ln -sfn /opt/jetendo-server/system/jetendo-nginx-init /etc/init.d/nginx
	ln -sfn /opt/jetendo-server/system/nginx-conf/nginx-development.conf /opt/nginx/conf/nginx.conf
	ln -sfn /opt/jetendo-server/system/jetendo-sysctl-development.conf /etc/sysctl.d/jetendo-sysctl-development.conf
	ln -sfn /opt/jetendo-server/system/monit/jetendo.txt /etc/monit/conf.d/jetendo.txt
	ln -sfn /opt/jetendo-server/system/apache-conf/sites-enabled /etc/apache2/sites-enabled
	ln -sfn /opt/jetendo-server/system/php/pool.d /etc/php5/fpm/pool.d
	
# production server symbolic link configuration
	ln -sfn /opt/jetendo-server/system/jetendo-mysql-production.cnf /etc/mysql/conf.d/jetendo-mysql-production.cnf
	ln -sfn /opt/jetendo-server/system/jetendo-nginx-init /etc/init.d/nginx
	ln -sfn /opt/jetendo-server/system/nginx-conf/nginx-production.conf /opt/nginx/conf/nginx.conf
	ln -sfn /opt/jetendo-server/system/jetendo-sysctl-production.conf /etc/sysctl.d/jetendo-sysctl-production.conf
	ln -sfn /opt/jetendo-server/system/monit/jetendo.txt /etc/monit/conf.d/jetendo.txt
	ln -sfn /opt/jetendo-server/system/apache-conf/sites-enabled /etc/apache2/sites-enabled
	ln -sfn /opt/jetendo-server/system/php/pool.d /etc/php5/fpm/pool.d
	
# enable apparmor profiles:
	development server:
		cp -f /opt/jetendo-server/system/apparmor.d/development/opt.nginx.sbin.nginx /etc/apparmor.d/opt.nginx.sbin.nginx
		cp -f /opt/jetendo-server/system/apparmor.d/development/opt.railo.jdk.jre.bin.java /etc/apparmor.d/opt.railo.jdk.jre.bin.java
		cp -f /opt/jetendo-server/system/apparmor.d/development/usr.bin.mysql /etc/apparmor.d/usr.bin.mysql
		cp -f /opt/jetendo-server/system/apparmor.d/development/usr.sbin.php5-fpm /etc/apparmor.d/usr.sbin.php5-fpm
		apparmor_parser -r /etc/apparmor.d/
	production server:
		cp -f /opt/jetendo-server/system/apparmor.d/production/opt.nginx.sbin.nginx /etc/apparmor.d/opt.nginx.sbin.nginx
		cp -f /opt/jetendo-server/system/apparmor.d/production/opt.railo.jdk.jre.bin.java /etc/apparmor.d/opt.railo.jdk.jre.bin.java
		cp -f /opt/jetendo-server/system/apparmor.d/production/usr.bin.mysql /etc/apparmor.d/usr.bin.mysql
		cp -f /opt/jetendo-server/system/apparmor.d/production/usr.sbin.php5-fpm /etc/apparmor.d/usr.sbin.php5-fpm
		apparmor_parser -r /etc/apparmor.d/
	
	configure the profiles to be specific to your application by editing them in /etc/apparmor.d/ directly.
	
# generate self-signed ssl certs for development
	cd /opt/jetendo-server/system/ssl/
	openssl genrsa -out dev.com.key 2048
	openssl rsa -in dev.com.key -out dev.com.pem
	openssl req -new -key dev.com.key -out dev.com.csr
	openssl x509 -req -days 3650 -in dev.com.csr -signkey dev.com.key -out dev.com.crt

# increase security limits
	vi /etc/security/limits.conf
		* soft nofile 32768
		* hard nofile 32768
		root soft nofile 32768
		root hard nofile 32768
		* soft memlock unlimited
		* hard memlock unlimited
		root soft memlock unlimited
		root hard memlock unlimited
		* soft as unlimited
		* hard as unlimited
		root soft as unlimited
		root hard as unlimited

# reboot system to have all changes take effect.	
reboot

Setup Git options
	git config --global user.name "Your Name Here"
	git config --global user.email "your_email@example.com"
	git config --global core.filemode false

	
Configure fail2ban:
	change max retry to 5 and ban time to 600 seconds in the /etc/fail2ban/jail.conf
	 Add ignoreregex to /etc/fail2ban/filter.d/sshd.conf - so the false break-in message is ignored for non-static ips
		ignoreregex = ^.* reverse mapping checking getaddrinfo .* failed$
		
		in /etc/fail2ban/filter.d/sshd.conf - comment out POSSIBLE BREAK-IN ATTEMPT - because it's not able to work right with the ISP
		# this next line causes failures incorrectly when we are firewalled:
			#            ^%(__prefix_line)sAddress <HOST> .* POSSIBLE BREAK-IN ATTEMPT!*\s*$
	service fail2ban restart
	If you are ever blocked from ssh login, restarting the server or waiting 10 minutes will allow you back in.

Configure Postfix to use Sendgrid.net for relying mail.
	Edit /etc/aliases,  Find the line for "root" and make it "root: EMAIL_ADDRESS" where EMAIL_ADDRESS is the email address that system & security related emails should be forwarded to.
	
	Relay mail with Sendgrid.net (Optional, but recommended for production servers)
		Add this to the end /etc/postfix/main.cf where your_username and your_password are replaced with the sendgrid.net login information.
			# jetendo-custom-smtp-begin
			smtp_sasl_auth_enable = yes
			smtp_sasl_password_maps = static:your_username:your_password
			smtp_sasl_security_options = noanonymous
			smtp_tls_security_level = may
			header_size_limit = 4096000
			relayhost = [smtp.sendgrid.net]:587
			# jetendo-custom-smtp-end
		
	Or relay mail to a google account by adding the following to 
		vi /etc/postfix/main.cf
			#jetendo-custom-smtp-begin
			relayhost = [smtp.gmail.com]:587
			smtp_sasl_auth_enable = yes
			smtp_sasl_password_maps = hash:/etc/postfix/sasl_passwd
			smtp_sasl_security_options = noanonymous
			smtp_tls_CAfile = /etc/postfix/cacert.pem
			smtp_use_tls = yes
			# jetendo-custom-smtp-end
		vi /etc/postfix/sasl_passwd
			[smtp.gmail.com]:587    USERNAME@gmail.com:PASSWORD
		chmod 400 /etc/postfix/sasl_passwd
		postmap /etc/postfix/sasl_passwd
		cat /etc/ssl/certs/Thawte_Premium_Server_CA.pem | sudo tee -a /etc/postfix/cacert.pem
	
	After changing the postfix configuration, restart the service:
		service postfix reload
		
	Verify the mail service is working, by logging in to the guest machine with SSH and typing the following command:
		echo "Test email" | mailx -s "Hello world" your_email@your_company.com
		
	If the mail service isn't working, make sure you entered the right information and followed the steps correctly.
	
	If the problem persists, check the logs at /var/log/mail.log or /var/log/syslog for error messages.
	
Enable hardware random number generator on non-virtual machine.  This is not safe on a virtual machine.
	rngd -r /dev/urandom
	
Configure Jungledisk (Optional)
	This is a recommend solution for remote backup of production servers.
	
	Install Jungledisk
		Download 64-bit Server Edition Software from this URL:
		https://www.jungledisk.com/downloads/business/server/linux/
		
		Place in /opt/jetendo-server/system/ and run this command to install it.  Make sure the file name matches the file you downloaded.
		dpkg -i /opt/jetendo-server/system/junglediskserver_316-0_amd64.deb
		
		Reset the license key on your jungledisk.com account page and replace LICENSE_KEY below with the key they generated for you.
		vi /etc/jungledisk/junglediskserver-license.xml
			<?xml version="1.0" encoding="utf-8"?><configuration><LicenseConfig><licenseKey>LICENSE_KEY</licenseKey><proxyServer><enabled>0</enabled> <proxyServer></proxyServer><userName></userName><password></password></proxyServer></LicenseConfig></configuration>

		service junglediskserver restart
	Use the management client interface from https://www.jungledisk.com/downloads/business/server/linux/ to further configure what and when to backup.  It is highly recommended you enable the encrypted backup feature for best security.  Be sure not to lose your decryption password.

Configuring Static IPs
	vi /etc/network/interfaces
	
	# Be careful, you may accidentally take your server off the Internet if you make a mistake.  It is best to do this with KVM access or have the hosting company help you.
	
	# By default, Virtualbox is configured to use NAT, and this configuration looks like this in /etc/network/interfaces after installing ubuntu
		auto eth0
		iface eth0 inet dhcp
	
	# To replace NAT with a static IP for same interface, delete "auto eth0" and "iface eth0 inet dhcp" and use the settings below.  Make sure the IPs match what is provided by your ISP.  The DNS Nameservers should ideally be your ISP's nameservers for best performance or google public dns which is: 8.8.8.8 8.8.4.4
		auto eth0
		iface eth0 inet static
		address 192.168.0.2
		netmask 255.255.255.0
		network 192.168.0.0
		broadcast 192.168.0.255
		gateway 192.168.0.1
		dns-nameservers 192.168.0.1 192.168.0.1
	
	This is the static ip configuration for development server:
		auto eth0
		iface eth0 inet static
			address 10.0.2.15
			netmask 255.255.255.0
			network 10.0.2.0
			broadcast 10.0.2.255
			gateway 10.0.2.2
			dns-nameservers 10.0.2.2
			
		auto eth0:1
		iface eth0:1 inet static
			address 10.0.2.16
			netmask 255.255.255.0
			network 10.0.2.0
			broadcast 10.0.2.255

	# each additional ip appends to the interface name, a colon and a sequential number.  Such as p4p1:1 below.  You can add as many of these as you wish to a single interface.  It is not necessary to specify the dns-nameservers again.
	auto p4p1:1
	iface p4p1:1 inet static
		address 192.168.0.3
		netmask 255.255.255.0
		network 192.168.0.0
		broadcast 192.168.0.255

Visit http://xip.io/ to understand how this free service helps you create development environments with minimal re-configuration.
	Essentially it automates dns configuration, to let you create new domains instantly that point to any ip address you desire.
	http://mydomain.com.127.0.0.1.xip.io/ would attempt to connection to 127.0.0.1 with the host name mydomain.com.127.0.0.1.xip.io. 
	Jetendo has been designed to support this service by default.
	
one listen path for each fastcgi pool.   /etc/php5/fpm/pool.d/dev.com.conf  - but lets symbolic link it to /opt/jetendo-server/system/php/fpm-pool-conf/
			[dev.com]
			listen = /opt/jetendo-server/php/run/fpm.dev.com.sock
			listen.owner = www-user
			listen.mode = 0600
			user = devsite1
			group = www-data
			pm = dynamic
			pm.max_children = 5
			pm.min_spare_servers = 1
			pm.max_spare_servers = 2
	useradd -g www-data devsite1
	mkdir /var/www/devsite1
	chown devsite1:www-data /var/www/devsite1
	
	for devsite1.com, nginx uses
		fastcgi_pass unix:/opt/jetendo-server/php/run/fpm.devsite1.sock;

Reboot the virtual machine to ensure all services are installed and running before continuing with Jetendo CMS installation
	At a shell prompt, type: 
		reboot
	Also, to turn off the machine gracefully, you can type the following at a shell prompt:
		poweroff
	
		
Configure Jetendo CMS

	Install the Jetendo source code from git by running the php script below from the command line.
	You can edit this file to change the git repo or branch if you want to work on a fork or different branch of the project.  If you intend to contribute to the project, it would be wise to create a fork first.  You can always change your git remote origin later.
	Note: If you want to run a RELEASE version of Jetendo CMS, skip running this file.
		php /opt/jetendo-server/system/install-jetendo.php
		
	Add the following mappings to the Railo web admin for the /opt/jetendo/ context:
		Railo web admin URL for VirtualBox (create a new password if it asks.)
		
		http://dev.com.127.0.0.2.xip.io:8888/railo-context/admin/web.cfm?action=resources.mappings
	
		The resource path for "/zcorecachemapping" must be the sites-writable path for the adminDomain.
		For example, if request.zos.adminDomain = "http://jetendo.your-company.com";
		Then the correct configuration is:
			Virtual: /zcorecachemapping
			Resource Path: /opt/jetendo/sites-writable/jetendo_your-company_com/_cache
		
		Virtual: /zcorerootmapping
		Resource Path: /opt/jetendo/core
		After creating "/zcorerootmapping", click the edit icon and make sure "Top level accessible" is checked and click save.
		
		Virtual: /jetendo-themes
		Resource Path: /opt/jetendo/themes
		
		Virtual: /jetendo-sites-writable
		Resource Path: /opt/jetendo/sites-writable
		
		Virtual: /jetendo-database-upgrade
		Resource Path: /opt/jetendo/database-upgrade
	
	Setup the Jetendo datasource - the database, datasource, jetendo_datasource, and request.zos.zcoreDatasource must all be the same name.
		http://dev.com.127.0.0.2.xip.io:8888/railo-context/admin/web.cfm?action=services.datasource
		Add mysql datasource named "jetendo" or whatever you've configured it to be in the jetendo config files.
			host: 127.0.0.1
			Required options: 
				Blog: Check
				Clob: Check
				Use Unicode: true
				Alias handling: true
				Allow multiple queries: false
				Zero DateTime behavior: convertToNull
				Auto reconnect: false
				Throw error upon data truncation: false
				TinyInt(1) is bit: false
				Legacy Datetime Code: true
	
	Enable complete null support:
		http://dev.com.127.0.0.2.xip.io:8888/railo-context/admin/server.cfm?action=server.compiler
		
	Enable mail server:
		http://dev.com.127.0.0.2.xip.io:8888/railo-context/admin/server.cfm?action=services.mail
		
		Under Mail Servers -> Server (SMTP), type "localhost" and click update"
		
	Configure Railo security sandbox
		http://jetendo.your-company.com.127.0.0.2.xip.io:8888/railo-context/admin/server.cfm?action=security.access&sec_tab=SPECIAL
		Under Create new context, select "b180779e6dc8f3bb6a8ea14a604d83d4 (/opt/jetendo/sites)" and click Create
		Then click edit next to the specific web context
		On a production server, set General Access for read and write to "closed" when you don't need to access the Railo admin.   You can re-enable it only when you need to make changes.
		Under File Access, select "Local" and enter the following directories. 
			Note: In Railo 4.2, you have to enter one directory at a time by submitting the form with one entered, and then click edit again to enter the next one.
			/opt/jetendo/core
			/opt/jetendo/sites
			/opt/jetendo/share
			/opt/jetendo/execute
			/opt/jetendo/public
			/opt/jetendo-server/railo/vhosts/b180779e6dc8f3bb6a8ea14a604d83d4/temp
			/opt/jetendo/sites-writable
			/opt/jetendo/themes
			/opt/jetendo/database-upgrade
			/zbackup/backup
		Uncheck "Direct Java Access"
		Uncheck all the boxes under "Tags & Functions" - Jetendo CMS intentionally allows not using these features to be more secure.
		
	Edit the values in the following files to match the configuration of your system.
		/opt/jetendo/core/config.cfc
		/opt/jetendo/scripts/jetendo.ini
	
	Make sure the jetendo.ini symbolic link is created:
		ln -sfn /opt/jetendo/scripts/jetendo.ini /etc/php5/mods-available/jetendo.ini
	Enable the php configuration module:
		php5enmod jetendo
	
	If you want to run a RELEASE version of Jetendo CMS, follow these steps:
		Download the release file for the "jetendo" project, and unzip its contents to /opt/jetendo in the virtual machine or server.  Make sure that there is no an extra /opt/jetendo/jetendo directory.  The files should be in /opt/jetendo/
		Download the release file for the "jetendo-default-theme" project and unzip its contents to /opt/jetendo/themes/jetendo-default-theme in the virtual machine or server. Make sure that there is no an extra /opt/jetendo/themes/jetendo-default-theme/jetendo-default-theme directory.  The files should be in /opt/jetendo/themes/jetendo-default-theme
		
		Run this command to install it the release without forcing it to use the git repository:
			php /opt/jetendo/scripts/install.php disableGitIntegration
		Note: the project will not be installed as a git repository, so you will have to manually perform upgrades in the future.
		
	If you want to run the DEVELOPMENT version of Jetendo CMS, follow these steps:
		Run this command to install the Jetendo CMS cron jobs and verify the integrity of the source code.
			php /opt/jetendo/scripts/install.php
		Any updates since the last time you ran this installation file, will be pulled from github.com.
		Note: The project will be installed as a git respository.
		
	At the end of a successful run of install.php, you'll be told to visit a URL in the browser to complete installation.  The first time you run that URL, it will restore the database tables, and verify the integrity of the installation.  Please be patient as this process can take anywhere from 10 seconds to a couple minutes the first time depending on your environment.
	
	Troubleshooting Tip: If you have a problem during this step, you may need to drop the entire database, and restart the Railo Server after correcting the configuration.   This is because the first time database installation may fail if you make a mistake in your configuration or if there is a bug in the install script.  Please make us aware of any problems you encountered during installation so we can improve the software.
	
	After it finishes loading, a page should appear saying "You're Almost Ready".
	
	You will be prompted to create a server administrator user and set some other information.
	
	Make sure you select the 127.0.0.1 as the ip on a development machine unless you know what you're doing.
	
	Make sure to remember the email and password you use for the server administrator account.  If you do lose your login, you can reset the password via email.
	
	Once that is done, you will be prompted to login to the server manager and begin using Jetendo CMS.
	
Preparing the virtual machine for distribution:
	Run these commands inside the virtual machine - it will automatically poweroff when complete.
		killall -9 php
		php /opt/jetendo-server/system/clean-machine.php
	In host, run compact on the vdi file - Windows command line example:
		"C:\Program Files\Oracle\VirtualBox\VBoxManage.exe" modifyhd jetendo-server-os.vdi --compact
	# The VDI files should be less then 4gb afterwards.
	# Manually 7-zip the virtual machine - It takes about 10 minutes to make it 6 times smaller
	# regular zip takes 5 minutes to make it 5 times smaller
		jetendo-server-os.vdi, jetendo-server-swap.vdi and jetendo-server.vbox
	