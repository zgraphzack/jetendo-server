
# fix for windows 7+ to be able to handle large amounts of file serving / samba activity:
	Set the following registry key to ’1' hex:
	HKLM\SYSTEM\CurrentControlSet\Control\Session Manager\Memory Management\LargeSystemCache

	and set the following registry key to ’3' hex:
	HKLM\SYSTEM\CurrentControlSet\Services\LanmanServer\Parameters\Size

	On windows, I had to create a 32-bit dword with decimal value 18 in order to avoid running out of memory when using samba.
	HKEY_LOCAL_MACHINE\System\CurrentControlSet\Services\LanmanServer\Parameters\IRPStackSize
	
	Restart "Server" windows service
	
	