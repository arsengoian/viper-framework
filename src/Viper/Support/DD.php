<?php

	namespace Viper\Support;

	class DD {

		public static function nice($var) {
			ob_start();
			echo "\n\n";
			print_r($var);
			echo "\n";
			return ob_get_clean();
		}

		public static function dump($var) {
			ob_start();
			echo "\n\n";
			var_dump($var);
			echo "\n";
			return ob_get_clean();			
		}

		public static function dd(...$vars) {
		    echo '<pre>';
		    var_dump($vars);
		    die();
        }

	}


