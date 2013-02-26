<?php
namespace app\model\db;
use \mysqli;
use \Exception;

/**
 * :: DB Model ::
 * Maintains connection to database
 */

class Db extends \app\model\Model {
    private $dbSettings;
    private $dbOject;
    private $connected = false;
    private $resultObject;
    private $insertId = 0;
    
    public function __construct() {
        parent::__construct();
        $conf = $this->getConfig();
        if(isset($conf['db'])) {
            $this->dbSettings = $conf['db'];
            $this->connect();
        } else {
            throw new Exception('No DB settings found in config file!');
        }
    }
    
    /**
     * Connects to datbase using MySQLi
     * @throws Exception
     */
    public function connect() {
        if($this->connected == true) return true;
        $this->dbOject = new mysqli($this->dbSettings['server'], $this->dbSettings['username'], $this->dbSettings['password'], $this->dbSettings['database']);
        if($this->dbOject->connect_errno) {
            throw new Exception('Could not connect to database! Server returned: '. $this->dbOject->connect_errno);
        } else {
            $this->dbOject->set_charset('utf8');
            $this->connected = true;
        }
    }

    public function close() {
        $this->connected = false;
        $this->dbOject->close();
    }
    
    /**
     * Runs a query against the DB
     * 
     * @param type $q
     * @return mysqli_result
     */
    public function query($q = null) {
        if(!is_string($q)) return false;
        if(!$this->connected) return false;
        $this->resultObject = $this->dbOject->query($q);
        if($this->resultObject == false) {
            throw new Exception('Query Error: '. $this->dbOject->error. 'Query: '. $q);
            return false;
        }
        $this->insertId = $this->dbOject->insert_id;
        $this->close();
        return $this->resultObject;
    }
    
    /**
     * Inset some data iinto db
     * 
     * @param string $table
     * @param array $data
     * @return mysqli_result
     * @throws Exception
     */
    public function insert($table = null, $data = null) {
        if(!is_string($table))  throw new Exception('Table given is not a sting');
        if(!is_array($data))    throw new Exception('Data given is not an array!');

        $table = $this->escape($table, false);
        $data = $this->escape($data);
        $fields = "(";
        $values = "(";
        $lenth = count($data);
        $c = 1;
        
        foreach($data as $field => $value) {
            $fields .= "`". $field. "`";
            $values .= $value;
            
            if($c != $lenth) {
                $fields .= ", ";
                $values .= ", ";
                $c++;
            }
        }
        
        $fields .= ")";
        $values .= ")";
        
        $q = "INSERT INTO ". $table. " ". $fields. " VALUES ". $values;
        return ($this->query($q)) ? $this->insertId : false;
    }

    /**
     * Returns list where the query fits
     * 
     * @param string $table
     * @param array $query
     * @return mysqli_result
     * @throws Exception
     */
    public function get_where($table = null, $query = null, $fields = null, $sorting = array(), $limit = 0) {
        if(!is_string($table))  throw new Exception('Table given is not a sting');
        if(!is_array($query))    throw new Exception('Query given is not an array!');
        
        $table = $this->escape($table, false);
        $query = $this->whereValueList($query);

        if(is_array($fields) && count($fields) > 0) {
            $fields = $this->escape($fields, false);
            $fields = $this->getFieldList($fields);
        } else {
            $fields = "*";
        }

        $q = "SELECT $fields FROM ". $table. " WHERE ". $query. " ". $this->getSortingString($sorting). " ". $this->getLimit($limit);
        return $this->query($q);
    }
    
    /**
     * Runs a select command against the given table
     * 
     * @param string $table
     * @return mysqli_result
     * @throws Exception
     */
    public function get($table = null, $sorting = array(), $limit = 0) {
        if(!is_string($table))  throw new Exception('Table given is not a sting');
        $table = $this->escape($table, false);
        $q = "SELECT * FROM ". $table. " ". $this->getSortingString($sorting). " ". $this->getLimit($limit);
        return $this->query($q);
    }

    /**
     * Returns only specific fields from table query
     * @param string $table
     * @param string/array $field
     * @return mysqli_result
     * @throws Exception
     */
    public function get_only($table = null, $field = null, $sort = array(), $limit = 0) {
        if(!is_string($table)) throw new Exception('Table given is not a string');
        if(!is_string($field) && !is_array($field)) throw new Exception('Field given is not string or array');
        $table = $this->escape($table, false);
        $field = $this->escape($field, false);
        if(!is_array($field)) $field = array($field);
        $fildStr = $this->getFieldList($field);

        $q = "SELECT $fildStr FROM $table ". $this->getSortingString($sort). " ". $this->getLimit($limit);
        return $this->query($q);
    }
    
