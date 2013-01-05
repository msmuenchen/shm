<?
//SH management - Net - Address

//Abstraction of an IP address
class Net_Address extends Meta {
  //database ID
  public $id;
  //network object
  public $network_obj;
  //the IP address of the interface
  public $addr;
  
  //value-assign maps for Meta getById/commitById
  //1:1 loadById
  public static $va_map=array("id"=>"id","addr"=>"addr");
  //child-objects of this object
  public static $va_external=array("network_obj"=>array("class"=>"Net_Network","table"=>"link_net_addresses_network","own_key"=>"address_id","external_key"=>"network_id"));
  //Objects which can reference this object (used to build a "This object is used in xyz"-style list)
  public static $va_references=array("Host_Interface"=>array("table"=>"link_hosts_interfaces_net_addresses","own_key"=>"address_id","external_key"=>"interface_id"));
  public static $va_table="net_addresses";
  
  //initialize a new Net_Address object with the given parameters
  public function __construct2($id,$addr) {
    logger::trace("Constructing Net_Address object with id=%d, addr='%s'",$id,$addr);
    $this->id=$id;
    $this->addr=$addr;
    $network_obj=Net_Network::getByReference("Net_Address",$id);
    if(sizeof($network_obj)>1)
      logger::error("Net_Address %d has more than 1 network-object (%d)",$id,sizeof($network_obj));
    $this->network_obj=$network_obj;
  }
    
  public function __toString() {
    if(!isset($this->network_obj[0]))
      $net_str="no network_obj";
    else
      $net_str=(string)$this->network_obj[0];
    return sprintf("[Net_Address id=%d addr='%s' netobj='%s']",$this->id,$this->addr,$net_str);
  }
}
