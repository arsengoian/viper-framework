<?php
	
	namespace Viper\Template\Nodes;
	
	use Viper\Support\Collection;

	class LogicalNode extends Collection {
		
		public function __construct(...$values) {
			parent::__construct($values);
		}
		
		public static function fromArr($arr) {
			return new parent($arr);
		}
		
	}

?>
