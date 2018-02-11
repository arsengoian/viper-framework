<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 31.10.2017
 * Time: 18:44
 */

namespace Viper\Core;


use Viper\Core\Routing\App;
use Viper\Support\Libs\Util;

// TODO add optional controller include and exclude lists

abstract class Filter
{
    private $app;

    final public function __construct (App $app)
    {
        $this -> app = $app;
    }

    abstract public function proceed();

    /**
     * Must return an array of routes, e.g. "user/dashboard"
     * Regular expressions apply, non case-sensitive
     * ! NOTE screen everything, including '/'
     * If route begins like current, the filter is skipped
     * @return array|null
     */
    public function blackListRoutes(): ?array {
        return NULL;
    }

    /**
     * If non-null, the filter will only work
     * if current route matches one in return array
     * Regular expressions apply, non case-sensitive
     * ! NOTE screen everything, including '/'
     * @return array|null
     */
    public function whiteListRoutes(): ?array {
        return NULL;
    }


    final public function app(): App
    {
        return $this->app;
    }


    final public function isToSkip(): bool {
        $thisRoute = implode('/', $this -> app() -> routeSegments());

        if ($lst = $this -> blackListRoutes()) {
            foreach ($lst as $route)
                if (preg_match('/^'.$route.'$/i', $thisRoute))
                    return TRUE;
        }
        if ($lst = $this -> whiteListRoutes()) {
            foreach ($lst as $route)
                if (preg_match('/^'.$route.'$/i', $thisRoute))
                    return FALSE;
            return TRUE;
        }
        return FALSE;
    }
}