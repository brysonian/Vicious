<?php
declare(encoding='UTF-8');

namespace vicious
{

require_once(__DIR__.'/ViciousException.php');
require_once(__DIR__.'/Route.php');


class Router
{
	protected $routes = array();
	protected $base = false;

	protected $params = array();

	function __construct() {
		$this->routes['POST']		= array();
		$this->routes['PUT']		= array();
		$this->routes['GET']		= array();
		$this->routes['DELETE']	= array(); 
	}


	public function get($pattern, $handler)			{ $this->route('GET',    $pattern, $handler); }
	public function put($pattern, $handler)			{ $this->route('PUT',    $pattern, $handler); }
	public function post($pattern, $handler)		{ $this->route('POST',   $pattern, $handler); }
	public function delete($pattern, $handler)	{ $this->route('DELETE', $pattern, $handler); }

	protected function route($verb, $pattern, $handler)	{
		# create the regex from the pattern
		if (is_array($pattern) && array_key_exists("regex", $pattern)) {
			$regex = $pattern['regex'];
			$pattern = false;
			
		} else {
			# choose regex delimiter dynamically incase a pipe is in the pattern
			$delim = '|';
			foreach(array('|', '`', '~', '^', "#") as $delim) if (strpos($pattern, $delim) === false) break;
		
			$terminate = (strpos($pattern, ':') || strpos($pattern, '*')) ? '' : '$';
		
			$regex = $delim.'^'.preg_replace_callback(
				'/\/([:|\*])?([a-zA-Z0-9_]*)/',
				array($this, 'pattern_to_regex'),
				$pattern
			).'/?'.$terminate.$delim;
		}
		$this->routes[$verb][] = new Route($pattern, $regex, $handler);
	}

	/**
	 * Callback to convert a url pattern into a regex
	 */
	protected function pattern_to_regex($matches) {
		if ($matches[1] == '*') {
			return '/?(.*)';
		} else if ($matches[1] != ':') {
			return '/?('.$matches[2].')';
		}
		return '/([^\/]+)';
	}

	/**
	 * 
	 */
	protected function url_matches_route($url, $route) {
		# make sure they match
		return (preg_match($route->regex(), $url) == 1);
	}

	protected function params_for_url_with_route($url, $route) {
		$pattern	= $route->pattern();
		$regex		= $route->regex();
		$out 			= array();

		# make sure they match
		$urlparts = array();
		
		# just in case
		if (!preg_match($regex, $url, $urlparts)) return false;
		
		# switch here if the pattern was a regex from the user,
		# just populate params with indexes
		if ($pattern == false) {
			$out = array_slice($urlparts, 1);
		} else {
			# map it
			$parts = explode('/', $pattern);
			array_shift($parts);
			array_shift($urlparts);

			foreach($parts as $k => $v) {			
				if (empty($v)) continue;
				if ($v{0} == ':' || $v{0} == '*') {
					$name = ($v{0} == '*') ? 'splat' : substr($v,1);

					# get the value
					if (array_key_exists($k, $urlparts) && ($urlparts[$k] != '')) $out[$name] = $urlparts[$k];

					# clear nulls
					if (is_null($out[$name])) unset($out[$name]);
				}
			}	
		}

		return $out;
	}
	

	/**
	* Find the best matching map for the url
	*/
	protected function match_request($verb, $url) {
		# watch out for bad verbs
		if (!array_key_exists($verb, $this->routes)) return false;

		# remove the base
		$url = substr($url, strlen($this->base()));
		if ($url{0} != '/') $url = "/$url";

		foreach($this->routes[$verb] as $route) {
			if ($this->url_matches_route($url, $route)) {
				return $route;
			}
		}
		return false;
	}
	
	/**
	* find the best route and return it
	*/
	public function route_for_request($verb, $url) {
		# trim off the query string if there is one
		if (strpos($url, '?') !== false) {
			$query = explode('?', $url);
			$url = $query[0];
		}

		# routing is here
		$active_route = $this->match_request($verb, $url);

		# if no match was found, throw
		if ($active_route === false) throw new NotFound("No mapping was found for &quot;$url&quot;.");
		
		# get the params for this url using the choosen route
		$params = $this->params_for_url_with_route($url, $active_route);
		
		# add request to params and make sure magic quotes are dealt with
		$params = $this->process_request_vars($params);
	
		$active_route->set_params($params);
		
		# return the route
		return $active_route;
	}
	

	protected function process_request_vars($params) {
		# add request to params and make sure magic quotes are dealt with
		unset($_POST['MAX_FILE_SIZE']);
		unset($_GET['MAX_FILE_SIZE']);
		$gpc = (get_magic_quotes_gpc() == 1);

		foreach(array($_GET, $_POST) as $R) {
			foreach($R as $k => $v) {
				if (!array_key_exists($k, $params)) {
					if (is_array($v)) {
						$params[$k] = array();
						foreach($v as $k2 => $v2) {
							$params[$k][$k2] = ($gpc && !is_array($v2))?stripslashes($v2):$v2;
						}
					} else {
						$params[$k] = ($gpc)?stripslashes($v):$v;
					}
				}
			}
		}
		
		# add files to params
		foreach($_FILES as $k => $v) {
			if (!array_key_exists($k, $params)) $params[$k] = array();
			$params[$k] = array_merge(
				$params[$k], 
				# TODO: easy file uploads
				$v
			);
		}
		return $params;
	}
	
	
// ===========================================================
// - ACCESSORS
// ===========================================================
	/**
	* Set the base for urls
	*/
	public function set_base($val) {
		$this->base = $val;
	}
	
	public function base() {
		return $this->base?$this->base:'/';
	}
}


// ===========================================================
// - EXCEPTIONS
// ===========================================================
class NotFound extends ViciousException {}
class UnknownController extends ViciousException {}

}

namespace 
{
	
	function r($pattern) {
		return array('regex' => $pattern);
	}

}

?>