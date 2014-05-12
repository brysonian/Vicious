<?php

/**
 * Toss convenience funcs into the global namespace.
 * All of these are available as instance methods on Application
 * but put here to make them easier to get reach. In other words, just syntactic sugar.
 * To disable this global namespace pollution set:
 *	define('OMIT_GLOBAL_HELPERS', true);
 * Before including the Application class.
 */
namespace
{
	# get a static instance of a Application
	function vicious() { return Vicious\Vicious::instance(); }

	function get($pattern, $handler)		{ vicious()->get($pattern, $handler); }
	function put($pattern, $handler)		{ vicious()->put($pattern, $handler); }
	function post($pattern, $handler)		{ vicious()->post($pattern, $handler); }
	function delete($pattern, $handler)	{ vicious()->delete($pattern, $handler); }
	function r($pattern) {	return array('regex' => $pattern); }



	function params($p=false)						{	return vicious()->params($p); }

	function before($handler)						{	vicious()->before($handler); }

	function error($handler)						{	vicious()->error($handler); }
	function not_found($handler)				{	vicious()->not_found($handler); }

	function configure($environment, $handler=false)	{	vicious()->configure($environment, $handler); }


	# RESPONSE
	function status($s) { vicious()->status($s); }
	function redirect($loc=false, $code=false) { vicious()->redirect($loc, $code); }

	# REQUEST
	function request($k=false) { return ($k === false) ? vicious()->request() : vicious()->request()->$k; }

	# VIEW
	/**
	 * Return a static instance of View Subclass. This works as a singleton and provides easy access to the output object
	 * while not requiring that the class itself be a singleton which is undesirable.
	 *
	 * All classes that want to act as views for vicious should follow this convention.
	 */
	function pjson() {
		static $instance;
		if (!$instance) $instance = new Vicious\PJSON();
		return $instance;
	}

	function phtml($template=null, $layout=null) {
		static $instance;
		if (!$instance) $instance = new Vicious\PHTML();
		if ($template !== null) $instance->set_template($template);
		if ($layout !== null) $instance->set_layout($layout);
		return $instance;
	}

	function pxml($template=null, $layout=null) {
		static $instance;
		if (!$instance) $instance = new Vicious\PXML();
		if ($template !== null) $instance->set_template($template);
		if ($layout !== null) $instance->set_layout($layout);
		return $instance;
	}

	# CONFIG
	function set($key, $value=false) {
		$i = vicious()->config();
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
		return vicious()->config()->$key;
	}


}

