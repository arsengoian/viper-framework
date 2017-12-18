<?php
	
	namespace Viper\Template;
	
	use Viper\Support\Collection;

	abstract class Preprocessor extends Tokenizer {
		
		function __construct(string $file, array $data, $view) {
			parent::__construct($file, $data, $view);
		}


        public function formLogicalTree(Collection $tokencollection) : NodeCollection {

//			sleep(1);

			$nodes = new NodeCollection();

			for ($i = 0; $i < $tokencollection -> length(); $i++) {

				$token = $tokencollection[$i];

				switch ($token -> name) {
					
					case static::VT_HTML:
						$nodes[] = new Nodes\TextNode($token -> data);
						break;
						
					case static::VT_AT:
						$nodes[] = new Nodes\TextNode('@');
						break;
						
					case static::VT_VAR:
						$nodes[] = new Nodes\VarNode($token -> data);
						break;
						
					case static::VT_EMBEDDED_OPEN:
						if (($exec = $tokencollection[++$i]) -> name != static::VT_EMBEDDED_EXEC)
							$this -> unexpectedTokenError($exec, static::VT_EMBEDDED_EXEC);
						if (($close = $tokencollection[++$i]) -> name != static::VT_EMBEDDED_END)
							$this -> unexpectedTokenError($close, static::VT_EMBEDDED_END);
						$nodes[] = new Nodes\ExecNode($exec -> data);
						break;
						
						
					case static::VT_ARRAY_OPEN:
						$name = $token -> data;
						if (($key = $tokencollection[++$i]) -> name != static::VT_ARGS)
							$this -> unexpectedTokenError($key, static::VT_ARGS);
						if (($close = $tokencollection[++$i]) -> name != static::VT_ARRAY_CLOSE)
							$this -> unexpectedTokenError($close, static::VT_ARRAY_CLOSE);
						$nodes[] = new Nodes\ArrayNode($name, $key -> data);
						break;						
					
					
					case static::VT_FUNCTION_OPEN:
						$name = $token -> data;
						if (($args = $tokencollection[++$i]) -> name != static::VT_ARGS)
							$this -> unexpectedTokenError($args, static::VT_ARGS);
						if (($close = $tokencollection[++$i]) -> name != static::VT_FUNCTION_CLOSE)
							$this -> unexpectedTokenError($close, static::VT_FUNCTION_CLOSE);
						$nodes[] = new Nodes\FunctionNode($name, $args -> data);
						break;							
		
					
					case static::VT_HELPER_OPEN:
						$i += 2;
					case static::VT_ENDHELPER:
					#case static::VT_HELPER_CLOSE:
					#case static::VT_HELPER_CONTINUE:
						// May be both HelperNode and HelperBlockNode
						break;
					
					
					
					case static::VT_IF:
					
						if (($conditional = $tokencollection[++$i]) -> name != static::VT_ARGS)
							$this -> unexpectedTokenError($conditional, static::VT_ARGS);
						if (($cont = $tokencollection[++$i]) -> name != static::VT_CONTINUE)
							$this -> unexpectedTokenError($cont, static::VT_CONTINUE);
							
						$condition = $conditional -> data;
							
						$count = ($c = $tokencollection) -> length();
						$pos = NULL;
						
						$elseifs = [];
						$else = NULL;
						$level = 0;
						
						for($j = $i; $j < $count; $j++) {
						
							$cname = $c[$j] -> name;
						
							if ($level > 0) {
								if ($cname == static::VT_ENDIF)
									$level--;
								continue;
							}
							
							if ($cname == static::VT_IF) {
								$level++;
								continue;
							}
							
							if ($cname == static::VT_ELSE) {
								$else = $j;
								continue;
							}
							if ($cname == static::VT_ELSEIF) {
								if ($else !== NULL)
									$this -> unexpectedTokenError("elseif after else");
								else {
									$elseifs[] = $j;
									continue;
								}
							}
							if ($cname != static::VT_ENDIF)
								continue;
							else {
								$pos = $j;
								break;
							}
							
						}
						
						if ($pos === NULL)
							$this -> parseError("Unexpected end of file, expected \"endif\"");
						$children = Collection::slice($c, $i + 1, $pos);
						$i = $pos;
						
						
						$node = new Nodes\ConditionalNode();
						$last = 0;
						
						foreach ($elseifs as $p) {
						
							$actual = $p - $pos;
							$current = $actual;
							
							if (($conditional = $children[++$actual]) -> name != static::VT_ARGS)
								$this -> unexpectedTokenError($conditional, static::VT_ARGS);
							if (($continue = $children[++$actual]) -> name != static::VT_CONTINUE)
								$this -> unexpectedTokenError($continue, static::VT_CONTINUE);
								
							$node[] = new ConditionalExpression(
							    $conditional -> data,
                                $this -> formLogicalTree(Collection::slice($children, $last + 1, $current))
                            );
							$last = $actual + 1;
							
						}
						
						if ($else !== NULL) {
							$node[] = new ConditionalExpression(
							    $condition,
                                $this -> formLogicalTree(Collection::slice($children, $last + 1, $else))
                            );
							$last = $else + 1;
						}

						$node[] = new ConditionalExpression(
						    $condition,
                            $this -> formLogicalTree(Collection::slice($children, $last, $children -> length()))
                        );
						
						$nodes[] = $node;
						
						break;
				
					
					
					
					
					case static::VT_FOR:
					
						if (($args = $tokencollection[++$i]) -> name != static::VT_ARGS)
							$this -> unexpectedTokenError($args, static::VT_ARGS);
						if (($cont = $tokencollection[++$i]) -> name != static::VT_CONTINUE)
							$this -> unexpectedTokenError($cont, static::VT_CONTINUE);
							
						$condition = $args -> data;
							
						$count = $tokencollection -> length();
						$pos = NULL;
						$level = 0;
						
						for($j = $i; $j < $count; $j++) {
							$cname = $tokencollection[$j] -> name;
							if ($level > 0) {
								if ($cname == static::VT_ENDFOR)
									$level--;
							} elseif ($cname == static::VT_FOR) {
								$level++;
							} elseif ($cname == static::VT_ENDFOR) {
								$pos = $j;
								break;
							}
						}
						
						if ($pos === NULL)
							$this -> parseError("Unexpected end of file, expected \"endfor\"");

						$children = Collection::slice($tokencollection, $i + 1, $pos);
						$i = $pos;
						
						$nodes[] = new Nodes\ForNode($condition, $this -> formLogicalTree($children));
						
						break;
						
						
						
						
					
					case static::VT_FOREACH:
						
						if (($args = $tokencollection[++$i]) -> name != static::VT_ARGS)
							$this -> unexpectedTokenError($args, static::VT_ARGS);
						if (($cont = $tokencollection[++$i]) -> name != static::VT_CONTINUE)
							$this -> unexpectedTokenError($cont, static::VT_CONTINUE);
							
						$condition = $args -> data;
						$count = $tokencollection -> length();
						$pos = NULL;
						
						$level = 0;

						for($j = $i; $j < $count; $j++) {
							$cname = $tokencollection[$j] -> name;
							if ($level > 0) {
								if ($cname == static::VT_ENDFOREACH)
									$level--;
							} elseif ($cname == static::VT_FOREACH) {
								$level++;
							} elseif ($cname == static::VT_ENDFOREACH) {
								$pos = $j;
								break;
							}
						}
						
						if ($pos === NULL)
							$this -> parseError("Unexpected end of file, expected \"endforeach\"");

						$children = Collection::slice($tokencollection, $i + 1, $pos);
						$i = $pos;
						
						$nodes[] = new Nodes\ForeachNode($condition, $this -> formLogicalTree($children));
						
						break;
					
					
					
					
					
					case static::VT_WHILE:
						
						if (($args = $tokencollection[++$i]) -> name != static::VT_ARGS)
							$this -> unexpectedTokenError($args, static::VT_ARGS);
						if (($cont = $tokencollection[++$i]) -> name != static::VT_CONTINUE)
							$this -> unexpectedTokenError($cont, static::VT_CONTINUE);
							
						$condition = $args -> data;
						$count = $tokencollection -> length();
						$pos = NULL;
						
						$level = 0;
						
						for($j = $i; $j < $count; $j++) {
							$cname = $tokencollection[$j] -> name;
							if ($level > 0) {
								if ($cname == static::VT_ENDWHILE)
									$level--;
							} elseif ($cname == static::VT_WHILE) {
								$level++;
							} elseif ($cname == static::VT_ENDWHILE) {
								$pos = $j;
								break;
							}
						}
						
						if ($pos === NULL)
							$this -> parseError("Unexpected end of file, expected \"endwhile\"");

						$children = Collection::slice($tokencollection, $i + 1, $pos);
						$i = $pos;
						
						$nodes[] = new Nodes\WhileNode($condition, $this -> formLogicalTree($children));
						
						break;







					
					case static::VT_SWITCH:
					case static::VT_CASE:
					case static::VT_DEFAULT:
					case static::VT_ENDSWITCH:
						break;				
					
				}
				
			}
			
			return $nodes;
				
		}
		
		
	}
	
?>
