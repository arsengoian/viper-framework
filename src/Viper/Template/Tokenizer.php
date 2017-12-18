<?php

namespace Viper\Template;

use Viper\Support\Collection;

abstract class Tokenizer extends Collection {

	const VT_HTML = 1;
	const VT_AT = 2;
	const VT_VAR = 3;

	const VT_ARGS = 4;
	const VT_CONTINUE = 5;

	const VT_EMBEDDED_OPEN = 6;
	const VT_EMBEDDED_EXEC = 7;
	const VT_EMBEDDED_END = 8;
	const VT_ARRAY_OPEN = 9;
	const VT_ARRAY_CLOSE = 10;
	const VT_FUNCTION_OPEN = 11;
	const VT_FUNCTION_CLOSE = 12;
	const VT_HELPER_OPEN = 13;
	const VT_HELPER_CLOSE = 14;
	const VT_HELPER_CONTINUE = 15;
	const VT_ENDHELPER = 16;

	const VT_IF = 17;
	const VT_ELSEIF = 18;
	const VT_ELSE = 19;
	const VT_ENDIF = 20;
	const VT_FOR = 21;
	const VT_ENDFOR = 22;
	const VT_FOREACH = 23;
	const VT_ENDFOREACH = 24;
	const VT_WHILE = 25;
	const VT_ENDWHILE = 26;
	const VT_SWITCH = 27;
	const VT_CASE = 28;
	const VT_DEFAULT = 29;
	const VT_ENDSWITCH = 30;



	protected $raw;
	protected $file;

	protected $line = 0;
	protected $position = 0;


	abstract protected function parse();

	function __construct(string $file, array $data, $view) {
		$this -> file = $file;
		$this -> raw = file_get_contents($file);
		$this -> view = $view;
		parent::__construct($data);
	}

	public function unexpectedTokenError(int $tok, int $exp = NULL) {
		throw new UnexpectedTokenError($tok, $this -> file, $this -> line, $this -> position, $exp);
	}

	public function parseError(string $err) {
		throw new ParseError($err, $this -> file, $this -> line, $this -> position);
	}



