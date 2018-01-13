<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 31.10.2017
 * Time: 17:24
 */

namespace Viper\Core\Model;

use Viper\Core\StringCodeException;
use Viper\Core\Model\Types\SizedType;
use Viper\Core\Model\Types\Type;
use Viper\Support\MysqlDB;
use Viper\Support\MysqlDBException;
use Viper\Core\Config;
use Viper\Support\Required;
use Viper\Support\ValidationException;
use Viper\Support\Validator;


// TODO test all constraints


class ModelConfig
{
    const MAX_RETRIES = 6;

    private $table;
    private $columns;
    private $idSpace;
    private $constraints;
    private $migrations;

    private $allowOverwrite;

    use MysqlDBTableStructure;


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

    private function parseComplex(array $a): array {
        $ret = [];
        foreach($a as $k => $v)
            foreach (explode(',', $k) as $key)
                $ret[trim($key)] = trim($v);
        return $ret;
    }

    private function dbFields(array $columns): array {
        $ret = [];
        $ret['id'] = new DBField('id', $this -> idQuery());
        foreach($columns as $column => $value)
            $ret[$column] = new DBField($column, $value);
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

    private function validateChecks(string $field, $value) {
        // Process checks
        $constraints = [];
        if (isset($this -> constraints['checks'])) {
            foreach ($this->parseComplex($this->constraints['checks']) as $column => $check)
                if ($column == $field)
                    $constraints[] = $value . $check;

            if (count($constraints) == 0)
                return;

            $result = MysqlDB::instance()->qResult('SELECT ' . implode(' AND ', $constraints) . ' AS _constraint');

            if(!$result[0]['_constraint'])
                throw new ValidationException('Constraints not true for field '.$field.' with value '.$value);
        }
    }

    private function validateUnique(string $field, $value, string $className) {
        // Check unique
        if (isset($this -> constraints['unique'])) {
            foreach (explode(',', $this->constraints['unique']) as $column => $unique)
                if (trim($column) == $field)
                    /** @noinspection PhpUndefinedMethodInspection */
                    if ($className::getBy($field, $value))
                        throw new ValidationException("Field $field with value $value failed unique check; class: $className");
        }
    }



    public function testTable(StringCodeException $e, callable $retry, int $num = 0) {
        try {
            switch (get_class($e)) {
                case MysqlDBException::class:
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
                    } catch (MysqlDBException $exc) {
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


    // TODO implement command-line interface daemon for these

    public function updateTableStructure() {
        if (!$this -> writeAllowed())
            throw new ModelConfigException('Writing to models currently forbidden');
        $this -> checkTableStructure();
    }

    public function dropTable() {
        if ($this -> deleteAllowed())
            MysqlDB::instance() -> response('DROP TABLE '.$this -> getTable());
    }

}