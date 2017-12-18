<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 21.06.2017
 * Time: 12:55
 */

namespace Viper\Support;

// Add joins
// Add system commands
// TODO refactor
// TODO improve error reporting
// TODO add other than equal conditions

// TODO use escape strings for everything
// TODO move globalizer to Util
// TODO move config to Support namespace to isolate
// TODO add option to make static requests wwithout Table parameter
// TODO te

use Viper\Core\Config;
use Viper\Support\Libs\Util;
use PDO;
use PDOException;
use PDOStatement;

class MysqlDB extends PDO {

    function __construct () {
        try {
            parent::__construct(
                "mysql:host=".Config::get('DB_HOST').
                    ";dbname=".Config::get('DB_NAME').
                    ";charset=".Config::get('DB_CHARSET'),
                Config::get('DB_USER'),
                Config::get('DB_PASS')
            );
            $this -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new MysqlDBException(
                "Database connection error, SQL says: ".
                $e -> getMessage(), $this -> ec($e));
        }
    }

    public static function instance() {
        return Util::RAM('db.mysql', function (): MysqlDB {
            return new MysqlDB();
        });
    }


    public function response(string $q): ?array {
        try {
            return $this -> fetch($this -> query($q));
        } catch (PDOException $e) {
            throw new MysqlDBException("Wrong query, SQL says: ".
                $e -> getMessage().", query being: ".$q, $this -> ec($e));
        }
    }
    
    private function eC(PDOException $e) {
        if($e -> getCode())
            return $e -> getCode();
        return $this -> errorInfo()[1];
    }


    public function preparedStatement(string $preparedQuery, array $values, string $functionName): ?array {

        try {
            $query = $this -> prepare($preparedQuery);
        } catch (PDOException $e) {
            throw new MysqlDBException("$functionName: wrong query, SQL says: ".$e -> getMessage(), $this -> ec($e));
        }

        $c = 0;
        foreach ($values as $value) {
            switch (gettype($value)) {
                case "NULL":
                    $type = PDO::PARAM_NULL;
                    break;
                case "integer":
                    $type = PDO::PARAM_INT;
                    break;
                case "boolean":
                    $type = PDO::PARAM_BOOL;
                    break;
                case "string":
                default:
                    $type = PDO::PARAM_STR;
                    break;
            }
            $query -> bindValue(++$c, $value, $type);
        }

        try {
            $query -> execute();
            return $this -> fetch($query);
        } catch (PDOException $e) {
            throw new MysqlDBException("MysqlDB::insert(): wrong query, SQL says: ".$e -> getMessage(), $this -> ec($e));
        }

    }

    private function fetch(PDOStatement $result) {
        if ($result -> columnCount() > 0)
            return $result -> fetchAll();
        else return NULL;
    }



    private function qMarks(int $count) {
        $retStr = '';
        for($i = 0; $i < $count; $i++) {
            if ($i > 0)
                $retStr .= ',';
            $retStr .= '?';
        }
        return $retStr;
    }



    public function insert(string $table, array $valuearr) {
        $cols = implode(',', array_keys($valuearr));
        $q_marks = $this -> qMarks(count($valuearr));
        $values = array_values($valuearr);
        return $this -> preparedStatement(
            'INSERT INTO '.$table.'('.$cols.') VALUES('.$q_marks.')',
            $values,
            'MysqlDB::insert()'
        );
    }



    public function select(string $table, string $columns = "*", string $condition = "1") : array {
        $this -> normalizeSelectVals($columns, $condition);
        return $this -> response("SELECT $columns FROM $table WHERE $condition");
    }

    public function find(string $table, string $columns = "*", array $valuearr =[], string $appendix = '') {
        $condition = $this -> preparedCondition($valuearr);
        $this -> normalizeSelectVals($columns, $condition);
        return $this -> preparedStatement(
            "SELECT $columns FROM $table WHERE $condition $appendix",
            array_values($valuearr),
            'MysqlDB::find()'
        );
    }

    private function normalizeSelectVals(string &$columns, string &$condition) {
        if (!$columns)
            $columns = "*";
        if (!$condition)
            $condition = 1;
    }

