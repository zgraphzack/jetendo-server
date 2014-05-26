<?php

function getMachineXML($machine, $newBase){
	global $disableKVM;
	$xml='<domain type="kvm" id="1">
	<name>'.$machine['name'].'</name>
	<os>
	<type>hvm</type>
	<boot dev="hd"/>
	</os>
	<cpu mode="host-passthrough"/>
	<features>
    <acpi/>
    <apic/>
    <pae/>
      <hyperv>
        <relaxed state="on"/> 
      </hyperv>
	</features>
	<memory>'.$machine['memory'].'</memory>
	<vcpu>8</vcpu>
	<devices>';
	foreach($machine['drives'] as $key=>$drive){
		if($drive['type']=='cdrom'){
			$xml.='<disk type="file" device="cdrom">
				<source file="'.$drive['file'].'"/>
				<target dev="'.$key.'"/>
				<readonly/>
			</disk>'."\n";
		}else{
			$xml.='<disk type="file" snapshot="external">'."\n";
			if($drive['type']=='raw'){
				$xml.='<driver name="qemu" type="raw" cache="none" io="native" />'."\n";
			}else{
				$xml.='<driver name="qemu" type="'.$drive['type'].'" cache="none" />'."\n";
			}
			if($drive['file']=='base'){
				$drive['file']='/var/jetendo-server/virtual-machines/'.$machine['name'].'-'.$newBase;
			}
			if(!$disableKVM && !file_exists($drive['file'])){
				echo $drive['file']." doesn't exist. You can't define a machine with a missing image path.\n";
				exit;
			}
			$xml.='<source file="'.$drive['file'].'"/>'."\n";
			$xml.='<target dev="'.$key.'" bus="virtio"/>
			</disk>'."\n";
		}
	}
	$xml.='	<interface type="bridge">
			<mac address="'.$machine['mac'].'"/>
			<source bridge="virbr0"/>
			<model type="virtio"/>
		</interface>
		<graphics type="spice" port="'.$machine['spicePort'].'" autoport="no" listen="127.0.0.1"/>
		 <video>
	      <model type="qxl" vram="32768" heads="1"/>
	      <address type="pci" domain="0x0000" bus="0x00" slot="0x02" function="0x0"/>
	    </video>';
	foreach($machine['shared_folders'] as $name=>$folder){
		// consider using accessmode "passthrough" (lets guest change permissions) or "squashed" (ignores some failures but acts like passthrough)
		// Mapped doesn't let guest change permissions at all.
		$xml.='<filesystem type="mount" accessmode="mapped"> 
		   <source dir="'.$folder['path'].'"/>
		   <target dir="'.$name.'"/>'."\n";
			if(isset($folder['readonly']) && $folder['readonly']){
				$xml.='<readonly />'."\n";
			}
		$xml.='</filesystem>'."\n";
	}
	$xml.='</devices>
</domain>
';
/*
private network xml:
<network>
<name>default</name>
<bridge name="virbr0" />
<forward mode="nat"/>
<ip address="192.168.48.1" netmask="255.255.255.0">
  <dhcp>
	<range start="192.168.48.2" end="192.168.48.254" />
  </dhcp>
</ip>
</network>
*/
	return $xml;
}


?>