	protected function tokenize(string $raw) : Collection {

		$tokens = new Collection();

		$lasttoken = new Token(0, 0, 0, 0, 0);
		$accept = 'all';

		for ($i = 0; $i < strlen($raw); $i++) {


			// Start tokenizing loop
			$char = $raw[$i];


			// Check what the last token is expecting
			switch ($accept) {


				case "embedded":

					$end = $this -> findEmbeddedEnd($i, $lasttoken);
					$args = new Token(
						self::VT_EMBEDDED_EXEC,
						$this -> line,
						$this -> position,
						$i,
						$end -> absposition - $i,
						trim(substr($raw, $i, $end -> absposition - $i))
					);

					$tokens[] = $args;
					$tokens[] = $end;
					$lasttoken = $end;

					$this -> line = $end -> line;
					$this -> position = $end -> position + 2;
					$i = $end -> absposition + 2;
					$accept = 'all';

					break;



				case "array":

					$end = $this -> findArrayEnd($i, $lasttoken);
					$args = new Token(
						self::VT_ARGS,
						$this -> line,
						$this -> position,
						$i,
						$end -> absposition - $i,
						trim(substr($raw, $i, $end -> absposition - $i))
					);

					$tokens[] = $args;
					$tokens[] = $end;
					$lasttoken = $end;

					$this -> line = $end -> line;
					$this -> position = $end -> position + $end -> length;
					$i = $end -> absposition + $end -> length;
					$accept = 'all';

					break;



				case "helper":

					$end = $this -> findHelperEnd($i, $lasttoken);
					$args = new Token(
						self::VT_ARGS,
						$this -> line,
						$this -> position,
						$i,
						$end -> absposition - $i,
						trim(substr($raw, $i, $end -> absposition - $i))
					);

					$tokens[] = $args;
					$tokens[] = $end;
					$lasttoken = $end;

					$this -> line = $end -> line;
					$this -> position = $end -> position + $end -> length;
					$i = $end -> absposition + $end -> length;
					$accept = 'all';

					break;



				case "function":

					$end = $this -> findFunctionEnd($i, $lasttoken);
					$args = new Token(
						self::VT_ARGS,
						$this -> line,
						$this -> position,
						$i,
						$end -> absposition - $i,
						trim(substr($raw, $i, $end -> absposition - $i))
					);

					$tokens[] = $args;
					$tokens[] = $end;
					$lasttoken = $end;

					$this -> line = $end -> line;
					$this -> position = $end -> position + $end -> length;
					$i = $end -> absposition + $end -> length;
					$accept = 'all';

					break;


				// If outside any logical constructions and in pure HTML
				default:

					if ($char === PHP_EOL) {
						$this -> line++;
						$this -> position = 0;
						break;
					}

					if ($ret = $this -> findAnything($i)) {

						$t = $ret['t'];
						$len = $ret['len'];
						$data = $ret['data'];

						$newtoken = new Token($t, $this -> line, $this -> position, $i, $len, $data);
						if ($lasttoken -> absposition != $i)
							$html = new Token(
								self::VT_HTML,
								$lasttoken -> line,
								$lasttoken -> position + $lasttoken -> length,
								$lasttoken -> absposition + $lasttoken -> length,
								$i - $lasttoken -> absposition - $lasttoken -> length,
								substr(
									$raw,
									$lasttoken -> absposition + $lasttoken -> length,
									$i - $lasttoken -> absposition - $lasttoken -> length
								)
							);

						// if opening tags, switch to different logic
						switch ($t) {

							case self::VT_EMBEDDED_OPEN:
								$accept = "embedded";
								break;

							case self::VT_ARRAY_OPEN:
								$accept = "array";
								break;

							case self::VT_FUNCTION_OPEN:
							case self::VT_IF:
							case self::VT_ELSEIF:
							case self::VT_FOR:
							case self::VT_FOREACH:
							case self::VT_WHILE:
							case self::VT_SWITCH:
							case self::VT_CASE:
								$accept = "function";
								break;

							case self::VT_HELPER_OPEN:
								$accept = "helper";
								break;

						}

						$i += $len - 1;

						$lasttoken = $newtoken;
						if (isset($html))
							$tokens[] = $html;

						$tokens[] = $newtoken;

					}

			}


			// End loop
			if ($i < strlen($raw) - 1)
				$this -> position++;
				// TODO fix adding manual data

		}

		// Add HTML to the end if needed
		if ($lasttoken -> absposition != $i)
			$tokens[] = new Token(
				self::VT_HTML,
				$lasttoken -> line,
				$lasttoken -> position + $lasttoken -> length,
				$lasttoken -> absposition + $lasttoken -> length,
				$i - $lasttoken -> absposition,
				substr($raw, $lasttoken -> absposition + $lasttoken -> length, $i)
			);


		if ($accept != 'all')
			$this -> parseError('Unexpected end of file');



		return $tokens;

	}







	protected function findAnything(int $pos) {

		$ch = $this -> raw[$pos];
		$t = $len = NULL;

		if ($ch == '<' || $ch == '@') {

			if ($ch == '<') {

				if ($this -> raw[$pos + 1] == '@') {

					$t = self::VT_EMBEDDED_OPEN;
					$len = 2;

				} else return NULL;

			} else {

				if ($this -> raw[++$pos] == '@') {

					$t = self::VT_AT;
					$len = 2;

				} else {

					$token = "";
					while (isset($this -> raw[$pos]) && preg_match('/[A-Za-z0-9_]/', $char = $this -> raw[$pos++]))
						$token .= $char;

					if (isset($this -> raw[$pos - 1]) && preg_match('/[:;\\[({]/', $char = $this -> raw[$pos - 1]))
						$token .= $char;


					$len = strlen($token);
					$stoken = substr($token, 0, $len - 1);
					$tokenend = $token[$len - 1];

					if ($tokenend == '(') {

						if ($stoken == "if") {

							$t = self::VT_IF;

						} elseif ($stoken == "elseif") {

							$t = self::VT_ELSEIF;

						} elseif ($stoken == "for") {

							$t = self::VT_FOR;

						} elseif ($stoken == "foreach") {

							$t = self::VT_FOREACH;

						} elseif ($stoken == "while") {

							$t = self::VT_WHILE;

						} elseif ($stoken == "switch") {

							$t = self::VT_SWITCH;

						} elseif ($stoken == "case") {

							$t = self::VT_CASE;

						} else {

							$t = self::VT_FUNCTION_OPEN;
							$data = $stoken;

						}

					} elseif ($tokenend == '[') {

						$t = self::VT_ARRAY_OPEN;
						$data = $stoken;

					} elseif ($tokenend == '{') {

						$t = self::VT_HELPER_OPEN;
						$data = $stoken;

					} else {

						if (preg_match('/else:?/', $token))
							$t = self::VT_ELSE;
						elseif (preg_match('/endif;?/', $token))
							$t = self::VT_ENDIF;
						elseif (preg_match('/endforeach;?/', $token))
							$t = self::VT_ENDFOREACH;
						elseif (preg_match('/endfor;?/', $token))
							$t = self::VT_ENDFOR;
						elseif (preg_match('/endwhile;?/', $token))
							$t = self::VT_ENDWHILE;
						elseif (preg_match('/default:?/', $token))
							$t = self::VT_DEFAULT;
						elseif (preg_match('/endswitch;?/', $token))
							$t = self::VT_ENDSWITCH;
						elseif (preg_match('/end;?/', $token))
							$t = self::VT_ENDHELPER;
						else {
							if ($tokenend == ':')
								$this -> unexpectedTokenError(':');
							else {
								$t = self::VT_VAR;
								$data = $tokenend == ';' ? $stoken : $token;
							}
						}

					}
				}

			}

			$arr =  [
				't' => $t,
				'len' => $t === self::VT_EMBEDDED_OPEN || $t === self::VT_AT ? $len : $len + 1,
				'data' => NULL
			];
			if (isset($data))
				$arr['data'] = $data;

			return $arr;

		} else return FALSE;
	}



