<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 21.06.2017
 * Time: 20:19
 */

namespace Viper\Core\Model;


use Viper\Core\AppLogicException;
use Viper\Core\StringCodeException;

class ModelException extends AppLogicException implements StringCodeException {

    public function getStringCode ()
    {
        return (string) $this -> getCode();
    }
}