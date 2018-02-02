<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 21.06.2017
 * Time: 20:18
 */

namespace Viper\Core\Model;

use Viper\Core\Model\DB\DB;
use Viper\Support\Collection;
use Viper\Support\IdGen;
use Viper\Support\IdGenException;

// TODO add queries
// TODO fix random order in all()

// TODO convert to semantically correct DBAL

abstract class Model extends Element {

    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct() {
        $args = func_get_args();
        static::attempt(function () use ($args) {
            call_user_func_array('parent::__construct', $args);
        });
    }

    final public static function fromCollection(Collection $dat): Collection {
        $objarr = new Collection();
        foreach ($dat as $local_data)
            $objarr[] = new static(['id' => $local_data['id']], $local_data);
        return $objarr;
    }
    
    
    // TODO TODO add more of these: search

    public static function all(string $key = NULL, string $value = NULL): Collection {
        return static::attempt(function () use ($key, $value): Collection {
            $db = DB::instance();
            if ($key || $value == 0)
                $dat = $db -> selectall(static::table());
            else $dat = $db -> find(static::table(), implode(',', static::columns()), [$key => $value]);
            $objarr = new Collection();
            foreach ($dat as $local_data) {
                $ld = new static(['id' => $local_data['id']], $local_data);
                $objarr[] = $ld;
            }
            return $objarr;
        });
    }



    public function get(string $fld) {
        return static::attempt(function() use ($fld) {
            return self::modelConfig() -> typeControl($fld, parent::get($fld));
        });
    }


    public function set(string $fld, $value) {
        if (!self::modelConfig() -> overwriteAllowed())
            throw new ModelAccessException('Cannot overwrite existing data');
        return static::attempt(function() use ($fld, $value) {
            $value = self::modelConfig() -> validateField($fld, $value, static::class);
            return parent::set($fld, $value);
        });
    }


    private static function safeguard() {
        if (!self::modelConfig() -> deleteAllowed())
            throw new ModelAccessException('Cannot erase');
    }

    public function murder () {
        self::safeguard();
        parent::murder();
    }

    public static function genocide (array $conditionarr) {
        self::safeguard();
        parent::genocide($conditionarr);
    }


    /**
     * @param string $id
     * @return static
     */
    public static function getById(string $id) : ?Model {
        return static::getBy('id', $id);
    }

    /**
     * @param string $query
     * @param string $key
     * @return static
     */
    public static function search(string $query, string $key) {
        return static::attempt(function() use ($query, $key) {
            return parent::search($query, $key);
        });
    }

    /**
     * @param string $key
     * @param $value
     * @return Model|null
     */
    public static function getBy(string $key, $value) : ?Model {
        return static::attempt(function () use ($key, $value): ?Model {
            return parent::getBy($key, $value);
        });
    }

    final public static function newId(int $n): string {
        return (new IdGen($n, static::table())) -> neu();
    }

    final public static function populateId(Collection &$valuearr): string {
        if (static::idSpace() === NULL)
            throw new ModelException('Id space not set');
        $id = self::newId(static::idSpace());
        $valuearr = Collection::fromArray(array_merge($valuearr -> toArray(),['id' => $id]));
        return $id;
    }


    /** @noinspection PhpDocSignatureInspection */
    /**
     * @param Collection $valuearr
     * @return static
     * @throws ModelAccessException, IdGenException
     */
    public static function add(Collection $valuearr): ?Model {
        if (!self::modelConfig() -> writeAllowed())
            throw new ModelAccessException('Can`t write to read-only database');
        return static::attempt(function () use ($valuearr): ?Model {
            if (!isset($valuearr['id']))
                self::populateId($valuearr);
            foreach ($valuearr as $key => $value)
                self::modelConfig() -> validateField($key, $value, static::class);
            if(static::getBy('id', $valuearr['id']))
                throw new IdGenException('Model with this ID already exists');
            return parent::add($valuearr);
        });
    }

}