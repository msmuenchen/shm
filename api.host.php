<?
//SH management - Host

//Abstraction of a Host
class Host extends Meta{
  //database ID
  public $id;
  //host type (1: physical, 2: VMware virtual)
  public $type;
  //the groups of the host, type Host_Group
  public $group_objs;
  //the roles of the host, type Host_Role
  public $role_objs;
  //the host name
  public $name;
  //the Host hosting the VM (if type==2)
  public $vmhost_obj;
  //the Interface objects of this Host
  public $interface_objs;
  
  //value-assign maps for Meta getById/commitById
  //1:1 loadById
  public static $va_map=array("id"=>"id","name"=>"name","type"=>"type");
  //child-objects of this object
  public static $va_external=array("interface_obj"=>array("class"=>"Host_Interface","table"=>"link_hosts_interfaces","own_key"=>"hosts_id","external_key"=>"interface_id"));
  //Objects which can reference this object (used to build a "This object is used in xyz"-style list)
  public static $va_references=array();
  public static $va_table="hosts";
  
  //initialize a new Host object with the given parameters
  public function __construct3($id,$name,$type) {
    logger::trace("Constructing Host object with id=%d, name='%s', type=%d",$id,$name,$type);
    $this->id=$id;
    $this->type=$type;
    $interfaces=Host_Interface::getByReference("Host",$id);
    $this->interface_objs=$interfaces;
    $this->name=$name;
  }
    
  public function __toString() {
    if(sizeof($this->interface_objs)>0)
      $interfaces_str=implode(",",$this->interface_objs);
    else
      $interfaces_str="no interface_objs";
    return sprintf("[Host id=%d name='%s' type='%d' interfaces='%s']",$this->id,$this->name,$this->type,$interfaces_str);
  }
}
