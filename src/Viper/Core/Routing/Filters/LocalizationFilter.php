<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 06.11.2017
 * Time: 18:43
 */

namespace Viper\Core\Routing\Filters;


use Viper\Core\Filter;
use Viper\Core\Localization;


/**
 * Class LocalizationFilter
 * Localizes the application
 * Extend this filter to allow/disallow routes
 * @package LocalizationFilter
 */
class LocalizationFilter extends Filter
{

    public function proceed ()
    {
        (new Localization($this -> app())) -> init();
    }

}