<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 21.01.2018
 * Time: 14:24
 */

namespace Viper\Core\Model\DB\MSSql;


use PDO;
use PDOException;
use Viper\Core\Config;
use Viper\Core\Model\DB\Common\SQL;
use Viper\Core\Model\DB\DBException;

// TODO adapt

class MSSqlDB extends SQL
{
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