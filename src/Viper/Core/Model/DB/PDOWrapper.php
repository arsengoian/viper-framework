<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 21.01.2018
 * Time: 15:17
 */

namespace Viper\Core\Model\DB;


use PDO;
use PDOException;
use PDOStatement;

abstract class PDOWrapper extends PDO implements RDBMS
{

    public function preparedStatement(string $preparedQuery, array $values, string $functionName): ?array {
        try {
            $query = $this -> prepare($preparedQuery);
        } catch (PDOException $e) {
            throw new DBException("$functionName: wrong query, SQL says: ".$e -> getMessage(), $this -> ec($e));
        }

        $c = 0;
        foreach ($values as $value) {
            switch (gettype($value)) {
                case "NULL":
                    $type = PDO::PARAM_NULL;
                    break;
                case "integer":
                    $type = PDO::PARAM_INT;
                    break;
                case "boolean":
                    $type = PDO::PARAM_BOOL;
                    break;
                case "string":
                default:
                    $type = PDO::PARAM_STR;
                    break;
            }
            $query -> bindValue(++$c, $value, $type);
        }

        try {
            $query -> execute();
            return $this -> fetch($query);
        } catch (PDOException $e) {
            throw new DBException("DB::insert(): wrong query, SQL says: ".$e -> getMessage(), $this -> ec($e));
        }

    }


    protected function fetch(PDOStatement $result): ?array {
        if ($result -> columnCount() != 0)
            return $result -> fetchAll(PDO::FETCH_ASSOC);
        else return NULL;
    }

    public function response(string $q): ?array {
        try {
            return $this -> fetch($this -> query($q));
        } catch (PDOException $e) {
            throw new DBException("Wrong query, SQL says: ".
                $e -> getMessage().", query being: ".$q, $this -> ec($e));
        }
    }

    protected function eC(PDOException $e) {
        if($e -> getCode())
            return $e -> getCode();
        return $this -> errorInfo()[1];
    }
}