<?php

namespace Viper\Core\Routing;


use Viper\Core\Config;
use Viper\Core\Filter;
use Viper\Core\FilterCollection;
use Viper\Core\View;
use Viper\Support\DaemonLogger;
use Viper\Support\Libs\DataCollection;
use Viper\Support\Libs\Util;
use Viper\Support\ValidationException;
use Viper\Support\Libs\DataStream;
use Viper\Support\Writer;


// TODO Finish Viper to support foreach, while and switch

// TODO add daemon routing

// TODO implement data propagation for viper

// TODO add async requests from ReactPHP

// TODO implement sessions

// TODO replace ROOT constant

// TODO add support for regexp to routing

// TODO custom models deployment

// TODO update Viper package


abstract class App extends Loggable{
    
    private $params;
    private $files;
    private $headers;
    private $method;
    private $env;
    private $session;
    private $cookies;

    protected $route;
    protected $router;

    private $flags = [
        'exceptionsDisabled' => FALSE,
        'noBuffering' => FALSE,
        'contentEncodingNone' => FALSE,
    ];

    protected abstract function onLoad(): void;

    protected abstract function systemOnLoad(): void;

    protected abstract function declareFilters(): FilterCollection;

    protected abstract function declareSystemFilters(): FilterCollection;

    protected abstract function declareDyingFilters(): FilterCollection;

    protected abstract function declareSystemDyingFilters(): FilterCollection;

    function __construct(){
        self::phpConfig();

        Util::RAM('clock', function () {
           return microtime(TRUE);
        });

        $this -> setupVars();

        $filters = $this -> declareSystemFilters() -> merge($this -> declareFilters());
        foreach ($filters as $filterName) {
             $filter = $this -> filter($filterName);
             if (!$filter -> isToSkip())
                $filter -> proceed();
        }

        $this -> systemOnLoad();
        $this -> onLoad();
    }

    private function setupVars() {
        $path = isset($_GET['path']) ? $_GET['path'] : '';
        $this -> setRoute($path);

        if (function_exists('getallheaders'))
            $this -> headers = getallheaders();
        else $this -> headers = [];
        $this -> method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

        $this -> setupParams();

        $this -> env = $_SERVER;
        $this -> cookies = $_COOKIE;
        $this -> router = new Router($this);
    }



    private function filter(string $name): Filter {
        return new $name($this);
    }

    public static function phpConfig() {
        Config::parsePreferences();

        if (Config::get('DEBUG') === TRUE) {
            error_reporting(E_ALL);
        } else error_reporting(NULL);

        setlocale(LC_CTYPE, "en_US.utf8");
        date_default_timezone_set(Config::get('DEFAULT_TIMEZONE') ??'Europe/Kiev');

        set_exception_handler(static::class.'::handler');
        set_error_handler(static::class.'::errhandler');
    }

    public static function handler(\Throwable $exc, bool $prettyprint = FALSE) {
        $e = $exc -> getMessage();
        $_response["error"] = is_array(json_decode($e)) ? json_decode($e) : $e;
        $_response["type"] = get_class($exc);
        if (Config::get('DEBUG') === TRUE) {
            $_response["line"] = $exc -> getLine();
            $_response["code"] = $exc -> getCode();
            $_response["file"] = $exc -> getFile();
            $_response["trace"] = $exc -> getTrace();
        }
        if (Config::get('DEBUG') === TRUE && $prettyprint || Config::get('PRETTY_PRINT') === TRUE)
            echo '<pre>' && print_r($_response);
        else echo json_encode($_response);
        try {
            $l = new DaemonLogger(root().'/logs/error.log');
            $l -> write("Error occured\n");
            $l -> dump($exc);
        } catch (\Exception $e) {}
        return '';
    }

    public static function errhandler(int $errno, string $errstr, string $errfile, int $errline) {
        $_response["error"] = $errstr;
        if (Config::get('DEBUG') === TRUE) {
            $_response["line"] = $errline;
            $_response["code"] = $errno;
            $_response["file"] = $errfile;
        }
        if (Config::get('DEBUG') === TRUE && Config::get('PRETTY_PRINT') === TRUE)
            echo '<pre>' && print_r($_response);
        else echo json_encode($_response);
        try {
            $l = new DaemonLogger(root().'/logs/error.log');
            $l -> write("Error occured\n");
            $l -> dump($_response);
        } catch (\Exception $e) {}
    }


    public function getRawBody() {
        return Util::RAM('rawBody', function() {
            return file_get_contents('php://input');
        });
    }

    private function fromPHPInput() {
        parse_str($this -> getRawBody(), $ro);
        if (count($ro) > 0) {
            $this -> params = new DataCollection($ro);
            $this -> files = new DataCollection();
        } else {
            $this -> params = new DataCollection();
            $this -> files = new DataCollection();
        }
    }

