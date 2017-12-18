<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 21.06.2017
 * Time: 16:20
 */

namespace Viper\Core\Routing;


class RoutingException extends HttpException
{
    function __construct(string $message)
    {
        parent::__construct(405, $message);
    }
}