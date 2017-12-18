<?php
	
	namespace Viper\Template;
	
	use Viper\Support\Collection;
	use Viper\Core\Config;
use Viper\Support\Libs\Util;

// TODO add include directive

	class Viper extends Interpreter {
	
		// !!! IMPORTANT SECURITY NOTE: No unprocessed user input should be passed on the view page!
		
		
		function __construct(string $file, array $data, $view = NULL) {
			parent::__construct($file, $data, $view);
		}
		
		public static function parseStatic(string $file, array $data, Viper $view) {
			$snake = new Viper($file, $data, $view);
			return $snake -> parse();
		}
		
		
		protected function getRaw() {
			return $this -> raw;
		}
				
		

		protected function resolveHelper() {}
		



		
		// Chief executive viper function
		public function parse() {
            /** @noinspection PhpParamsInspection */
            return $this -> execute(Util::cache(
            	$this -> file,
				$this -> raw,
				'viper',
				function($data): NodeCollection {
                	$tokens = $this -> tokenize($data);
                	return $this -> formLogicalTree($tokens);
            	}
            ), $this -> data);
		}
		

		
		
		public function debugTokenize() {
			if (Config::get('DEBUG') == 'Yes') {
				$tokens = $this -> tokenize($this -> raw);
				self::debugPrintTokens($tokens, TRUE);
			}
		}
		
		public static function debugPrintTokens(Collection $tokens, bool $full = FALSE) {
			if (Config::get('DEBUG') == 'Yes') {
				$class = new \ReflectionClass('Viper\Template\Tokenizer');
				$consts = $class -> getConstants();
				foreach ($tokens as $t) {
					if (!$full) {
						var_dump(array_search($t -> name, $consts));
					} else {
						$t -> type = array_search($t -> name, $consts);
						var_dump($t);
					}
				}
			}
		}
		
		
		
	}
	
?>
