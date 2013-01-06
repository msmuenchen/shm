<?
//SH management - API - CLI base

//provides argument parsing, CLI error handling and CLI output

//Log output / Error handling
function sh_cli_display_log($level,$msg,$trace,$caller) {
  global $config;
  if($level>$config["cli_api"]["debug_level"] && $level)
    return;
  echo "Log entry in $caller (lv $level): $msg\n";
  if($level <= $config["api"]["debug_trace_level"])
    echo "Trace data:$trace\n";
  if($level==SH_ERROR) {
    throw new Exception("Log entry in $caller (lv $level): $msg\n");
  }
}
logger::register_handler("sh_cli_display_log");

//CLI table output
require("Console/Table.php");
function sh_draw_text_table($table,$level=0) {
  if(is_object($table)) {
//    echo "LV $level - converting object to array\n";
    $table=array(array("id"=>get_class($table)),(array)$table);
  }
  //check if it's a 0-sized array
  if(is_array($table) && sizeof($table)==0) {
    $table=array("-" => "empty array");
  }
  $ctbl=new Console_Table();
  $headers=array();
  //stringify objects and arrays of objects as cell contents
  foreach($table as $rk=>$row) {
    $cellidx=0;
    //translate Objects to arrays
    if(is_object($row)) {
//      echo "LV $level - row $rk is an object: ".print_r($row,true)."\n";
      $ctbl->addRow(array("id"=>get_class($row)));
      $ctbl->addSeparator();
      $row=(array)$row;
      $table[$rk]=$row;
//      echo" LV $level - row is now: ".print_r($row,true)."\n";
    }
    //translate strings into arrays (useful when passed array("a","b","c"))
    if(!is_array($row)) {
      $row=array("key"=>$rk,"value"=>$row);
      $table[$rk]=$row;
    }
    foreach($row as $ck=>$cell) {
      $headers[$cellidx++]=$ck;
      if(!is_string($cell)) {
//        echo "LV $level - $rk:$ck has type ".gettype($cell)."\n";
      }
      if(is_object($cell)) {
//        echo "LV $level - Calling __toArray on cell $rk:$ck\n";
        $table[$rk][$ck]=array(array("id"=>get_class($cell)),(array)$cell);
      }
      if(is_array($cell)) {
//        echo "LV $level -de-array'ing $rk:$ck\n";
//        echo "LV $level - Passing ".print_r($cell,true)."\n";
        $table[$rk][$ck]=sh_draw_text_table($cell,$level+1);
      }
    }
    $ctbl->addRow($table[$rk]);
    if($rk!=sizeof($table)-1)
      $ctbl->addSeparator();
  }
  
  $ctbl->setHeaders($headers);
  return $ctbl->getTable();
}

function main($ac,$av) {
  if($ac<3) {
    echo "Usage: shapi module function [arguments]\n";
    return(1);
  }
  $api_args=array_slice($av,3);
  $api_argstr="'".implode("', ",$api_args)."'";

  $class=$av[1];
  $function=$av[2];
  logger::trace("Attempting to launch %s::%s (%s)",$av[1],$av[2],$api_argstr);

  class_exists($class);
  if($function=="__construct") {
    $inst=new ReflectionClass($class);
    $return=$inst->newInstanceArgs($api_args);
  } else {
    $return=call_user_func_array("$class::$function",$api_args);
  }

  if(is_array($return) || is_object($return))
    echo sh_draw_text_table($return);
  elseif($return===false)
    echo "Boolean FALSE";
  else
    echo $return;
  echo "\n";
  
  return 0;
}

try {
  $rc=main($argc,$argv);
  exit($rc);
} catch(Exception $e) {
  echo "Uncaught exception ".$e->getMessage()."\n";
}
