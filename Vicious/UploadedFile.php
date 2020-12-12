<?php

namespace Vicious;

class UploadedFile
{

	protected $path;
	protected $name;
	protected $type;
	protected $mime_type;
	protected $error;
	protected $size;

	protected $extension;

	protected $imagetypecode;
	protected $isimage;
	protected $width;
	protected $height;


	static function create($fileinfo) {
		if ($fileinfo['size'] == 0) return false;
		$out = array();
		if (is_array($fileinfo['name'])) {
			foreach($fileinfo['name'] as $k => $v) {
				if (!empty($v) && $fileinfo['error'][$k] == UPLOAD_ERR_OK) {
					$f = new UploadedFile(
						$fileinfo['tmp_name'][$k],
						$v,
						$fileinfo['type'][$k],
						$fileinfo['error'][$k],
						$fileinfo['size'][$k]);
					if ($f instanceof UploadedFile) $out[$k] = $f;
				}
			}
			if (count($out) == 1) $out = current($out);
			return $out;
		} else {
			$f = new UploadedFile(
				$fileinfo['tmp_name'],
				$fileinfo['name'],
				$fileinfo['type'],
				$fileinfo['error'],
				$fileinfo['size']);
			return ($f instanceof UploadedFile) ? $f : false;
		}
		if (is_array($out) && count($out) == 0) {
			return false;
		}
		return $out;
	}


	function __construct($path, $name, $mime, $err, $size) {

		# make sure it was uploaded
		if (!is_uploaded_file($path)) throw new NotUploadedFile("The file $path is not a valid uploaded file.");

		# set params
		$this->set_path($path);
		$this->set_filename($name);
		$pi = pathinfo($name);
		$this->set_extension($pi['extension']);
		$this->set_mime_type($mime);
		$this->set_error($err);
		$this->set_size($size);

		$exif_type = exif_imagetype($path);
		$t = false;
		switch(true) {
			case ($exif_type == IMAGETYPE_GIF && $this->get_extension() == 'gif'):
				$t = 'gif';
				break;

			case ($exif_type == IMAGETYPE_JPEG && ($this->get_extension() == 'jpg' || $this->get_extension() == 'jpeg')):
				$t = 'jpg';
				break;

			case ($exif_type == IMAGETYPE_PNG && $this->get_extension() == 'png'):
				$t = 'png';
				break;

			case ($exif_type == IMAGETYPE_SWF && $this->get_extension() == 'swf'):
				$t = 'swf';
				break;
		}

		if ($t) {
			$this->set_type($t);
			$this->set_image_type_code($exif_type);
			$this->set_is_image(true);
		} else {
			$this->set_image_type_code(false);
			$this->set_is_image(false);
		}
	}

// ===========================================================
// - ACCESSORS
// ===========================================================
	// getters
	public function get_path() { return $this->path; }
	public function get_web_path($root) { return str_replace($root, '', $this->path); }
	public function get_type() { return $this->type; }
	public function get_filename() { return $this->name; }
	public function get_extension() { return $this->extension; }
	public function get_mime_type() { return $this->mime_type; }
	public function get_error() 		{ return $this->error; }
	public function get_size() 			{ return $this->size; }

	public function get_image_type_code() { return $this->imagetypecode; }
	public function is_image() { return $this->isimage; }
	public function get_width() {
		if (!$this->width) $this->init_width_and_height();
		return $this->width;
	}
	public function get_height() {
		if (!$this->height) $this->init_width_and_height();
		return $this->height;
	}


	// setters
	private function set_path($newval) { $this->path = $newval; }
	private function set_filename($newval) { $this->name = $newval; }
	private function set_extension($newval) { $this->extension = $newval; }
	private function set_mime_type($newval) { $this->mime_type = $newval; }
	private function set_error($newval) { $this->error = $newval; }
	private function set_size($newval) { $this->size = $newval; }

	private function set_type($newval) { $this->type = $newval; }
	private function set_image_type_code($newval) { $this->imagetypecode  = $newval; }
	private function set_is_image($newval) { $this->isimage  = $newval; }
	private function set_width($newval) { $this->width  = $newval; }
	private function set_height($newval) { $this->height  = $newval; }
	private function init_width_and_height() {
		$s = getimagesize($this->get_path());
 		$this->set_width($s[0]);
		$this->set_height($s[1]);
		}

