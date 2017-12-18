<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 01.11.2017
 * Time: 19:11
 */

namespace Viper\Core\Model\Types;

// TODO Support UNSIGNED

class IntegerType extends SizedType
{
    const TYPES = [
      'TINYINT' => 128,
      'SMALLINT' => 32767,
      'MEDIUMINT' => 8388608,
      'INT' => 2147483647,
      'BITINT' => 9223372036854775807,
      'FLOAT' => NULL,     // TODO support double float parameter: DOUBLE(size,d)
      'DOUBLE' => NULL,    // TODO move to separate DoubleType
      'DECIMAL' => NULL
    ];

    /**
     * Checks if the type is correspondent
     */
    public function validate ($value): void
    {
        switch ($this -> sqlType) {
            case 'FLOAT':
            case 'DOUBLE':
            case 'DECIMAL':
                // Not implemented;
                break;

            default:
                $common = self::TYPES[$this -> sqlType];
                if (!$this -> size)
                    $this -> size = $common;
                else $this -> size = min($this -> size, $common);
                $this -> getValidator()::apply('compare', $value, - $this -> size);
                $this -> getValidator()::apply('compare', $value, $this -> size - 1, FALSE);
        }
    }

    /**
     * Converts the value to the needed PHP type
     * @param $value
     * @return mixed
     */
    public function convert ($value): int
    {
        $this -> getValidator()::apply('number', $value);
        return (int) $value;
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