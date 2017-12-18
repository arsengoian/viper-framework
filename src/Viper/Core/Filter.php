<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 31.10.2017
 * Time: 18:44
 */

namespace Viper\Core;


use Viper\Core\Routing\App;

abstract class Filter
{
    private $app;

    public function __construct (App $app)
    {
        $this -> app = $app;
    }

    abstract public function proceed();


    public function app(): App
    {
        return $this->app;
    }
}