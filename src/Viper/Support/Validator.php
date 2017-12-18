<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 21.06.2017
 * Time: 13:14
 */

namespace Viper\Support;


use Viper\Core\AppLogicException;
use Viper\Support\Libs\Location;
use ReflectionFunction;

class Validator
{

    const REGEXP_HEX = '/^[0-9A-Fa-f]+$/';
    const REGEXP_PHONE_NUM = '/^[0-9\+\- ]+$/';
    const REGEXP_EMAIL = '/^(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?)){255,})(?!(?:(?:\\x22?\\x5C[\\x00-\\x7E]\\x22?)|(?:\\x22?[^\\x5C\\x22]\\x22?)){65,}@)(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|(?:\\x5C[\\x00-\\x7F]))*\\x22))(?:\\.(?:(?:[\\x21\\x23-\\x27\\x2A\\x2B\\x2D\\x2F-\\x39\\x3D\\x3F\\x5E-\\x7E]+)|(?:\\x22(?:[\\x01-\\x08\\x0B\\x0C\\x0E-\\x1F\\x21\\x23-\\x5B\\x5D-\\x7F]|(?:\\x5C[\\x00-\\x7F]))*\\x22)))*@(?:(?:(?!.*[^.]{64,})(?:(?:(?:xn--)?[a-z0-9]+(?:-+[a-z0-9]+)*\\.){1,126}){1,}(?:(?:[a-z][a-z0-9]*)|(?:(?:xn--)[a-z0-9]+))(?:-+[a-z0-9]+)*)|(?:\\[(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){7})|(?:(?!(?:.*[a-f0-9][:\\]]){7,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,5})?)))|(?:(?:IPv6:(?:(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){5}:)|(?:(?!(?:.*[a-f0-9]:){5,})(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3})?::(?:[a-f0-9]{1,4}(?::[a-f0-9]{1,4}){0,3}:)?)))?(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))(?:\\.(?:(?:25[0-5])|(?:2[0-4][0-9])|(?:1[0-9]{2})|(?:[1-9]?[0-9]))){3}))\\]))$/iD';
    const REGEXP_LOCATION = '/^(-?[1-8]?\d(?:\.\d{1,6})?|90(?:\.0{1,6})?),(-?(?:1[0-7]|[1-9])?\d(?:\.\d{1,6})?|180(?:\.0{1,6})?)$/';
    const REGEXP_GOOGLE_MAPS_LOCATION_ID = '/^[0-9A-Za-z\-\_]+$/';
    const REGEXP_ONESIGNAL_PLAYER_ID = '/[a-fA-F0-9]{8}(\-[a-fA-F0-9]{4}){3}\-[a-fA-F0-9]{12}/';

    protected $valuearr;

    function __construct(Collection $valuearr)
    {
        $this -> valuearr = $valuearr;
    }

    public static function apply($method, $value, ...$args) {
        $arr = ['key' => $value];
        return call_user_func_array(
            [new Validator(new Collection($arr)), $method],
            array_merge(['key'], $args)
        );
    }



    public function lock (...$vars) {
        foreach ($vars as $var)
            if (isset($this -> valuearr[$var]))
                throw new ValidationException("$var cannot be set on initiation");
    }




    public function id (string $key, int $len, ?string $varname = NULL): ?string {
        $this -> validate($key, $varname, function($key) {
            return preg_match(self::REGEXP_HEX, $key);
        }, "Id must only contain hexidecimal characters");
        return $this -> validate($key, $varname, function($key) use ($len){
            return strlen($key) == $len;
        }, "Id must be $len characters long, but it is ".strlen($key));
    }

    public function number (string $key, ?string $varname = NULL): ?string {
        return $this -> validate($key, $varname, function($key) {
            return is_numeric($key);
        },"%n must be a number");
    }

    public function compare (string $key, int $than, bool $more = TRUE, ?string $varname = NULL): ?string {
        return $this -> validate($key, $varname, function($key) use ($than, $more) {
            return $more ? $key > $than: $key < $than;
        },"%n must be ".($more ? 'more' : 'less')." than ".$than);
    }

    public function strlen (string $key, int $maxlen, int $minlen = 0, ?string $varname = NULL): ?string {
        return $this -> validate($key, $varname, function($key) use ($maxlen, $minlen) {
            return mb_strlen($key) <= $maxlen && mb_strlen($key) >= $minlen;
        }, "%n must be longer than $minlen and shorter than $maxlen characters long");
    }

    public function inArray (string $key, array $haystack, ?string $varname = NULL): ?string {
        return $this -> validate($key, $varname, function($key) use ($haystack) {
            return in_array($key, $haystack);
        },"%n must be contained in array $haystack");
    }


    public function phoneNum (string $key, ?string $varname = NULL): ?string {
        return $this -> validate($key, $varname, function($key) {
            return strlen($key) >= 9 && preg_match(self::REGEXP_PHONE_NUM, $key);
        },"%n must be a phone number");
    }

    public function email (string $key, ?string $varname = NULL): ?string {
        return $this -> validate($key, $varname, function($key) {
            $key = trim(strtolower($key));
            return filter_var($key, FILTER_VALIDATE_EMAIL) !== false || preg_match(self::REGEXP_EMAIL, $key) === 1;
        },"%n must be a valid email");
    }

    public function timestamp (string $key, ?string $varname = NULL): ?string {
        return $this -> validate($key, $varname, function($key) {
            return is_numeric($key) && strtotime(date('d-m-Y H:i:s',$key )) === (int)$key;
        }, '%n must be a valid UNIX timestamp');
    }

    public function coordinates(string $key, ?string $varname = NULL): ?string {
        try {
            return $this -> validate($key, $varname, function($key) {
                return preg_match(self::REGEXP_LOCATION, $key);
            }, "%n must be a valid GPS location");
        } catch (ValidationException $e) {
            $coords = $this -> valuearr[$key];
            $coords = str_replace(' ', '', $coords);
            try {
                $loc = new Location($coords);
            } catch (AppLogicException $ale) {
                throw $e;
            }
            $this -> valuearr[$key] = (string)$loc;
            return $this -> coordinates($key, $varname);
        }
    }


    public function locationID (string $key, ?string $varname = NULL): ?string {
        return $this -> validate($key, $varname, function($key) {
            return preg_match(self::REGEXP_GOOGLE_MAPS_LOCATION_ID, $key);
        }, "%n must be a valid Google Maps location ID");
    }

    public function oneSignalPlayerID (string $key, ?string $varname = NULL): ?string {
        return $this -> validate($key, $varname, function($key) {
            return preg_match(self::REGEXP_ONESIGNAL_PLAYER_ID, $key);
        }, "%n must be a valid OneSignal ID");
    }



    public function validate($key, ?string $varname, callable $test, string $errtext) {
        if (!isset($this -> valuearr[$key]) || ($k = $this -> valuearr[$key]) === NULL)
            return NULL;
        if ($varname === NULL)
            $varname = $key;
        if ((new ReflectionFunction($test)) -> getNumberOfRequiredParameters() > 1)
            throw new ValidationException('Invalid validation function, no more than one parameter required');
        if (!$test($k))
            throw new ValidationException(str_replace('%n', $varname, $errtext));
        return $k;
    }

}