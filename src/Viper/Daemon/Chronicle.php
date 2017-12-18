<?php
/**
 * Created by PhpStorm.
 * User: Арсен
 * Date: 23.07.2017
 * Time: 13:19
 */

namespace Viper\Daemon;


interface Chronicle
{

    /**
     * Logs a successful action
     * @param string $message
     */
    function actionLog(string $message) : void;

    /**
     * Logs an error
     * @param string $err
     */
    function errorLog(string $err): void;

    /**
     * Reads daemon log
     * @return string
     */
    function getLog() : string;

    /**
     * Reads daemon error log
     * @return string
     */
    function getErrorLog() : string;

    /**
     * Cleans daemon log
     */
    function clearLog() : void;

    /**
     * Stores daemon data
     * @param array $data
     */
    function store(array $data) : void;

    /**
     * Returns daemon data from memory
     * @return array
     */
    function restore() : ?array;



}