	public function findEmbeddedEnd(int $pos, Token $last) : ?Token {

		$position = $last -> position;
		$line = $last -> line;

		for ($i = $pos; $i < strlen($raw = $this -> raw); $i++) {

			if ($raw[$i] == PHP_EOL) {
				$line++;
				$position = 0;
			}
			if (isset($raw[$i + 1]))
				if ($raw[$i] == '@' && $raw[$i + 1] == '>')
					return new Token(self::VT_EMBEDDED_END, $line, $position, $i, 2);

			$position++;
		}
		$this -> parseError("Unexpected end of file, @> expected");
		return NULL;

	}



	public function findArrayEnd(int $pos, Token $last) : ?Token {

		$position = $last -> position;
		$line = $last -> line;

		for ($i = $pos; $i < strlen($raw = $this -> raw); $i++) {

			if ($raw[$i] == PHP_EOL) {
				$line++;
				$position = 0;
			}
			if ($raw[$i] == ']') {
				if (isset($raw[$i + 1]) && $raw[$i + 1] == ';')
					return new Token(self::VT_ARRAY_CLOSE, $line, $position, $i, 2);
				else return new Token(self::VT_ARRAY_CLOSE, $line, $position, $i, 1);
			}
			$position++;
		}
		$this -> parseError("Unexpected end of file, ] expected");
		return NULL;

	}



	public function findHelperEnd(int $pos, Token $last) : ?Token {

		$position = $last -> position;
		$line = $last -> line;

		for ($i = $pos; $i < strlen($raw = $this -> raw); $i++) {

			if ($raw[$i] == PHP_EOL) {
				$line++;
				$position = 0;
			}
			if ($raw[$i] == '}') {
				if (isset($raw[$i + 1]) && $raw[$i + 1] == ';')
					return new Token(self::VT_HELPER_CLOSE, $line, $position, $i, 2);
				elseif (isset($raw[$i + 1]) && $raw[$i + 1] == ':')
					return new Token(self::VT_HELPER_CONTINUE, $line, $position, $i, 2);
			}
			$position++;
		}
		$this -> parseError("Unexpected end of file, \"};\" expected");
		return NULL;

	}



	public function findFunctionEnd(int $pos, Token $last) : ?Token {

		$position = $last -> position;
		$line = $last -> line;

		if ($last -> name == self::VT_FUNCTION_OPEN) {
			$end = ';';
			$cl = self::VT_FUNCTION_CLOSE;
		} else {
			$end = ':';
			$cl = self::VT_CONTINUE;
		}


		for ($i = $pos; $i < strlen($raw = $this -> raw); $i++) {

			if ($raw[$i] == PHP_EOL) {
				$line++;
				$position = 0;
			}
			if ($raw[$i] == ')' && isset($raw[$i + 1])) {

				if ($raw[$i + 1] == $end)
					return new Token($cl, $line, $position, $i, 2);

			}
			$position++;
		}
		$this -> parseError("Unexpected end of file, \")$end\" expected");
		return NULL;

	}



}

