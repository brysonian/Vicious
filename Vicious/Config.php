<?php

namespace Vicious;

class Config
{

	const DEVELOPMENT = 'DEVELOPMENT';
	const PRODUCTION = 'PRODUCTION';

	private $props = array();

	public function __construct() {
		$this->environment		= self::DEVELOPMENT;
		$this->methodoverride = true;
		$this->root						= '';
		$this->cli						= (php_sapi_name() == 'cli');
	}

// ===========================================================
// - MAGICAL ACCESSORS FOR SETTING PROPERTIES
// ===========================================================
	public function __set($k, $v) {
		$this->props[$k] = $v;
	}

	public function __get($k) {
		if ($k == 'templates' && $this->props['templates'] == false) return '';
		return isset($this->props[$k]) ? $this->props[$k] : false;
	}
}

class AppFileUndefined extends ViciousException {}
