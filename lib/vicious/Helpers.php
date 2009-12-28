<?php
declare(encoding='UTF-8');

/**
 * Toss convenience funcs into the global namespace.
 * All of these are available as instance methods on Application
 * but put here to make them easier to get reach. In other words, just syntactic sugar.
 * To disable this global namespace pollution set: 
 *	define('OMIT_GLOBAL_HELPERS', true);
 * Before including the Application class.
 */
namespace {
	if (!defined('OMIT_GLOBAL_HELPERS')) {
		function get($pattern, $handler)		{ application()->get($pattern, $handler); }
		function put($pattern, $handler)		{ application()->put($pattern, $handler); }
		function post($pattern, $handler)		{ application()->post($pattern, $handler); }
		function delete($pattern, $handler)	{ application()->delete($pattern, $handler); }

		function params($p=false)						{	return application()->params($p); }

		function before($handler)						{	application()->before($handler); }

		function error($handler)						{	application()->error($handler); }
		function not_found($handler)				{	application()->not_found($handler); }
	
		function configure($environment, $handler=false)	{	application()->configure($environment, $handler); }	


		// ===========================================================
		// - RESPONSE HELPERS
		// ===========================================================
		function status($s) { application()->status($s); }
		function redirect($loc=false, $code=false) { application()->redirect($loc, $code); }
	}
}

?>