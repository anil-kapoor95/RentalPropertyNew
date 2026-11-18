<?php
if (!defined("ROOT_PATH"))
{
	header("HTTP/1.1 403 Forbidden");
	exit;
}
/**
 * Checks that an uploaded file really is a picture before it is kept, and turns modern formats into ones every
 * server can process.
 *
 * getimagesize() only reads the first bytes of a file, so "GIF89a<?php ... ?>" passes it. Here the whole file is
 * decoded with GD: a file that does not decode as a JPG / PNG / GIF (/ WEBP / AVIF) picture is refused.
 *
 * Pictures saved from websites are often WEBP or AVIF even when they are called ".jpg" (Airbnb, booking sites,
 * stock photo sites). Many PHP builds can't read those (GD without WebP / AVIF), so prepareUpload() converts them
 * to JPG (photos) or PNG (logos, transparency kept) with GD, Imagick or macOS "sips", whichever is available, and
 * names the file after its real type.
 */
class pjImageGuard
{
	/** @var int[] Picture types accepted everywhere (IMAGETYPE_* constants) */
	private static $types = array(IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_GIF);

	/**
	 * Is $path a picture of an accepted type that decodes completely?
	 * @param string $path  file on this server (upload tmp file or saved file)
	 * @param bool   $webp  also accept WEBP and AVIF (logos: kept as uploaded when the server can't convert them)
	 */
	public static function isImage($path, $webp = false)
	{
		if (!is_string($path) || $path === '' || !is_file($path) || !is_readable($path))
		{
			return false;
		}
		$info = @getimagesize($path);
		if ($info === false || empty($info[0]) || empty($info[1]))
		{
			return false;
		}
		$types = self::$types;
		if ($webp)
		{
			$types = array_merge($types, self::modernTypes());
		}
		if (!in_array((int) $info[2], $types, true))
		{
			return false;
		}
		// Very large pictures: decoding needs width * height * 4 bytes; do not run out of memory on a crafted header.
		if ((float) $info[0] * (float) $info[1] > 50000000)
		{
			return false;
		}
		$data = @file_get_contents($path);
		if ($data === false || $data === '')
		{
			return false;
		}
		// A real picture never carries a PHP open tag: a valid PNG with PHP code appended decodes fine with GD, so refuse it
		// here (stored pictures are never run as code, but they must not hold code either). Only a full "<?php" followed by
		// white space: the 3 bytes "<?=" turn up by chance inside ordinary JPG photos (that refused normal photos).
		if (preg_match('/<\?php[\s]/i', $data))
		{
			return false;
		}
		// Without GD, or a type this GD build can't read (WEBP / AVIF on many servers), the header and content checks above
		// are all that can be done: such a file is not refused just because the server can't decode it.
		if (!function_exists('imagecreatefromstring') || !self::gdCanRead((int) $info[2]))
		{
			return true;
		}
		try
		{
			$img = @imagecreatefromstring($data);
		} catch (\Throwable $e) {
			return false;
		}
		if ($img === false)
		{
			return false;
		}
		if (is_resource($img) || (is_object($img) && $img instanceof \GdImage))
		{
			@imagedestroy($img);
		}
		return true;
	}

	/** Extension of the real picture type of a file: jpg, png, gif, webp, avif, or '' (not a picture we know). */
	public static function extension($path)
	{
		$info = is_string($path) && is_file($path) ? @getimagesize($path) : false;
		if ($info === false)
		{
			return '';
		}
		$map = array(IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_GIF => 'gif');
		if (defined('IMAGETYPE_WEBP'))
		{
			$map[IMAGETYPE_WEBP] = 'webp';
		}
		if (defined('IMAGETYPE_AVIF'))
		{
			$map[IMAGETYPE_AVIF] = 'avif';
		}
		return isset($map[(int) $info[2]]) ? $map[(int) $info[2]] : '';
	}

