<?php
/**
 * ::: DbInterface :::
 * Connection interface for DB connections
 */

namespace app\model\db;


interface DbInterface {

    /**
     * Connects to datbase using MySQLi
     * @throws Exception
     */
    public function connect();

    /**
     * Closes connection to DB
     */
    public function close();

    /**
     * Runs a query against the DB
     *
     * @param type $q
     * @return mysqli_result
     */
    public function query($q = null);

    /**
     * Inset some data iinto db
     *
     * @param string $table
     * @param array $data
     * @return mysqli_result
     * @throws Exception
     */
    public function insert($table = null, $data = null);

    /**
     * Returns list where the query fits
     *
     * @param string $table
     * @param array $query
     * @return mysqli_result
     * @throws Exception
     */
    public function get_where($table = null, $query = null, $fields = null, $sorting = array(), $limit = 0);

    /**
     * Runs a select command against the given table
     *
     * @param string $table
     * @return mysqli_result
     * @throws Exception
     */
    public function get($table = null, $sorting = array(), $limit = 0);

    /**
     * Returns only specific fields from table query
     * @param string $table
     * @param string/array $field
     * @return mysqli_result
     * @throws Exception
     */
    public function get_only($table = null, $field = null, $sort = array(), $limit = 0);

    /**
     * Delets a item in the table, where query is fullfilled
     *
     * @param string $table
     * @param array $query
     * @return mysqli_result;
     * @throws Exception
     */
    public function delete($table = null, $query = null);

    /**
     * Runs an update against DB
     *
     * @param string $table
     * @param array $set
     * @param array $query
     * @return mysqli_result
     * @throws Exception
     */
    public function update($table = null, $set = null, $query = null);
}