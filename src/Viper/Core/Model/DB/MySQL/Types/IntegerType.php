<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 01.11.2017
 * Time: 19:11
 */

namespace Viper\Core\Model\DB\MySQL\Types;

// TODO Support UNSIGNED

use Viper\Core\Model\DB\Types\SizedType;
use Viper\Core\Model\ModelConfigException;

class IntegerType extends SizedType
{
    const TYPES = [
      'TINYINT' => 128,
      'SMALLINT' => 32767,
      'MEDIUMINT' => 8388608,
      'INT' => 2147483647,
      'BIGINT' => 9223372036854775807
    ];

    /**
     * Checks if the type is correspondent
     * @param $value
     * @throws ModelConfigException
     */
    public function validate ($value): void
    {
        $common = self::TYPES[$this -> sqlType];
        if (!$this -> size)
            $this -> size = $common;
        else $this -> size = min($this -> size, $common);
        $this -> getValidator()::apply('compare', (int) $value, - $this -> size);
        $this -> getValidator()::apply('compare', (int) $value, $this -> size - 1, FALSE);
    }

    /**
     * Converts the value to the needed PHP type
     * @param $value
     * @return mixed
     * @throws ModelConfigException
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