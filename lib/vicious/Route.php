<?php
declare(encoding='UTF-8');

namespace vicious
{

require_once(__DIR__.'/ViciousException.php');

/**
 * Fairly dumb container for:
 * 	route pattern
 * 	handler
 * 	route regex (made from the pattern)
 * 	parsed params
 */
class Route
{
	private $pattern			= false;
	private $handler	 		= false;
	private $regex				= false;
	private $params				= array();
	
 function __construct($_pattern, $_regex, $_handler) {
		$this->pattern			= $_pattern;
		$this->regex				= $_regex;
		$this->handler			= $_handler;
	}
		
	public function execute() {
		if (is_null($this->handler) || $this->handler === false) throw new HandlerUndefined();
		return call_user_func($this->handler);
	}
	
	public function handler() { return $this->handler; }
	public function regex() { return $this->regex; }
	public function pattern() { return $this->pattern; }	

	public function params() { return $this->params; }	
	public function set_params($p) { $this->params = $p; }	
}

class HandlerUndefined extends ViciousException {}

}

?>