	// testers
	public function is_jpg() { return ($this->get_image_type_code() == IMAGETYPE_JPEG); }
	public function is_jpeg() { return ($this->get_image_type_code() == IMAGETYPE_JPEG); }
	public function is_gif() { return ($this->get_image_type_code() == IMAGETYPE_GIF); }
	public function is_png() { return ($this->get_image_type_code() == IMAGETYPE_PNG); }
	public function is_animated() {
		if (!$this->is_gif()) return false;

		// from http://php.net/manual/en/function.imagecreatefromgif.php#88005
    //an animated gif contains multiple "frames", with each frame having a
    //header made up of:
    // * a static 4-byte sequence (\x00\x21\xF9\x04)
    // * 4 variable bytes
    // * a static 2-byte sequence (\x00\x2C)
    // We read through the file til we reach the end of the file, or we've found
    // at least 2 frame headers
    if(!($fh = @fopen($this->get_path(), 'rb'))) return false;
    $count = 0;
    while(!feof($fh) && $count < 2) {
    	$chunk = fread($fh, 1024 * 100); //read 100kb at a time
      $count += preg_match_all('#\x00\x21\xF9\x04.{4}\x00\x2C#s', $chunk, $matches);
		}
    fclose($fh);
    return $count > 1;
	}




// ===========================================================
// - MISC METHODS
// ===========================================================
	/**
	*	move uploaded file to a directory
	*	@param $path: the path to save the file to
	*	@param $newname: if set, the new name for the file
	*/
	function move_to($path, $newname=false, $force=false) {
		# first see if the dir exists
		if (!file_exists($path)) throw new InvalidPath("The path ".$path." does not seem to exist.");

		# now see if i can write to it
		if (!is_writable($path)) throw new WritePermission("Cannot write to the directory ".$path.".");

		# if no newname is specified, set newname to the original name
		if ($newname == false) $newname = $this->get_filename();
		else {
			# add extension if newname doesn't have one
			if (pathinfo($newname, PATHINFO_EXTENSION) == '') $newname .= '.' . $this->get_extension();
		}

		# make sure the filename is kosher by killing non alpanum chars
		$newname =  preg_replace("/[^a-zA-Z0-9_.]/i", '-', stripslashes($newname));

		# check for trailing slash
		if (substr($path, -1) != '/') $path .= '/';

		# if we aren't forcing, check that it doesn't exist first
		if (!$force) {
			if (file_exists($path.$newname)) return false;
		}

		# copy it to the directory
		$status = move_uploaded_file($this->get_path(), $path.$newname);

		# if it worked, set the perms and return new location
		if ($status) {
			$this->set_path($path.$newname);
			$this->set_filename($newname);
		} else {
			throw new FileMove("There was a problem moving the file ".$this->get_name()." to the directory $path.");;
		}
		return true;
	}




// ===========================================================
// - IMAGE METHODS
// ===========================================================
	/**
	*	resize image
	*	@param $width: new width
	*	@param $height: new height
	*	@returns bool of success
	*/
	function resize($width, $height, $path, $output_type='jpg') {
		# make sure GD is installed
		if (!function_exists('gd_info'))
			throw new GDMissing("GD is required to resize image.", 0);

		if ($this->is_jpeg()) {
			$src_img = @imagecreatefromjpeg($this->get_path());

		} else if ($this->is_gif()) {

			if ($this->is_animated()) {
				$err = "Animated gif resizing requires system() access to imagemagick.";
				if (!function_exists('system')) throw new UploadedFileException($err, 0);
				$out = shell_exec("which convert");
		    if (empty($out)) throw new UploadedFileException($err, 0);

				$coalesce = tempnam('/tmp', 'coalesce.gif');
				copy($this->get_path(), $coalesce);
				shell_exec("convert " . $coalesce . " -coalesce $coalesce");
				shell_exec("convert -size " . $this->get_width() . "x" . $this->get_height() . " $coalesce -resize {$width}x{$height} $coalesce");
				copy($coalesce, $path);
				unlink($coalesce);
				return true;

			} else {
				$src_img = @imagecreatefromgif($this->get_path());
			}

		} else if ($this->is_png()) {
			$src_img = @imagecreatefrompng($this->get_path());
			imagealphablending($src_img, false);
			imagesavealpha($src_img,true);
		} else {
			throw new FormatNotResizable("File is not a resizable format.", 0);
		}

		$dst_img = imagecreatetruecolor($width,$height);
		$bg = imagecolorallocate($dst_img, 255,255,255);
		imagefill($dst_img, 0, 0, $bg);

		imagecopyresampled($dst_img,$src_img,0,0,0,0,$width,$height,imagesx($src_img),imagesy($src_img));

		// cleanup
		imagedestroy($src_img);

		// update size
		$this->set_width($width);
		$this->set_height($height);

		switch ($output_type) {
			case 'gif':
				return imagegif($dst_img, $path);
			case 'png':
				return imagepng($dst_img, $path);
			default:
				return imagejpeg($dst_img, $path, 100);
		}
	}

	/**
	*	resize image preserving aspect to a max size
	*	@param $max: max size
	*	@returns bool of success
	*/
	function resize_max($max, $path, $output_type='jpg') {
		if ($this->get_width() > $this->get_height()) {
			$w = $max;
			$h = floor($max * ($this->get_height()/$this->get_width()));
		} else {
			$w = floor($max * ($this->get_width()/$this->get_height()));
			$h = $max;
		}
		return $this->resize($w, $h, $path, $output_type);
	}

	/**
	*	resize image preserving aspect to specific width
	*	@param $width: new width
	*	@returns bool of success
	*/
	function fit_width($width, $path, $output_type='jpg') {
		$h = floor(($this->get_height() * $width) / $this->get_width());
		return $this->resize($width, $h, $path, $output_type);
	}

	/**
	*	resize image preserving aspect to specific height
	*	@param $height: new height
	*	@returns bool of success
	*/
	function fit_height($height, $path, $output_type='jpg') {
		$w = floor(($this->get_width() * $height) / $this->get_height());
		return $this->resize($w, $height, $path, $output_type);
	}

	function __toString() {
		return $this->path;
	}
}

// ===========================================================
// - EXCEPTIONS
// ===========================================================
class UploadedFileException extends ViciousException {}
class InvalidPath extends UploadedFileException {}
class WritePermission extends UploadedFileException {}
class NotUploadedFile extends UploadedFileException {}
class FileMove extends UploadedFileException {}
class GDMissing extends UploadedFileException {}
class FormatNotResizable extends UploadedFileException {}
class InvalidFileType extends UploadedFileException {}
