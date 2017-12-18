<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 21.06.2017
 * Time: 20:01
 */

namespace Viper\Core\Model;

use Viper\Support\Collection;
use Viper\Support\MysqlDB;
use Viper\Support\MysqlDBException;

abstract class DBObject extends Collection {

    private $condition;
    private $offline = FALSE;

    abstract protected static function table() : string;
    abstract protected static function columns() : array;
    abstract protected static function idSpace() : int;

    function __construct(array $condition, array $local_data = NULL) {
        $this -> validateConstants();
        $this -> condition = $condition;
        if (!$local_data) {
            $data = MysqlDB::instance() -> find(static::table(), implode(',', static::columns()), $this -> condition);
            if (count($data) == 0)
                throw new ModelException('Not found');
            if (count($data) > 1)
                throw new ModelException('Select condition uncertain');
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

        MysqlDB::instance() -> findUpdate(static::table(), $args, $this -> condition);
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
        $this -> data = MysqlDB::instance() -> find(static::table(), implode(',', static::columns()), $this -> condition);
    }


    public function murder() {
        MysqlDB::instance() -> findDelete(static::table(), $this -> condition);
    }

    public static function genocide(array $conditionarr) {
        MysqlDB::instance() -> findDelete(static::table(), $conditionarr);
    }


    /**
     * @param Collection $valuearr
     * @return static
     */
    protected static function add(Collection $valuearr): ?Model {
        $vals = [];
        foreach (static::columns() as $header) {
            if (isset($valuearr[$header]))
                $vals[$header] = $valuearr[$header];
            else $vals[$header] = NULL;
        }
        MysqlDB::instance() -> insert(static::table(), $vals);
        try {
            foreach ($vals as $key => $val)
                if (!$val)
                    unset($vals[$key]);
            return static::construct($vals);
        } catch (MysqlDBException $e) {
            return NULL;
        }
    }

}