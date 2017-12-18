<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 02.11.2017
 * Time: 14:30
 */

namespace Viper\Core\Model;


use Viper\Core\StringCodeException;
use Viper\Core\AppLogicError;

class DBUnsolvableException extends AppLogicError
{
    public function __construct (StringCodeException $e)
    {
        parent::__construct($e -> getMessage(), $e -> getCode(), $e -> getPrevious());
    }
}