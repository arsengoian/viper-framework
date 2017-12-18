<?php

namespace Viper\Template;

class ParseError extends TemplateError {

	public function __construct(string $err, string $file, string $line, string $position) {
		parent::__construct("ParseError", $err, $file, $line, $position);
	}

}

