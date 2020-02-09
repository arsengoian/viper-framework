<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 22.06.2017
 * Time: 15:43
 */

namespace Viper\Support;


class Required extends Validator
{
    public function check(string $key, ?string $varname = NULL) {
        if ($varname === NULL)
            $varname = $key;
        if (!isset($this -> valuearr[$key]) || $this -> valuearr[$key] === NULL)
            throw new ValidationException("$varname required");
        return $this -> valuearr[$key];
    }

    public function validate($key, ?string $varname, callable $test, string $errtext)
    {
        $this -> check($key);
        return parent::validate($key, $varname, $test, $errtext);
    }

}