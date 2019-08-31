<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 01.11.2017
 * Time: 19:11
 */

namespace Viper\Core\Model\DB\MySQL\Types;


use Viper\Core\Model\DB\Types\SizedType;
use Viper\Core\Model\ModelConfigException;

class FloatType extends SizedType
{
    const TYPES = [
      'FLOAT' => NULL,     // TODO support double float parameter: DOUBLE(size,d)
      'DOUBLE' => NULL,    // TODO move to separate DoubleType
      'DECIMAL' => NULL
    ];

    /**
     * Checks if the type is correspondent
     * @param $value
     */
    public function validate ($value): void
    {
        switch ($this -> sqlType) {
            case 'FLOAT':
            case 'DOUBLE':
            case 'DECIMAL':
                // Not implemented;
                break;
        }
    }

    /**
     * Converts the value to the needed PHP type
     * @param $value
     * @return mixed
     * @throws ModelConfigException
     */
    public function convert ($value): float
    {
        $this -> getValidator()::apply('number', $value);
        return (float) $value;
    }

    /**
     * Converts the value to the needed Mysql string
     * @param $value
     * @return mixed
     */
    public function reverseConvert ($value)
    {
        return $value;
    }
}