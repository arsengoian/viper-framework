<?php

	namespace Viper\Support;

    interface Writer {

        public function newline();

        public function write(string $msg);

        public function append(string $msg);

        public function dump($msg);

    }