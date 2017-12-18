<?php

	namespace Viper\Template;

	class Token {
		
		public $name;
		public $line;
		public $position;
		public $absposition;
		public $length;
		public $data;	
		
		function __construct(int $name, int $line, int $position, int $abs, int $len, string $data = NULL) {
			$this -> name = $name;
			$this -> line = $line;
			$this -> position = $position;
			$this -> absposition = $abs;
			$this -> length = $len;
			$this -> data = $data;			
		}	
		
	}

?>
