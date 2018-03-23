<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 21.01.2018
 * Time: 15:25
 */

namespace Viper\Core\Model\DB\Common;

// TODO integrated SELECT TOP support for all selects (NULL default - do nothing)

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


    // TODO allow appendix keys for all
    public function search(string $table, string $columns = "*", string $condition = "1",
                           string $key = "", string $appendix = "", string $appendixkey = "") : ?array {
        $this -> normalizeSelectVals($columns, $condition);

        $key = substr($this -> quote("%".$key."%"), 1, -1);
        $appendixkey = substr($this -> quote($appendixkey), 1, -1);

        // Find number of keys
        $knum = substr_count($condition, "?");
        // Find number of appendix keys
        $aknum = substr_count($appendix, "?");

        $values = [];
        for ($i = 0; $i < $knum; $i++)
            $values[] = $key;
        for ($i = 0; $i < $aknum; $i++)
            $values[] = $appendixkey;

        return $this -> preparedStatement(
            "SELECT $columns FROM $table WHERE $condition $appendix",
            $values,
            'DB::search'
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

    public function findForceDelete (string $table, array $conditionArr) {
        $condition = $this -> preparedCondition($conditionArr);
        return $this -> forceDelete($table, $condition, array_values($conditionArr));
    }


    public function findDelete(string $table, array $conditionArr) {
        $this -> testOneFind($table, $conditionArr);
        $this -> findForceDelete($table, $conditionArr);
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

    public function selectall(string $table, string $columns = '*') : ?array {
        return $this -> response("SELECT $columns FROM $table");
    }
}