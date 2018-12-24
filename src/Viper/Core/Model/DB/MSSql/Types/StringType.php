<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 01.11.2017
 * Time: 16:06
 */

namespace Viper\Core\Model\DB\MSSql\Types;


use Viper\Core\Model\DB\Types\SizedType;
use Viper\Core\Routing\HttpException;
use Viper\Support\ValidationException;
// TODO support:
//    char(n)	Fixed width character string	8,000 characters	Defined width
//    varchar(n)	Variable width character string	8,000 characters	2 bytes + number of chars
//    varchar(max)	Variable width character string	1,073,741,824 characters	2 bytes + number of chars
//    text	Variable width character string	2GB of text data	4 bytes + number of chars
//    nchar	Fixed width Unicode string	4,000 characters	Defined width x 2
//    nvarchar	Variable width Unicode string	4,000 characters
//    nvarchar(max)	Variable width Unicode string	536,870,912 characters
//    ntext	Variable width Unicode string	2GB of text data
//    binary(n)	Fixed width binary string	8,000 bytes
//    varbinary	Variable width binary string	8,000 bytes
//    varbinary(max)	Variable width binary string	2GB
//    image	Variable width binary string	2GB

// TODO support VARCHAR(max)
class StringType extends SizedType
{

    const TYPES = [
        'CHAR' => 8000,
        'VARCHAR' => 8000,
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
        if ($value === NULL)
            return NULL;
        return (string) $value;
    }


}