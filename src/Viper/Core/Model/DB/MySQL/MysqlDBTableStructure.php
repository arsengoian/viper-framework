<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 02.11.2017
 * Time: 12:50
 */

namespace Viper\Core\Model\DB\MySQL;


use Viper\Core\Model\DB\Common\SQLTable;
use Viper\Core\Model\DB\MySQL\Types\DateType;
use Viper\Core\Model\DB\MySQL\Types\FloatType;
use Viper\Core\Model\DB\MySQL\Types\IntegerType;
use Viper\Core\Model\DB\MySQL\Types\StringType;
use Viper\Core\Model\DBField;
use Viper\Core\Model\DB\DBException;
use Viper\Support\ValidationException;


class MysqlDBTableStructure extends SQLTable
{

    public static function SQLTypeClasses (): array
    {
        return [
            StringType::class,
            IntegerType::class,
            FloatType::class,
            DateType::class
        ];
    }


    private function tableExists() {
        try {
            static::DB()->select($this->table, 'id', '1 LIMIT 1');
        } catch (DBException $e) {
            return FALSE;
        }
        return TRUE;
    }

    protected function idQuery (): string {
        return 'CHAR('.$this -> idSpace.') NOT NULL';
    }

    private function getQueryLines() {
        foreach ($this -> getColumns() as $column)
            $lines[] = $column.' '.$this -> getField($column) -> getQuery();
        $lines[] = 'CONSTRAINT pKey_ID PRIMARY KEY (id)';
        if (isset($this -> constraints['unique']))
            $lines[] = 'CONSTRAINT u_Unique UNIQUE ('.$this -> constraints['unique'].')';
        if (isset($this -> constraints['foreign_key']))
            foreach($this -> parseComplex($this -> constraints['foreignKeys']) as $k => $v)
                $lines[] = "CONSTRAINT fKey FOREIGN KEY ($k) REFERENCES $v";
        if (isset($this -> constraints['checks']))
            foreach($this -> parseComplex($this -> constraints['checks']) as $k => $v)
                $lines[] = "CHECK ($k$v)";
        return $lines;
    }

    protected function getCreateQuery(): string {
        $query = 'CREATE TABLE IF NOT EXISTS '.$this -> getTable();
        $query .= " ( \n".implode(",\n", $this -> getQueryLines())."\n) ";
        return $query;
    }




    protected function getTableStructure(): array {
        return static::DB() -> response('DESCRIBE '.$this -> getTable());
    }


    protected function checkColumnTypes() {
        foreach ($this -> getTableStructure() as $column) {
            if (in_array($field = $column['Field'], $this -> getColumns())) {
                $dbField = $this -> getField($field);
                if (
                    $column['Type'] != $dbField -> getType() ||
                    ($column['NULL'] === 'NO') == $dbField -> getNotNull() ||
                    $column['Default'] != $dbField -> getDefaultVal() ||
                    $column['Extra'] != $dbField -> getAutoIncrement() ? 'AUTO_INCREMENT' : ''
                ) $this -> changeColumn($field, $dbField);
            }
        }
    }


    protected function createColumn(DBField $field) {
        if ($this -> writeAllowed()) {
            $table = $this -> getTable();
            $col = $field -> getName();
            $q = "ALTER TABLE $table ADD $col ".$field -> getQuery();
            static::DB() -> response($q);
        }
    }

    protected function changeColumn(string $old, DBField $new) {
        if ($this -> overwriteAllowed()) {
            $table = $this -> getTable();
            $col = $new -> getName();
            $query = $new -> getQuery();
            static::DB() -> response("ALTER TABLE $table CHANGE COLUMN `$old` `$col` $query");
        }
    }

    protected function dropColumn(string $colname) {
        if ($this -> deleteAllowed()) {
            $table = $this -> getTable();
            static::DB() -> response("ALTER TABLE $table DROP COLUMN $colname");
        }
    }

    protected function validateChecks(string $field, $value) {
        // Process checks
        $constraints = [];
        if (isset($this -> constraints['checks'])) {
            foreach ($this->parseComplex($this->constraints['checks']) as $column => $check)
                if ($column == $field)
                    $constraints[] = $value . $check;

            if (count($constraints) == 0)
                return;

            $result = static::DB()-> response('SELECT ' . implode(' AND ', $constraints) . ' AS _constraint');

            if(!$result[0]['_constraint'])
                throw new ValidationException('Constraints not true for field '.$field.' with value '.$value);
        }
    }

    public function dropTable() {
        if ($this -> deleteAllowed())
            static::DB() -> response('DROP TABLE '.$this -> getTable());
    }


    protected function validateUnique(string $field, $value, string $className) {
        // Check unique
        if (isset($this -> constraints['unique'])) {
            foreach (explode(',', $this->constraints['unique']) as $column => $unique)
                if (trim($column) == $field)
                    /** @noinspection PhpUndefinedMethodInspection */
                    if ($className::getBy($field, $value))
                        throw new ValidationException("Field $field with value $value failed unique check; class: $className");
        }
    }


}