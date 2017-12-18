<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 22.06.2017
 * Time: 21:02
 */

namespace Viper\Support\Services;


use Viper\Support\Libs\Location;

/**
 * Class GoogleMaps
 * Doc: https://developers.google.com/places/web-service/search?hl=ru#deprecation
 * @package App\Support\Services
 */
class GoogleMaps
{
    const URL = "https://maps.googleapis.com/maps/api/place/nearbysearch/json?";

    public static function search(Location $location): array {
        // TODO search not only cafes (rand), also restaurants
        $url = self::URL . http_build_query([
            'location' => (string) $location,
            'radius' => 3500,
            'type' => 'cafe',
            'key' => GOOGLE_MAPS_API_KEY
        ]);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $output = curl_exec($ch);

        if ($output === FALSE)
            throw new ServiceException("CURL error: ".curl_error($ch));

        curl_close($ch);
        $result = json_decode($output);

        if (isset($result -> error_message))
            throw new ServiceException("Google Maps error: ".$result -> error_message);

        if(!$result)
            throw new ServiceException('Google maps JSON not parsed');

        $places = [];
        foreach ($result -> results as $place) {
            $places[] = [
                'place' => $place -> place_id,
                'place_name' => $place -> name,
                'place_coordinates' => $place -> geometry -> location -> lat . ',' . $place -> geometry -> location -> lng
            ];
        }

        return $places;
    }
}