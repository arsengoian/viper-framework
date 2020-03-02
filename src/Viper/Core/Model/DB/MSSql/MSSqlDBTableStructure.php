<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 21.01.2018
 * Time: 15:13
 */

namespace Viper\Core\Model\DB\MSSql;


use Viper\Core\Model\DB\Common\SQLTable;
use Viper\Core\Model\DB\DBException;
use Viper\Core\Model\DB\MSSql\Types\DateType;
use Viper\Core\Model\DB\MSSql\Types\IntegerType;
use Viper\Core\Model\DB\MSSql\Types\StringType;
use Viper\Core\Model\DBField;
use Viper\Core\Routing\Loggable;
use Viper\Core\StringCodeException;
use Viper\Support\ValidationException;


// TODO adapt

class MSSqlDBTableStructure extends SQLTable
{
    public static function SQLTypeClasses (): array
    {
        return [
            StringType::class,
            IntegerType::class,
            DateType::class
        ];
    }


    private function tableExists() {
        try {
            static::DB()->select($this->table, 'TOP 1 id', '1');
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
        $table = str_replace('-', '_', $this -> getTable());
        $lines[] = "CONSTRAINT pKey_ID_$table PRIMARY KEY (id)";
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
        $query = "IF OBJECT_ID(N'".$this->getTable()."', N'U') IS NULL".
            " BEGIN CREATE TABLE ".$this->getTable().' ';
        $query .= " ( \n".implode(",\n", $this -> getQueryLines())."\n); ";
        $query .= 'END;';
        return $query;
    }




    protected function getTableStructure(): array {
        $response = static::DB() -> response('EXEC sp_columns '.$this -> getTable());
        $struct = [];
        foreach ($response as $row)
            $struct[] = [
                'Field' => $row['COLUMN_NAME'],
                'Type' => $row['LENGTH'] ? $row['TYPE_NAME'] : $row['LENGTH'],
                'NULL' => $row['IS_NULLABLE'],
                'Key' => '', // Pointless, but may cause future compatibility problems
                'Default' => $row['COLUMN_DEF'],
                'Extra' => '', // Not supported...?
            ];
        return $struct;
    }


    protected function checkColumnTypes() {
        foreach ($this -> getTableStructure() as $column) {
            if (in_array($field = $column['Field'], $this -> getColumns())) {
                $dbField = $this -> getField($field);
                if (
                $column['Type'] != $dbField -> getType() ||
                ($column['NULL'] === 'NO') == $dbField -> getNotNull() ||
                $column['Default'] != $dbField -> getDefaultVal() ||
                $column['Extra'] != $dbField -> getAutoIncrement() ? 'IDENTITY(1,1)' : ''
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
            if ($new->getType() == 'TIMESTAMP') {
                Loggable::log('notices', 'Cannot update TIMESTAMP column in MSSQL. Skipping');
                return;
            }

            $table = $this -> getTable();
            $col = $new -> getName();
            $query = $new -> getQuery();

            $table = $this->getTable();
            if ($col != $old)
                static::DB() -> response("EXEC sp_rename '$table.$old', '$new', 'COLUMN'");
            static::DB() -> response("ALTER TABLE $table ALTER COLUMN $col $query");
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

    protected function analyzeCode(StringCodeException $e): bool {
        switch ($e -> getStringCode()) {

            case "42S22":
            case "42S02":
                return $this -> tableMissing() && $this -> unknownColumn();

            // TODO which code do missing columns use then???

            default:
                return $this -> unknownError();
        }
    }

}