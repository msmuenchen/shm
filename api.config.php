<?
//SH management - API - Config

//Provides a way to get config files and override them in the DB
class Config extends Meta {
//Meta features unused at the moment
  //get the config file $key for the host with id $host
  //throws Exception_404 when host or config key could not be found
  //throws Exception_401 when authentication is needed
  public static function getConfig($key,$host,$os) {
    $childclass=get_called_class();
    logger::trace("Trying to get config key %s of host %s in OS %s, class %s",$key,$host,$os,$childclass);
    //check if we're supposed to load a DB entry. Hostnames must per RFC952 not contain underscores.
    if(substr($host,0,6)=="_dbid_") {
      $host_id=substr($host,6);
      if(!is_numeric($host_id))
        throw new Exception_404("Host ID not numeric!");
      //when this fails, Host::getById will throw an exception
      $host_obj=Host::getById($host_id);
      logger::trace("Using host id %d",$host_id);
    } else {
      //First, try to check if we can get a name-match to avoid expensive back-lookups with Host_Interface and Net_Address
      //Use LIKE to allow % wildcard
      $maybe_hosts=Host::getByProperty("name",$host,"LIKE");
      if(sizeof($maybe_hosts)>1)
        logger::error("More than 1 host for hostname %s",$host);
      elseif(sizeof($maybe_hosts)==1) {
        $host_id=$maybe_hosts[0]->id;
        logger::trace("Using host id %d",$host_id);
      } else {
        logger::trace("Trying reverse lookup from IP");
        
        //get the Net_Address
        $addr_obj=Net_Address::getByProperty("addr",$host);
        if(sizeof($addr_obj)!=1)
          throw new Exception_404("IP address not found in database");
        $addr_obj=$addr_obj[0];
        logger::trace("Using Net_Address %d",$addr_obj->id);
        
        //get the Host_Interface
        $if_obj=Host_Interface::getByChild("Net_Address",$addr_obj->id);
        if(sizeof($if_obj)>1) //more than 1 interface using this IP - may happen when IP is anycast
          throw new Exception_404("Supplied IP is used on multiple interfaces. Please use a unique IP");
        elseif(sizeof($if_obj)==0)
          throw new Exception_404("Supplied IP is not used");
        $if_obj=$if_obj[0];
        logger::trace("Using Host_Interface %d",$if_obj->id);
        
        //get the Host
        $host_obj=Host::getByChild("Host_Interface",$if_obj->id);
        if(sizeof($host_obj)>1) //more than 1 host using this interface - may be in future with Multiboot
          throw new Exception_404("Supplied Interface is used on more than one host. Please retry with hostname");
        elseif(sizeof($host_obj)==0)
          throw new Exception_404("Supplied Interface is not used");
        $host_obj=$host_obj[0];
        logger::trace("Using Host %d",$host_obj->id);
        
        print_r($host_obj);
        $host_id=$host_obj->id;
      }
    }
    $mname="get_$key";
    if(is_callable(array($childclass,$mname))) {
      logger::trace("Found the get-method in %s in %s or its parents",$mname,$childclass);
      return call_user_func(array($childclass,$mname),$host_id);
    } else {
      //check if one of the classes in the stack has a subclass for the wanted method
      $top=get_called_class();
      while($top!="Meta") {
        $cname=$top."_$key";
        logger::trace("looking at %s",$cname);
        if(sh_class_exists($cname)) {
          logger::trace("Found the get-class in %s",$cname);
          return call_user_func(array($cname,"getConfig"),$host_id);
          break;
        }
        $top=get_parent_class($top);
      }
      logger::warn("No handler found for config key %s, host %s, OS %s, cc %s",$key,$host,$os,$childclass);
      throw new Exception_404("Config endpoint not found");
    }
  }
}