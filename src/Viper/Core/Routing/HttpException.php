<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 21.06.2017
 * Time: 13:38
 */

namespace Viper\Core\Routing;


class HttpException extends \Exception {

    public $http_status = 200;

    public function __construct(int $code, string $explanation = "") {
        $this -> http_status = $code;
        switch ($code) {

            # https://en.wikipedia.org/wiki/List_of_HTTP_status_codes

            case 400:
                $e = "Bad request";
                break;

            case 401:
                $e = "Unauthorized";
                break;

            case 403:
                $e = "Forbidden";
                break;

            case 404:
                $e = "Not found";
                break;

            case 405:
                $e = "Method not allowed";
                break;

            case 500:
                $e = "Internal server error";
                break;

            case 501:
                $e = "Not implemented";
                break;


            default:
                $e = "Internal server error";

        }
        if ($explanation)
            $e .= ": $explanation";

        http_response_code($code);

        parent::__construct("$code $e");
    }

}