    public function setupParams() {
        // Weird bug with "para" param
        if ($this -> getMethod() == 'GET') {
            $output = [];
            parse_str($_SERVER['QUERY_STRING'] ?? '', $output);
            $this -> params = new DataCollection($output);
            $this -> files = new DataCollection();
        } elseif (strpos($this -> getHeader('Content-Type'), 'application/json') !== FALSE) {
            $data = json_decode($this -> getRawBody(), TRUE);
            if (!is_array($data))
                throw new ValidationException('JSON not valid: '.$this -> getRawBody());
            $this -> params = new DataCollection($data);
            $this -> files = new DataCollection();
        } elseif ($this -> getMethod() == 'POST') {
            if (strpos($this -> getHeader('Content-Type'), 'multipart/form-data') !== FALSE) {
                $this->params = new DataCollection($_POST);
                $this->files = new DataCollection($_FILES);
            } else {
                $this -> fromPHPInput();
            }
        } else {
            if (strpos($this -> getHeader('Content-Type'), 'multipart/form-data') !== FALSE) {
                $stream = new DataStream($this -> getRawBody());
                $this -> params = $stream['post'] ?? new DataCollection();
                $this -> files = $stream['files'] ?? new DataCollection();

                if (count($this -> params) < 1)
                    $this -> params = new DataCollection();
                if (count($this -> files) < 1)
                    $this -> files = new DataCollection();

            } else {
                $this -> fromPHPInput();
            }

        }
    }


    public function getElement(string $var, string $key, bool $required = true, callable $errorcallback = NULL) {
        if (!$errorcallback)
            $errorcallback = function($k) { throw new ValidationException();};

        if (isset(($this -> $var)[$key])) {
            return ($this -> $var)[$key];
        }
        else {
            if (!$required)
                return NULL;
            try {
                $errorcallback($key);
            } catch(\Throwable $e) {
                throw new HttpException(400, 'Parameter '.$key.' missing');
            }
        }
        return NULL;
    }




    public function getParams(): DataCollection {
        return $this -> params;
    }

    public function getParam(string $key, bool $required = true, callable $errorcallback = NULL) {
        return $this -> getElement('params', $key, $required, $errorcallback);
    }

    public function getFiles(): DataCollection {
        return $this -> files;
    }

    public function getFile(string $key, bool $required = true, callable $errorcallback = NULL) {
        return $this -> getElement('files', $key, $required, $errorcallback);
    }


    public function setRoute(string $route) {
        if ($route[0] == '/')
            $route = substr($route, 1);
        $this -> route = explode('/', $route);
    }

    public function getRouter(): Router {
        return $this -> router;
    }

    public function routeSegment(int $num): ?string {
        if (isset($this -> route[$num]))
            return $this -> route[$num];
        return NULL;
    }

    public function routeShift() {
        return array_shift($this -> route);
    }

    public function routeSegments(): array {
        return $this -> route;
    }

    public function getHeader(string $name): ?string {
        return $this -> headers[$name] ?? NULL;
    }

    public function getEnv (string $key)
    {
        return $this->env[$key] ?? NULL;
    }

    public function getMethod(): string {
        return $this -> method;
    }

    public function session($k, $v = NULL) {
        if ($v)
            return $this -> session[$k] = $v;
        return $this -> session[$k];
    }

    final protected function afterFlushTasks() {
        $filters = $this -> declareDyingFilters() -> merge($this -> declareSystemDyingFilters());
        foreach ($filters as $filterName) {
            $filter = $this -> filter($filterName);
            if (!$filter -> isToSkip())
                $filter -> proceed();
        }
    }



    public static function clock() {
        return microtime(TRUE) - Util::RAM('clock');
    }




    public function disableExceptionHandler() {
        $this -> flags['exceptionsDisabled'] = TRUE;
    }

    public function noBuffering() {
        $this -> flags['noBuffering'] = TRUE;
    }

    public function contentEncodingNone() {
        $this -> flags['contentEncodingNone'] = TRUE;
    }


    public function parseResponse() {

        if (!$this -> flags['noBuffering'])
            ob_start();

        try {
            $data = $this -> router -> exec();

            switch (gettype($data)) {
                case 'string':
                    echo $data;
                    break;
                default:
                    if (Config::get('DEBUG') === TRUE &&
                        isset($this -> params['prettyprint']) || Config::get('PRETTY_PRINT') === TRUE)
                            echo '<pre>' && print_r($data) && die();
                    echo json_encode($data);
            }

            if (!$this -> flags['noBuffering']) {
                $size = ob_get_length();
                header("Content-Length: $size");
                header("Connection: close");
                if ($this -> flags['contentEncodingNone'])
                    header("Content-encoding: none");
            }


        } catch (\Throwable $exc) {

            if (!$this -> flags['exceptionsDisabled']) {
                // If not caught earlier
                try {
                    echo View::parseException($exc);
                } catch (\Exception $e) {
                    echo App::handler($exc, isset($this -> params['prettyprint']));
                }
            } else {
                throw $exc;
            }
        }

        if (!$this -> flags['noBuffering']) {
            ob_end_flush();
            ob_flush();
            flush();
            if(session_id()) session_write_close();
        }
        
        $this -> afterFlushTasks();

    }


}