	/**
	 * Gets an upload ready to be stored: a WEBP / AVIF picture is converted in place to JPG (or PNG with $alpha), and
	 * the name and type of the upload follow the real picture ("photo.jpg" that is really a WEBP becomes a real JPG;
	 * a PNG called ".jpg" is named ".png"). The tmp path does not change, so is_uploaded_file() still passes.
	 *
	 * @param array $file        one upload (name, type, tmp_name, error, size)
	 * @param bool  $alpha       convert to PNG (keeps transparency: logos) instead of JPG (photos)
	 * @param bool  $keepModern  keep a WEBP / AVIF as it is when no converter is available (logos: browsers show them)
	 * @param int   $maxSide     larger pictures are scaled down to this many pixels on the long side (camera and stock
	 *                           photos of 8000+ px are too big to check and to show; 0 = keep the size)
	 * @return string|false      real extension (jpg, png, gif, or webp / avif when kept), or false: not usable
	 */
	public static function prepareUpload(array &$file, $alpha = false, $keepModern = false, $maxSide = 2560)
	{
		if (!isset($file['tmp_name'], $file['error']) || (int) $file['error'] !== UPLOAD_ERR_OK || !is_file($file['tmp_name']))
		{
			return false;
		}
		$ext = self::extension($file['tmp_name']);
		$info = $ext !== '' ? @getimagesize($file['tmp_name']) : false;
		// absurd sizes (decompression bombs) are refused before anything opens them
		if ($ext === '' || $info === false || empty($info[0]) || empty($info[1]) || (float) $info[0] * (float) $info[1] > self::MAX_PIXELS)
		{
			return false;
		}
		if ($ext === 'webp' || $ext === 'avif')
		{
			$converted = self::convert($file['tmp_name'], $alpha ? 'png' : 'jpg');
			if ($converted !== false)
			{
				$ext = $converted;
			} elseif (!$keepModern) {
				return false;
			}
		}
		if ($maxSide > 0 && in_array($ext, array('jpg', 'png', 'gif'), true) && !self::downscale($file['tmp_name'], $ext, (int) $maxSide))
		{
			return false;
		}
		$base = preg_replace('/\.[A-Za-z0-9]{1,5}$/', '', (string) (isset($file['name']) ? $file['name'] : 'image'));
		$file['name'] = ($base !== '' ? $base : 'image') . '.' . $ext;
		$file['type'] = $ext === 'jpg' ? 'image/jpeg' : 'image/' . $ext;
		if (isset($file['size']))
		{
			clearstatcache(true, $file['tmp_name']);
			$file['size'] = (int) @filesize($file['tmp_name']);
		}
		return $ext;
	}

	/** Pictures larger than this (width x height) are refused outright; up to it, large ones are scaled down. */
	const MAX_PIXELS = 150000000;

