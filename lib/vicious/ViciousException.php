<?php
declare(encoding='UTF-8');

namespace vicious {


class ViciousException extends \Exception {
	
// ===========================================================
// - Constructor
// ===========================================================
	function __construct($message='', $code=0) {
		parent::__construct($message, $code);
	}

// ===========================================================
// - Accessors
// ===========================================================
	function message()					{ return $this->getMessage(); }
	function code()							{ return $this->getCode(); }
	function file()							{ return $this->getFile(); }
	function line()							{ return $this->getLine(); }
	function trace()	 					{ return $this->getTrace(); }
	function trace_as_string()	{ return $this->getTraceAsString(); }
}

class InvalidStatement extends ViciousException {}

}

?>