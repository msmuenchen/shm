<?
//SH management - Host

//Host class
class Host extends Meta {
  //database ID
  public $id;
  //Host instance key (differentiates between Hosts on Machines)
  public $key;
  //OS
  public $os=null;
  
  //value-assign maps for Meta get*/commit*
  public static $va_table="hosts";
  //1:1 loadById
  public static $va_map=array("id"=>"id","key"=>"key","os_id"=>"os_id");
  //child objects
  public static $va_external=array("os"=>array("class"=>"OS","table"=>"hosts","own_key"=>"id","external_key"=>"os_id"));
  //parent objects
  public static $va_references=array();
  
  public function __construct3($id,$key,$os_id) {
    $this->id=$id;
    $this->key=$key;
//    if($parent_id!==null)
//      $this->parent=array(OS::getById($parent_id));
  }  
  public function toString() {
    if($this->os===null)
      $osstr="unknown os";
    else
      $osstr=(string)$this->parent;
    return sprintf("[Host id=%d '%s' os='%s']",$this->id,$this->key,$osstr);
  }
}