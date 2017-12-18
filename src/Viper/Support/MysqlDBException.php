<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 21.06.2017
 * Time: 12:59
 */

namespace Viper\Support;


use Viper\Core\StringCodeException;
use PDOException;
use Throwable;

class MysqlDBException extends PDOException implements StringCodeException
{
    private $PDOCode;

    public function __construct ($message = "", string $code = "", Throwable $previous = null)
    {
        $this -> PDOCode = ($code);
        parent::__construct($message, (int) $code, $previous);
    }

    public function getStringCode ()
    {
        return $this -> PDOCode;
    }
}