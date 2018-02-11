<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 21.06.2017
 * Time: 12:55
 */

namespace Viper\Core\Model\DB\MySQL;

// Add joins
// Add system commands
// TODO refactor
// TODO improve error reporting
// TODO add other than equal conditions

// TODO use escape strings for everything
// TODO add option to make static requests wwithout Table parameter

use Viper\Core\Config;
use PDO;
use PDOException;
use PDOStatement;
use Viper\Core\Model\DB\Common\SQL;
use Viper\Core\Model\DB\DBException;

class MysqlDB extends SQL {

    function __construct () {
        try {
            parent::__construct(
                "mysql:host=".Config::get('DB_HOST').
                    ";dbname=".Config::get('DB_NAME').
                    ";charset=".Config::get('DB_CHARSET'),
                Config::get('DB_USER'),
                Config::get('DB_PASS')
            );
            $this -> setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            throw new DBException(
                "Database connection error, SQL says: ".
                $e -> getMessage(), $this -> ec($e));
        }
    }


    public function createTable(string $name, array $columns) {
        $rows = [];
        foreach ($columns as $attributes)
            $rows[] = $attributes[0].' '.$attributes[1];
        $query = 'CREATE TABLE IF NOT EXISTS '.$name;
        $query .= " ( \n".implode(",\n", $rows)."\n) ";
        return $this -> response($query);
    }



}

