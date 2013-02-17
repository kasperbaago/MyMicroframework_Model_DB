<?php
namespace app\model\db;
use \Exception;

/*
 * ::: DB MODEL :::
 * Adds a data processing layer to Models extending from it
 */

class DbModel extends \app\model\Model
{
    protected $table;
    protected $ID;
    private $filterVars = array("table", "ID", "db", "filterVars");

    public function __construct() {
        parent::__construct();
        $this->db = new \app\model\db\Db();
    }

    /**
     * Load object data from DB
     *
     * @param null $id
     * @return bool
     * @throws \Exception
     */
    public function load($id = null) {
        if(!isset($this->table)) throw new Exception('DB table is not set!');
        $id = (int) $id;
        $this->loadModel('db\db');

        $res = $this->db->get_where($this->table, array("ID" => $id));
        if($res->num_rows <= 0 || $res == false) return false;
        foreach($res->fetch_assoc() as $field => $value) {
            $this->$field = $value;
        }

        return true;
    }

    /**
     * Save object data to DB
     *
     * @return bool
     * @throws \Exception
     */
    public function save() {
        if(!isset($this->table)) throw new Exception('DB table is not set!');

        $d = $this->runFilter(get_object_vars($this));
        return (isset($this->ID)) ? $this->update($d) : $this->create($d);
    }

    /**
     * Update DB row
     *
     * @param $d
     * @return bool
     */
    private function update($d) {
        return ($this->db->update($this->table, $d, array("ID" => $this->ID)) != false) ? true : false;
    }

    /**
     * Create new row in table
     *
     * @param $d
     * @return bool
     */
    private function create($d) {
        $r = $this->db->insert($this->table, $d);
        if($r != false) {
            $this->ID = $r;
            return true;
        }

        return false;
    }

    /**
     * Run filtervars against keys
     * @param $data
     * @return array
     */
    private function runFilter($data) {
        $o = array();
        foreach($data as $k => $v) {
            if(!in_array($k, $this->filterVars)) {
                $o[$k] = $v;
            }
        }

        return $o;
    }

    /**
     * Delete option from table
     * @return bool
     */
    public function delete() {
        if(!isset($this->ID)) return false;
        return ($this->db->delete($this->table, array("ID" => $this->ID)) != false) ? true : false;
    }

    public function getID()
    {
        return $this->ID;
    }
}
