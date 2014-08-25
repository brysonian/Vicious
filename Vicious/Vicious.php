<?php

namespace Vicious;

class Vicious
{

	protected $router;
	protected $route;
	protected $request;
	protected $config;
	protected $before = array();

	protected $error_handler = false;
	protected $not_found_handler = false;
	protected $error_shown = false;

	protected $config_handlers = array();

	/*
   * PSR-0 autoloader
   */
	public static function autoload($class_name) {
		static $base_dir;
		if (!$base_dir) {
			$this_class = str_replace(__NAMESPACE__.'\\', '', __CLASS__);
			$base_dir = __DIR__;
			if (substr($base_dir, -strlen($this_class)) === $this_class) {
				$base_dir = substr($base_dir, 0, -strlen($this_class));
			}
		}
		$class_name = ltrim($class_name, '\\');
		$file_name  = $base_dir;
		$namespace = '';
		if ($last_pos = strripos($class_name, '\\')) {
			$namespace = substr($class_name, 0, $last_pos);
			$class_name = substr($class_name, $last_pos + 1);
			$file_name  .= str_replace('\\', DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
		}
		$file_name .= $class_name . '.php';
		if (file_exists($file_name)) require $file_name;
	}

	public static function RegisterAutoloader() {
		spl_autoload_register(__NAMESPACE__ . "\\Vicious::autoload");
	}


	public static function Free() {
		require(__DIR__ . DIRECTORY_SEPARATOR . 'Free.php');
	}


	public static function Autorun() {
		$s = self::instance();
		register_shutdown_function(array($s, 'run'));
	}

	public function run() {
		try {
			$this->request = new Request(false, false, $this->config->methodoverride);
			$this->dispatch($this->request);
		} catch (\Exception $e) {
			$this->handle_error($e);
		}
	}


	public function __construct() {
		# handle errors our way
		set_error_handler(array(&$this, 'default_error_handler'));
		set_exception_handler(array(&$this, 'default_exception_handler'));

		# need a router instance
		$this->router = new Router();
		$this->config = new Config();
	}

	/**
	 * Singleton instance method
	 */
	public static function instance() {
		static $instance;
		if (!$instance) {
			$instance = new Vicious;
		}
		return $instance;
	}

	/**
	 * Dispatch is the main point of execution.
	 */
	public function dispatch($uri, $verb=false) {
		if ($uri instanceof Request) {
			if ($verb === false) $verb = $uri->method;
			$uri = $uri->uri;
		}

		if ($verb === false) $verb = $this->request->method;

		# first run configs
		if (isset($this->config_handlers[$this->config->environment]) && is_array($this->config_handlers[$this->config->environment])) {
			foreach($this->config_handlers[$this->config->environment] as $handler) {
				call_user_func($handler, $this->config);
			}
		}

		if (isset($this->config_handlers['ALL']) && is_array($this->config_handlers['ALL'])) {
			foreach($this->config_handlers['ALL'] as $handler) {
				call_user_func($handler, $this->config);
			}
		}

		# find the right route
		$this->route = $this->router->route_for_request($verb, $uri);

		# run filters and catch output
		ob_start();
		foreach($this->before as $filter) {
			call_user_func($filter);
		}
		$filter_output = ob_get_clean();

		# exec the method
		$out = $this->route->execute($this->config->routes);

		# show the results
		if ($out != null) {
			if (is_string($out)) {
				echo $filter_output;
				echo $out;
			} else if ($out instanceof View\Renderable) {
				$out->template_dir = $this->config->templates;
				$out->send_content_type_header();
				echo $filter_output;
				$out->render();
			}
		}

	}


	public function get($pattern, $handler)			{ return $this->router->get($pattern, $handler); }
	public function put($pattern, $handler)			{ return $this->router->put($pattern, $handler); }
	public function post($pattern, $handler)		{ return $this->router->post($pattern, $handler); }
	public function delete($pattern, $handler)	{ return $this->router->delete($pattern, $handler); }

	/**
	 * Add a filter before
	 */
	public function before($handler)	{ $this->before[] = $handler; }

	/**
	 * Set a function to be called as setup depending on the environment
	 */
	public function configure($environment, $handler=false) {
		if ($handler == false) {
			$handler = $environment;
			$environment = 'ALL';
		}
		$this->config_handlers[$environment][] = $handler;
	}

	/**
	 * Errors
	 */
	public function error($e) { $this->error_handler = $e;	}
	public function not_found($h) { $this->not_found_handler = $h;	}

	public function handle_error($e) {
		if ($this->error_shown) return;
		$this->error_shown = true;
		$logo = 'data:image/png;base64,' . base64_encode(file_get_contents(__DIR__.'/images/vicious.png'));
		if (!($e instanceof ViciousException)) $e = ViciousException::fromException($e);
		if ($e instanceof NotFound) {
			$this->status(404);
			if ($this->config->environment != Config::PRODUCTION) {
				if ($this->config->error_style == Config::JSON_ERROR_STYLE) {
					$out = json_encode(array('error' => $this->request->uri . ' Not Found'));
				} else {
					$out = "<!DOCTYPE html>
					<html><head><title>404 Not Found</title>
					<style type='text/css'>
	        	body { font-family:helvetica,arial;font-size:18px; margin:50px; letter-spacing: .1em;}
	        	div, h1 {margin:0px auto;width:500px;}
						h1 { background-color:#FC63CD; color: #FFF; padding:125px 0px 10px 10px; background-image:url($logo); background-repeat:no-repeat;width:490px;}
						h2 { background-color:#888; color:#FFF; margin: 0px; padding: 3px 10px;}
						pre { background-color:#FF0; color:#000; padding: 10px; margin: 0px; white-space: pre-wrap;}
					</style>
					</head>
					<body>
					<h1>I dunno what you&rsquo;re after.</h1>
					<div><h2>Try this:</h2><pre>get('".$this->request->uri."', function() {
	return 'Hello World';
	});
	</pre></div>
					</body></html>";
				}
			} else if ($this->not_found_handler) {
				$out = call_user_func($this->not_found_handler, $e);
			} else {
				# standard apache 404 page
				$out = '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN"><html><head><title>404 Not Found</title></head><body><h1>Not Found</h1><p>The requested URL '.$this->request->uri.' was not found on this server.</p></body></html>';
			}

		} else {
			$this->status(500);
			if ($this->config->environment != Config::PRODUCTION) {
				if ($this->config->error_style == Config::JSON_ERROR_STYLE) {
					$out = json_encode(array('error' => array(
						'class' 	=> substr(get_class($e), strrpos(get_class($e), "\\") + 1),
						'file' 		=> pathinfo($e->file(), PATHINFO_BASENAME) . ':' . $e->line(),
						'message' => $e->message(),
						'uri' 		=> $this->request->uri,
					)));
				} else {
					$t = $e->trace();
					$backtrace = explode("\n", $e->trace_as_string());
					array_shift($backtrace);
					foreach($backtrace as $k => $v) {
						$i = strpos($v, ':');
						$backtrace[$k] = substr_replace($v, '<br>&nbsp;&nbsp;', $i, 1);
					}
					$backtrace = join('</pre></li><li><pre>', $backtrace);


					$vars = array('GET' => $_GET, 'POST' => $_POST, 'SESSION' => isset($_SESSION) ? $_SESSION : array(), 'SERVER' => $_SERVER);

					# make server path prettier
					$vars['SERVER'] = str_replace(':', '<br>', $vars['SERVER']);

					foreach($vars as $type => $sg) {
						//$html = "";
						$html = "<h3 onclick='toggle(\"type-$type\", \"table\")'>$type</h3>
											<table class='more' id='type-$type'>";
						if (empty($sg)) {
							$html .= "<tr><td colspan='2'>No $type data.</td></tr></table>";
						} else {
							foreach($sg as $k => $v) {
								if (is_array($v)) {
									ob_start();
									var_export($v);
									$v = nl2br(ob_get_clean());
								}
								$html .= "<tr><td class='key'>$k</td><td>".wordwrap($v, 150, "<br />\n", true)."</td></tr>";
							}
							$html .= '</table>';
						}
						$vars[$type] = $html;
					}

					$out = sprintf("<!DOCTYPE html>
					<html><head><title>500 Internal Server Error</title>
					<style type='text/css'>
	        	body { font-family:helvetica,arial;font-size:18px; margin:50px; letter-spacing: .1em;}
						#c { width: 960px; margin:0  auto; position: relative; }
						header { padding-top: 75px; background: black url($logo) no-repeat 50%% 0; background-size: 300px;}
						header .info { padding: 10px; background-color: white; }
						header h1 {
							padding: 10px;
						}
						header li { margin-top: 5px; padding: 5px 0; border-top: 1px solid black; border-bottom: none;}
						h1, h2 { margin:0; }
						h2 { font-size: 16px; color: white; }
						h2, h3 { font-weight: normal; }
						h3 { color:#000; border: 1px solid black; margin: 20px 0 0 0; padding: 3px 10px;}
						pre { background-color:#FF0; color:#000; padding: 10px; margin: 0px; font-size:12px; line-height: 1.5em; white-space: pre-wrap;}
						ul {margin:0px; padding: 0px; list-style: none; }
						li { border-bottom: 1px solid black; }
						table { width: 960px; border: 0px; border-spacing: 0px;  }
						table th.type { font-size: 21px; width: 110px; border-right: 1px solid white;}
						th { text-align: left; background-color:#888; color:#FFF; padding: 0px 10px; height: 30px; font-weight: normal; font-size: 14px;}
						td { border-bottom: 1px solid white; background-color:#FF0; color:#000; padding: 10px; margin: 0px; font-size:12px; line-height: 1.5em; }
						td.key { width: 170px; border-right: 1px solid white; }
						td.blank { background-color: white; border: none; }
						tr.empty td { border: none; }
						.more { display: none; }
						.backtrace, table, header .info {
							border: 1px solid black;
							border-top: none;
						}
						header pre {
							margin-top:7px;
						}
					</style>
					<script type='text/javascript'>
					function toggle(id, type) {
						var d = document.getElementById(id).style.display;
						if (d != type) document.getElementById(id).style.display = type;
						else document.getElementById(id).style.display = 'none';
					}
					</script>
					</head>
					<body>
					<div id='c'>
					<header>
						<div class='info'>
							<h1>%s</h1>
							<ul>
								<li>file: %s(%s)</li>
								<li>uri: %s</li>
								<li><pre>%s</pre></li>
							</ul>
						</div>
					</header>

					<h3>BACKTRACE</h3>
					<ul class='backtrace'><li><pre>%s</pre></li></ul>
					%s
					%s
					%s
					%s
					<div style='clear: both'></div>
					</div></body></html>",
						substr(get_class($e), strrpos(get_class($e), "\\") + 1),
						pathinfo($e->file(), PATHINFO_BASENAME),
						$e->line(),
						$this->request->uri,
						$e->message(),
						$backtrace,
						$vars['GET'],
						$vars['POST'],
						$vars['SESSION'],
						$vars['SERVER']
					);
				}
			} else if ($this->error_handler) {
				$out = call_user_func($this->error_handler, $e);
			} else {
				# standard default 500 page
				$out = '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN"><html><head><title>500 Internal Server Error</title></head><body><h1>Internal Server Error</h1><p>Please try again later.</p></body></html>';
			}
		}

		if ($out != null) {
			if (is_string($out)) {
				die($out);
			} else if ($out instanceof View\Renderable) {
				$out->send_content_type_header();
				$out->render();
			}
		}
	}

	public function default_error_handler($errno, $errstr, $errfile, $errline) {
		if (libxml_use_internal_errors()) {
			$err = libxml_get_last_error();
			if ($err) {
				$exp = LibXMLException::fromLibXMLError($err);
				libxml_clear_errors();
				$this->default_exception_handler($exp);
				return;
			}
		}
		$this->default_exception_handler(new InvalidStatement($errstr, $errno, $errfile, $errline));
	}

	public function default_exception_handler($e) {
		$l = ob_get_level();
		while($l--) ob_end_clean();
		$this->handle_error($e);
		exit;
	}


	public function params($p=false) {
		if (!$this->route) return false;
		$params = $this->route->params();
		if ($p === false) return $params;
		if (isset($params[$p])) {
			return $params[$p];
		} else {
			return false;
		}
	}

	public function route() {
		return $this->route;
	}

	public function request() {
		return $this->request;
	}

	public function config() {
		return $this->config;
	}

	// ===========================================================
	// - RESPONSE HELPERS
	// ===========================================================
	public function status($s) {
		if (is_numeric($s))	{
			switch($s) {
				case 404:
					header("HTTP/1.0 404 Not Found");
					header("Status: 404 Not Found");
					return;

				case 500:
					header('HTTP/1.1 500 Internal Server Error');
					header("Status: 500 Internal Server Error");
					return;

				default:
					header("Status: $s");
					return;
			}
		}
	}

	public function redirect($loc=false, $code=false) {
		if ($code !== false) status($code);
		$loc = $loc ? $loc : '/';
		header("Location: $loc");
		exit();
	}

}