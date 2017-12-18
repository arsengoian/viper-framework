<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 21.06.2017
 * Time: 15:07
 */

namespace Viper\Core\Routing;

use Viper\Support\Libs\DataCollection;

// TODO check for possible issues with multiple urls analyzed by the same controller
abstract class Controller
{

    private $app;
    private $current = 2;

    function __construct(App $app) {
        $this -> app = $app;
    }

    protected function param(string $key, bool $shout = FALSE, callable $err_callback = NULL): ?string {
        return $this -> app -> getParam($key, $shout, $err_callback);
    }

    protected function params(): DataCollection {
        return $this -> app -> getParams();
    }

    protected function files(): DataCollection {
        return $this -> app -> getFiles();
    }

    protected function app(): App {
        return $this -> app;
    }

    protected function shiftRoute(): ?string {
        return $this -> app -> routeSegment($this -> current++);
    }

    public function __call(string $name, array $args): void {
        if (!isset($this -> allowed[$name]))
            throw new RoutingException('Not allowed for this element');
        else throw new HttpException(501);
    }

}