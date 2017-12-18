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
use Throwable;

class ModelAccessException extends ModelException {
    public function __construct (string $err)
    {
        parent::__construct($err.' due to settings. Check model config and general settings');
    }
}