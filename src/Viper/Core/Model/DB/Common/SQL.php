<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 21.01.2018
 * Time: 15:25
 */

namespace Viper\Core\Model\DB\Common;

// TODO integrated SELECT TOP support

use Viper\Core\Model\DB\PDOWrapper;

abstract class SQL extends PDOWrapper
{
    public function select(string $table, string $columns = "*", string $condition = "1") : ?array {
        $this -> normalizeSelectVals($columns, $condition);
        return $this -> response("SELECT $columns FROM $table WHERE $condition");
    }

    public function find(string $table, string $columns = "*", array $valuearr =[], string $appendix = ''): ?array {
        $condition = $this -> preparedCondition($valuearr);
        $this -> normalizeSelectVals($columns, $condition);
        return $this -> preparedStatement(
            "SELECT $columns FROM $table WHERE $condition $appendix",
            array_values($valuearr),
            'DB::find()'
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
            throw new \Viper\Core\Model\DB\DBException("DB::search(): wrong query, SQL says: ".$query -> error, $this -> errno);

        // Fetch data
        return $query -> fetchAll();

    }


    public function forceUpdate(string $table, array $valuearr, string $condition, array $conditionarr) {

        if (count($valuearr) == 0)
            throw new \Viper\Core\Model\DB\DBException('DB::forceUpdate(): updating with no data');

        $prep_params = [];
        foreach ($valuearr as $k => $value)
            $prep_params[] = "$k=?";
        $prep_string = implode(", ", $prep_params);

        return $this -> preparedStatement(
            'UPDATE '.$table.' SET '.$prep_string.' WHERE '.$condition,
            array_merge(array_values($valuearr), array_values($conditionarr)),
            'DB::forceUpdate()'
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
            'DB::forceDelete()'
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
            throw new \Viper\Core\Model\DB\DBException("Too many lines. For an unsafe alternative, use DB::forceX()", 0);
    }

    public function selectall(string $table, string $columns = '*') : array {
        return $this -> response("SELECT $columns FROM $table");
    }
}