    /**
     * Delets a item in the table, where query is fullfilled
     * 
     * @param string $table
     * @param array $query
     * @return mysqli_result;
     * @throws Exception
     */
    public function delete($table = null, $query = null) {
        if(!is_string($table))  throw new Exception('Table given is not a sting');
        
        $table = $this->escape($table, false);
        $q = "DELETE FROM ". $table;
        
        if(isset($query) && is_array($query)) {
            $query = $this->setValueList($query);
            $q .= " WHERE ". $query;
        }

        return $this->query($q);
    }
    
    /**
     * Runs an update against DB
     * 
     * @param string $table
     * @param array $set
     * @param array $query
     * @return mysqli_result
     * @throws Exception
     */
    public function update($table = null, $set = null, $query = null) {
        if(!is_string($table))  throw new Exception('Table given is not a sting');
        if(!is_array($set))    throw new Exception('Set parameter given is not an array!');
        
        $table = $this->escape($table, false);
        $set = $this->setValueList($set);
        $q = "UPDATE ". $table. " SET ". $set;
        
        if(is_array($query)) {
            $queryString = $this->setValueList($query);
            $q .= " WHERE ". $queryString;
        }
        
        return $this->query($q);
    }
    
    /**
     * Escapes a single string or array
     * 
     * @param type $inp
     * @return boolean|string
     */
    public function escape($inp = null, $pling = true) {
        if(is_array($inp)) return $this->escapeArray($inp, $pling);
        if(!is_string($inp)) return $inp;
        if(!$this->connected) $this->connect();
        $ret = $this->dbOject->real_escape_string($inp);
        if($pling && !is_numeric($ret) ) {
            $ret = "'". $ret. "'";
        }
        
        return $ret;
    }
    
    /**
     * Escapes an array
     * 
     * @param type $list
     * @return type
     */
    private function escapeArray($list = null, $pling = true) {
        if(!is_array($list)) return $list;
        
        foreach($list as $k => $item) {
            if(is_array($item) && isset($item['value']) && isset($item['operator'])) {
                $item['value'] = $this->escape($item['value']);
                $list[$k] = $item;
                continue;
            }

            $list[$k] = $this->escape($item, $pling);
        }
        
        return $list;
    }
    
    /**
     * Makes an Array to a comma seperatet list
     * 
     * @param type $list
     * @return string
     */
    private function makeList($list = null) {
        if(!is_array($list)) return $list;
        $output = "";
        foreach($list as $item) {
            $output .= $this->escape($item). ", ";
        }
        
        return $output;
    }
    
    /**
     * 
     * @param type $inp
     */
    private function setValueList($inp = null) {
        if(!is_array($inp)) return $inp;

        $query = $this->escape($inp);
        $lenth = count($query);
        $c = 1;
        
        $queryString = "";
        
        foreach($query as $field => $value) {
            if(!is_numeric($field) && is_string($field)) $field = "`". $field. "`";
            $queryString .= $field. "=". $value;
            
            if($c != $lenth) {
                $queryString .= ", "; 
                $c++;
            }
        }
        
        return $queryString;
    }
    
    public function whereValueList($inp = null) {
        if(!is_array($inp)) return $inp;

        $query = $this->escape($inp);
        $lenth = count($query);
        $c = 1;

        $queryString = "";
        
        foreach($query as $field => $value) {
            $operator = "=";

            if(is_array($value)) {
                if(isset($value["value"]) && isset($value["operator"])) {
                    $operator = $value["operator"];
                    $value = $value["value"];
                } else {
                    continue;
                }
            }

            if(!is_numeric($field) && is_string($field) && count(explode(".", $field)) <= 1) $field = "`". $field. "`";
            $queryString .= $field. $operator. $value;
            
            if($c != $lenth) {
                $queryString .= " && "; 
                $c++;
            }
        }
        return $queryString;
    }

    /**
     * Returns a string of DB fields
     * @param array $field
     * @return string
     */
    private function getFieldList($field = array()) {
        $fildStr = "";
        $fieldLen = count($field);
        $c = 1;
        foreach($field as $f) {
            $fildStr .= $f;

            if($c != $fieldLen) {
                $fildStr .= ", ";
            }
        }

        return $fildStr;
    }

    /**
     * Returns sorting string
     * @param array $inp
     * @return string
     */
    private function getSortingString($inp = array()) {
        if(!isset($inp['field'])) {
            return "";
        }

        if(!isset($inp['order'])) {
            return "";
        }

        $field = $inp['field'];
        $order = $inp['order'];
        return "ORDER BY $field $order";
    }

    /**
     * Creates limit string
     * @param int $limit
     * @return string
     */
    private function getLimit($limit = 0) {
        if($limit > 0) {
            return "LIMIT ". $limit;
        } else {
            return "";
        }
    }
}

?>
