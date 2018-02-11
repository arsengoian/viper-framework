<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 05.11.2017
 * Time: 17:38
 */

namespace Viper\Core\Model;

use Viper\Core\Model\Cache\CachedModel;
use Viper\Support\Collection;
use Viper\Support\Libs\Util;

// Carefully think about security: data won't be stored or validated until the object is destroyed
// Is reasonable to use for read-only purposes when may be requested frequently

abstract class AsyncModel extends Model
{

    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct ()
    {
        $args = func_get_args();

        $models = Util::RAM('cached.models', function(): Collection {
            $cache = [];
            return new Collection($cache);
        });
        if (!$models -> contains(static::class))
            $models[] = static::class;
        call_user_func_array('parent::__construct', $args);

        if (count($args) > 1 && is_array($args[1]) && $id = $args[1]['id'])
            if (isset(self::cache()[$id]))
                return; // Model already cached, model is created from existing data

        static::cache()[parent::get('id')] = (new CachedModel($this));
    }

    private static function cache(): Collection {
        return Util::RAM('cached.models.'.static::class, function(): Collection {
            return new Collection();
        });
    }


    final public static function destruction ()
    {
        foreach(self::cache() as $id => $cachedItem) {
            if ($cachedItem -> isInsert()) {
                $fields = call_user_func_array([$cachedItem -> model(), 'fields'], static::columns());
                parent::add(Collection::fromArray($fields));
            }
            if ($cachedItem -> isDoomed()) {
                $cachedItem -> model() -> baseMurder();
                continue;
            }
            if (count($sets = $cachedItem -> getSets()) > 0) {
                $model = $cachedItem -> model();
                foreach ($sets as $offset => $value)
                    /** @noinspection PhpUndefinedMethodInspection */
                    $model -> baseSet($offset, $value);
            }
        }
    }


    public static function all(string $key = NULL, string $value = NULL): Collection
    {
        $all = parent::all($key, $value);
        foreach ($all as $model)
            if (!isset(self::cache()[$model['id']]))
                self::cache()[$model['id']] = new CachedModel($model);
        return $all;
    }

    public function get (string $fld)
    {
        return static::cache()[$this -> data['id']][$fld];
    }

    final function baseGet(string $fld) {
        return parent::get($fld);
    }

    public function set (string $fld, $value)
    {
        return static::cache()[parent::get('id')][$fld] = $value;
    }

    final function baseSet(string $fld, $value) {
        return parent::set($fld, $value);
    }

    public function murder ()
    {
        static::cache()[parent::get('id')] -> doom();
    }

    public function baseMurder() {
        return parent::murder();
    }


    public static function genocide (array $conditionarr)
    {
        parent::genocide($conditionarr);
    }

    public static function search(string $query, string $key) {
        // TODO search cache?
        return parent::search($query, $key);
    }

    public static function getById (string $id): ?Model
    {
        if (($cache = self::cache()) -> hasKey($id))
            return $cache[$id] -> model();
        return parent::getById($id);
    }

    public static function add (Collection $valuearr): ?Model
    {
        self::populateId($valuearr);
        $model = new static(['id' => $valuearr['id']], $valuearr -> toArray());
        $cached = new CachedModel($model, TRUE);
        self::cache()[$valuearr['id']] = $cached;
        return $model;
    }

}