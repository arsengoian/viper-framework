<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 24.12.2017
 * Time: 1:10
 */

namespace Viper\Core\Routing;

use Viper\Support\Libs\Util;
use Viper\Core\Config;
use Viper\Support\Writer;
use Viper\Support\DaemonLogger;

class Loggable
{
    private static function dummyLogger(): Writer {
        return new class() implements Writer {
            public function newline() {}
            public function write(string $msg) {}
            public function append(string $msg) {}
            public function dump($var) {}
        };
    }

    public static  function dump(string $key, ...$vars) {
        ob_start();
        foreach ($vars as $var) {
            echo "\n";
            print_r($var);
            echo "\n";
        }
        self::log($key, ob_get_clean());
    }

    public static function log(string $key, string $string) {
        $logger = Util::RAM('logger.'.$key, function() use($key) {
            if (Config::get('DEBUG')) {
                return new DaemonLogger(root().'/logs/'.$key.'.log');
            } else {
                return self::dummyLogger();
            }
        });
        $logger -> write($string);
    }
}