<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 11.02.2018
 * Time: 16:22
 */

use Viper\Core\Config;
use Viper\Core\Localization;
use Viper\Core\Model\DB\DB;
use Viper\Core\Model\DB\RDBMS;
use Viper\Core\RedirectView;
use Viper\Core\TextView;
use Viper\Core\View;
use Viper\Support\Libs\Util;

if (!function_exists('root')) {
    function root(): string {
        return dirname(__FILE__, 5);
    }
}
if (!function_exists('lang')) {
    function lang(string $string): string {
        return Localization::lang($string);
    }
}
if (!function_exists('url')) {
    function url(string $url): string {
        return Localization::localizedURL($url);
    }
}
if (!function_exists('getLocale')) {
    function getLocale(): string {
        return Localization::getLocale();
    }
}
if (!function_exists('config')) {
    function config(string $key) {
        return Config::get($key);
    }
}

if (!function_exists('view')) {
    function view(string $view, array $data = []): View {
        return new View($view, $data);
    }
}
if (!function_exists('redirect')) {
    function redirect(string $redirect): RedirectView {
        return new RedirectView($redirect);
    }
}
if (!function_exists('stub')) {
    function stub() {
        return new TextView();
    }
}

if (!function_exists('db')) {
    function db(): RDBMS {
        return DB::instance();
    }
}
if (!function_exists('util')) {
    function util(string $func, ...$args) {
        call_user_func_array([Util::class, $func], $args);
    }
}