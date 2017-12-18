<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 21.06.2017
 * Time: 13:46
 */

namespace Viper\Core\Routing;

// TODO add support for '/' routes

use Viper\Core\Config;
use Viper\Core\Routing\Methods\Method;
use Viper\Support\Libs\Util;
use Viper\Core\Viewable;

class Router
{
    private const CONTROLLERS_NAMESPACE = 'App\\Controllers\\';

    private $app;
    private $routes;

    function __construct(App $app) {
        $this -> app = $app;
        if (file_exists($file = ROOT.'/routes/'.strtolower($app -> getMethod()).'.yaml'))
            $this -> routes = Util::fromYaml($file);
        else $this -> routes = [];
    }


    public function exec() {

        $app = $this -> app;

        $controller_name = $first = $app -> routeShift();
        if ($first) {
            $action = $app -> routeShift();
            if (!$action)
                $action = strtolower($app -> getMethod());
        } else {
            $action = 'get';
        }

        if (array_key_exists($controller_name, $this -> routes)) {
            $element = $this -> routes[$controller_name];
            if (is_array($element)) {
                if (array_key_exists($action, $element)) {
                    $ret = $this -> adaptData($element[$action], $action, $controller_name);
                } else $ret = [$action, $controller_name];
            } else {
                $ret = $this -> adaptData($element, $action, $controller_name);
            }
            $action = $ret[0];
            $controller_name = $ret[1];
        } else {
            if (!$controller_name)
                $controller_name = Config::get('DEFAULT_CONTROLLER');
            $controller_name = ucfirst(strtolower($controller_name)).'Controller';
        }
        $controller_name = self::CONTROLLERS_NAMESPACE.$controller_name;

        $content = $this -> runAction($controller_name, $action);
        if ($content)
            return $content -> flush();
        else return NULL;

    }


    private function adaptData($do, string $action, string $controller_name): array {
        if (!is_string($do))
            throw new RoutingException('3-level routing is not allowed');
        else $arrdo = explode('.', $do);
        switch (count($arrdo)) {
            /** @noinspection PhpMissingBreakStatementInspection */
            case 2:
                $action = $arrdo[1];
            case 1:
                $controller_name = $arrdo[0];
                break;
            default:
                throw new RoutingException('No more than one dot allowed');
        }
        return [$action, $controller_name];
    }



    public function runAction(string $controller_name, string $action): ?Viewable {

        $this -> app -> log('access', 'New request: '.$controller_name.' '.$action);

        $compatible_name = str_replace('App\Controllers', 'app\Controllers', $controller_name);
        $compatible_name = str_replace('\\', DIRECTORY_SEPARATOR, $compatible_name);

        if (!file_exists(ROOT.DIRECTORY_SEPARATOR."$compatible_name.php"))
            throw new RoutingException('No such controller');

        // Auxillary pseudo-controllers
        if (Config::get('DEBUG') == "Yes" && Util::contains($compatible_name,'Debug')) {
            if (file_exists(ROOT.'/debug.php'))
                require ROOT . 'debug.php';
            else throw new HttpException(404);
            return new class implements Viewable { public function flush(): string { return ''; } };
        }

        // Check if implements
        $poppy = explode('\\',Method::class);
        array_pop($poppy);
        if (!in_array(
            implode('\\', array_merge($poppy, [$this -> app -> getMethod()])),
            class_implements($controller_name)
        )) throw new HttpException(501);

        if (!method_exists($controller_name, $action))
            throw new RoutingException('No such method');

        $controller = new $controller_name($this -> app);

        return $controller -> $action($this -> app -> routeSegments());

    }
}