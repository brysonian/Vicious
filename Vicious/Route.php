<?php

namespace Vicious;


/**
 * Fairly dumb container for:
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

	public function execute() {
		if (is_null($this->callback) || $this->callback === false) throw new CallbackUndefined();

		if (is_string($this->callback) && strpos($this->callback, '\\') !== false) {
			if (!function_exists($this->callback)) {
				$spec = explode('\\', $this->callback);
				Vicious::autoload($spec[0]);
			}
		}
		return call_user_func_array($this->callback, $this->params);
	}

	public function callback() { return $this->callback; }
	public function regex() { return $this->regex; }
	public function pattern() { return $this->pattern; }

	public function params() { return $this->params; }
	public function set_params($p) { $this->params = $p; }
}

class CallbackUndefined extends ViciousException {}

