<?php

	if ($_SERVER['argv'][1] == 'htaccess' && array_key_exists(2, $_SERVER['argv'])) {

		$appfile = $_SERVER['argv'][2];
		$ht = <<<EOD
RewriteEngine On
RewriteBase /

### any slash, followed by a "_" , do nothing, and stop.
RewriteCond  %{REQUEST_URI} ^/_.*
RewriteRule .* - [L]

### same with favicon
RewriteCond  %{REQUEST_URI} ^/favicon.ico
RewriteRule .* - [L]

### if the requested item exists, use it.
RewriteCond %{REQUEST_FILENAME} -f
RewriteRule ^(.+)	$1 [L]

### if {requested item}.php exists, use that
RewriteCond %{REQUEST_FILENAME}.php -f
RewriteRule ^(.+)	$1.php [L]

### if {requested item}.html exists, use that
RewriteCond %{REQUEST_FILENAME}.html -f
RewriteRule ^(.+)	$1.html [L]

### if {requested item}/index.php exists, use that
RewriteCond %{REQUEST_FILENAME}/index.php -f
RewriteRule ^(.+)	$1/index.php [L]

### if {requested item}/index.html exists, use that
RewriteCond %{REQUEST_FILENAME}/index.html -f
RewriteRule ^(.+)	$1/index.html [L]

RewriteRule ^(.*)$ $appfile?%{QUERY_STRING} [T=application/x-httpd-php,L]

EOD;
		$f = fopen('.htaccess', 'w');
		fwrite($f, $ht);
		fclose($f);
	} else {
		$err = "\nVicious generator. \n==================\n";
		if ($_SERVER['argv'][1] != 'htaccess') $err .= "ERROR: Only htaccess generation is currently supported. To generate an htaccess document the first argument must be 'htaccess'.\n";
		if (!array_key_exists(2, $_SERVER['argv'])) $err .= "ERROR: You must pass the path to your main app file.";
		$err .= "\n\nExample Usage:\n    php vicious.php htaccess routes.php\n\n";
		echo $err;
	}

?>