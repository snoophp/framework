<?php

namespace SnooPHP\Gd;

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
	 * @var int $w image width
	 */
	protected $w;

	/**
	 * @var int $h image height
	 */
	protected $h;

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

		// Set width and height
		if ($this->raw)
		{
			$this->w	= imagesx($this->raw);
			$this->h	= imagesy($this->raw);
		}
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
	 * Return image size
	 * 
	 * @return object|null object with w and h or null if resource is not valid
	 */
	public function size()
	{
		if ($this->raw) return (object)[
			"w"	=> $this->w,
			"h"	=> $this->h
		];
		return null;
	}

	/**
	 * Resize image
	 * 
	 * If width or height is zero aspect ratio is preserved
	 * 
	 * @param int	$w		new width
	 * @param int	$h		new height
	 * @param int	$mode	resize method
	 * 
	 * @return Image|bool return this or false if failed
	 */
	public function resize($w = 0, $h = 0, $mode = IMG_BILINEAR_FIXED)
	{
		if ($this->raw)
		{
			$w		= $w ?: round($h * $this->w / $this->h);
			$h		= $h ?: round($w * $this->h / $this->w);
			$image	= imagescale($this->raw, $w, $h);

			if ($image && $w && $h)
			{
				imagedestroy($this->raw);

				$this->raw	= $image;
				$this->w	= $w;
				$this->h	= $h;

				return $this;
			}
		}
		
		return false;
	}

	/**
	 * Crop image
	 * 
	 * @param int	$x		rectangle x
	 * @param int	$y		rectangle y
	 * @param int	$width	rectangle width
	 * @param int	$height	rectangle height
	 * 
	 * @return Image|bool return this or false if failed
	 */
	public function crop($x = 0, $y = 0, $width = 0, $height = 0)
	{
		if ($this->raw)
		{
			$rect = [
				"x"			=> $x,
				"y"			=> $y,
				"width"		=> $width ?: $this->w,
				"height"	=> $height ?: $this->h
			];

			if ($image = imagecrop($this->raw, $rect))
			{
				imagedestroy($this->raw);

				$this->raw	= $image;
				$this->w	= $width;
				$this->y	= $height;

				return $this;
			}
		}

		return false;
	}

	/**
	 * Make square image using resize or crop
	 * 
	 * @param int		$size		if 0 will use min between height and width
	 * @param bool		$crop		if true crop, otherwise resize only
	 */
	public function square($size = 0, $crop = true)
	{
		if ($this->raw)
		{
			$crop = $crop && $this->w !== $this->h;
			if ($this->w > $this->h)
			{
				$size = $size ?: $this->h;
				return $crop ?
				$this->resize(0, $size)->crop(0, 0, $size, $size) :
				$this->resize($size, $size);
			}
			else
			{
				$size = $size ?: $this->w;
				return $crop ?
				$this->resize($size, 0)->crop(0, 0, $size, $size) :
				$this->resize($size, $size);
			}
		}

		return false;
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
			if (!$createDir || !mkdir($dir, 0775, true)) return false;
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