<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 23.07.2017
 * Time: 13:19
 */

namespace Viper\Daemon;


interface Daemon
{

    /**
     * Create new daemon instance (system-dependent).
     * Creates a loop which keeps executing Daemon::run.
     * @param int $sleep in seconds
     */
    function spawn(int $sleep) : void;

    /**
     * Gently stops daemon and starts it with new frequency
     * @param int $sleep
     */
    function restart(int $sleep) : void;

    /**
     * Makes preparations for run method
     * Also stores memory used by daemon to be recovered
     */
    function exec() : void;

    /**
     * Main daemon task, is being run all the time
     * @return string to be logged
     */
    function run() : string;

    /**
     * Kills daemon process
     */
    function kill() : void;

    /**
     * Stops process gently after the run is performed
     */
    function stop(): void;

    /**
     * Returns process id
     * @return string
     */
    function processId() : string;

    /**
     * Checks if the daemon is running
     * @return bool
     */
    function isAlive() : bool;

    /**
     * Finds in storage bot memory usage
     * @return string
     */
    function memoryUsage() : string;



}