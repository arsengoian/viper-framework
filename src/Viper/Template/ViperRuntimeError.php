<?php

	namespace Viper\Template;
	
	class ViperRuntimeError extends ViperError {
		
		public function __construct(string $err, string $file, string $line, string $position) {
			parent::__construct("RuntimeError", $err, $file, $line, $position);
		}
		
	}

?>
