<?php

namespace Gd;

/**
 * A raw image
 * 
 * Image has no type and no path, raw data is stored in an underlying resource
 * 
 * @author sneppy
 */
class Image
{
	/**
	 * @var resource $raw underlying image resource
	 */
	protected $raw;

	/**
	 * @var string $name image name
	 */
	protected $name;

	/**
	 * Create a new image
	 * 
	 * @param resource	$raw	image resource
	 * @param string	$name	image name
	 */
	public function __construct($raw = null, $name = "")
	{
		$this->raw	= $raw;
		$this->name	= $name;
	}

	/**
	 * Get or set name
	 * 
	 * @param string|null $name image name
	 * 
	 * @return string the image name
	 */
	public function name($name = null)
	{
		if ($name) $this->name = $name;
		return $this->name;
	}

	/**
	 * Return true if valid
	 * 
	 * @return bool
	 */
	public function valid()
	{
		return $this->raw !== null;
	}

	/**
	 * Render image to file
	 * 
	 * @param string		$dir		destination directory
	 * @param int			$type		destination type
	 * @param string|null	$name		name to use insted of image name
	 * @param bool			$createDir	if true and path to specified directory doesn't exist create it
	 * 
	 * @return string|bool the full path of the file or false if an error occured
	 */
	public function toFile($dir, $type = IMAGETYPE_JPEG, $name = null, $createDir = true)
	{
		if (!$this->raw) return false;

		$dir	= rtrim($dir, "\/");
		$name	= $name ?: $this->name;
		$ext	= image_type_to_extension($type);
		$path	= $dir."/".$name.$ext;
		if (!file_exists($dir))
		{
			if (!$createDir && !mkdir($dir, 0775, true)) return false;
		}

		switch($type)
		{
			case IMAGETYPE_GIF:
				if (!imagegif($this->raw, $path)) return false;
				break;
			case IMAGETYPE_JPEG:
				if (!imagejpeg($this->raw, $path)) return false;
				break;
			case IMAGETYPE_PNG:
				if (!imagepng($this->raw, $path)) return false;
				break;
			default:
				return false;
		}

		return $path;
	}

	/**
	 * Create a new image from path
	 * 
	 * @param string		$path	file path
	 * @param string|null	$name	image name
	 * @param int|null		$type	image type (0 to compute from image)
	 * 
	 * @return Image
	 */
	public static function fromFile($path, $name = null, $type = 0)
	{
		// Check that file exists
		if (file_exists($path))
		{
			$type = $type ?: exif_imagetype($path);
			$name = $name ?: basename($path, image_type_to_extension($type));
			switch($type)
			{
				case IMAGETYPE_GIF:
					return new static(imagecreatefromgif($path), $name);
					break;
				case IMAGETYPE_JPEG:
					return new static(imagecreatefromjpeg($path), $name);
					break;
				case IMAGETYPE_PNG:
					return new static(imagecreatefrompng($path), $name);
					break;
				default:
					return new static();
			}
		}
		
		return new static();
	}
}