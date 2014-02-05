The following readme is to help developers using Windows host.


# fix for windows 7+ to be able to handle large amounts of file serving / samba activity:
	Set the following registry key to ’1' hex:
	HKLM\SYSTEM\CurrentControlSet\Control\Session Manager\Memory Management\LargeSystemCache

	and set the following registry key to ’3' hex:
	HKLM\SYSTEM\CurrentControlSet\Services\LanmanServer\Parameters\Size

	On windows, I had to create a 32-bit dword with decimal value 18 in order to avoid running out of memory when using samba.
	HKEY_LOCAL_MACHINE\System\CurrentControlSet\Services\LanmanServer\Parameters\IRPStackSize
	
	Restart "Server" windows service
	
Instead of relaying mail from postfix to gmail or sendgrid, you can install your own mail server:
Installing hMailServer on Windows
	Download the latest version from http://www.hmailserver.com/
	Configuration:
	server admin password: YOUR_PASSWORD
	add domain called your-company.com
		advanced: catch-all: you@your-company.com
	add user called you@your-company.com with password: YOUR_PASSWORD
	You can then define a pop account in your email client (i.e. Thunderbird, Outlook) to check the local mail server with host: 127.0.0.1, username: you@your-company.com and password: YOUR_PASSWORD with smtp authentication enabled.
	
	# Optionally route external email through sendgrid in hmailserver administrator:
	settings -> protocols -> enable smtp/php/imap
		smtp -> delivery of email:
			enter the mail server authentication information
			for example with sendgrid, it is:
			remote host name: smtp.sendgrid.net
			remote port: 465
			server requires authentication checked with username, password set and ssl checked.
			
	Make sure the your-company.com domain is in your hosts file or has proper DNS configuration.
	Then you can configure PHP and Railo to send mail to this server.

	On the Linux virtual machine, you can update postfix to relay to hmailserver like this:
	/etc/postfix/main.cf
		smtp_sasl_auth_enable = yes
		smtp_sasl_password_maps = static:you@your-company:YOUR_PASSWORD
		smtp_sasl_security_options = noanonymous
		smtp_tls_security_level = may
		header_size_limit = 4096000
		relayhost = [your-company.com]:25
