<?php

	namespace Viper\Template;
	
	use Viper\Core\AppLogicError;

	class ViperError extends AppLogicError {
		
		public function __construct(string $name, string $err, string $file, string $line, string $position) {
			$line++;
			parent::__construct("Viper: uncaught $name: $err in file $file on line $line near position $position");
		}
		
	}


