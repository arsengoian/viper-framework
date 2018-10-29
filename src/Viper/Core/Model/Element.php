<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 31.10.2017
 * Time: 17:13
 */

namespace Viper\Core\Model;

use Viper\Core\Model\DB\DB;
use Viper\Core\Model\DB\DBException;
use Viper\Support\Libs\Util;
use Viper\Support\Libs\UtilException;
use Viper\Support\ValidationException;

abstract class Element extends DBObject
{

    /** @noinspection PhpMissingParentConstructorInspection */
    public function __construct ()
    {
        call_user_func_array('parent::__construct', func_get_args());
    }

    // To override
    public static function modelName(): string {
        return static::class;
    }

    // To override
    public static function customYAML(): ?string {
        return NULL;
    }


    final public static function attempt(callable $do) {
        try {
            return $do();
        } catch (DBException|ValidationException|ModelException $e) {
            return self::modelConfig() -> testTable($e, $do);
        }
    }




    private static function customYamlFile(string $cln) {
        return root().DIRECTORY_SEPARATOR.'storage'.DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR.$cln.'.yaml';
    }

    final public static function checkCustomYAML() {
        if ($yaml = static::customYAML()) {
            $cln = Util::explodeLast('\\', static::modelName());
            $file = self::customYamlFile($cln);
            if (!file_exists($file) || file_get_contents($file) != $yaml)
                Util::put($file, $yaml);
        }
    }


    final public static function modelConfig(): ModelConfig {
        $cln = static::modelName();
        return Util::RAM('model__'.$cln, function () use($cln): ModelConfig {
            $cln = Util::explodeLast('\\', $cln);
            try {
                if (static::customYAML()) {
                    $data = Util::fromYaml(self::customYamlFile($cln));
                } else {
                    $data = Util::fromYaml(root().DIRECTORY_SEPARATOR.'models'.DIRECTORY_SEPARATOR.$cln.'.yaml');
                }
            } catch (UtilException $e) {
                throw new ModelException('Model config not found');
            }
            $config = DB::modelConfig($data, $cln);
            $GLOBALS['__model__'.$cln] = $config;
            return $config;
        });
    }


    final protected static function table() : string {
        return self::modelConfig() -> getTable();
    }

    final protected static function columns() : array {
        return array_merge(['id'], self::modelConfig() -> getColumns());
    }

    final protected static function idSpace() : int {
        return self::modelConfig() -> getIdSpace();
    }
}