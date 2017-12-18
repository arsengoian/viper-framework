<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 03.11.2017
 * Time: 14:04
 */

namespace Viper\Core;

use Throwable;

interface StringCodeException extends Throwable
{
    public function getStringCode();
}