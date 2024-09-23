<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 01.11.2017
 * Time: 19:23
 */

namespace Viper\Core\Model\DB\MySQL\Types;


use DateTime;
use Viper\Core\Model\DB\Types\Type;
use Viper\Support\ValidationException;
use Jenssegers\Date\Date;
use InvalidArgumentException;

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
            throw new ValidationException('Date invalid format  ' . json_encode(debug_backtrace(), JSON_PRETTY_PRINT));
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
        try {
            return $this->parseDate($value);
        } catch (\Throwable $e) {
            throw new \Exception(json_encode(debug_backtrace(), JSON_PRETTY_PRINT));
        }
    }
    
    private function parseDate($value): string 
    {
        $format = self::TYPES[$this -> sqlType];
        $date = DateTime::createFromFormat($format, $value);

        return $date->format($format);
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