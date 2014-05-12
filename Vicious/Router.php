<?php

namespace Vicious;

class Router
{
	protected $routes = array();

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
		if (is_array($pattern) && isset($pattern["regex"])) {
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
					#if (array_key_exists($k, $urlparts) && ($urlparts[$k] != '') && (!is_null($urlparts[$k]))) $out[$name] = $urlparts[$k];
					if (isset($urlparts[$k]) && !empty($urlparts[$k])) $out[$name] = $urlparts[$k];
				}
			}
		}

		return $out;
	}


	/**
	* Find the best matching map for the url
	*/
	protected function match_request($verb, $url) {
		if (!isset($this->routes[$verb])) return false;

		# remove the leading slash
		$url = substr($url, 1);
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
		# throw an exception if the max post size is reached
		if (array_key_exists('CONTENT_LENGTH', $_SERVER) && !empty($_POST)) {
			$pms = ini_get('post_max_size');
			$mul = substr($pms, -1);
			$mul = ($mul == 'M' ? 1048576 : ($mul == 'K' ? 1024 : ($mul == 'G' ? 1073741824 : 1)));
			if ($_SERVER['CONTENT_LENGTH'] > $mul*(int)$pms && $pms) {
				throw new MaxPostSizeExceeded("The posted data was too large. The maximum post size is: $pms.");
			}
		}



		# add request to params and make sure magic quotes are dealt with
		unset($_POST['MAX_FILE_SIZE']);
		unset($_GET['MAX_FILE_SIZE']);
		$gpc = (get_magic_quotes_gpc() == 1);

		foreach(array($_GET, $_POST) as $R) {
			foreach($R as $k => $v) {
				if (!isset($params[$k])) {
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
			if (!isset($params[$k])) {
				try {
					$uploaded_file = UploadedFile::create($v);
				} catch (UpoadedFileException $e) {
					$uploaded_file = false;
				}
				$params[$k] = $uploaded_file;
			}
		}



		return $params;
	}
}


// ===========================================================
// - EXCEPTIONS
// ===========================================================
class NotFound extends ViciousException {}
class UnknownController extends ViciousException {}
class MaxPostSizeExceeded extends ViciousException {}
