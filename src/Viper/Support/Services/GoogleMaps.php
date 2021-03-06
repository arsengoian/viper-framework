<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 22.06.2017
 * Time: 21:02
 */

namespace Viper\Support\Services;


use Viper\Support\Libs\Location;
use Viper\Core\Config;

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
            'key' => Config::get('Google.API_KEY')
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
                'place_coordinates' => $place -> geometry -> location -> lat . ',' . $place -> geometry -> location -> lng,
                'photo' => isset($place -> photos) ? $place -> photos[0] -> photo_reference: ''
            ];
        }

        return $places;
    }



    public static function staticMap(
        Location $location,
        string $color = 'red',
        string $label = 'place',
        string $zoom = '13'
    ) {
        $key = config('Google.STATIC_MAP_API_KEY');
        return "https://maps.googleapis.com/maps/api/staticmap?&markers=color:$color|label:$label|".
            "$location->latitude,$location->longitude&zoom=$zoom&scale=1&size=600x300&maptype="
            ."roadmap&format=png&visual_refresh=true&key=$key";
    }


    public static function url(Location $location): string {
        $lat = str_replace(',' , '.', $location->latitude);
        $long = str_replace(',' , '.', $location->longitude);
        return "https://www.google.com/maps/?q=loc:$lat,$long";
    }
}