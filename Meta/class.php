<?
//SH management - API - Meta

//Parent class for objects
class Meta {
  //is this the primary object?
  //only useful for objects acquired through getByReference
  public $isPrimary=false;
  
  //allow for multiple constructors
  function __construct() {
    logger::trace("Meta CTOR called for class %s",get_called_class());
    $a = func_get_args(); 
    $i = func_num_args(); 
    $method="__construct$i";
    if (method_exists($this,$method)) {
      call_user_func_array(array($this,$method),$a); 
    } else
      logger::error("Meta CTOR can't find child CTOR with %d args for %s (function name should be %s)",$i,get_called_class(),$method);
  }
  
  //load from DB
  public static function getById($id) {
    $childclass=get_called_class();
    logger::trace("Getting %s object for id=%d",$childclass,$id);
    
    //Security
    if(!is_numeric($id))
      logger::error("Supplied '%s', which is not a numeric!",$id);
    
    //get the VA map and config
    if(!isset($childclass::$va_map) || !is_array($childclass::$va_map))
      logger::error("can't find VA map for %s",$childclass);
    if(!isset($childclass::$va_table))
      logger::error("can't find VA table for %s",$childclass);
    $va_map=$childclass::$va_map;
    $va_table=$childclass::$va_table;
    
    //fetch the object's data from DB
    $q=new DB_Query("select * from `$va_table` where id=?",$id);
    $entries=$q->numRows;
    if($entries!=1)
      logger::error("Expected 1 entry, got %d",$entries);
    $entry=$q->fetch();
    
    //parse the VA map
    $ctor_args=array();
    foreach($va_map as $member=>$db_field) {
      if(!isset($entry[$db_field]))
        logger::error("Mapping entry for classmember %s points to DB field %s, but this did not get returned in select",$member,$db_field);
      $ctor_args[]=$entry[$db_field];
      logger::trace("Mapping DB %s (value '%s') to classmember %s and CTOR id %d for %s",$db_field,$entry[$db_field],$member,sizeof($ctor_args),$childclass);
    }

    $q->free();
    $inst=new ReflectionClass($childclass);
    return $inst->newInstanceArgs($ctor_args);
  }

  //load from DB
  public static function getAll() {
    $ret=array();
    
    $childclass=get_called_class();
    logger::trace("Getting %s object for all ids",$childclass);
    
    //get the VA map and config
    if(!isset($childclass::$va_map) || !is_array($childclass::$va_map))
      logger::error("can't find VA map for %s",$childclass);
    if(!isset($childclass::$va_table))
      logger::error("can't find VA table for %s",$childclass);
    $va_map=$childclass::$va_map;
    $va_table=$childclass::$va_table;
    
    //fetch the object's data from DB
    $q=new DB_Query("select * from `$va_table`");
    $entries=$q->numRows;
    if($entries<1)
      return $ret;
    
    $inst=new ReflectionClass($childclass);
    while($entry=$q->fetch()) {
      
      //parse the VA map
      $ctor_args=array();
      foreach($va_map as $member=>$db_field) {
        if(!isset($entry[$db_field]))
          logger::error("Mapping entry for classmember %s points to DB field %s, but this did not get returned in select",$member,$db_field);
        $ctor_args[]=$entry[$db_field];
        logger::trace("Mapping DB %s (value '%s') to classmember %s and CTOR id %d for %s",$db_field,$entry[$db_field],$member,sizeof($ctor_args),$childclass);
      }  
      $ret[]=$inst->newInstanceArgs($ctor_args);
    }
    
    $q->free();
    return $ret;
  }

