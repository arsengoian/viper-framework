<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 14.10.2018
 * Time: 23:39
 */

namespace Viper\Core;


class StringCodeLogicException extends AppLogicException implements StringCodeException
{

    public function getStringCode ()
    {
        return (string) $this -> getCode();
    }
}