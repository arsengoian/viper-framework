<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 01.11.2017
 * Time: 19:23
 */

namespace Viper\Core\Model\DB\MSSql\Types;

//
//    TODO: Other data types:
//    Data type	Description
//    sql_variant	Stores up to 8,000 bytes of data of various data types, except text, ntext, and timestamp
//    uniqueidentifier	Stores a globally unique identifier (GUID)
//    xml	    Stores XML formatted data. Maximum 2GB
//    cursor	Stores a reference to a cursor used for database operations
//    table	    Stores a result-set for later processing
//
//    https://www.w3schools.com/sql/sql_datatypes.asp
//

use Viper\Core\Model\DB\Types\Type;
use Viper\Support\ValidationException;
use Jenssegers\Date\Date;
use InvalidArgumentException;

//    datetime	From January 1, 1753 to December 31, 9999 with an accuracy of 3.33 milliseconds	8 bytes
//    datetime2	From January 1, 0001 to December 31, 9999 with an accuracy of 100 nanoseconds	6-8 bytes
//    smalldatetime	From January 1, 1900 to June 6, 2079 with an accuracy of 1 minute	4 bytes
//    date	Store a date only. From January 1, 0001 to December 31, 9999	3 bytes
//    time	Store a time only to an accuracy of 100 nanoseconds	3-5 bytes
//    datetimeoffset	The same as datetime2 with the addition of a time zone offset	8-10 bytes
//    timestamp	Stores a unique number that gets updated every time a row gets created or modified. The timestamp value is based upon an internal clock and does not correspond to real time. Each table may have only one timestamp variable



class DateType extends Type
{

    const TYPES = [
      'DATE' => 'Y-m-d',
      'DATETIME' => 'Y-m-d H:i:s',
      'TIMESTAMP' => 'Y-m-d H:i:s',
      'TIME' => 'H:i:s',
      'YEAR' => 'Y',
    ];

    /**
     * Checks if the type is correspondent
     */
    public function validate ($value): void
    {
        $this -> convert($value);
    }

    /**
     * Converts the value to the needed PHP type
     * @param $value
     * @return mixed
     * @throws ValidationException
     */
    public function convert ($value): ?Date
    {
        if ($value === NULL)
            return NULL;
        try {
            return Date::createFromFormat(self::TYPES[$this -> sqlType], $value);
        } catch (InvalidArgumentException $e) {
            throw new ValidationException('Date invalid format');
        }
    }

    /**
     * Converts the value to the needed Mysql string
     * @param $value
     * @return mixed
     */
    public function reverseConvert ($value): ?string
    {
        if ($value === NULL)
            return NULL;
        return $this -> parseDate($value);
    }

    private function parseDate(Date $date): string {
        return $date -> format(self::TYPES[$this -> sqlType]);
    }

    /**
     * The list of SQL types supported
     * @return array
     */
    public function availableTypes (): array
    {
        return array_keys(self::TYPES);
    }
}