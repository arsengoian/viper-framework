<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 31.10.2017
 * Time: 17:13
 */

    namespace Viper\Core\Model;

    use Viper\Support\MysqlDBException;
    use Viper\Support\Libs\Util;
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

        final public static function attempt(callable $do) {
            try {
                return $do();
            } catch (MysqlDBException|ValidationException|ModelException $e) {
                return self::modelConfig() -> testTable($e, $do);
            }
        }

        final public static function modelConfig(): ModelConfig {
            $cln = self::modelName();
            return Util::RAM('model__'.$cln, function () use($cln): ModelConfig {
                $cln = Util::explodeLast('\\', $cln);
                $data = Util::fromYaml(ROOT.'/models/'.$cln.'.yaml');
                if (!$data)
                    throw new ModelException('Model config not found');
                $config = new ModelConfig($data, $cln);
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