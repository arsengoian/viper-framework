<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 31.10.2017
 * Time: 17:24
 */

namespace Viper\Core\Model;

use Viper\Core\Model\DB\DBTableStructure;
use Viper\Core\StringCodeException;
use Viper\Core\Model\DB\Types\SizedType;
use Viper\Core\Model\DB\Types\Type;
use Viper\Core\Model\DB\DBException;
use Viper\Core\Config;
use Viper\Support\Required;
use Viper\Support\ValidationException;
use Viper\Support\Validator;


// TODO test all constraints


abstract class ModelConfig extends DBTableStructure
{
    const MAX_RETRIES = 6;

    protected $table;
    protected $columns;
    protected $idSpace;
    protected $constraints;
    protected $migrations;

    protected $allowOverwrite;


    public function __construct (array $arr, string $cln)
    {
        if (!isset($arr['fields']) || !is_array($arr['fields']))
            throw new ModelConfigException('No fields specified');

        $this -> table = $arr['table'] ?? $cln;
        $this -> idSpace = $arr['idSpace'] ?? 8;
        $this -> allowOverwrite = $arr['allowOverwrite'] ?? FALSE;

        $this -> constraints = $arr['constraints'] ?? [];
        $this -> migrations = $arr['migrations'] ?? [];

        $this -> columns = $this -> dbFields($this -> parseComplex($arr['fields']));

    }

    protected function parseComplex(array $a): array {
        $ret = [];
        foreach($a as $k => $v)
            foreach (explode(',', $k) as $key)
                $ret[trim($key)] = trim($v);
        return $ret;
    }

    private function dbFields(array $columns): array {
        $ret = [];
        $ret['id'] = new DBField('id', $this -> idQuery(), static::SQLTypeClasses());
        foreach($columns as $column => $value)
            $ret[$column] = new DBField($column, $value, static::SQLTypeClasses());
        return $ret;
    }



    public function getTable (): string
    {
        return (string) $this->table;
    }

    public function getColumns (): array
    {
        return array_keys($this->columns);
    }

    public function getIdSpace (): int
    {
        return (int) $this->idSpace;
    }




    public function writeAllowed() {
        return Config::get('DB_ALLOW_WRITE');
    }

    public function overwriteAllowed() {
        return $this -> writeAllowed() && Config::get('DB_ALLOW_OVERWRITE') &&
            ($this -> allowOverwrite === TRUE || Config::get('DEBUG') && $this -> allowOverwrite === 'debug');
    }

    public function deleteAllowed() {
        return Config::get('DB_ALLOW_DELETE') && $this -> overwriteAllowed();
    }




    public function typeControl(string $field, $value) {
        $fld = $this -> getField($field);
        $type = $fld -> getSQLType();
        $this -> setupTypeHandler($type, $fld);
        if (!$fld -> getNotNull() && $value === NULL)
            return NULL;
        return $type -> convert($value);
    }

    public function validateField(string $field, $value, string $className) {
        $fld = $this -> getField($field);
        $type = $fld -> getSQLType();
        $this -> setupTypeHandler($type, $fld);
        $value = $type -> reverseConvert($value);

        // Check type
        $type -> validate($value);

        if ($fld -> getNotNull() && $value == NULL)
            throw new ValidationException($field.' cannot be NULL');

        $this -> validateChecks($field, $value);
        $this -> validateUnique($field, $value, $className);

        return $value;
    }

    private function setupTypeHandler(Type $type, DBField $fld): void {
        if ($fld -> getNotNull())
            $validator = Validator::class;
        else $validator = Required::class;
        $type -> setValidator($validator);

        if ($fld instanceof SizedType)
            /** @noinspection PhpUndefinedMethodInspection */
            $type -> setSize($fld -> getSize());
    }





    public function testTable(StringCodeException $e, callable $retry, int $num = 0) {
        try {
            switch (get_class($e)) {
                case DBException::class:
                    if ($this -> analyzeMysql($e))
                        return $retry();
                    else throw new DBUnsolvableException($e);
                    break;

                case ValidationException::class:
                    throw new DBUnsolvableException($e);
                    break;

                case ModelException::class:
                    throw new DBUnsolvableException($e);
                    break;

                default:
                    throw new DBUnsolvableException($e);
            }
        } catch (StringCodeException $exc) {
            if ($num > self::MAX_RETRIES)
                throw new DBUnsolvableException($exc);
            return $this -> testTable($exc, $retry, ++$num);
        }
    }

    private function analyzeMysql(StringCodeException $e): bool {
        switch ($e -> getStringCode()) {

            case "42S22": // Unknown column
                if ($this -> writeAllowed()) {
                    $this -> runMigrations();
                    $this -> createMissingColumns();
                    return TRUE;
                } else return FALSE;

            // Wrong format/constraints

            case "42S02": // Table does not exist
                if ($this->writeAllowed()) {
                    try {
                        $this->createTable();
                    } catch (DBException $exc) {
                        if ($exc -> getCode() !== 0)
                            throw $exc;
                    }
                    return TRUE;
                } else return FALSE;

            default:
                if ($this -> overwriteAllowed()) {
                    $this->checkTableStructure();
                    return TRUE;
                } else return FALSE;
        }
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




    private function createTable() {
        static::DB() -> response($this -> getCreateQuery());
    }


    private function createMissingColumns() {
        foreach($this -> getColumns() as $column)
            if (!$this -> fieldExists($column))
                $this -> createColumn($this -> getField($column));
    }

    private function sanitizeColumns() {
        foreach ($this -> getTableStructure() as $column) {
            if (!in_array($column['Field'], $this -> getColumns()))
                $this -> dropColumn($column);
        }
    }
    // TODO implement command-line interface daemon for these

    public function updateTableStructure() {
        if (!$this -> writeAllowed())
            throw new ModelConfigException('Writing to models currently forbidden');
        $this -> checkTableStructure();
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