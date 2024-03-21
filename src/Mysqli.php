<?php
namespace booosta\mysqli;
\booosta\Framework::init_module('mysqli');

abstract class Mysqli extends \booosta\base\Module
{
#  use moduletrait_mysqli;

  protected $host;
  protected $db;
  protected $user;
  public $link;
  public $debug;

  public $DB_RDBMS = 'mysql';
  public $DB_AUTOVAL = 'INT AUTO_INCREMENT';
  public $DB_PRIMARYKEY = 'PRIMARY KEY';

  const NIL = '___NIL___';

  public function __construct($host = null, $user = null, $pass = null, $database = null)
  {
    #\booosta\debug("construct mysqli: host $host");
    if(!class_exists("\\mysqli")):
      $msg = 'class mysqli not found. Is mysqli supported by this PHP installation?';
      if(is_object($this->topobj && is_a($this->topobj, "\\booosta\\webapp\\webapp"))) $this->topobj->raise_error($msg);
      else print "$msg";
    endif;

    parent::__construct();

    #print_r($this->CONFIG);
    if($host === null) $host = $this->config('db_hostname');
    if($user === null) $user = $this->config('db_user');
    if($pass === null) $pass = $this->config('db_password');
    if($database === null) $database = $this->config('db_database');

    $this->host = $host;
    $this->db   = $database;
    $this->user = $user;

    if(is_object($host) && is_a($host, 'mysqli')) $this->link = $host;
    else $this->link = new \mysqli($this->host, $this->user, $pass, $this->db);
    #else { $this->link = new \mysqli($this->host, $this->user, $pass, $this->db); \booosta\debug("new connection"); }

    $this->link->set_charset('utf8');
    $this->link->autocommit(true);

    #$this->debug = uniqid();
    #print "debug: $this->debug<br>";
    #print "constructor $database<br>";
    #print $this->link->ping() ? 'PING' : 'NO PING' . '<br>';
  }


  # query()
  # performs a query and returns errorcode (0=OK, -1=not OK)
  # also logs all actions if $do_log is not set to false

  public function query($sql, $param = self::NIL, $do_log = true)
  {
    #\booosta\Framework::debug($sql);
    #\booosta\Framework::debug($param);
    if(is_bool($param)):
      $do_log = $param;
      $param = self::NIL;
    endif;

    if($this->config('LOG_MODE') && $do_log) $this->log_db($sql);
  
    $this->query_result($sql, $param);
    if($this->link->error):
      if($this->config('DEBUG_MODE')):
        $this->debug_sql($this->link->error, 'mysql.err');
        $this->debug_sql($sql, 'mysql.err');
      endif;
      return -1;
    endif;

    return 0;
  }

  public function multi_query($sql, $do_log = true)
  {
    if($this->config('LOG_MODE') && $do_log) $this->log_db($sql);

    $this->link->multi_query($sql);
    if($this->link->error):
      if($this->config('DEBUG_MODE')):
        $this->debug_sql($this->link->error, 'mysql.err');
        $this->debug_sql($sql, 'mysql.err');
      endif;
      return -1;
    endif;

    while(\mysqli_next_result($this->link));
    return 0;
  }

  public function prepare($sql, $do_log = true)
  {
    if($this->config('LOG_MODE') && $do_log) $this->log_db("prepare: $sql");

    $statement = $this->link->prepare($sql);
    if($statement === false) \booosta\Framework::debug('mysqli prepare ERROR: ' . mysqli_error($this->link));
    return $statement;
  }
  
  public function execute($statement)
  {
    $statement->execute();
    if($this->link->error):
      if($this->config('DEBUG_MODE')):
        $this->debug_sql($this->link->error, 'mysql.err');
        $this->debug_sql($sql, 'mysql.err');
      endif;
      return -1;
    endif;

    return 0;
  }

  public function get_error() { return $this->link->error; }

  # query_list()
  # performs a query and returns a list of values
  # representing all fields of the first record

