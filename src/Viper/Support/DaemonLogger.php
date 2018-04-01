<?php

namespace Viper\Support;

use Viper\Core\Config;
use Viper\Support\Libs\Util;

class DaemonLogger implements Writer {

    private $log = NULL; // Pointer to log file
    private $clock;
    private $file;

    function __construct($file) {

        $this -> file = $file;
        $this -> clock = microtime(true);
        $date = date("m.Y");

        if (!file_exists($file))
            Util::put($file, '');

        $this -> log = fopen($file, "a");
        if (strlen(file_get_contents($file, "a")) == 0)
            $this -> newline();
        $this -> write(date("H:i:s d M Y")." New session started");

    }

    function __destruct() {
        $time = number_format(microtime(true) - $this -> clock, 4);
        $this -> write(date("H:i:s d M Y")." Session ended, ".$time."s elapsed");
        $this -> newline();
        fclose ($this -> log);
    }

    private function writ($msg) {
        fwrite($this -> log, $msg."\n");
    }

    public function newline() {
        $this -> writ("=================================================================================================");
    }

    public function write(string $msg) {
        $this -> writ("|  ".$msg);
    }

    public function append(string $msg) {
        fwrite($this -> log, $msg);
    }

    public function dump($var) {
        $this -> append(DD::nice($var));
    }


    public function getFile()
    {
        return $this->file;
    }


    public function setFile($file)
    {
        $this->file = $file;
    }

}


