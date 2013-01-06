<?
//SH management - actions

//Actions chain and group API calls to accomplish goals. API calls from clients (be it cli, confget or web-ui), always should use the Action classes as interfaces!

class Action {
  static public function getConfig($host,$key,$os) {
    $classname=sprintf("Config_%s",$os);
    logger::trace("Getting config %s for %s on %s from %s",$key,$host,$os,$classname);
    return $classname::getConfig($key,$host,$os);
  }
}