  public function query_list($sql, $param = self::NIL, $numindex = false)
  {
    if(is_bool($param)):
      $numindex = $param;
      $param = self::NIL;
    endif;

    $assoc = $numindex ? MYSQLI_BOTH : MYSQLI_ASSOC;

    $res = $this->query_result($sql, $param);
    if($this->link->error && $this->config('DEBUG_MODE')):
      $this->debug_sql($this->link->error, 'mysql.err');
      $this->debug_sql($sql, 'mysql.err');
    endif;  

    if(!is_object($res)) return [];
    $ret = $res->fetch_array($assoc);
    return $ret;
  }
  

  # last_insert_id()
  # returns the id of the last inserted row

  public function last_insert_id($table_ressource = null) { 
  
    return $this->link->insert_id;
  }
  
  
  # query_value_set()
  # performs a query and returns a list of values
  # representing the first field of all records
  
  public function query_value_set($sql, $param = self::NIL)
  {
    $ret = [];
  
    $result = $this->query_result($sql, $param);
    if($this->link->error && $this->config('DEBUG_MODE')):
      $this->debug_sql($this->link->error, 'mysql.err');
      $this->debug_sql($sql, 'mysql.err');
    endif;  
  
    for($i = 0; $i < $result->num_rows; $i++)
      $ret[] = array_shift($result->fetch_row());
  
    return $ret;
  }
  
  
  # query_result()
  # returns a resultset of the given query
  
  public function query_result($sql, $param = self::NIL)
  {
    #if(!$this->link->ping()){ print 'MYSQL conn broken<br>';
    #\booosta\debug(unserialize($_SESSION['AUTH_USER']));}
    #\booosta\Framework::debug('param'); \booosta\Framework::debug($param);
    #print "debug1: $this->debug<br>";
    #print_r( $this->link );
    #print "$sql<br>";

    if($param !== self::NIL && (!is_array($param) || sizeof($param) > 0)):
      $types = '';
      $values = [];

      if(!is_array($param)) $param = [$param];
      foreach($param as $par):
        if(is_int($par)) $types .= 'i';
        elseif(is_float($par)) $types .= 'd';
        else $types .= 's';

        $values[] = $par;
      endforeach;
      #\booosta\debug("types: $types"); \booosta\debug($values);

      $stmt = $this->prepare($sql);
      if(!is_object($stmt)):
        \booosta\Framework::debug("{$_SERVER['PHP_SELF']} Error in prepare: $sql");
        return null;  // at errors in sql prepare() returns false
      endif;

      try {
        $stmt->bind_param($types, ...$values);
        $this->execute($stmt);
        $result = $stmt->get_result();
      } catch(\Error | \Exception $e) {
        $this->debug_sql($e->getMessage(), 'mysql.err');
        $this->debug_sql($sql, 'mysql.err');
        $this->debug_sql($types, 'mysql.err');
        $this->debug_sql($values, 'mysql.err');
      }
      #\booosta\debug($result);
    else:
      try {
        $result = $this->link->query($sql);
      } catch(\Exception $e) {
        $this->debug_sql($e->getMessage(), 'mysql.err');
        $this->debug_sql($this->link->error, 'mysql.err');
        $this->debug_sql($sql, 'mysql.err');
      }
    endif;

    if($this->link->error && $this->config('DEBUG_MODE')):
      $this->debug_sql($this->link->error, 'mysql.err');
      $this->debug_sql($sql, 'mysql.err');
    endif; 

    return $result;
  }
  
  
  # result_fetch_array()
  # fetches row of resultset into array
  
  public function result_fetch_array($result) 
  { 
    if(!is_object($result)) return 'ERROR: result non object';
    return $result->fetch_array(MYSQLI_ASSOC); 
  }
  
  
  # query_arrays()
  # returns array of resultarrays of given query
  
  public function query_arrays($sql, $param = self::NIL)
  {
    $ret = [];
    $result = $this->query_result($sql, $param);
    if($result) while($res = $result->fetch_array(MYSQLI_ASSOC)) $ret[] = $res;
    
    return $ret;
  }

  # query_index_array()
  # returns array with first query result as key and second as value

  public function query_index_array($sql, $param = self::NIL)
  {
    $ret = [];

    $result = $this->query_result($sql, $param);
    if(!is_object($result)) return false;
    while($res = $result->fetch_array()) $ret[$res[0]] = $res[1];
    return $ret;
  }

  # query_index_valueset
  # returns array which is indexed with third parameter
  
