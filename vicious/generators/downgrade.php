<?php

$out = getcwd();
mkdir("$out/vicious");
mkdir("$out/vicious/images");
mkdir("$out/vicious/generators");

$vicious_loc = realpath(dirname(__FILE__).'/../..');

# update the core classes
$it = new DirectoryIterator($vicious_loc.'/vicious');
foreach ($it as $fileInfo) {
	if($fileInfo->isDot() || $fileInfo->isDir()) continue;
	downgrade_file($out.'/vicious', $it->getPath(), $fileInfo->getFilename());
}

# move the images
foreach (new DirectoryIterator($vicious_loc.'/vicious/images') as $fileInfo) {
	if($fileInfo->isDot() || $fileInfo->isDir()) continue;
	copy($vicious_loc.'/vicious/images/'.$fileInfo->getFilename(),
				"$out/vicious/images/".$fileInfo->getFilename());
}

# move the generators
foreach (new DirectoryIterator($vicious_loc.'/vicious/generators') as $fileInfo) {
	if($fileInfo->isDot() || $fileInfo->isDir()) continue;
	copy($vicious_loc.'/vicious/generators/'.$fileInfo->getFilename(),
				"$out/vicious/generators/".$fileInfo->getFilename());
}

# update vicious.php
downgrade_file($out, $vicious_loc, 'vicious.php');


function downgrade_file($out, $src_path, $file) {
	$code = file_get_contents($src_path.'/'.$file);

	# remove declare	
	$code = str_replace('declare(', '#declare(', $code);

	# remove namespace declarations
	$code = str_replace('namespace', '#namespace', $code);

	# replace namespaced references with prefixed references
	$code = str_replace(
		array("\\Exception", '__DIR__', "vicious\\\\", "vicious\\"),
		array('Exception', 'dirname(__FILE__)', '', ''),
		$code);

	# prefix classes

	# save it
	$f = fopen($out.'/'.$file, 'w');
	fwrite($f, $code);
	fclose($f);
}

?>