    private function preparedCondition($valuearr): string {
        $conds = [];
        foreach ($valuearr as $k => $value)
            $conds[] = "$k=?";
        return implode(' AND ', $conds);
    }




    // TODO refactor
    public function search(string $table, string $columns = "*", string $condition = "1",
                           string $key = "", string $appendix = "", string $appendixkey = "") : array {

        $key = "%{$this -> quote($key)}%";
        $appendixkey = $this -> quote($appendixkey);

        $args = [];
        $value_types = "";
        $values = [];

        // Find number of keys
        $knum = substr_count($condition, "?");
        if ($knum > 0) {
            switch (gettype($key)) {
                case "integer":
                    $t = "i";
                    break;
                case "string":
                default:
                    $t = "s";
                    break;
            }
            for ($i = 0; $i < $knum; $i++) {
                $values[] = $key;
                $value_types .= $t;
            }
        }

        // Find number of appendix keys
        $aknum = substr_count($appendix, "?");
        if ($aknum > 0) {
            switch (gettype($appendixkey)) {
                case "integer":
                    $t = "i";
                    break;
                case "string":
                default:
                    $t = "s";
                    break;
            }
            for ($i = 0; $i < $aknum; $i++) {
                $values[] = $appendixkey;
                $value_types .= $t;
            }
        }

        $args[] = &$value_types;
        for ($i = 0; $i < count($values); $i++)
            $args[] = &$values[$i];

        $query = $this -> prepare("SELECT $columns FROM $table WHERE $condition $appendix");
        call_user_func_array(array($query, "bindParam"), $args);
        $ret = $query -> execute();

        if ($query -> error)
            throw new MysqlDBException("MysqlDB::search(): wrong query, SQL says: ".$query -> error, $this -> errno);

        // Fetch data
        return $query -> fetchAll();

    }

    public function selectall(string $table, string $columns = '*') : array {
        return $this -> response("SELECT $columns FROM $table");
    }




    public function forceUpdate(string $table, array $valuearr, string $condition, array $conditionarr) {

        if (count($valuearr) == 0)
            throw new MysqlDBException('MysqlDB::forceUpdate(): updating with no data');

        $prep_params = [];
        foreach ($valuearr as $k => $value)
            $prep_params[] = "$k=?";
        $prep_string = implode(", ", $prep_params);

        return $this -> preparedStatement(
            'UPDATE '.$table.' SET '.$prep_string.' WHERE '.$condition,
            array_merge(array_values($valuearr), array_values($conditionarr)),
            'MysqlDB::forceUpdate()'
        );
    }

    public function update(string $table, array $valuearr, string $condition) { //
        $this -> testOne($table, $condition);
        return $this -> forceUpdate($table, $valuearr, $condition, []);
    }

    public function findUpdate(string $table, array $valuearr, array $conditionarr) {
        $condition = $this -> preparedCondition($conditionarr);
        $this -> testOneFind($table, $conditionarr);
        return $this -> forceUpdate($table, $valuearr, $condition, array_values($conditionarr));
    }



    public function forceDelete(string $table, string $condition, array $values) {
        return $this -> preparedStatement(
            "DELETE FROM $table WHERE $condition",
            $values,
            'MysqlDB::forceDelete()'
        );
    }

    public function delete(string $table, string $condition) {
        $this -> testOne($table, $condition);
        return $this -> forceDelete($table, $condition, []);
    }

    public function findDelete(string $table, array $conditionarr) {
        $condition = $this -> preparedCondition($conditionarr);
        $this -> testOneFind($table, $conditionarr);
        return $this -> forceDelete($table, $condition, array_values($conditionarr));
    }


    private function testOne(string $table, string $condition) {
        $this -> test($this -> select($table, '*', $condition));
    }

    private function testOneFind(string $table, array $conditionarr) {
        $this -> test($this -> find($table, '*', $conditionarr));
    }

    private function test(array $testarr) {
        if (count($testarr) > 1)
            throw new MysqlDBException("Too many lines. For an unsafe alternative, use MysqlDB::forceX()", 0);
    }

}

