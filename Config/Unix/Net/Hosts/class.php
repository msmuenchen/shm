<?
//SH management - API - Config - Unix - Net - Hosts

//Get the configuration file /etc/hosts for a machine

class Config_Unix_Net_Hosts {
  static public function getConfig($machine_id) {
    //make sure that even when an error happens, at least a basic conffile can be written
    $buffer="";
    //use this to check if a primary interface was specified
    $foundPrimaryInterface=false;
    try {
      //No need to check validity - getById will do this for us :)
      $machine=Machine::getById($machine_id);
      $interfaces=$machine->interface_objs;
      foreach($interfaces as $idx=>$interface) {
        if(!$interface instanceof Machine_Interface) {
          $buffer.=sprintf("# Interface index=%d is not valid\n",$idx);
          continue;
        }
        $buffer.=sprintf("# Interface ID=%d, MAC=%s\n",$interface->id,$interface->mac);
        if(!isset($interface->addr_obj[0]) || !$interface->addr_obj[0] instanceof Net_Address) {
          $buffer.="# No address configuration found for this interface\n";
          continue;
        }
        $addr_obj=$interface->addr_obj[0];
        $buffer.=sprintf("# Address id=%d\n",$addr_obj->id);

        if(!isset($addr_obj->network_obj[0]) || !$addr_obj->network_obj[0] instanceof Net_Network) {
          $buffer.=sprintf("\n# No network configuration found for addr_obj(id=%d)",$addr_obj->id);
          continue;
        }
        $network_obj=$addr_obj->network_obj[0];
        $buffer.=sprintf("# Network id=%d\n",$network_obj->id);

        $buffer.=sprintf("%s\t",$addr_obj->addr);
        if($network_obj->dns_dom!="")
          $buffer.=sprintf("%s.%s ",$machine->name,$network_obj->dns_dom);
        //the IP of the primary interface is the IP to which "just" the machinename will resolve
        if($interface->isPrimary) {
          $buffer.=sprintf("%s",$machine->name);
          $foundPrimaryInterface=true;
        }
        $buffer.="\n";

      }
    } catch(Exception $e) {
      $buffer="# Error ".trim($e->getMessage())."\n";
    }
    //Always echo this, even in case of failure.
    //This way, the system can at least start
    $return="# Autogenerated config file for machine $machine_id on ".date("d.m.Y H:i:s")."\n\n";
    $return.= "127.0.0.1\tlocalmachine\n";
    $return.= "::1\t\tlocalmachine ip6-localmachine ip6-loopback\n";
    $return.= "ff02::1\t\tip6-allnodes\n";
    $return.= "ff02::2\t\tip6-allrouters\n";
    $return.= "\n";

    if(!$foundPrimaryInterface) {
      $return.="# WARNING - DID NOT FIND PRIMARY INTERFACE\n";
      logger::warn("Did not find primary interface for machine %d",$machine_id);
    }

    $return.=$buffer;
    return $return;
  }
}