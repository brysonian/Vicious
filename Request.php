<?php
declare(encoding='UTF-8');

namespace vicious
{

require_once(__DIR__.'/ViciousException.php');
require_once(__DIR__.'/Config.php');

class Request {

	private $accept			= false;
	private $method			= false;
	private $uri				= false;


	public function __construct($uri=false, $method=false) {
		if ($uri === false) {
			if (isset($_SERVER['REQUEST_URI'])) $this->uri = $_SERVER['REQUEST_URI'];
		} else {
			$this->uri = $uri;
		}
		if ($method !== false) $this->method = $method;
		if (options('methodoverride')) $this->method_override();
	}


	/**
	 * Method fix for browsers
	 */
	private function method_override() {
		if (!array_key_exists('REQUEST_METHOD', $_SERVER)) return false;

		if (isset($_POST['_method'])) {
			$verbs = array('GET', 'POST', 'DELETE', 'PUT');
			$m = strtoupper($_POST['_method']);
			if (in_array($m, $verbs)) {
				$_SERVER['REQUEST_METHOD'] = $m;
				unset($_POST['_method']);
			}
		}
		$this->method = $_SERVER['REQUEST_METHOD'];
		return $_SERVER['REQUEST_METHOD'];
	}

	/**
	 * Accessor
	 */
	public function __get($k) {
		switch($k) {
			case 'agent':
			case 'user_agent': return $_SERVER['HTTP_USER_AGENT'];

			case 'accept':
				if (!$this->accept) {
					$this->accept = array();
					$p = explode(';', $_SERVER['HTTP_ACCEPT']);
					$p = explode(',', $p[0]);
					foreach($p as $v) {
						$this->accept[] = trim($v);
					}
				}
				return $this->accept;

			case 'uri':
			case 'request_uri':
				return $this->uri;

			case 'method':
			 	return $this->method;

			default:
				throw new UnknownProperty();
				break;
		}
	}

}

class UnknownProperty extends ViciousException {}

}

namespace
{
	function request($k=false) {
		static $instance;
		if (!$instance) $instance = new vicious\Request();

		if ($k !== false) {
			return $instance->__get($k);
		} else {
			return $instance;
		}
	}
}

?>