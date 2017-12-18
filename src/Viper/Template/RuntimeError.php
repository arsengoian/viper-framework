<?php

namespace Viper\Template;

class RuntimeError extends TemplateError {

	public function __construct(string $err, string $file, string $line, string $position) {
		parent::__construct("RuntimeError", $err, $file, $line, $position);
	}

}

