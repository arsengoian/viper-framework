<?php

	namespace Viper\Template;
	
	class ViperParseError extends ViperError {
		
		public function __construct(string $err, string $file, string $line, string $position) {
			parent::__construct("ParseError", $err, $file, $line, $position);
		}
		
	}

?>
