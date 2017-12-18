<?php

	namespace Viper\Template;
	
	class ViperUnexpectedTokenError extends ViperParseError {
		
		public function __construct($token, string $file, string $line, string $position, int $expected = NULL) {
			if (typeof($token) == "int") {
				$class = new \ReflectionClass('Viper\Tokenizer');
				$consts = $class -> getConstants();
				$tok = array_search($token, $consts);
				if ($expected) {
					$ex = array_search($expected, $consts);
					if ($ex)
						$tok .= ", expected $ex";
				}
			} else {
				$tok = $token;
			}
			parent::__construct("ParseError: unexpected token $tok", $file, $line, $position);
		}
		
	}

?>
