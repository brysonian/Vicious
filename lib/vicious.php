<?php

declare(encoding='UTF-8');

# generator
if (php_sapi_name() == 'cli') {
	require_once(__DIR__.'/vicious/generator.php');
	exit();
}

# include the App class
require_once(__DIR__.'/vicious/Application.php');

# enable using register_shutdown_function to handle a request.
# there are consequences which you can read about at http://php.net/register-shutdown-function
enable('auto_dispatch');

# init the app
vicious\Application::init();


?>