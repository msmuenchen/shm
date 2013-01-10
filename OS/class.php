<?
//SH management - OS

//OS class
class OS extends Meta {
  //database ID
  public $id;
  //OS name
  public $name;
  //OS version
  public $version;
  //Classname
  public $classname;
  //Parent (may be NULL at top of tree, else this is supposed to be child of OS)
  public $parent=null;
  
  public static $va_table="os";
  public static $va_map=array("id"=>"id","name"=>"name","version"=>"version","classname"=>"classname","parent_id"=>"parent_os_id");

  public function __construct5($id,$name,$version,$classname,$parent_id) {
    $this->id=$id;
    $this->name=$name;
    $this->version=$version;
    $this->classname=$classname;
    if($parent_id!==null)
      $this->parent=array(OS::getById($parent_id));
  }  
  public function toString() {
    if($this->parent===null)
      $parentstr="/";
    else
      $parentstr=(string)$this->parent;
    return sprintf("[OS id=%d '%s' (%s) class='%s' parent='%s']",$this->id,$this->name,$this->version,$this->classname,$parentstr);
  }
}