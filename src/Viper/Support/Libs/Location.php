<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 21.06.2017
 * Time: 22:17
 */

namespace Viper\Support\Libs;


use Viper\Core\StringCodeLogicException;

class Location
{
    const EARTH_RADIUS = 6371000;

    public $latitude;
    public $longitude;

    function __construct(?string $text = NULL)
    {
        if ($text) {
            $locs = explode(',', $text);
            if (count($locs) != 2)
                throw new StringCodeLogicException('Location invalid: '. $text);
            $this -> latitude = (float) trim($locs[0]);
            $this -> longitude = (float) trim($locs[1]);
        }
    }

    /**
     * @param string $lat
     * @param string $long
     * @return Location
     * @throws StringCodeLogicException
     */
    public static function fromCoordinates(string $lat, string $long): Location {
        $loc = new Location();
        $loc -> latitude = $lat;
        $loc -> longitude = $long;
        return $loc;
    }

    function __toString()
    {
        return number_format($this -> latitude, 6).','.number_format($this -> longitude, 6);
    }

    /**
     * Get distance in km (approximate)
     * @param Location $a
     * @param Location $b
     * @return float
     */
    public static function distance(Location $a, Location $b): float {
        $f1 = deg2rad($a -> latitude);
        $f2 = deg2rad($b -> latitude);
        $df = deg2rad($b -> latitude - $a -> latitude);
        $dl = deg2rad($b -> longitude - $a -> longitude);

        $a = sin($df/2) * sin($df/2) +
            cos($f1) * cos($f2) *
            sin($dl/2) * sin($dl/2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return self::EARTH_RADIUS * $c;
    }

}