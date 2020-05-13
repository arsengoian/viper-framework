<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 05.11.2017
 * Time: 18:12
 */

namespace Viper\Core;


use Viper\Core\Routing\Filters\HttpsFilter;
use Viper\Core\Routing\Filters\ModelDestructionFilter;
use Viper\Core\Routing\App;
use Viper\Core\Routing\Router;

/**
 * Class Application
 * @package Viper\Core
 *
 * @property  Router router
 */
class Application extends App
{
    public static $CLI = false;

    final protected function systemOnLoad (): void
    {
        // After all filters are passed
    }

    final protected function declareSystemFilters (): FilterCollection
    {
        return new FilterCollection([
            HttpsFilter::class
        ]);
    }

    final protected function declareSystemDyingFilters (): FilterCollection
    {
        return new FilterCollection([
            ModelDestructionFilter::class
        ]);
    }

    protected function onLoad (): void
    {

    }

    protected function declareFilters (): FilterCollection
    {
        return new FilterCollection([]);
    }

    protected function declareDyingFilters (): FilterCollection
    {
        return new FilterCollection([]);
    }




    public static function run() {
        (new static()) -> parseResponse();
    }





    private static function getCliArgs(string $cName = NULL, string $action = NULL): array {
        $argv = $_SERVER['argv'];
        array_shift($argv);

        $thisargs = [];
        foreach ([$cName, $action] as $v)
            if ($v)
                $thisargs[] = $v;

        $args = array_merge($thisargs, $argv);

        if (count($args) < 2)
            throw new \Exception('At least a controller name and an action required');

        return $args;
    }



    public static function cliRun(string $cName = NULL, string $action = NULL) {
        $app = new static();

        self::$CLI = true;

        $args = self::getCliArgs($cName, $action);
        $cn = array_shift($args);
        $a = array_shift($args);

        $app -> route = $args;

        $app -> router -> CLIrunAction($cn, $a, $args);

        $app -> afterFlushTasks();
    }
}