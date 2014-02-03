Jetendo Server Installation Documentation
OS: Ubuntu Server 12.04 LTS 

This readme is for users that downloaded the pre-configured virtual machine from https://www.jetendo.com/

If you'd like to install the OS yourself from scratch, please use README.txt that should be included in the Jetendo Server or you can download it from https://github.com/jetendo/jetendo-server/

To use this image with virtualbox, there are a few steps to customize it so that you receive the email alerts and that it works with your projects.

Configure Virtualbox

Virtualbox initial setup
	Make sure you are running the latest version of Virtualbox available for download at https://www.virtualbox.org/

	You should be able to double click the jetendo-server.vbox file to load it into VirtualBox.
	
	Click settings and review/modify the configuration as desired.   These are the recommended default options:
		Ubuntu Linux x64
		Minimum requirements: 2048mb ram, 5gb hard drive, 1gb 2nd hard drive for swap, 1 NAT network adapter
		NAT Advanced Settings -> Port forwarding
			Name: SSH, Host Ip: 127.0.0.2: Host Port: 3222, Guest Ip: 10.0.2.15, Guest Port: 22
			Name: Nginx, Host Ip: 127.0.0.2: Host Port: 80, Guest Ip: 10.0.2.15, Guest Port: 80
			Name: Nginx SSL, Host Ip: 127.0.0.2: Host Port: 443, Guest Ip: 10.0.2.15, Guest Port: 443
			Name: Apache, Host Ip: 127.0.0.3: Host Port: 80, Guest Ip: 10.0.2.16, Guest Port: 80
			Name: Apache SSL, Host Ip: 127.0.0.3: Host Port: 443, Guest Ip: 10.0.2.16, Guest Port: 443
			Name: Railo, Host Ip: 127.0.0.2: Host Port: 8888, Guest Ip: 10.0.2.15, Guest Port: 8888
		Setup Shared Folders - The following names must point to the directory with the same name on your host system.  By default, they are a subdirectory of this project, however, you may relocate the paths if you wish for more space or performance.
			nginx
			mysql
			coldfusion
			system
			railo
			php
			apache
			jetendo
		Hard Disk 1: jetendo-os.vdi
		Hard Disk 2: jetendo-swap.vdi

After virtual machine has booted:

Verify the VirtualBox shared folders are working:
	Open an SSH connection to the guest machine.
		Reminder, the SSH connection information is as follows:
			Host: 127.0.0.2
			Port: 22
			Username: root
		Note: No password is required to login.
	Run this command and verify that the directory isn't empty.
		ls -al /opt/jetendo-server/system
	
	If the directory was empty, you most likely need to update the VirtualBox Guest Additions because you are running a different version then our version.  
	
	Follow these steps to upgrade the VirtualBox Guest Additions:

		1. In the VirtualBox Host GUI, click on Devices -> Insert Guest Addition CD Image
		2. Open an SSH connection to the guest machine
		3. Type: mount /dev/cdrom /media/cdrom
		4. Type: /media/cdrom/VBoxLinuxAdditions.run
		5. Type: reboot and then login to SSH again when system is done rebooting.
		6. Verify the shared folder is working by typing: ls -al /opt/jetendo-server/system
		7. If you still have trouble, make sure the path is correct on the host system in the shared folders configuration and that you have a complete copy of the Jetendo Server project available at https://github.com/jetendo/jetendo-server

Configure MySQL (MariaDB):
	If you already have database data files installed at /opt/mysql/data through the virtualbox shared folders, skip this step.
	
	To reinstall the initial mysql tables, run this command:
		/usr/bin/mysql_install_db --user=mysql --basedir=/usr --datadir=/opt/mysql/data
	
	Then run:
		/usr/bin/mysql_secure_installation
		
	Restart mysql service:
		/usr/sbin/service mysql restart
	
	Verify you can connect to the mysql server using sqlyog or another GUI in your host system where YOUR_PASSWORD is the password you provided during the execution of "mysql_secure_installation":
		host: 127.0.0.2
		port: 3306
		username: root
		password: YOUR_PASSWORD
	
