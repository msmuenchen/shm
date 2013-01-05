<?
//SH management - API - Meta

//Parent class for objects
class Meta {
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
  public static function getByReference($refclass, $id) {
    $ret=array();
    
    $childclass=get_called_class();
    logger::trace("Getting all %s objects which are referenced by the %s object with id=%d",$childclass,$refclass,$id);
    
    //Security
    if(!is_numeric($id))
      logger::error("Supplied id '%s', which is not a numeric!",$id);
    if(!class_exists($refclass))
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
      $ret[]=call_user_func("$childclass::getById",$entry[$refdata["own_key"]]);
    }
    $q->free();
    return $ret;
  }

}