	/**
	 * Scales a JPG / PNG / GIF down in place (same type) when its long side is over $max pixels. Tries Imagick, macOS
	 * sips, then GD (with enough memory for this one picture). True when the picture is now small enough.
	 */
	private static function downscale($path, $ext, $max)
	{
		$info = @getimagesize($path);
		if ($info === false)
		{
			return false;
		}
		$w = (int) $info[0];
		$h = (int) $info[1];
		if (max($w, $h) <= $max)
		{
			return true;
		}
		$scale = $max / max($w, $h);
		$nw = max(1, (int) round($w * $scale));
		$nh = max(1, (int) round($h * $scale));
		$out = $path . '.small.' . $ext;
		@unlink($out);

		// 1. Imagick (low memory)
		if (class_exists('Imagick'))
		{
			try
			{
				$im = new \Imagick($path);
				$im->setIteratorIndex(0);
				$im->thumbnailImage($nw, $nh);
				if ($ext === 'jpg')
				{
					$im->setImageCompressionQuality(88);
				}
				$im->writeImage($out);
				$im->clear();
			} catch (\Throwable $e) {
				@unlink($out);
			}
		}
		// 2. macOS sips (local development)
		if (!self::fits($out, $max) && PHP_OS_FAMILY === 'Darwin' && is_executable('/usr/bin/sips') && self::canExec())
		{
			$o = array();
			$code = 1;
			@copy($path, $out);
			@exec('/usr/bin/sips -Z ' . (int) $max . ($ext === 'jpg' ? ' -s formatOptions 88' : '') . ' ' . escapeshellarg($out) . ' 2>&1', $o, $code);
		}
		// 3. GD: decoding needs about width x height x 5 bytes; the memory limit is raised for this picture only
		if (!self::fits($out, $max) && function_exists('imagecreatefromstring') && self::gdCanRead((int) $info[2]))
		{
			$need = (int) (($w * $h + $nw * $nh) * 5.5) + 32 * 1048576;
			if (self::ensureMemory($need))
			{
				$src = @imagecreatefromstring((string) @file_get_contents($path));
				if ($src)
				{
					$dst = imagecreatetruecolor($nw, $nh);
					if ($ext !== 'jpg')
					{
						imagealphablending($dst, false);
						imagesavealpha($dst, true);
						imagefill($dst, 0, 0, imagecolorallocatealpha($dst, 0, 0, 0, 127));
					}
					imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
					imagedestroy($src);
					if ($ext === 'jpg')
					{
						@imagejpeg($dst, $out, 88);
					} elseif ($ext === 'png') {
						@imagepng($dst, $out, 6);
					} else {
						@imagegif($dst, $out);
					}
					imagedestroy($dst);
				}
			}
		}
		if (!self::fits($out, $max))
		{
			@unlink($out);
			return false;
		}
		$data = @file_get_contents($out);
		@unlink($out);
		if ($data === false || $data === '' || @file_put_contents($path, $data) === false)
		{
			return false;
		}
		clearstatcache(true, $path);
		return true;
	}

	/** Is $out a picture whose long side is at most $max pixels? */
	private static function fits($out, $max)
	{
		if (!is_file($out) || (int) @filesize($out) === 0)
		{
			return false;
		}
		$info = @getimagesize($out);
		return $info !== false && max((int) $info[0], (int) $info[1]) <= $max;
	}

	/** Raises memory_limit (for this request) so that $bytes more fit; false when it can't (cap 2 GB). */
	private static function ensureMemory($bytes)
	{
		$limit = trim((string) ini_get('memory_limit'));
		if ($limit === '-1')
		{
			return true;
		}
		$units = array('k' => 1024, 'm' => 1048576, 'g' => 1073741824);
		$u = strtolower(substr($limit, -1));
		$current = isset($units[$u]) ? (int) substr($limit, 0, -1) * $units[$u] : (int) $limit;
		$wanted = memory_get_usage(true) + (int) $bytes;
		if ($wanted <= $current)
		{
			return true;
		}
		if ($wanted > 2147483648)
		{
			return false;
		}
		return @ini_set('memory_limit', (string) (int) ceil($wanted / 1048576) . 'M') !== false;
	}

