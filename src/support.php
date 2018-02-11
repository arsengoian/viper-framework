<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 11.02.2018
 * Time: 16:22
 */

use Viper\Core\Config;
use Viper\Core\Localization;
use Viper\Core\View;

if (!function_exists('string')) {
    function lang(string $string): string {
        return Localization::lang($string);
    }
}
if (!function_exists('config')) {
    function config(string $key) {
        return Config::get($key);
    }
}
if (!function_exists('view')) {
    function view(string $view, array $data): View {
        return new View($view, $data);
    }
}
