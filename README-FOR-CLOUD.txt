In addition to the instructions in README.txt, you can also follow these steps to convert the machine startup/shutdown/upgrades to be automated via Jetendo Server's cloud automation scripts.

before this script can be used to boot the server, we need to prepare the system by disabling services, removing some of the mounts from fstab
update-rc.d apache2 disable
update-rc.d coldfusion disable
update-rc.d railo_ctl disable
update-rc.d monit disable
update-rc.d postfix disable
update-rc.d dnsmasq disable
update-rc.d fail2ban disable
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
echo "manual" > /etc/init/fail2ban.override

Disable all /etc/fstab jetendo-server/jetendo mounts except /var/jetendo-server/system and /var/jetendo-server/config

To install this script to automatically run at boot, you must run these commands:
	/bin/cp -f /var/jetendo-server/system/jetendo-server /etc/init.d/jetendo-server
	/bin/chmod 755 /etc/init.d/jetendo-server
	update-rc.d jetendo-server defaults
	
Enable 9p to load at boot
	vi /etc/initramfs-tools/modules
		9p
		9pnet
		9pnet_virtio
	update-initramfs -u

qemu -kernel "/boot/vmlinuz-$(uname -r)" \
  -initrd "/boot/initrd.img-$(uname -r)" \
  -fsdev local,id=r,path=/,security_model=none \
  -device virtio-9p-pci,fsdev=r,mount_tag=r \
  -nographic \
  -append 'root=r ro rootfstype=9p rootflags=trans=virtio console=ttyS0 init=/bin/sh'
	
Manually running the cloud automation scripts

	Starting Jetendo Server:
		php /var/jetendo-server/system/jetendo-start.php
		
	Stopping Jetendo Server:
		php /var/jetendo-server/system/jetendo-stop.php
		
	Upgrade Jetendo Server (replacing the base image with a new base image):
		php /var/jetendo-server/system/jetendo-upgrade.php
		
	Show Status for Jetendo Server:
		php /var/jetendo-server/system/jetendo-status.php
		
	Jetendo Server stop replication / public traffic in a controlled manner:
		php /var/jetendo-server/system/jetendo-down.php
		
	Jetendo Server resume replication / public traffic in a controlled manner:
		php /var/jetendo-server/system/jetendo-up.php
		
During development, you can deploy new versions of Jetendo Server configuration files from the development machine to the production host machine(s) using the following command:
	PREVIEW MODE:
		php /var/jetendo-server/system/deploy-jetendo-server.php preview=1
	WRITE MODE:
		php /var/jetendo-server/system/deploy-jetendo-server.php preview=0

NOT SURE WHAT THIS IS FOR: on production, make sure you have a copy of the /etc/hosts file stored here:
	/var/jetendo-server/jetendo/share/hosts