  public function query_index_valueset($sql, $valuefield = 'name', $idfield = 'id')
  {
    $ret = [];
  
    $result = $this->query_result($sql);
    while($res = $result->fetch_array()) $ret[$res[$idfield]] = $res[$valuefield];
  
    return $ret;
  }


  # query_value()
  # returns the first field of the first record of given query
  
  public function query_value($sql, $param = self::NIL)
  {
    $result = $this->query_result($sql, $param);
    if ($this->link->error && $this->config('DEBUG_MODE')):
      $this->debug_sql($this->link->error, 'mysql.err');
      $this->debug_sql($sql, 'mysql.err');
    endif;

    if ($result->num_rows == 0) return ''; 
    $res = $result->fetch_row();
    return array_shift($res);
  }
  
  
  # result_numrows()
  # returns the number of rows contained in a resultset
  
  public function result_numrows($result) { return $result->num_rows; }
  
  public function get_geo_coordinates($table, $id, $field = 'coordinates', $idfield = 'id')
  {
    $json = $this->query_value("select ST_AsGeoJson(`$field`) from `$table` where `$idfield`=?", $id);
    $arr = json_decode($json, true);
    return $arr['coordinates'];
  }
  
  public function set_geo_coordinates($latitude, $longitude, $table, $id, $field = 'coordinates', $idfield = 'id')
  {
    #\booosta\debug("lat: $latitude");
    $this->query("update `$table` set `$field`=ST_GeomFromText('POINT($latitude $longitude)', 4326) where `$idfield`=?", $id);
  }

  # result_fetch_row()
  # returns next fetched row from resultset
  
  public function result_fetch_row($result) { return $result->fetch_row(); }
  
  public function query_error() { return $this->get_error(); }
  
  public function transaction_start($tname = null) { $this->query('start transaction'); }
  public function transaction_commit($tname = null) { $this->query('commit'); }
  public function transaction_rollback($tname = null) { $this->query('rollback'); }
  
  public function escape($str) 
  {
    return $this->link->real_escape_string($str);
  }


  public function get_insert_statement($table, $fields, $values)
  {
    if(!is_array($fields)) return '';
    $result = '';

    $fieldlist = '';
    $varlist_stmt = '';
    $varlist_type = '';
    $varlist_bind = '';
    $loglist_bind = '';

    foreach($fields as $name=>$type):
      $fieldlist .= "`$name`, ";
      $varlist_stmt .= '?, ';
      $varlist_type .= $type;
      $varlist_bind .= "{$values[$name]}, ";
      $loglist_bind .= "{$values[$name]} . ', ' . ";
    endforeach;

    $fieldlist = substr($fieldlist, 0, -2);
    $varlist_stmt = substr($varlist_stmt, 0, -2);
    $varlist_bind = substr($varlist_bind, 0, -2);
    $loglist_bind = substr($loglist_bind, 0, -10);

    $sql = "insert into `$table` ($fieldlist) values ($varlist_stmt)";

    $result .= "\$statement = \$this->DB->prepare(\"$sql\", \$log);\n";
    $result .= "    if(\$this->config('LOG_MODE') && \$log) \$this->DB->log_db(\"bind_param: '$varlist_type', \" . $loglist_bind);\n";
    $result .= "    \$statement->bind_param('$varlist_type', $varlist_bind);\n";
    $result .= '    $res = $this->DB->execute($statement);';

    return $result;
  }

