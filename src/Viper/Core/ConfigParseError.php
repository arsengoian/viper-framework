<?php

	namespace Viper\Core;
	
	class ConfigParseError extends AppLogicError {
		
		public function __construct(string $token, string $address, int $line) {
			parent::__construct("Unexpected token $token in file $address on line $line");
		}
		
	}
	
?>
