<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 01.11.2017
 * Time: 16:06
 */

namespace Viper\Core\Model\Types;


use Viper\Core\Routing\HttpException;
use Viper\Support\ValidationException;

class StringType extends SizedType
{

    const TYPES = [
        'CHAR' => 255,
        'VARCHAR' => 255,
        'TINYTEXT' => 255,
        'TEXT' => 65535,
        'BLOB' => 65535,
        'MEDIUMTEXT' => 16777215,
        'MEDIUMBLOB' => 16777215,
        'LONGTEXT' => 4294967295,
        'LONGBLOB' => 4294967295,
        'ENUM' => NULL, // TODO move to CollectionType, EnumType
        'SET' => NULL
    ];

    /**
     * Checks if the type is correspondent
     */
    public function validate ($value): void
    {
        switch ($this -> sqlType) {
            case 'ENUM':
            case 'SET':
                // Not implemented;
                break;

            default:
                $common = self::TYPES[$this -> sqlType];
                if (!$this -> size)
                    $this -> size = $common;
                else $this -> size = min($this -> size, $common);
                $this -> getValidator()::apply('strlen', $value, $this -> size);
        }
    }

    /**
     * Converts the value to the needed PHP type
     * @param $value
     * @return string
     * @throws ValidationException
     */
    public function convert ($value): string
    {
        if (!is_string($value))
            throw new ValidationException('Expected string');
        return (string) $value;
    }

    /**
     * Converts the value to the needed Mysql string
     * @param $value
     * @return mixed
     */
    public function reverseConvert ($value)
    {
        return (string) $value;
    }


}