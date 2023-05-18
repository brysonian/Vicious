<?php

namespace Vicious;


/**
 * Container for:
 * 	route pattern
 * 	callback
 * 	route regex (made from the pattern)
 * 	parsed params
 */
class Route
{
	private $pattern			= false;
	private $callback	 		= false;
	private $regex				= false;
	private $params				= array();

 function __construct($_pattern, $_regex, $_callback) {
		$this->pattern			= $_pattern;
		$this->regex				= $_regex;
		$this->callback			= $_callback;
	}

	public function execute($route_base='') {
		if (is_null($this->callback) || $this->callback === false) throw new UndefinedCallback();
		if (is_string($this->callback) && strpos($this->callback, '\\') !== false) {
			if (!function_exists($this->callback)) {
				$file_name = str_replace('\\', DIRECTORY_SEPARATOR, $route_base
										 . DIRECTORY_SEPARATOR
										 . substr($this->callback, 0, strrpos($this->callback, "\\"))
										 . '.php');
				if (file_exists($file_name)) require $file_name;
			}
		}
		return call_user_func_array($this->callback, array_values($this->params));
	}

	public function callback() { return $this->callback; }
	public function regex() { return $this->regex; }
	public function pattern() { return $this->pattern; }

	public function params() { return $this->params; }
	public function set_params($p) { $this->params = $p; }
}

class UndefinedCallback extends ViciousException {}

