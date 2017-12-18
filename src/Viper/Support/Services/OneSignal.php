<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 23.06.2017
 * Time: 20:40
 */

namespace Viper\Support\Services;

// TODO TEST CAREFULLY
class OneSignal {

    public static function sendNotification(string $header, string $text, array $data, string $token) {

        $fields = json_encode([
            'app_id' => ONESIGNAL_APP_ID,
            'include_player_ids' => [$token],
            'data' => $data,
            'headings' => array (
                "en" => $header
                // TODO localisation
            ),
            'contents' => array(
                "en" => $text
                // TODO localisation
            ),
            //'isIos' => true,
            'isAndroid' => true,
            'content_avaliable' => true, // Allow run user code
            // TODO more customization
        ]);

        // CODE from onesignal.com DOCS: https://documentation.onesignal.com/docs/notifications-create-notification
        // BEGIN

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, "https://onesignal.com/api/v1/notifications");
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json', 'Authorization: Basic '.ONESIGNAL_APP_KEY));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);

        $response = curl_exec($ch);
        curl_close($ch);

        // END

        $result = json_decode($response);
        if ($result === false)
            throw new ServiceException("ERROR N10: Wrong JSON received as a response");

        if (isset($result -> error))
            throw new ServiceException("ERROR N11: OneSignal error: response contains: ".$result -> error);

        if (isset($result -> errors))
            throw new ServiceException("ERROR N12: Onesignal errors: response contains: ".json_encode($result -> errors).", result dump: ".json_encode($result));

        return $result; // TODO Add analysing

    }

}


