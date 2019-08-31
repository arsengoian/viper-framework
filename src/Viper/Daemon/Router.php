<?php

namespace Viper\Daemon;

use Viper\Daemon\Platforms\Linux;

abstract class Router extends DaemonHistorian implements Daemon
{

    private $platform;
    private $storage;
    private $name;

    /**
     * Daemon constructor. Id is daemon name, which corresponds to storage paths.
     * @param string $id
     */
    function __construct(string $id) {
        $this -> name = $id;
        parent::__construct($id);
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows not implemented
            $this -> errorLog('Windows not supported');
        } else {
            $this -> platform = new Linux();
        }

        $this -> storage = $this -> restore();
        if ($this -> storage === NULL) {
            $this -> storage = [
                'id' => NULL,
                'sentenced' => 'run',
                'memory' => NULL,
                'sleep' => NULL,
            ];
        }
    }

    /**
     * Create new daemon instance (system-dependent).
     * Creates a loop which keeps executing Daemon::run.
     * @param int $sleep in seconds
     * @throws DaemonException
     */
    public function spawn(int $sleep) : void
    {
        if (isset($this -> storage['id']) && $this -> isAlive())
            throw new DaemonException('It\'s alive anyway!');

        $this -> actionLog('Spawning process with sleep length '.$sleep);
        $this -> actionLog('See shell output.');

        $thisfile = __FILE__;
        $thisclass = static::class;
        $segments = explode('\\', $thisclass);
        $thiscname = array_pop($segments);
        str_replace('Router.php', "$thiscname.php", $thisfile);
        $bootstrap = root().'/vendor/autoload.php';
        $thisid = $this -> getName();

        $this -> storage['id'] = $this -> platform -> newProcess($sleep, "
             require \\\\\\\"$bootstrap\\\\\\\";
             (new $thisclass(\\\\\\\"$thisid\\\\\\\")) -> exec();
        ", $this -> getSuccFile(), $this -> getErrFile());

        $this -> storage['sleep'] = $sleep;
        $this -> storage['sentenced'] = 'run';
        $this -> store($this -> storage);
        $this -> actionLog('Successful');
    }

    /**
     * Gently stops daemon and starts it with new frequency
     * @param int $sleep
     */
    public function restart(int $sleep) : void
    {
        $this -> storage['sentenced'] = 'rewind';
        $this -> storage['sleep'] = $sleep;
        $this -> store($this -> storage);
    }

    private function rewind() {
        $this -> kill();
        if ($this -> storage['sleep'])
            $this -> spawn($this -> storage['sleep']);
        else $this -> errorLog('Too little info to wake up; Bye forever');
    }


    /**
     * Makes preparations for run method
     * Also stores memory used by daemon to be recovered
     */
    public function exec() : void
    {
        $this -> actionLog('Script successfully entered');

        $sentence = $this -> storage['sentenced'];
        $response = NULL;
        if (method_exists($this, $sentence))
            $response = $this -> $sentence();
        else $this -> errorLog('Unknown method '.$sentence);

        if ($response)
            $this -> actionLog('Received data: '.$response);

        $this -> setMemory(memory_get_usage(TRUE));
        $this -> actionLog('Script successfully completed, action: '.$sentence);
    }

    /**
     * Kills daemon process
     */
    public function kill() : void
    {
        $this -> platform -> killProcess($this -> processId());
        // @unlink($this -> getFile());
    }


    /**
     * Stops process gently after the run is performed
     */
    public function stop(): void
    {
        $this -> storage['sentenced'] =  'kill';
        $this -> store($this -> storage);
    }

    /**
     * Returns process id
     * @return string
     * @throws DaemonException
     */
    public function processId() : string
    {
        if (!$this -> storage['id'])
            die("ERROR: The daemon is not running\n");
        return trim($this -> storage['id']);
    }

    /**
     * Checks if the daemon is running
     * @return bool
     */
    public function isAlive() : bool
    {
        return $this -> platform -> processIsRunning($this -> processId());
    }


    private function setMemory(string $mem) {
        $this -> storage['memory'] = $mem;
        $this -> store($this -> storage);
    }

    /**
     * Finds in storage bot memory usage
     * @return string
     */
    public function memoryUsage() : string
    {
        return $this -> storage['memory'];
    }


    public function help() : string {
        $cls = static::class;
        return "\nclass $cls\n\nUsage: \nspawn \$frequency \nrestart \$frequency \nkill \nstop \nprocessId \nisAlive\nmemoryUsage\n\n";
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    public function getSleep(): string {
        return $this -> storage['sleep'];
    }


    /**
     * Start a command to services
     * @param string $command
     * @param array $args
     */
    public static function route(string $command, array $args) : void {
        $object = new static('test');
        echo "\n";
        if (method_exists(static::class, $command)) {
            $response = call_user_func_array([$object, $command], $args);
            if ($response === NULL)
                echo "SUCCESS";
            else if ($response === FALSE)
                echo "FALSE";
            else if ($response === TRUE)
                echo "TRUE";
            else {
                if (is_string($response))
                    echo $response;
                else print_r($response);
            }
        } else echo "No such method available";
        echo "\n\n";
    }

}