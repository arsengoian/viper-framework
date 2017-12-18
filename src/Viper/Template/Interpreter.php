<?php
	
/*

	Scope-related notes.
	Variables are imported from higher scopes, but aren't sent back.
	Functions are not imported!

*/

namespace Viper\Template;

use Viper\Core\Routing\HttpException;

abstract class Interpreter extends Preprocessor {

	protected $view;


	function __construct(string $file, array $data, $view) {
		parent::__construct($file, $data, $view);
	}

	public function runtimeError(string $err) {
		throw new RuntimeError($err, $this -> file, $this -> line, $this -> position);
	}



	public function execute(NodeCollection $nodes, array &$import = NULL) : string {

		// Main execution environment
		// all variables created during page processing must be stored in this scope

		ob_start();

		if ($import !== NULL) {
			foreach ($import as $var => $value) {
				if (!isset($$var))
					$$var = $value;
			}
		}


		foreach ($nodes as $node) {

			$nodeclass = explode('\\', get_class($node));
			$nodetype = $nodeclass[count($nodeclass) - 1];

			switch ($nodetype) {


				case 'TextNode':
					echo $node[0];
					break;


				case 'VarNode':

					$name = $node[0];
					if (isset($this[$name]))
						echo $this[$name];
					elseif (isset($this -> $name))
						echo $this -> $name;
					elseif (isset($$name))
						echo $$name;
					elseif (isset($import[$name]))
						echo $import[$name];
					else $this -> parseError("Undefined variable $name");

					break;



				case 'ExecNode':
					echo eval($node[0]) ?? eval('echo '.$node[0]); // TODO crutch! Add node for this.
					// And add functions for everything
					break;



				case 'ArrayNode':

					$arr = $node[0];
					$key = eval('return '.$node[1].';');

					if (isset($this[$arr])) {
						if (isset($this[$arr][$key]))
							echo $this[$arr][$key];
						else $this -> parseError("Undefined key {$arr}['{$key}']");
					} elseif (isset($this -> $arr)) {
						if (isset($this -> $arr[$key]))
							echo $this -> $arr[$key];
						else $this -> parseError("Undefined key {$arr}['{$key}']");
					} elseif (isset($$arr)) {
						if (isset($$arr[$key]))
							echo $$arr[$key];
						else $this -> parseError("Undefined key {$arr}['{$key}']");
					} elseif (isset($import[$arr])) {
						if (isset($import[$arr][$key]))
							echo $import[$arr][$key];
						else $this -> parseError("Undefined key {$arr}['{$key}']");
					} else $this -> parseError("Undefined array $arr");

					break;



				case 'FunctionNode':

					$args = explode(';', $node[1]);
					$params = [];
					foreach ($args as $arg)
						$params[] = eval('return '.$arg.';');
					$functionname = $node[0];

					if (method_exists($this, $functionname))
						echo call_user_func_array(array($this, $functionname), $params);
					elseif ($this -> view != NULL && method_exists($this -> view, $functionname))
						echo call_user_func_array(array($this -> view, $functionname), $params);
					elseif (function_exists($functionname))
						echo call_user_func_array($functionname, $params);
					else $this -> parseError("Undefined function $functionname");

					break;



				case 'HelperNode':
				case 'HelperBlockNode':
					throw new HttpException(501, 'Not Implemented helpers!');
					break;



				case 'ConditionalNode':
					foreach ($node as $row) {
						if (eval('return '.$row -> condition.';')) {

							if (isset($import))
								$export = array_merge(get_defined_vars(), $import);
							else $export = get_defined_vars();
							$this -> sanitizeExport($export);

							echo $this -> execute($row -> body, $export);
							break;
						}
					}
					break;



				case 'ForNode':
					$args = explode(';', $node[0]);
					if (($c = count($args)) != 3)
						$this -> runtimeError("For loop expecting 3 expressions, but $c provided");
					for (eval('return '.$args[0].';'); eval('return '.$args[1].';'); eval('return '.$args[2].';')) {

						if (isset($import))
							$export = array_merge(get_defined_vars(), $import);
						else $export = get_defined_vars();
						$this -> sanitizeExport($export);

						echo $this -> execute($node[1], $export);
					}
					break;



				case 'ForeachNode':
					if (substr_count($cond = $node[0], 'as') != 1)
						$this -> runtimeError('Foreach loop requires one AS statement');
					$beforeas = substr($cond, 0, $pos = strpos($cond, 'as'));
					$arr = eval('return '.$beforeas.';');
					$afteras = substr($cond, $pos + 2, strlen($cond));
					switch (substr_count($afteras, '=>') <=> 1) {   // TODO Fucking IF! You could make this with an if!

						case -1:
							$varn = substr(trim($afteras), 1);
							foreach ($arr as $$varn) {

								if (isset($import))
									$export = array_merge(get_defined_vars(), $import);
								else $export = get_defined_vars();
								$this -> sanitizeExport($export); // TODO Not even nearly working,
											// should sanitize each separately
											// And in fact should only sanitize defined vars. Not $import

								echo $this -> execute($node[1], $export);
							}
							break;

						case 0:
							$keyname = trim(substr($afteras, 0, $apos = strpos($afteras, '=>')));
							// TODO remove dollar signs
							$valuename = trim(substr($afteras, $apos + 2, strlen($afteras)));

							// TODO fix vars
							foreach ($arr as $$keyname => $$valuename) {

								if (isset($import))
									$export = array_merge(get_defined_vars(), $import);
								else $export = get_defined_vars();
								$this -> sanitizeExport($export);

								echo $this -> execute($node[1], $export);
							}
							break;

						case 1:
							$this -> runtimeError('Foreach loop can\'t contain more than one "=>" statements');
							break;
					}
					break;



				case 'WhileNode':
					while (eval('return '.$node[0].';')) {

						if (isset($import))
							$export = array_merge(get_defined_vars(), $import);
						else $export = get_defined_vars();
						$this -> sanitizeExport($export);

						echo $this -> execute($node[1], $export);
					}
					break;

			}

		}

		return ob_get_clean();


	}



	public function sanitizeExport(array &$export) {
		unset($export['nodes']);
		unset($export['import']);
		unset($export['node']);
		unset($export['output']);
		unset($export['this']);
		unset($export['nodetype']);
		unset($export['nodeclass']);
	}


}

