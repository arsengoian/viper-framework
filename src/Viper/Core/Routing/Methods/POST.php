<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 31.10.2017
 * Time: 19:30
 */

namespace Viper\Core\Routing\Methods;

use Viper\Core\Viewable;

interface POST extends Method
{
    public function post(...$args): ?Viewable;
}