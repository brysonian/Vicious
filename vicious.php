<?php

declare(encoding='UTF-8');

# generator
if (php_sapi_name() == 'cli') {
	if (!isset($_SERVER['argv'][1])) {
		$err = "\nVicious generator. \n==================\n";
		$err .= "ERROR: You must specify an action.\n";
		$err .= "\n\nExample Usage:\n    php vicious.php htaccess routes.php\n";
		$err .= "\n\nExample Usage:\n    php vicious.php downgrade.php\n\n";
		die($err);
	}

	switch ($_SERVER['argv'][1]) {
		case 'downgrade':
			require_once(dirname(__FILE__).'/generators/downgrade.php');
			break;

		case 'htaccess':
			require_once(dirname(__FILE__).'/generators/htaccess.php');
			break;

		default:
			$err = "\nVicious generator. \n==================\n";
			$err .= "ERROR: You must specify an action.\n";
			$err .= "\n\nExample Usage:\n    php vicious.php htaccess routes.php\n";
			$err .= "\n\nExample Usage:\n    php vicious.php downgrade\n\n";
			die($err);
	}
	exit();
}

# include the App class
require_once(__DIR__.'/Application.php');

# enable using register_shutdown_function to handle a request.
# there are consequences which you can read about at http://php.net/register-shutdown-function
enable('auto_dispatch');

# init the app
vicious\Application::init();


?>