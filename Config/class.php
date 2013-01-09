<?
//SH management - API - Config

//Provides a way to get config files and override them in the DB
class Config extends Meta {
//Meta features unused at the moment
  //get the config file $key for the machine with id $machine
  //throws Exception_404 when machine or config key could not be found
  //throws Exception_401 when authentication is needed
  public static function getConfig($key,$machine,$os) {
    $childclass=get_called_class();
    logger::trace("Trying to get config key %s of machine %s in OS %s, class %s",$key,$machine,$os,$childclass);
    //check if we're supposed to load a DB entry. Machinenames must per RFC952 not contain underscores.
    if(substr($machine,0,6)=="_dbid_") {
      $machine_id=substr($machine,6);
      if(!is_numeric($machine_id))
        throw new Exception_404("Machine ID not numeric!");
      //when this fails, Machine::getById will throw an exception
      $machine_obj=Machine::getById($machine_id);
      logger::trace("Using machine id %d",$machine_id);
    } else {
      //First, try to check if we can get a name-match to avoid expensive back-lookups with Machine_Interface and Net_Address
      //Use LIKE to allow % wildcard
      $maybe_machines=Machine::getByProperty("name",$machine,"LIKE");
      if(sizeof($maybe_machines)>1)
        logger::error("More than 1 machine for machinename %s",$machine);
      elseif(sizeof($maybe_machines)==1) {
        $machine_id=$maybe_machines[0]->id;
        logger::trace("Using machine id %d",$machine_id);
      } else {
        logger::trace("Trying reverse lookup from IP");
        
        //get the Net_Address
        $addr_obj=Net_Address::getByProperty("addr",$machine);
        if(sizeof($addr_obj)!=1)
          throw new Exception_404("IP address not found in database");
        $addr_obj=$addr_obj[0];
        logger::trace("Using Net_Address %d",$addr_obj->id);
        
        //get the Machine_Interface
        $if_obj=Machine_Interface::getByChild("Net_Address",$addr_obj->id);
        if(sizeof($if_obj)>1) //more than 1 interface using this IP - may happen when IP is anycast
          throw new Exception_404("Supplied IP is used on multiple interfaces. Please use a unique IP");
        elseif(sizeof($if_obj)==0)
          throw new Exception_404("Supplied IP is not used");
        $if_obj=$if_obj[0];
        logger::trace("Using Machine_Interface %d",$if_obj->id);
        
        //get the Machine
        $machine_obj=Machine::getByChild("Machine_Interface",$if_obj->id);
        if(sizeof($machine_obj)>1) //more than 1 machine using this interface - may be in future with Multiboot
          throw new Exception_404("Supplied Interface is used on more than one machine. Please retry with machinename");
        elseif(sizeof($machine_obj)==0)
          throw new Exception_404("Supplied Interface is not used");
        $machine_obj=$machine_obj[0];
        logger::trace("Using Machine %d",$machine_obj->id);
        
        print_r($machine_obj);
        $machine_id=$machine_obj->id;
      }
    }
    $mname="get_$key";
    if(is_callable(array($childclass,$mname))) {
      logger::trace("Found the get-method in %s in %s or its parents",$mname,$childclass);
      return call_user_func(array($childclass,$mname),$machine_id);
    } else {
      //check if one of the classes in the stack has a subclass for the wanted method
      $top=get_called_class();
      while($top!="Meta") {
        $cname=$top."_$key";
        logger::trace("looking at %s",$cname);
        if(sh_class_exists($cname)) {
          logger::trace("Found the get-class in %s",$cname);
          return call_user_func(array($cname,"getConfig"),$machine_id);
          break;
        }
        $top=get_parent_class($top);
      }
      logger::warn("No handler found for config key %s, machine %s, OS %s, cc %s",$key,$machine,$os,$childclass);
      throw new Exception_404("Config endpoint not found");
    }
  }
}