<?php

abstract class PGTable{
  var $attributes;
  protected static $conn = null;
  protected static $table_name = null;

  function __construct($agent_id = null){
    $this->attributes = array();
  }

  // private functions
  private function sprintf_key_value($key, $value){
    switch(gettype($value)){
      case 'integer':
      case 'double':
        return sprintf("`%s`=%d", mysql_real_escape_string($key), mysql_real_escape_string($value));
      case 'string': return sprintf("`%s`='%s'", mysql_real_escape_string($key), mysql_real_escape_string($value));
      case 'boolean': return sprintf("`%s`=%d", mysql_real_escape_string($key), ($value ? 1 : 0));
      case 'NULL': return sprintf("`%s`=null", mysql_real_escape_string($key));
      default: die("unknown type: $value - " . gettype($value));
    }
  }

  // static functions
  public static function initialize($user, $password, $database, $host = 'localhost'){
    // check version
    if(version_compare(PHP_VERSION, '5.3.0') < 0) die('Error: PGTable requires php version 5.3.0 or greater.');

    self::$conn = mysql_connect($host, $user, $password) or die('Could not connect: ' . mysql_error());
    mysql_select_db($database, self::$conn) or die('Could not select $db: ' . mysql_error());
  }

  public static function find_by_sql($sql){
    $result = mysql_query($sql, self::$conn) or die('Invalid query: ' . mysql_error());
    if($row = mysql_fetch_array($result, MYSQL_ASSOC)){
      $class = get_called_class();
      $ret = new $class();
      $ret->update_attributes($row);
      return $ret;
    } else {
      return null;
    }
  }

  public static function find_all_by_sql($sql){
    $result = mysql_query($sql, self::$conn) or die('Invalid query: ' . mysql_error());
    $ret = array();
    $class = get_called_class();
    while($row = mysql_fetch_array($result, MYSQL_ASSOC)){
      $record = new $class();
      $record->update_attributes($row);
      $ret[] = $record;
    }
    return $ret;
  }

  public static function find($id){
    $sql = "select * from " . static::$table_name . " where id = $id";
    return self::find_by_sql($sql);
  }

  public static function all(){
    $sql = "select * from " . static::$table_name;
    return self::find_all_by_sql($sql);
  }

  public static function found_rows(){
    $row = mysql_fetch_array(mysql_query('select found_rows()', self::$conn));
    return $row[0];
  }

  // find_by magic methods
  public static function __callStatic($name, $values){
    if(preg_match('/^find(_all)?_by_(.*)/i', $name, $m)){
      $mode = $m[1] == '_all' ? 'all' : '';
      $keys = preg_split('/_and_/i', $m[2]);

      $vals = array();
      foreach($keys as $key){
        $value = array_shift($values);
        $vals[] = self::sprintf_key_value($key, $value);
      }

      $sql = "select * from " . static::$table_name . " where " . implode(' and ', $vals);
      if($mode == 'all'){
        return self::find_all_by_sql($sql);
      } else {
        return self::find_by_sql($sql);
      }
    }
  }

  // instance functions
  public function update_attributes($attributes){
    foreach($attributes as $key => $value){
      $this->attributes[$key] = $value;
    }
  }

  public function save(){
    if(!isset($this->attributes['id'])){
      mysql_query("insert into " . static::$table_name . " (id) values (0)") or die('Invalid query: ' . mysql_error());
      $this->attributes['id'] = mysql_insert_id();
    }
    $vals = array();
    foreach($this->attributes as $key => $value){
      if($key == 'id') continue;
      $vals[] = $this->sprintf_key_value($key, $value);
    }
    $sql = "update " . static::$table_name . " set " . implode(', ', $vals). " where id=".$this->attributes['id'];
    if(!mysql_query($sql)){
      echo $sql . "\n";
      die('Invalid query: ' . mysql_error());
    }
  }

}

?>