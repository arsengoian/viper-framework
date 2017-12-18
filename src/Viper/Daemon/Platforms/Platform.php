<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 23.07.2017
 * Time: 15:54
 */

namespace Viper\Daemon\Platforms;


interface Platform
{
    /**
     * Add process
     * @param int $sleep
     * @param string $phpAction
     * @param string $logfile
     * @param string $errlogfile
     * @return string Process ID
     */
    function newProcess(int $sleep, string $phpAction, string $logfile, string $errlogfile) : string;

    /**
     * Kills process with this ID
     * @param string $id
     */
    function killProcess(string $id) : void;

    /**
     * Checks if process exists
     * @param string $id
     * @return bool
     */
    function processIsRunning(string $id) : bool;
}