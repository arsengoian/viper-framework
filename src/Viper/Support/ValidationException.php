<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 21.06.2017
 * Time: 13:15
 */

namespace Viper\Support;

use Viper\Core\AppLogicException;
use Viper\Core\StringCodeException;

class ValidationException extends AppLogicException implements StringCodeException {
    public function getStringCode ()
    {
        return (string) $this -> getCode();
    }
}