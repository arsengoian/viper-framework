<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 21.01.2018
 * Time: 14:04
 */

namespace Viper\Core\Model\DB;


use Viper\Core\Config;
use Viper\Core\Model\DB\Common\SQLTable;
use Viper\Core\Model\DB\MSSql\MSSqlDB;
use Viper\Core\Model\DB\MSSql\MSSqlDBTableStructure;
use Viper\Core\Model\DB\MySQL\MysqlDB;
use Viper\Core\Model\DB\MySQL\MysqlDBTableStructure;
use Viper\Core\Model\ModelConfig;
use Viper\Support\Libs\Util;

class DB
{
    public static function getDBMS(): string {
        switch ($dialect = Config::get('DB_DIALECT')) {
            case 'MySQL':
                return  MysqlDB::class;
            case 'SQLServer':
                return MSSqlDB::class;
            default:
                throw new DBException('Invalid RDBMS '.$dialect);
        }
    }

    public static function instance(): RDBMS {
        return Util::RAM('db.'.Config::get('DB_DIALECT'), function (): RDBMS {
            $cname = self::getDBMS();
            return new $cname();
        });
    }

    public static function modelConfig(array $data, string $cln): ?ModelConfig {
        switch (self::getDBMS()) {
            case MysqlDB::class:
                return new MysqlDBTableStructure($data, $cln);
            case MSSqlDB::class:
                return new MSSqlDBTableStructure($data, $cln);
            default: return NULL;
        }
    }
}