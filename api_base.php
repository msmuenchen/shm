<?
//SH management - API base

//Provide file logger, autoloader and API caller

//Logger
define("SH_ERROR",1);
define("SH_WARN",2);
define("SH_TRACE",3);

class logger {
  //this function is called to display the logentry to the user (if configured)
  private static $log_function=null;
  
  static function log($level,$msg) {
    global $config;
    
    $trace=debug_backtrace();
    array_shift($trace); //remove logger::log
    
    //remove the shorthands when they're in the stack
    if(isset($trace[1]) && ($trace[1]["function"]=="trace" || $trace[1]["function"]=="error" || $trace[1]["function"]=="warn") && $trace[1]["class"]=="logger" ) {
      array_shift($trace); //call-user-func
      array_shift($trace); //shorthand function
    }
    
    //check if we got called by a closure (which has no source info!)
    if($trace[0]["function"]=="{closure}") {
      array_shift($trace);
      $trace[0]["function"].=" (closure caller)";
    }
    
    if($trace[0]["function"]=="spl_autoload_call" || $trace[0]["function"]=="spl_autoload_call (closure caller)") {
      array_shift($trace);
      $acaller=$trace[1];
      if(!isset($acaller["class"]))
        $acaller["class"]="GLOBAL";
      $acaller=$acaller["class"]."::".$acaller["function"];
      $trace[0]["function"].=" (called by autoload from $acaller)";
    }
    
    $caller=$trace[0];
    
    //build information
    $function=$caller["function"];
    if (isset($caller["class"]))
      $class=$caller["class"];
    else
      $class="GLOBAL";
    $q_function="$class::$function";
    
    //log only the maximum level, but always log errors!
    if($level > $config["api"]["debug_level"] && $level>1)
      return;
    
    //log only wanted components
    if($config["api"]["debug_components"]!="*" && array_search($class,$config["api"]["debug_components"])!==false)
      return;
    
    //Replace the trace level with a string, if below level
    if($level > $config["api"]["debug_trace_level"])
      $trace=array(0=>array("function"=>"omitted due to setting!"));
    $trace_raw=base64_encode(serialize($trace));
    
    //check if $msg is a format-string
    if(func_num_args()>2) {
      $args=func_get_args();
      array_shift($args); //remove level
      
      $msg=call_user_func_array("sprintf",$args);
    }
    
    $logfile=$config["dir"]["log_basedir"]."api_trace.log";
    $fp=fopen($logfile,"a+");
    if($level <= $config["api"]["debug_trace_level"])
      $fmtstr="%s - %d in %s: %s\nTrace data: %s\n";
    else
      $fmtstr="%s - %d in %s: %s\n";
    fwrite($fp,sprintf($fmtstr,date("d.m.Y H:i:s"),$level,$q_function,$msg,$trace_raw));
    fclose($fp);
    
    if(is_callable(self::$log_function))
      call_user_func(self::$log_function,$level,$msg,$trace_raw,$q_function);
  }
  
  //Shorthands
  //trace
  static function trace($msg) {
    //preserve printf-data
    if(func_num_args()>1) {
      $args=func_get_args();
      array_unshift($args,SH_TRACE);
      call_user_func_array("logger::log",$args);
    } else
      call_user_func("logger::log",SH_TRACE,$msg);
  }
  //warn
  static function warn($msg) {
    //preserve printf-data
    if(func_num_args()>1) {
      $args=func_get_args();
      array_unshift($args,SH_WARN);
      call_user_func_array("logger::log",$args);
    } else
      call_user_func("logger::log",SH_WARN,$msg);
  }
  //error
  static function error($msg) {
    //preserve printf-data
    if(func_num_args()>1) {
      $args=func_get_args();
      array_unshift($args,SH_ERROR);
      call_user_func_array("logger::log",$args);
    } else
      call_user_func("logger::log",SH_ERROR,$msg);
  }
  
  //register a function to call when logs are encountered (to properly display them and not just writing to disk)
  //the callable must have exactly three parameters - the message, the base64_encoded raw trace array and the level
  static function register_handler($function) {
    if(!is_callable($function))
      self::log(SH_ERROR,"Supplied handler %s is not callable!",$function);
    else
      self::$log_function=$function;
  }
}

//API autloader
spl_autoload_register(function ($class) {
  global $config;
  $class = strtolower(preg_replace('/[^-a-zA-Z0-9_]/', '', $class));
  $file=$config["dir"]["api_basedir"]."api.$class.php";
  logger::trace("Autoloader trying to load class %s",$class);

  if(!is_file($file))
    logger::log(SH_ERROR,"Autloader failed to load '%s' (from file %s)",$class,$file);
  require_once($file);
});

?>