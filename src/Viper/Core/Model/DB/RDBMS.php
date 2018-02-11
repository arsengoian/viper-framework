<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 21.01.2018
 * Time: 0:19
 */

namespace Viper\Core\Model\DB;


interface RDBMS
{

    /**
     * @param string $q DB query
     * @return array|null
     */
    public function response(string $q): ?array;


    /**
     * @param string $preparedQuery String with question marks
     * @param array $values Question mark values
     * @param string $functionName Where the function is invoked from
     * @return array|null
     */
    public function preparedStatement(string $preparedQuery, array $values, string $functionName): ?array;


    /**
     * @param string $table
     * @param array $valuearr
     * @return mixed
     */
    public function insert(string $table, array $valuearr);


    /**
     * @param string $table
     * @param string $columns Comma-separated list
     * @param string $condition
     * @return array|null
     */
    public function select(string $table, string $columns = "*", string $condition = "1") : ?array;

    /**
     * @param string $table
     * @param string $columns Comma-separated list
     * @param array $valuearr Key-value search pairs
     * @param string $appendix Goes after WHERE clause
     * @return array|null
     */
    public function find(string $table, string $columns = "*", array $valuearr =[], string $appendix = '') : ?array;


    /**
     * @param string $table
     * @param string $columns Comma-separated list
     * @param string $condition Prepared statement
     * @param string $key
     * @param string $appendix Prepared statement. Goes after WHERE clause
     * @param string $appendixKey
     * @return array|null
     */
    public function search(string $table, string $columns = "*", string $condition = "1",
                           string $key = "", string $appendix = "", string $appendixKey = "") : ?array;

    /**
     * @param string $table
     * @param string $columns Comma-separated list
     * @return array|null
     */
    public function selectall(string $table, string $columns = '*') : ?array;


    /**
     * @param string $table
     * @param array $valuearr
     * @param string $condition
     * @param array $conditionArr
     * @return mixed
     */
    public function forceUpdate(string $table, array $valuearr, string $condition, array $conditionArr);

    /**
     * @param string $table
     * @param array $valuearr
     * @param string $condition
     * @return mixed
     */
    public function update(string $table, array $valuearr, string $condition);

    /**
     * @param string $table
     * @param array $valuearr
     * @param array $conditionArr
     * @return mixed
     */
    public function findUpdate(string $table, array $valuearr, array $conditionArr);


    /**
     * @param string $table
     * @param string $condition
     * @param array $values
     * @return mixed
     */
    public function forceDelete(string $table, string $condition, array $values);

    /**
     * @param string $table
     * @param string $condition
     * @return mixed
     */
    public function delete(string $table, string $condition);

    /**
     * @param string $table
     * @param array $conditionArr
     * @return mixed
     */
    public function findDelete(string $table, array $conditionArr);


    /**
     * @param string $name
     * @param array $columns Assoc array ['type', 'attributes']
     * @return mixed
     */
    public function createTable(string $name, array $columns);


}