	/**
	 * Converts a WEBP / AVIF file in place to JPG or PNG ($target). Tries GD, then Imagick, then macOS sips.
	 * Returns $target, or false when nothing on this server can read the picture.
	 */
	private static function convert($path, $target)
	{
		$info = @getimagesize($path);
		if ($info === false || empty($info[0]) || empty($info[1]) || (float) $info[0] * (float) $info[1] > self::MAX_PIXELS)
		{
			return false;
		}
		$type = (int) $info[2];
		$out = $path . '.conv.' . $target;
		@unlink($out);

		// 1. GD (when built with WebP / AVIF)
		$img = false;
		if (defined('IMAGETYPE_WEBP') && $type === IMAGETYPE_WEBP && function_exists('imagecreatefromwebp') && self::gdCanRead($type))
		{
			$img = @imagecreatefromwebp($path);
		} elseif (defined('IMAGETYPE_AVIF') && $type === IMAGETYPE_AVIF && function_exists('imagecreatefromavif') && self::gdCanRead($type)) {
			$img = @imagecreatefromavif($path);
		}
		if ($img)
		{
			if ($target === 'png')
			{
				imagealphablending($img, false);
				imagesavealpha($img, true);
				@imagepng($img, $out, 6);
			} else {
				// transparent parts on white, like a photo
				$flat = imagecreatetruecolor(imagesx($img), imagesy($img));
				imagefill($flat, 0, 0, imagecolorallocate($flat, 255, 255, 255));
				imagecopy($flat, $img, 0, 0, 0, 0, imagesx($img), imagesy($img));
				@imagejpeg($flat, $out, 90);
				imagedestroy($flat);
			}
			imagedestroy($img);
		}

		// 2. Imagick
		if (!self::usable($out, $target) && class_exists('Imagick'))
		{
			try
			{
				$im = new \Imagick($path);
				$im->setIteratorIndex(0);
				if ($target === 'jpg')
				{
					$im->setImageBackgroundColor('white');
					$im = $im->mergeImageLayers(\Imagick::LAYERMETHOD_FLATTEN);
					$im->setImageCompressionQuality(90);
				}
				$im->setImageFormat($target === 'png' ? 'png' : 'jpeg');
				$im->writeImage($out);
				$im->clear();
			} catch (\Throwable $e) {
				@unlink($out);
			}
		}

		// 3. macOS (local development with XAMPP / MAMP): the built-in "sips" reads WEBP and AVIF
		if (!self::usable($out, $target) && PHP_OS_FAMILY === 'Darwin' && is_executable('/usr/bin/sips') && self::canExec())
		{
			$o = array();
			$code = 1;
			@exec('/usr/bin/sips -s format ' . ($target === 'png' ? 'png' : 'jpeg') . ($target === 'jpg' ? ' -s formatOptions 90' : '')
				. ' ' . escapeshellarg($path) . ' --out ' . escapeshellarg($out) . ' 2>&1', $o, $code);
		}

		if (!self::usable($out, $target))
		{
			@unlink($out);
			return false;
		}
		// back into the original file (same path: the upload tmp file stays "uploaded")
		$data = @file_get_contents($out);
		@unlink($out);
		if ($data === false || $data === '' || @file_put_contents($path, $data) === false)
		{
			return false;
		}
		clearstatcache(true, $path);
		return $target;
	}

	/** Is $out a picture of the $target type? */
	private static function usable($out, $target)
	{
		if (!is_file($out) || (int) @filesize($out) === 0)
		{
			return false;
		}
		$info = @getimagesize($out);
		return $info !== false && (int) $info[2] === ($target === 'png' ? IMAGETYPE_PNG : IMAGETYPE_JPEG);
	}

	private static function canExec()
	{
		if (!function_exists('exec'))
		{
			return false;
		}
		$off = array_map('trim', explode(',', strtolower((string) ini_get('disable_functions'))));
		return !in_array('exec', $off, true);
	}

	/** @return int[] WEBP and AVIF type constants known to this PHP */
	private static function modernTypes()
	{
		$types = array();
		if (defined('IMAGETYPE_WEBP'))
		{
			$types[] = IMAGETYPE_WEBP;
		}
		if (defined('IMAGETYPE_AVIF'))
		{
			$types[] = IMAGETYPE_AVIF;
		}
		return $types;
	}

	/** Can this GD build decode the picture type? */
	private static function gdCanRead($type)
	{
		if (!function_exists('imagetypes'))
		{
			return false;
		}
		$map = array(IMAGETYPE_JPEG => 'IMG_JPG', IMAGETYPE_PNG => 'IMG_PNG', IMAGETYPE_GIF => 'IMG_GIF');
		if (defined('IMAGETYPE_WEBP'))
		{
			$map[IMAGETYPE_WEBP] = 'IMG_WEBP';
		}
		if (defined('IMAGETYPE_AVIF'))
		{
			$map[IMAGETYPE_AVIF] = 'IMG_AVIF';
		}
		return isset($map[$type]) && defined($map[$type]) && (imagetypes() & constant($map[$type]));
	}
}
?>
