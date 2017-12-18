<?php

namespace Viper\Core;

use Viper\Support\Libs\Util;
use GlobIterator;

class Config {

    const availablePreferences = [
        "Debug",
        "Report_errors",
        "Domain",
        "Language",
        "Require_https",
        "Default_timezone",
        "Master_layout",
        "Default_controller",

        "Db_host",
        "Db_name",
        "Db_user",
        "Db_pass"
    ];


    // TODO parse not all configs (because it's taking too much time). Find a more efficient yaml parser
    // Currently most time is wasted for yaml parsing...
    public static function parsePreferences() {
        foreach (new GlobIterator(ROOT.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'*.yaml') as $file) {
            $filechunks = explode(DIRECTORY_SEPARATOR,(string)$file);
            $chunk = array_pop($filechunks);
            if ($chunk == 'local.yaml' || $chunk == 'global.yaml')
                self::parse($file);
            else self::parse($file, strtoupper(explode('.', $file)[0]));
        }
    }

    public static function parse(string $address, string $namespace = NULL) {

        $conf = Util::fromYaml($address);
        $lc = 0;
        $prefix = $namespace ? $namespace.'.' : '';

        foreach ($conf as $key => $value) {
            $lc++;
            if (!isset($GLOBALS['__preferences__'][$prefix.strtoupper($key)]))
                $GLOBALS['__preferences__'][$prefix.strtoupper($key)] = $value;
        }

    }

    public static function get(string $key) {
        return $GLOBALS['__preferences__'][strtoupper($key)] ?? NULL;
    }

}


