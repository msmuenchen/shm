<?
//SH management - API - MySQL DB interface

//Provides MySQL connectivity to other objects
class DB {
  private static $instance;
  private $link;
  
  //return link
  public static function get() {
    global $config;
    if(!self::$instance)
      self::$instance=new self($config["db"]["host"],$config["db"]["user"],$config["db"]["pass"],$config["db"]["db"]);
    return self::$instance;
  }
  
  private function __construct($host,$user,$pass,$db) {
    logger::trace("Opening link to %s:%s@%s/%s",$user,$pass,$host,$db);
    $this->link=new mysqli($host,$user,$pass,$db);
    if($this->link->connect_error)
      logger::error("MySQL connect failed: %s (%d)",$this->link->connect_error,$this->link->connect_errno);
  }
  
  public function getLink() {
    return $this->link;
  }
  
  public function getError() {
    return sprintf("%s (%d)",$this->link->error,$this->link->errno);
  }
  
  public static function esc($str) {
    return self::get()->link->real_escape_string($str);
  }
  
  //get an array of the columns in a table
  public static function getTableCols($table) {
    $q=new DB_Query("SHOW COLUMNS FROM `".self::esc($table)."`");
    if($q->numRows<1)
      logger::error("Table %s has no rows?!",$table);
    $cols=array();
    while($e=$q->fetch())
      $cols[]=$e["Field"];
    $q->free();
    return $cols;
  }
}