  public function get_update_statement($table, $fields, $values, $id_fields)
  {
    if(!is_array($fields) || !is_array($id_fields)) return '';
    $result = '';

    $updatelist_stmt = '';
    $rowidentifier_stmt = '';
    $updatelist_type = '';
    $rowidentifier_type = '';
    $updatelist_bind = '';
    $rowidentifier_bind = ', ';
    $loglist_bind = '';

    foreach($fields as $name=>$type):
      $updatelist_stmt .= "`$name`=?, ";
      $updatelist_type .= $type;
      $updatelist_bind .= "{$values[$name]}, ";
      $loglist_bind .= "{$values[$name]} . ', ' . ";
    endforeach;

    foreach($id_fields as $name=>$type):
      $rowidentifier_stmt .= "`$name`=? and ";
      $rowidentifier_type .= $type;
      $rowidentifier_bind .= "{$values[$name]}, ";
      $loglist_bind .= "{$values[$name]} . ', ' . ";
    endforeach;

    $updatelist_stmt = substr($updatelist_stmt, 0, -2);
    $updatelist_bind = substr($updatelist_bind, 0, -2);
    $rowidentifier_bind = substr($rowidentifier_bind, 0, -2);
    $rowidentifier_stmt = substr($rowidentifier_stmt, 0, -5);
    $loglist_bind = substr($loglist_bind, 0, -10);
 
    $sql = "update `$table` set $updatelist_stmt where $rowidentifier_stmt";

    $result .= "\$statement = \$this->DB->prepare(\"$sql\");\n";
    $result .= "    if(\$this->config('LOG_MODE') && \$log) \$this->DB->log_db(\"bind_param: '$updatelist_type$rowidentifier_type', \" . $loglist_bind);\n";
    $result .= "    \$statement->bind_param('$updatelist_type$rowidentifier_type', $updatelist_bind$rowidentifier_bind);\n";
    $result .= '    $res = $this->DB->execute($statement);';

    return $result;
  }

  public function DB_tablenames($database = null)
  {
    if($database === null) $database = $this->config('db_database');
    
    $ret = [];
    $liste = $this->query_result('show tables');
    while(list($tabname) = $liste->fetch_array()) $ret[] = $tabname;
    return $ret;
  }


  public function DB_fields($database, $table = null)
  {
    // workaround for default database:
    // if only one parameter given, then it is the table, not the database
    if($table === null):
      $table = $database;
      $database = $this->config('db_database');
    endif;

    $ret = [];

    $fields = $this->query_result("show full columns from `$table`");
    #\booosta\debug($this->get_error());
    while($f = $this->result_fetch_array($fields)) {
    #\booosta\debug("f: $f");
      if(is_string($f) && strstr($f, 'ERROR')):
        print "ERROR showing columns from $table: $f";
        return null;
      endif;

      $fieldname = $f['Field'];
      $autoval = false;
      if(strstr($f['Extra'], 'auto_increment') || $f['Comment'] == 'PK') $autoval = true;

      if($f['Key'] == 'PRI' || $f['Comment'] == 'PK') $primarykey = true; else $primarykey = false;

      $default = $f['Default'];
      if($autoval) $default = '';

      $type = preg_replace('/([^\(]+)\(.*/', '$1', $f['Type']);
      $param = preg_replace('/[^\(]+\((.*)\)/', '$1', $f['Type']);

      $null = $f['Null'] == 'YES';

      $ret[] = new \booosta\database\FW_tablefield($fieldname, $autoval, $primarykey, $default, $type, $param, $null);
    }

    return $ret;
  }


  public function DB_foreignkeys($tables = null, $database = null)
  {
    if($database === null) $database = $this->config('db_database');

    if($tables !== null):
      if(is_array($tables)) $tlist = implode("','", $tables);
      else $tlist = $tables;

      $clause = " and table_name in ('$tlist')";
    endif;

    return $this->query_arrays("select table_name, column_name, referenced_table_name, referenced_column_name
                                from information_schema.key_column_usage where table_schema='$database' and
                                referenced_table_schema='$database' $clause");
  }


  public function log_db($sql)
  {
    $query = addslashes($sql);

    #if($_SESSION['AUTH_USER']) $username = unserialize($_SESSION['AUTH_USER'])->get_username();
    if($_SESSION['AUTH_USER']) $user = unserialize($_SESSION['AUTH_USER']);

    if(is_object($user) && $user->is_valid()) $username = $user->get_username();
    else $username = '';

    $sql = "insert into log_db (user, time, ip, query, phpself) values ('$username', '" . date('Y-m-d H:i:s') . 
           "', '{$_SERVER['REMOTE_ADDR']}', '$query', '{$_SERVER['REQUEST_URI']}')";
    #\booosta\debug($sql);
    $this->query($sql, false);
  }


  # Adjust debug_sql public function here
  protected function debug_sql($text, $file = null) { \booosta\Framework::debug($_SERVER['PHP_SELF'] . "\n" . print_r($text, true)); }
}
