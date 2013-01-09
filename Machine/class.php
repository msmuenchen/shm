<?
//SH management - Machine

//Abstraction of a Machine
class Machine extends Meta{
  //database ID
  public $id;
  //machine type (1: physical, 2: VMware virtual)
  public $type;
  //the groups of the machine, type Machine_Group
  public $group_objs;
  //the roles of the machine, type Machine_Role
  public $role_objs;
  //the machine name
  public $name;
  //the Machine machineing the VM (if type==2)
  public $vmmachine_obj;
  //the Interface objects of this Machine
  public $interface_objs;
  
  //value-assign maps for Meta getById/commitById
  //1:1 loadById
  public static $va_map=array("id"=>"id","name"=>"name","type"=>"type");
  //child-objects of this object
  public static $va_external=array("interface_objs"=>array("class"=>"Machine_Interface","table"=>"link_machines_interfaces","own_key"=>"machine_id","external_key"=>"interface_id"));
  //Objects which can reference this object (used to build a "This object is used in xyz"-style list)
  public static $va_references=array();
  public static $va_table="machines";
  
  //initialize a new Machine object with the given parameters
  public function __construct3($id,$name,$type) {
    logger::trace("Constructing Machine object with id=%d, name='%s', type=%d",$id,$name,$type);
    $this->id=$id;
    $this->type=$type;
    $interfaces=Machine_Interface::getByReference("Machine",$id);
    $this->interface_objs=$interfaces;
    $this->name=$name;
  }
    
  public function __toString() {
    if(sizeof($this->interface_objs)>0)
      $interfaces_str=implode(",",$this->interface_objs);
    else
      $interfaces_str="no interface_objs";
    return sprintf("[Machine id=%d name='%s' type='%d' interfaces='%s']",$this->id,$this->name,$this->type,$interfaces_str);
  }
}
