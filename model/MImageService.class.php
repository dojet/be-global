<?php
/**
 *
 *
 * Filename: MImageService.class.php
 *
 * @author liyan
 * @since 2014 7 21
 */
abstract class MImageService {

    public static function writeImage($filepath, $imageData) {
        $path = dirname($filepath);
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
            chmod($path, 0755);
        }
        $ret = file_put_contents($filepath, $imageData);
        return $ret;
    }

    protected static function imageHashPath($hash) {
        $path = substr($hash, 0, 2);
        return $path;
    }

    public static function getImagePath($hash, $create = false) {
        $path = static::imageRoot();
        $path.= self::imageHashPath($hash);
        if (!file_exists($path) && $create) {
            mkdir($path, 0755, true);
            chmod($path, 0755);
        }
        $filepath = $path.'/'.$hash;
        return $filepath;
    }

    public static function getImageUrl($hash) {
        $urlprefix = static::imageUrlPrefix();
        $url = $urlprefix.self::imageHashPath($hash);
        $url.= '/'.$hash;
        return $url;
    }

    public static function writeThumb($imageData, $hash, $width, $height) {
        $filepath = self::getThumbPath($hash, $width, $height);
        return self::writeImage($filepath, $imageData);
    }

    public static function getThumbPath($hash, $width, $height) {
        $thumbRoot = Config::runtimeConfigForKeyPath('image.thumb_root');
        $filepath = sprintf("%s%dx%d/%s/%s", $thumbRoot, $width, $height, self::imageHashPath($hash), $hash);
        return $filepath;
    }

    public static function getThumbUrl($hash, $width, $height) {
        $thumbPrefix = Config::runtimeConfigForKeyPath('image.thumb_urlprefix');
        $url = sprintf("%s%dx%d/%s/%s", $thumbPrefix, $width, $height, self::imageHashPath($hash), $hash);
        return $url;
    }

    abstract protected static function imageRoot();

    abstract protected static function imageUrlPrefix();

}
