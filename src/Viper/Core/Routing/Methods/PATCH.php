<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 31.10.2017
 * Time: 19:30
 */

namespace Viper\Core\Routing\Methods;

use Viper\Core\Viewable;

interface PATCH extends Method
{
    public function patch(...$args): ?Viewable;
}