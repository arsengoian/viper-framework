<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 21.06.2017
 * Time: 20:01
 */

namespace Viper\Core\Model;

use Viper\Support\Collection;
use Viper\Core\Model\DB\DB;
use Viper\Core\Model\DB\DBException;

abstract class DBObject extends Collection {

    private $condition;
    private $offline = FALSE;

    abstract protected static function table() : string;
    abstract protected static function columns() : array;
    abstract protected static function queryColumns() : array;
    abstract protected static function idSpace() : int;

    private static function validateDataArr(&$data, array $condition) {
        if (count($data) == 0)
            throw new ModelException('Not found: object with '.json_encode($condition).' missing');
        if (count($data) > 1)
            throw new ModelException('Select condition uncertain');
    }

    function __construct(array $condition, array $local_data = NULL) {
        $this -> validateConstants();

        foreach ($condition as $key => $val)  // TODO make correct representation for date type
            if (!$val || is_object($val))
                unset($condition[$key]);
        $this -> condition = $condition;

        if (!$local_data) {
            $data = DB::instance() -> find(static::table(), implode(',', static::queryColumns()), $this -> condition);
            $this -> validateDataArr($data, $condition);
            parent::__construct($data[0]);
        } else {
            parent::__construct($local_data);
            $this -> offline = TRUE;
        }
    }

    final public static function construct() : DBObject {
        $argv = func_get_args();
        switch (func_num_args()) {
            case 1:
                if (gettype($argv[0]) != 'array')
                    throw new DBObjectException();
                return new static($argv[0]);
            case 2:
                if (gettype($argv[0]) != 'string' && gettype($argv[1]) != 'string')
                    throw new DBObjectException();
                return new static([$argv[0] => $argv[1]]);
            default:
                throw new DBObjectException();
        }
    }

    final public function validateConstants():void {
        if (static::table() === NULL || static::columns() === NULL)
            throw new ModelException('Not all constants set for proper class construction');
    }


    protected static function getBy(string $what, $value) {
        try {
            return static::construct($what, $value);
        } catch (\Exception $e) {
            return NULL;
        }
    }

    public static function search(string $query, string $key) {
        $data = DB::instance() -> search(static::table(),implode(',', static::queryColumns()), $query, $key);
        $condition = ['id', $data[0]['id']];
        self::validateDataArr($data, $condition);
        return new static($condition, $data[0]);
    }


    final protected function isOffline() {
        return $this -> offline;
    }



    public function get(string $fld) {
        return parent::offsetGet($fld);
    }

    // Override
    final public function offsetGet($fld) {
        return $this -> get($fld);
    }

    public function set(string $fld, $value) {
        $args = [];
        $args[$fld] = $value;
        $this -> validateConstants();
        if (!in_array($fld, static::columns()) && $this -> isOffline())
            throw new ModelException('Cannot update unknown column '.$fld.' created through offline object construction');

        DB::instance() -> findUpdate(static::table(), $args, $this -> condition);
        return parent::offsetSet($fld, $value);
    }

    // Override
    final public function offsetSet($fld, $value) {
        return $this -> set($fld, $value);
    }


    final public function fields(string ...$keys): array {
        $ret = [];
        foreach ($keys as $key)
            $ret[$key] = $this[$key];
        return $ret;
    }

    final public function fieldsSerializable(string ...$keys): array {
        $ret = [];
        foreach ($keys as $key)
            $ret[$key] = (string) $this[$key];
        return $ret;
    }


    // Overriding serialization
    public function serialize (): string
    {
        return serialize([
            'd' => $this -> data,
            'o' => $this -> offline,
            'c' => $this -> condition
        ]);
    }

    public function unserialize ($data)
    {
        $arr = unserialize($data);
        $this -> data = $arr['d'];
        $this -> offline = $arr['o'];
        $this -> condition = $arr['c'];
    }


    final public function updateFields() {
        $this -> data = DB::instance() -> find(static::table(), implode(',', static::queryColumns()), $this -> condition);
    }


    public function murder() {
        DB::instance() -> findDelete(static::table(), $this -> condition);
    }

    public static function genocide(array $conditionarr) {
        DB::instance() -> findForceDelete(static::table(), $conditionarr);
    }


    /**
     * @param Collection $valuearr
     * @return static
     * @throws DBObjectException
     */
    protected static function add(Collection $valuearr): ?Model {
        $vals = [];
        foreach (static::columns() as $header) {
            if (isset($valuearr[$header]))
                $vals[$header] = $valuearr[$header];
            else $vals[$header] = NULL;
        }
        DB::instance() -> insert(static::table(), $vals);
        try {
            foreach ($vals as $key => $val) { // TODO standardize this checks! This is crutchy
                if (!$val || is_object($val))
                    unset($vals[$key]);
                if (is_float($val) || (is_string($val) && $val == (string)(float)$val)) // string contains a float
                    unset($vals[$key]);
            }
            return static::construct($vals);
        } catch (DBException $e) {
            return NULL;
        }

    }

}