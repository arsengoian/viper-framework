<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 02.11.2017
 * Time: 12:50
 */

namespace Viper\Core\Model;


use Viper\Support\MysqlDB;
use Viper\Support\MysqlDBException;


trait MysqlDBTableStructure
{
    public function getField(string $field): DBField {
        if (isset($this -> columns[$field]))
            return $this -> columns[$field];
        else throw new ModelConfigException('Field '.$field.' not found');
    }

    private function tableExists() {
        try {
            MysqlDB::instance()->select($this->table, 'id', '1 LIMIT 1');
        } catch (MysqlDBException $e) {
            return FALSE;
        }
        return TRUE;
    }

    private function idQuery () {
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

    private function getCreateQuery(): string {
        $query = 'CREATE TABLE IF NOT EXISTS '.$this -> getTable();
        $query .= " ( \n".implode(",\n", $this -> getQueryLines())."\n) ";
        return $query;
    }

    private function createTable() {
        MysqlDB::instance() -> response($this -> getCreateQuery());
    }



    private function runMigrations() {
        // TODO test if data can't be migrated due to constraints/type differences
        foreach ($this -> migrations as $old => $new) {
            if (isset($this -> columns[$old]))
                throw new ModelConfigException('Warning! Column '.$old.' will be re-created with new request');
            if (!isset($this -> columns[$new]))
                throw new ModelConfigException('Can`t find column '.$new.' to migrate');
            if ($this -> fieldExists($new)) {
                return; // Migration already completed
            } elseif (!$this -> fieldExists($old)) {
                return; // Outdated migration
            } else {
                if (!$this -> overwriteAllowed())
                    throw new ModelConfigException('Migrations require overwriteAllowed rights');
                $this -> changeColumn($old, $this -> getField($new));
            }
        }
    }




    private function getTableStructure(): array {
        return MysqlDB::instance() -> response('DESCRIBE '.$this -> getTable());
    }

    private function fieldExists(string $field): bool {
        foreach ($this -> getTableStructure() as $column)
            if ($column['Field'] == $field)
                return TRUE;
        return FALSE;
    }

    private function createMissingColumns() {
        foreach($this -> getColumns() as $column)
            if (!$this -> fieldExists($column))
                $this -> createColumn($this -> getField($column));
    }

    private function checkColumnTypes() {
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

    private function sanitizeColumns() {
        foreach ($this -> getTableStructure() as $column) {
            if (!in_array($column['Field'], $this -> getColumns()))
                $this -> dropColumn($column);
        }
    }

    private function createColumn(DBField $field) {
        if ($this -> writeAllowed()) {
            $table = $this -> getTable();
            $col = $field -> getName();
            $q = "ALTER TABLE $table ADD $col ".$field -> getQuery();
            MysqlDB::instance() -> response($q);
        }
    }

    private function changeColumn(string $old, DBField $new) {
        if ($this -> overwriteAllowed()) {
            $table = $this -> getTable();
            $col = $new -> getName();
            $query = $new -> getQuery();
            MysqlDB::instance() -> response("ALTER TABLE $table CHANGE COLUMN `$old` `$col` $query");
        }
    }

    private function dropColumn(string $colname) {
        if ($this -> deleteAllowed()) {
            $table = $this -> getTable();
            MysqlDB::instance() -> response("ALTER TABLE $table DROP COLUMN $colname");
        }
    }


    // TODO if called upon error try various remedies in order to save time if the first one works
    private function checkTableStructure() {
        $this -> runMigrations();

        if ($this -> writeAllowed()) {
            $this -> createMissingColumns();
            // TODO ADD foreign, primary keys and constraints
        }

        // Check column structure
        if ($this -> overwriteAllowed()) {
            $this -> checkColumnTypes();
            // TODO edit column order so that it corresponds
            // TODO MODIFY keys and constraints
        }

        // Remove columns
        if ($this -> deleteAllowed()) {
            $this -> sanitizeColumns();
            // TODO delete keys and constraints
        }
    }

}