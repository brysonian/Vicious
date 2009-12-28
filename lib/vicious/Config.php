<?php
declare(encoding='UTF-8');

namespace vicious {

require_once(__DIR__.'/ViciousException.php');

define('DEVELOPMENT', 'DEVELOPMENT');
define('PRODUCTION', 'PRODUCTION');

class Config
{
	private $props = array();

	private function Config() {
		$this->environment		= DEVELOPMENT;
		$this->methodoverride = true;
		$this->root						= '';
		$this->app_file				= false;
		$this->app_root				= false;
		$this->views					= false;
		$this->cli						= (php_sapi_name() == 'cli');		
	}
	
	/**
	 * Return the same instance of this class.
	 */
	public static function instance() {
		static $instance;
		if (!$instance) {
			$c = get_called_class();
			$instance = new $c();
		}
		return $instance;
	}

	
// ===========================================================
// - MAGICAL ACCESSORS FOR SETTING PROPERTIES
// ===========================================================
	public function __set($k, $v) {
		$this->props[$k] = $v;
	}
	
	public function __get($k) {
		# set defaults for items dependent on the app_file
		if ($k == 'views' && $this->props['views'] == false) {
			$this->views = $this->app_root.'/views';
			return $this->props['views'];
		
		} else if ($k == 'app_root' && $this->props['app_root'] == false) {
			if ($this->props['app_file'] == false) throw new AppFileUndefined();
			$this->app_root = realpath(dirname($this->props['app_file']).'/..');
			return $this->props['app_root'];
		}
		
		return isset($this->props[$k]) ? $this->props[$k] : false;
	}
}

class AppFileUndefined extends ViciousException {}
}

// ===========================================================
// - TOSS FUNCS IN THE GLOBAL NAMESPACE FOR CONVENIENCE
// ===========================================================
namespace {
	function set($key, $value=false) {
		$i = vicious\Config::instance();
		if (is_array($key)) {
			foreach($key as $k => $v) {
				$i->__set($k, $v);
			}
		} else {
			$i->__set($key, $value);
		}
	}

	function enable($key) {
		if (is_array($key)) {
			foreach($key as $k => $v) set($k, true);
		} else {
			set($key, true);
		}
	}

	function disable($key) {
		if (is_array($key)) {
			foreach($key as $k => $v) set($k, false);
		} else {
			set($key, false);
		}
	}

	function options($key) {
		return vicious\Config::instance()->$key;
	}
}

?>
