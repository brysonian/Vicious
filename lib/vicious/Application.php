<?php
declare(encoding='UTF-8');

namespace vicious
{


	require_once(__DIR__.'/Helpers.php');
	require_once(__DIR__.'/Config.php');
	require_once(__DIR__.'/Router.php');
	require_once(__DIR__.'/Renderable.php');
	require_once(__DIR__.'/Request.php');


	class Application
	{

		protected $router;
		protected $route;
		protected $before = array();

		protected $error_handler = false;
		protected $not_found_handler = false;
		protected $error_shown = false;

		protected $config_handlers = array();

		/**
		 * Initialize a vicious based app.
		 * Not strictly necessary if you don't want to use auto dispatch and
		 * want to manage your own paths.
		 */
		public static function init() {
			$inc = get_include_path();
			if (!strpos($inc, __DIR__)) set_include_path(__DIR__.PATH_SEPARATOR.$inc);

			# grab an instance to get it constructed
			$s = self::instance();
			if (options('auto_dispatch') === true) {
				register_shutdown_function(array($s, 'auto_dispatch'));
			}
		}

		public function auto_dispatch() {
			try {
				$this->dispatch(request('uri'));
			} catch (\Exception $e) {
				$this->handle_error($e);
			}
		}


		protected function __construct() {
			# set app file location
			set('app_file', $_SERVER['SCRIPT_FILENAME']);

			# handle errors our way
			set_error_handler(array(&$this, 'default_error_handler'));
			set_exception_handler(array(&$this, 'default_exception_handler'));

			# need a router instance
			$this->router = new Router();
		}

		/**
		 * Singleton instance method
		 */
		public static function instance() {
			static $instance;
			if (!$instance) {
				$instance = new Application;
			}
			return $instance;
		}

		/**
		 * Dispatch is the main point of execution.
		 */
		public function dispatch($uri, $verb=false) {
			# some PHP polymorphism here.
			if ($uri instanceof Request) {
				if ($verb === false) $verb = $uri->method;
				$uri = $uri->uri;
			}

			if ($verb === false) $verb = request('method');

			# first run configs
			if (array_key_exists('ALL', $this->config_handlers)) {
				foreach($this->config_handlers['ALL'] as $handler) {
					call_user_func($handler);
				}
			}

			if (array_key_exists(options('environment'), $this->config_handlers)) {
				foreach($this->config_handlers[options('environment')] as $handler) {
					call_user_func($handler);
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
			$out = $this->route->execute();

			# show the results
			if ($out != null) {
				if (is_string($out)) {
					echo $filter_output;
					echo $out;
				} else if ($out instanceof Renderable) {
					$out->send_content_type_header();
					echo $filter_output;
					$out->render();
				}
			}

		}


		public function get($pattern, $handler)			{ $this->router->get($pattern, $handler); }
		public function put($pattern, $handler)			{ $this->router->put($pattern, $handler); }
		public function post($pattern, $handler)		{ $this->router->post($pattern, $handler); }
		public function delete($pattern, $handler)	{ $this->router->delete($pattern, $handler); }

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
				if (options('environment') != PRODUCTION) {
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
					<div><h2>Try this:</h2><pre>get('".request('uri')."', function() {
  return 'Hello World';
});
</pre></div>
					</body></html>";
				} else if ($this->not_found_handler) {
					$out = call_user_func($this->not_found_handler, $e);
				} else {
					# standard apache 404 page
					$out = '<!DOCTYPE HTML PUBLIC "-//IETF//DTD HTML 2.0//EN"><html><head><title>404 Not Found</title></head><body><h1>Not Found</h1><p>The requested URL '.request('uri').' was not found on this server.</p></body></html>';
				}

			} else {
				$this->status(500);
				if (options('environment') != PRODUCTION) {
					$t = $e->trace();
					$backtrace = explode("\n", $e->trace_as_string());
					array_shift($backtrace);
					$backtrace = join('</pre></li><li><pre>', $backtrace);


					$vars = array('GET' => $_GET, 'POST' => $_POST, 'SESSION' => isset($_SESSION) ? $_SESSION : array(), 'SERVER' => $_SERVER);

					foreach($vars as $type => $sg) {
						$html = "";
						if (empty($sg)) {
							$html .= "<tr class='empty'><th class='type'>$type</th><th colspan='2'>No $type data.</th></tr><tr><td class='blank'></td><td class='empty' colspan='2'>&nbsp;</td></tr>";
						} else {
							$html .= "<tr><th class='type'>$type</th><th>Variable</th><th>Value</th></tr>";
							foreach($sg as $k => $v) {
								if (is_array($v)) {
									ob_start();
									var_export($v);
									$v = nl2br(ob_get_clean());
								}
								$html .= "<tr><td class='blank'></td><td class='key'>$k</td><td>".wordwrap($v, 150, "<br />\n", true)."</td></tr>";
							}
						}
						$vars[$type] = $html;
					}


					$out = sprintf("<!DOCTYPE html>
					<html><head><title>500 Internal Server Error</title>
					<style type='text/css'>
          	body { font-family:helvetica,arial;font-size:18px; margin:50px; letter-spacing: .1em;}
						#c { width: 960px; margin:0  auto; position: relative; }
						#h { display: table-cell; vertical-align: bottom; height: 109px; background-color:#FC63CD; color: #FFF; padding:0px 0px 10px 510px; background-image:url($logo); background-repeat:no-repeat;width:460px;}
						h1, h2 { margin:0; }
						h2 { font-size: 16px; color: white; }
						h2 span { font-weight: normal; }
						h3 { background-color:#888; color:#FFF; margin: 0px; padding: 3px 10px;}
						pre { background-color:#FF0; color:#000; padding: 10px; margin: 0px; font-size:12px; line-height: 1.5em; white-space: pre-wrap;}
						ul {margin:0px; padding: 0px; list-style: none; }
						li { border-bottom: 1px solid white; }
						table { width: 960px; border: 0px; border-spacing: 0px;  }
						table th.type { font-size: 21px; font-weight: bold; width: 110px; border-right: 1px solid white;}
						th { text-align: left; background-color:#888; color:#FFF; padding: 0px 10px; height: 30px; font-weight: normal; font-size: 14px;}
						td { border-bottom: 1px solid white; background-color:#FF0; color:#000; padding: 10px; margin: 0px; font-size:12px; line-height: 1.5em; }
						td.key { width: 170px; border-right: 1px solid white; }
						td.blank { background-color: white; border: none; }
						tr.empty td { border: none; }
					</style>
					</head>
					<body>
					<div id='c'>
					<div id='h'><h1>%s</h1><h2>file: <span>%s</span> line: <span>%s</span> location: <span>%s</span></h2></div>
					<div><pre>%s</pre></div>
					<h3>Backtrace</h3>
					<ul><li><pre>%s</pre></li></ul>


					<table>%s
					%s
					%s
					%s</table>
					<div style='clear: both'></div>
					</div></body></html>", str_replace(array("vicious\\", 'Vicious'), '', get_class($e)), pathinfo($e->file(), PATHINFO_BASENAME), $e->line(), request('uri'), $e->message(), $backtrace, $vars['GET'], $vars['POST'], $vars['SESSION'], $vars['SERVER']);
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
				} else if ($out instanceof Renderable) {
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
			if (is_array($params) && array_key_exists($p, $params)) {
				return $params[$p];
			} else {
				return false;
			}
		}

		public function route() {
			return $this->route;
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
}

namespace
{
	# get single static instance of a Application
	function app() { return vicious\Application::instance(); }
	function application() { return vicious\Application::instance(); }
}

?>