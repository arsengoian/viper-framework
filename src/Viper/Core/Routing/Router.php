<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 21.06.2017
 * Time: 13:46
 */

namespace Viper\Core\Routing;

// TODO add support for '/' routes

use Viper\Core\AppLogicException;
use Viper\Core\Config;
use Viper\Core\Routing\Methods\Method;
use Viper\Support\Libs\Util;
use Viper\Core\Viewable;

/**
 * Class Router
 * Priority:
 * 1. Routes in files
 * 2. Custom routes
 * 3. Custom route classes
 * 4. Fallback on controller name
 * 5. Fallback on default controller
 * @package Viper\Core\Routing
 */
class Router
{
    private const CONTROLLERS_NAMESPACE = 'App\\Controllers\\';

    private $app;
    private $routes = [];

    private static $customRouteRegistrationOpen = TRUE;
    private static $customRoutes = [];
    private static $customRouteClasses = [];

    function __construct(App $app) {
        $this -> app = $app;
        if (file_exists($file = root().'/routes/'.strtolower($app -> getMethod()).'.yaml'))
            $this -> routes = Util::fromYaml($file);
        else $this -> routes = [];
    }


    private static function checkRegisterAvailability() {
        if (!self::$customRouteRegistrationOpen)
            throw new AppLogicException('Cannot register route in this point in app. Register in App onLoad method');
    }

    public static function registerCustomRoute(string $routeKey, string $routeValue) {
        self::checkRegisterAvailability();
        self::$customRoutes[$routeKey] = $routeValue;
    }

    // Can't set custom actions. Only parsable from URL
    public static function registerCustomRouteClass(string $routeKey, string $routeClass) {
        self::checkRegisterAvailability();
        if (!class_exists($routeClass))
            throw new AppLogicException('Class '.$routeClass.' does not exist');
        self::$customRouteClasses[$routeKey] = $routeClass;
    }

    public function checkControllerName(App $app, ?string $controller_name) {
        if ($controller_name) {
            $action = $app -> routeSegment(0);
            if (!$action)
                $action = strtolower($app -> getMethod());
        } else {
            $action = 'get';
        }

        $routes = self::$customRoutes + $this -> routes;

        if (array_key_exists($controller_name, $routes)) {

            $element = $routes[$controller_name];
            if (is_array($element)) {
                if (array_key_exists($action, $element)) {
                    $ret = $this -> adaptData($element[$action], $action, $controller_name);
                } else $ret = [$action, $controller_name];
            } else {
                $ret = $this -> adaptData($element, $action, $controller_name);
            }
            $action = $ret[0];
            $controller_name = $ret[1];
            $controller_name = self::CONTROLLERS_NAMESPACE.$controller_name;

        } elseif (array_key_exists($controller_name, self::$customRouteClasses)) {

            $controller_name = self::$customRouteClasses[$controller_name];

        } else {

            if (!$controller_name)
                $controller_name = Config::get('DEFAULT_CONTROLLER');
            $controller_name = ucfirst(strtolower($controller_name)).'Controller';
            $controller_name = self::CONTROLLERS_NAMESPACE.$controller_name;

        }
        return [$controller_name, $action];
    }


    public function exec() {
        self::$customRouteRegistrationOpen = FALSE;

        $app = $this -> app;

        $controller_name = $app -> routeShift();
        list($controller_name, $action) = $this -> checkControllerName($app, $controller_name);


        if ($action == $app -> routeSegment(0))
            $this -> app -> routeShift(); // Shifting only now because earlier we weren't sure that the method exists
                                          // It could have been a parameter

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





    private function isDebugRequest(string $controller_name): bool {
        return Config::get('DEBUG') === TRUE && Util::contains($controller_name,'Debug');
    }

    
    public function validateController(string $controller_name, string $action) {
        if (!class_exists($controller_name))
            throw new HttpException(404,'No such controller');

        // Check if implements
        if (!$this -> isDebugRequest($controller_name)) {
            $poppy = explode('\\',Method::class);
            array_pop($poppy);
            if (!in_array(
                implode('\\', array_merge($poppy, [$this -> app -> getMethod()])),
                class_implements($controller_name)
            )) throw new HttpException(501);

            if (!method_exists($controller_name, $action))
                throw new HttpException(404,'No such method');
        }
    }

    public function runAction(string $controller_name, string $action): ?Viewable {

        App::log('request', 'New request: '.$controller_name.' '.$action);
        
        $this -> validateController($controller_name, $action);

        // Auxillary pseudo-controllers
        if ($this -> isDebugRequest($controller_name)) {
            if (file_exists(root().'/debug.php'))
                require root(). 'debug.php';
            else throw new HttpException(404);
            return stub();
        }

        $controller = new $controller_name($this -> app);

        return call_user_func_array([$controller, $action], $this -> app -> routeSegments());

    }
    
    
    public function CLIrunAction(string $controller_name, string $action, array $args) {
        list($controller_name, $a_) = $this -> checkControllerName($this->app, $controller_name);

        $controller = new $controller_name($this -> app);

        $content = call_user_func_array([$controller, $action], $this -> app -> routeSegments());
        if ($content)
            echo $content -> flush();
    }
}