<?php
	
	namespace Viper\Template;
	
	class ConditionalExpression {
		
		public $condition;
		public $body;
		
		function __construct(string $condition, NodeCollection $body) {
			$this -> condition = $condition;
			$this -> body = $body;
		}
		
	}
	
?>