Configure Postfix
	vi /etc/postfix/main.cf
	
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
		
	Or relay mail to a google account by adding the following to /etc/postfix/main.cf
			#jetendo-custom-smtp-begin
			relayhost = [smtp.gmail.com]:587
			smtp_sasl_auth_enable = yes
			smtp_sasl_password_maps = hash:/etc/postfix/sasl_passwd
			smtp_sasl_security_options = noanonymous
			smtp_tls_CAfile = /etc/postfix/cacert.pem
			smtp_use_tls = yes
			# jetendo-custom-smtp-end
		Then continue with these commands to complete google account setup:  Replace and EMAIL and PASSWORD with your actual login information.  You may want to use a separate gmail account so your business email account password doesn't need to be stored in plain text.
			Create password file:
				vi /etc/postfix/sasl_passwd
					[smtp.gmail.com]:587    EMAIL:PASSWORD
			chmod 400 /etc/postfix/sasl_passwd
			postmap /etc/postfix/sasl_passwd
			cat /etc/ssl/certs/Thawte_Premium_Server_CA.pem | sudo tee -a /etc/postfix/cacert.pem
	
	After changing the postfix configuration, restart the service:
		service postfix reload
		
	Verify the mail service is working, by logging in to the guest machine with SSH and typing the following command:
		echo "Test email" | mailx -s "Hello world" your_email@your_company.com
		
	If the mail service isn't working, make sure you entered the right information and followed the steps correctly.
	
	If the problem persists, check the logs at /var/log/mail.log or /var/log/syslog for error messages.
	
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
	
Setup Git options
	Personalize git now so we know who made changes later.
	git config --global user.name "Your Name Here"
	git config --global user.email "your_email@example.com"
	git config --global core.filemode false
	
	
Configure Jetendo CMS

	Install the jetendo source code from git by running the php script below from the command line.
	You can edit this file to change the git repo or branch if you want to work on a fork or different branch of the project.  If you intend to contribute to the project, it would be wise to create a fork first.  You can always change your git remote origin later.
		php /opt/jetendo/system/install-jetendo.php

	Edit the values in the following files to match the configuration of your system.
		/opt/jetendo/core/config.cfc
		/opt/jetendo/scripts/jetendo.ini
	
	Run this command to install the Jetendo CMS cron jobs and verify the integrity of the source code.
		php /opt/jetendo/scripts/install.php

	At the end of a successful run of install.php, you'll be told to visit a URL in the browser to complete installation.  The first time you run that URL, it will restore the database tables, and verify the integrity of the installation.  Please be patient as this process can take anywhere from 10 seconds to a couple minutes the first time depending on your environment.
	
	After it finishes loading, a page should appear saying "You're Almost Ready".
	
	You will be prompted to create a server administrator user and set some other information.  Make sure to remember the email and password you use for the server administrator account.  If you do lose your login, you can reset the password via email.
	
	Once that is done, you will be prompted to login and begin using Jetendo CMS.

At this point of the readme, you should have a fully working version of Jetendo CMS installed on Jetendo Server.

How to access web sites and administrative services:
	Make sure to update the domains to match the domain you specified in configuration files.
		
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
		
Visit http://xip.io/ to understand how this free service helps you create development environments with minimal re-configuration.
	Essentially it automates dns configuration, to let you create new domains instantly that point to any ip address you desire.
	http://mydomain.com.127.0.0.2.xip.io/ would attempt to connection to 127.0.0.2 with the host name mydomain.com.127.0.0.2.xip.io. 
	Jetendo has been designed to support this service by default.
	
Linux reboot and poweroff commands:
	To reboot Ubuntu linux, type the following at a shell prompt:
		reboot
	To poweroff Ubuntu linux gracefully, type the following at a shell prompt:
		poweroff
	To suddenly & forcefully poweroff the machine (Dangerous), type the following at a shell prompt:
		shutdown -h now
		
	To suddenly & forcefully reboot the machine (Dangerous), type the following at a shell prompt:
		shutdown -r now