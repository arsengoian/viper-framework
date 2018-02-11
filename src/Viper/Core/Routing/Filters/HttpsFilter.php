<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 06.11.2017
 * Time: 15:02
 */

namespace Viper\Core\Routing\Filters;


use Viper\Core\Config;
use Viper\Core\Filter;
use Viper\Core\View;

/**
 * Class HttpsFilter
 * Use to redirect non-HTTPS requests to HTTPS
 * @package Viper\Core\Routing\Filters
 */
class HttpsFilter extends Filter
{

    public function proceed ()
    {
        if (Config::get('REQUIRE_HTTPS') == "Yes") {
            if (!$this -> app() -> getEnv("HTTPS")) {
                View::redirect('https://'.Config::get('DOMAIN').$this -> app() -> getEnv("REQUEST_URI"));
            }
        }
    }
}