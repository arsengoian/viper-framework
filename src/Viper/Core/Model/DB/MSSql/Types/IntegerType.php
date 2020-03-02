<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 01.11.2017
 * Time: 19:11
 */

namespace Viper\Core\Model\DB\MSSql\Types;

// TODO Support all these:
//    bit	Integer that can be 0, 1, or NULL
//    tinyint	Allows whole numbers from 0 to 255	1 byte
//    smallint	Allows whole numbers between -32,768 and 32,767	2 bytes
//    int	Allows whole numbers between -2,147,483,648 and 2,147,483,647	4 bytes
//    bigint	Allows whole numbers between -9,223,372,036,854,775,808 and 9,223,372,036,854,775,807	8 bytes
//    decimal(p,s)	Fixed precision and scale numbers.
//    Allows numbers from -10^38 +1 to 10^38 –1.
//
//    The p parameter indicates the maximum total number of digits that can be stored (both to the left and to the right of the decimal point). p must be a value from 1 to 38. Default is 18.
//
//    The s parameter indicates the maximum number of digits stored to the right of the decimal point. s must be a value from 0 to p. Default value is 0
//
//    5-17 bytes
//    numeric(p,s)	Fixed precision and scale numbers.
//    Allows numbers from -10^38 +1 to 10^38 –1.
//
//    The p parameter indicates the maximum total number of digits that can be stored (both to the left and to the right of the decimal point). p must be a value from 1 to 38. Default is 18.
//
//    The s parameter indicates the maximum number of digits stored to the right of the decimal point. s must be a value from 0 to p. Default value is 0
//
//    5-17 bytes
//    smallmoney	Monetary data from -214,748.3648 to 214,748.3647	4 bytes
//    money	Monetary data from -922,337,203,685,477.5808 to 922,337,203,685,477.5807	8 bytes
//    float(n)	Floating precision number data from -1.79E + 308 to 1.79E + 308.
//    The n parameter indicates whether the field should hold 4 or 8 bytes. float(24) holds a 4-byte field and float(53) holds an 8-byte field. Default value of n is 53.
//
//    4 or 8 bytes
//    real	Floating precision number data from -3.40E + 38 to 3.40E + 38	4 bytes


use Viper\Core\Model\DB\Types\SizedType;

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
                $this -> getValidator()::apply('compare', (int) $value, - $this -> size);
                $this -> getValidator()::apply('compare', (int) $value, $this -> size - 1, FALSE);
        }
    }

    /**
     * Converts the value to the needed PHP type
     * @param $value
     * @return mixed
     * @throws \Viper\Core\Model\ModelConfigException
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