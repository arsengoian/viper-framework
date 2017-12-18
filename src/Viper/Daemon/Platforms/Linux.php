<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 23.07.2017
 * Time: 15:55
 */

namespace Viper\Daemon\Platforms;


class Linux implements Platform
{

    /**
     * Add process
     * @param int $sleep
     * @param string $phpAction
     * @param string $logfile
     * @param string $errlogfile
     * @return string Process ID
     */
    function newProcess(int $sleep, string $phpAction, string $logfile, string $errlogfile) : string
    {
        $interpreter = PHP_BIN;
        return trim(shell_exec("
            nohup bash -c \"
                while [ true ]
                do
                    sleep $sleep;
                    echo \\\" <?php
                        $phpAction
                    \\\" | $interpreter
                done
            \" > $logfile.shell 2> $errlogfile.shell & echo $!
        "));
    }

    /**
     * Kills process with this  numeric ID
     * @param string $id
     */
    function killProcess(string $id) : void
    {
        shell_exec("kill $id");
    }


    /**
     * Checks if process exists
     * @param string $id
     * @return bool
     */
    function processIsRunning(string $id) : bool
    {
        return trim(shell_exec("
            if [ -d /proc/$id ]
            then
                echo \"yes\"
            fi
        ")) == 'yes';
    }

}