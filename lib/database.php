<?php
/**
 * @file
 * This file contains general functions to access and query database.
 *
 */

function GetServerName() {
	if(isset($_SERVER['SERVER_NAME'])) {
		return $_SERVER['SERVER_NAME'];
	}elseif(isset($_SERVER['HTTP_HOST'])) {
		return $_SERVER['HTTP_HOST'];
	}else{
		die("Cannot find server address");
	}
}

$serverName = GetServerName();
//include prefix can be used to locate root level of directory tree.
$include_prefix = "";
while (!(is_file($include_prefix.'conf/config.inc.php') || is_file($include_prefix.'conf/'.$serverName.".config.inc.php"))) {
  $include_prefix .= "../";
}

require_once $include_prefix.'lib/gettext/gettext.inc';
include_once $include_prefix.'lib/common.functions.php';

if (is_file($include_prefix.'conf/'.$serverName.".config.inc.php")) {
	require_once $include_prefix.'conf/'.$serverName.".config.inc.php";
} else {
	require_once $include_prefix.'conf/config.inc.php';
}

include_once $include_prefix.'sql/upgrade_db.php';

//When adding new update function into upgrade_db.php change this number
//Also whenb you change the database, please add a database definition into
// 'lib/table-definition-cache' with the database version in the file name.
// You can get it by getting ext/restful/show_tables.php
define('DB_VERSION', 64); //Database version matching to upgrade functions.

$mysqlconnectionref = 0;

/**
 * Open database connection.
 */
function OpenConnection() {
  
  global $mysqlconnectionref;
  
  //connect to database
  $mysqlconnectionref = mysql_connect(DB_HOST, DB_USER, DB_PASSWORD);
  if(!$mysqlconnectionref) {
    die('Failed to connect to server: ' . mysql_error());
  }

  //select schema
  $db = mysql_select_db(DB_DATABASE);
  mysql_set_charset('utf8');

  if(!$db) {
    die("Unable to select database");
  }
  
  //check if database is up-to-date
  if (!isset($_SESSION['dbversion'])) {
    CheckDB();
    $_SESSION['dbversion'] = getDBVersion();
  }
}

/**
 * Closes database connection.
 */
function CloseConnection() {
  global $mysqlconnectionref;
  mysql_close($mysqlconnectionref);
  $mysqlconnectionref = 0;
}

/**
 * Checks if there is need to update database and execute upgrade functions.
 */
function CheckDB() {
  $installedDb = getDBVersion();
  for ($i = $installedDb; $i <= DB_VERSION; $i++) {
    $upgradeFunc = 'upgrade'.$i;
    //echo "calling ".$upgradeFunc."<br/>";
    $upgradeFunc();
    $query = sprintf("insert into uo_database (version, updated) values (%d, now())", $i + 1);
    runQuery($query);
  }
}

/**
 * Returns ultiorganizer database internal version number.
 *
 * @return integer version number
 */
function getDBVersion() {
  $query = "SELECT max(version) as version FROM uo_database";
  $result = mysql_query($query);
  if (!$result) {
    $query = "SELECT max(version) as version FROM pelik_database";
    $result = mysql_query($query);
  }
  if (!$result) return 0;
  if (!$row = mysql_fetch_assoc($result)) {
    return 0;
  } else return $row['version'];
}

/**
 * Executes sql query and  returns result as an mysql array.
 *
 * @param srting $query database query
 * @return Array of rows
 */
function DBQuery($query) {
  $result = mysql_query($query);
  if (!$result) { die('Invalid query: ("'.$query.'")'."<br/>\n" . mysql_error()); }
  return $result;
}

/**
 * Executes sql query and returns the ID generated the query.
 *
 * @param srting $query database query
 * @return id
 */
function DBQueryInsert($query) {
  $result = mysql_query($query);
  if (!$result) { die('Invalid query: ("'.$query.'")'."<br/>\n" . mysql_error()); }
  return mysql_insert_id();
}

/**
 * Executes sql query and  returns result as an value.
 *
 * @param srting $query database query
 * @return Value of first cell on first row
 */
function DBQueryToValue($query, $docasting=false) {
  $result = mysql_query($query);
  if (!$result) { die('Invalid query: ("'.$query.'")'."<br/>\n" . mysql_error()); }

  if(mysql_num_rows($result)){
    $row = mysql_fetch_row($result);
    if ($docasting) {
      $row = DBCastArray($result, $row);
    }
    return $row[0];
  }else{
    return -1;
  }
}

/**
 * Executes sql query and returns number of rows in resultset
 *
 * @param srting $query database query
 * @return number of rows
 */
function DBQueryRowCount($query) {
  $result = mysql_query($query);
  if (!$result) { die('Invalid query: ("'.$query.'")'."<br/>\n" . mysql_error()); }

  return mysql_num_rows($result);
}
/**
 * Executes sql query and copy returns to php array.
 *
 * @param srting $query database query
 * @return Array of rows
 */
function DBQueryToArray($query, $docasting=false) {
  $result = mysql_query($query);
  if (!$result) { die('Invalid query: ("'.$query.'")'."<br/>\n" . mysql_error()); }
  return DBResourceToArray($result,$docasting);
}


/**
 * Converts a db resource to an array
 *
 * @param $result The database resource returned from mysql_query
 * @return array of rows
 */
function DBResourceToArray($result, $docasting=false) {
  $retarray = array();
  while ($row = mysql_fetch_assoc($result)) {
    if ($docasting) {$row = DBCastArray($result, $row);}
    $retarray[] = $row;
  }
  return $retarray;
}

/**
 * Executes sql query and copy returns to php array of first row.
 *
 * @param srting $query database query
 * @return first row in array
 */
function DBQueryToRow($query, $docasting=false) {
  $result = mysql_query($query);
  if (!$result) { die('Invalid query: ("'.$query.'")'."<br/>\n" . mysql_error()); }
  $ret = mysql_fetch_assoc($result);
  if ($docasting && $ret) {$ret = DBCastArray($result, $ret);}
  return $ret;
}

/**
 * Copy mysql_associative array row to regular php array.
 *
 * @param $result return value of mysql_query
 * @param $row mysql_associative array row
 * @return php array of $row
 */
function DBCastArray($result, $row) {
  $ret = array();
  $i=0;
  foreach ($row as $key => $value) {
    if (mysql_field_type($result, $i) == "int") {
      $ret[$key] = (int)$value;
    } else {
      $ret[$key] = $value;
    }
    $i++;
  }
  return $ret;
}

if (function_exists('mysql_set_charset') === false) {
  /**
   * Sets the client character set.
   *
   * Note: This function requires MySQL 5.0.7 or later.
   *
   * @see http://www.php.net/mysql-set-charset
   * @param string $charset A valid character set name
   * @param resource $link_identifier The MySQL connection
   * @return TRUE on success or FALSE on failure
   */
  function mysql_set_charset($charset, $link_identifier = null){
    if ($link_identifier == null) {
      return mysql_query('SET CHARACTER SET "'.$charset.'"');
    } else {
      return mysql_query('SET CHARACTER SET "'.$charset.'"', $link_identifier);
    }
  }
}
?>
