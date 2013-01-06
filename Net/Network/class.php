<?
//SH management - Net - Network

//Abstraction of an IP network (a IP range)
class Net_Network extends Meta {
  //database ID
  public $id;
  //start IP (IPs valid for clients)
  public $ip_start;
  //end IP
  public $ip_end;
  //router (default gateway)
  public $router;
  //network
  public $network;
  //netmask
  public $netmask;
  //broadcast
  public $broadcast;
  //dns0
  public $dns0;
  //dns1
  public $dns1;
  //dns-search-domain
  public $dns_dom;
    
  //value-assign maps for Meta getById/commitById
  //1:1 loadById
  public static $va_map=array("id"=>"id","ip_start"=>"ip_start","ip_end"=>"ip_end","router"=>"router","network"=>"network","netmask"=>"netmask","broadcast"=>"broadcast","dns0"=>"dns0","dns1"=>"dns1","dns_dom"=>"dns_dom");
  //child-objects of this object
  public static $va_external=array();
  //Objects which can reference this object (used to build a "This object is used in xyz"-style list)
  public static $va_references=array("Net_Address"=>array("table"=>"link_net_addresses_networks","own_key"=>"network_id","external_key"=>"address_id"));
  public static $va_table="net_networks";
  
  //initialize a new Net_Network object with the given parameters
  public function __construct10($id,$ip_start,$ip_end,$router,$network,$netmask,$broadcast,$dns0,$dns1,$dns_dom) {
    logger::trace("Constructing Net_Network object with id=%d, ip_start='%s', ip_end='%s', router='%s', network='%s', netmask='%s', broadcast='%s', dns0='%s', dns1='%s', dns_dom='%s'",$id,$ip_start,$ip_end,$router,$network,$netmask,$broadcast,$dns0,$dns1,$dns_dom);
    $this->id=$id;
    $this->ip_start=$ip_start;
    $this->ip_end=$ip_end;
    $this->router=$router;
    $this->network=$network;
    $this->netmask=$netmask;
    $this->broadcast=$broadcast;
    $this->dns0=$dns0;
    $this->dns1=$dns1;
    $this->dns_dom=$dns_dom;
  }
    
  public function __toString() {
    return sprintf("[Net_Network id=%d, ip_start='%s', ip_end='%s', router='%s', network='%s', netmask='%s', broadcast='%s', dns0='%s', dns1='%s', dns_dom='%s'",$this->id,$this->ip_start,$this->ip_end,$this->router,$this->network,$this->netmask,$this->broadcast,$this->dns0,$this->dns1,$this->dns_dom);
  }
}
