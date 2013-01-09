<?
//SH management - Machine - Interface

//Abstraction of a network interface of a Machine
class Machine_Interface extends Meta{
  //database ID
  public $id;
  //address type (1: get via DHCP, 2: set static)
  public $type;
  //address object (if $type==2); typeof $addr_obj is array(Net_Address)
  public $addr_obj;
  //the physical address of the interface (aka MAC address)
  public $mac;
  //the OS name for the interface (e.g. eth0 on Linux, "LAN-Verbindung" on Windows)
  public $name;
  
  //type: address gets assigned by DHCP to this mac address
  const IF_DHCP=1;
  //type: address gets assigned statically to this interface name
  const IF_STATIC=1;
  //value-assign maps for Meta getById/commitById
  //1:1 loadById
  public static $va_map=array("id"=>"id","name"=>"os_name","mac"=>"hw_addr","type"=>"type");
  //child-objects of this object
  public static $va_external=array("addr_obj"=>array("class"=>"Net_Address","table"=>"link_machines_interfaces_net_addresses","own_key"=>"interface_id","external_key"=>"address_id"));
  //Objects which can reference this object (used to build a "This object is used in xyz"-style list)
  public static $va_references=array("Machine"=>array("table"=>"link_machines_interfaces","own_key"=>"interface_id","external_key"=>"machine_id"));
  public static $va_table="machines_interfaces";
  
  //initialize a new Machine_Interface object with the given parameters
  public function __construct4($id,$name,$mac,$type) {
    logger::trace("Constructing Machine_Interface object with id=%d, name='%s', mac='%s', type=%d",$id,$name,$mac,$type);
    $this->id=$id;
    $this->type=$type;
    $addr_obj=Net_Address::getByReference("Machine_Interface",$id);
    if(sizeof($addr_obj)>1)
      logger::error("Machine_Interface %d has more than 1 addr-object (%d)",$id,sizeof($addr_obj));
    $this->addr_obj=$addr_obj;
    $this->name=$name;
    $this->mac=$mac;
  }
    
  public function __toString() {
    if(!isset($this->addr_obj[0]))
      $addr_str="no addr_obj";
    else
      $addr_str=(string)$this->addr_obj[0];
    return sprintf("[Machine_Interface id=%d name='%s' mac='%s' type='%d' addrobj='%s']",$this->id,$this->name,$this->mac,$this->type,$addr_str);
  }
}