  //load referencing objects from DB
  //like Host_Interface::getByReference("Host",3) gets all Host_Interface which are child elements of the Host with ID 3
  public static function getByReference($refclass, $id) {
    $ret=array();
    
    $childclass=get_called_class();
    logger::trace("Getting all %s objects which are referenced by the %s object with id=%d",$childclass,$refclass,$id);
    
    //Security
    if(!is_numeric($id))
      logger::error("Supplied id '%s', which is not a numeric!",$id);
    if(!sh_class_exists($refclass))
      logger::error("Supplied refclass '%s' which does not exist, not even in autloader",$refclass);

    //get the VA map
    if(!isset($childclass::$va_references) || !is_array($childclass::$va_references))
      logger::error("can't find VA references map for %s",$childclass);
    $va_references=$childclass::$va_references;
    
    //Check if the referencing class is inside the list; if yes, load config
    if(!isset($va_references[$refclass]))
      logger::error("%s is not in the references list of %s",$refclass,$childclass);
    $refdata=$va_references[$refclass];
    
    //fetch the object's data from DB
    $q=new DB_Query("select * from `{$refdata['table']}` where `{$refdata['external_key']}`=?",$id);
    $entries=$q->numRows;
    if($entries==0) {
      logger::trace("Expected 1 or more entries, got %d",$entries);
      return $ret;
    }
    
    //get the data
    while($entry=$q->fetch()) {
      $inst=$ret[]=call_user_func("$childclass::getById",$entry[$refdata["own_key"]]);
      if(isset($entry["is_primary"]) && $entry["is_primary"]==1) {
        logger::trace("Object %d is set as primary",$inst->id);
        $inst->isPrimary=true;
      }
    }
    $q->free();
    return $ret;
  }
  
  //get all objects which have the column $prop == $val; $mode can also be set to LIKE
  public static function getByProperty($prop,$val,$mode="=") {
    $ret=array();
    
    $childclass=get_called_class();
    
    //get the VA map
    if(!isset($childclass::$va_map) || !is_array($childclass::$va_map))
      logger::error("can't find VA map for %s",$childclass);
    if(!isset($childclass::$va_table))
      logger::error("can't find VA table for %s",$childclass);
    $va_map=$childclass::$va_map;
    $va_table=$childclass::$va_table;    

    $cols=DB::getTableCols($va_table);
    if(array_search($prop,$cols)===false)
      logger::error("%s is not in the column list of %s",$prop,$va_table);

    //fetch the object's data from DB
    $q=new DB_Query("select * from `$va_table` where `$prop` $mode ?",$val);
    $entries=$q->numRows;
    if($entries<1)
      return $ret;
    
    $inst=new ReflectionClass($childclass);
    while($entry=$q->fetch()) {
      
      //parse the VA map
      $ctor_args=array();
      foreach($va_map as $member=>$db_field) {
        if(!isset($entry[$db_field]))
          logger::error("Mapping entry for classmember %s points to DB field %s, but this did not get returned in select",$member,$db_field);
        $ctor_args[]=$entry[$db_field];
        logger::trace("Mapping DB %s (value '%s') to classmember %s and CTOR id %d for %s",$db_field,$entry[$db_field],$member,sizeof($ctor_args),$childclass);
      }  
      $ret[]=$inst->newInstanceArgs($ctor_args);
    }
    
    $q->free();
    return $ret;    
  }
  
  //get one or more elements by one of their child elements
  //like Host::getByChild("Host_Interface",3") gets all Hosts which have the Interface with ID=3
  public static function getByChild($cc,$id) {
    $ret=array();
    
    $childclass=get_called_class();
    logger::trace("Getting all %s objects which have the object %s with ID %d as children",$childclass,$cc,$id);

    //Security
    if(!is_numeric($id))
      logger::error("Supplied id '%s', which is not a numeric!",$id);
    if(!sh_class_exists($cc))
      logger::error("Supplied refclass '%s' which does not exist, not even in autloader",$cc);

    //get the VA map
    if(!isset($childclass::$va_external) || !is_array($childclass::$va_external))
      logger::error("can't find VA external map for %s",$childclass);
    $va_external=$childclass::$va_external;
    
    //Check if the referencing class is inside the list; if yes, load config
    $found=false;
    foreach($va_external as $varname=>$data) {
      if(!isset($data["class"])) {
        logger::warn("Class %s did not specify VA external class for %s",$childclass,$varname);
        continue;
      }
      if($data["class"]==$cc) {
        $found=true;
        break;
      }
    }
    if(!$found)
      logger::error("%s is not in the VA external list of %s",$cc,$childclass);
    $extdata=$va_external[$varname];

    //fetch the object IDs
    $q=new DB_Query("select * from `{$extdata['table']}` where `{$extdata['external_key']}`=?",$id);
    $entries=$q->numRows;
    if($entries==0) {
      logger::trace("Expected 1 or more entries, got %d",$entries);
      return $ret;
    }
    
    //get the data
    while($entry=$q->fetch()) {
      $ret[]=call_user_func("$childclass::getById",$entry[$extdata["own_key"]]);
    }
    $q->free();
    return $ret;
  }
}
