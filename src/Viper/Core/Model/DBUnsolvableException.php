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
    public $previous;

    public function __construct (StringCodeException $e)
    {
        $this -> previous = $e;

        $error = $e -> getMessage().", file: {$e->getFile()}, line: {$e->getLine()}";
        if ($sC = $e -> getStringCode())
            $error .= ", string code: $sC";

        parent::__construct(
            $error,
            $e -> getCode(),
            $e -> getPrevious()
        );
    }
}