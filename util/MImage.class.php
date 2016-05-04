<?php
/**
 * description
 *
 * Filename: LibImage.class.php
 *
 * @author liyan
 * @since 2015 1 14
 */
class MImage {

    protected $imagePathFile;
    protected $image;

    function __construct($imagePathFile) {
        DAssert::assert(extension_loaded('gd') || dl('gd'), 'gd lib not exist');
        $this->imagePathFile = $imagePathFile;
    }

    protected function getImage() {
        if (!is_resource($this->image)) {
            $imageData = file_get_contents($this->imagePathFile);
            $this->image = imagecreatefromstring($imageData);
        }
        return $this->image;
    }

    public function width() {
        return imagesx($this->getImage());
    }

    public function height() {
        return imagesy($this->getImage());
    }

    public function resize($width, $height) {
        $iw = $this->width();
        $ih = $this->height();

        $k1 = $iw / $ih;
        $k2 = $width / $height;

        if ($k1 > $k2) {
            $src_x = ($iw - $ih * $k2) / 2;
            $src_y = 0;
            $src_w = $iw - $src_x * 2;
            $src_h = $ih;
        } else {
            $src_x = 0;
            $src_y = ($ih - $iw / $k2) / 2;
            $src_w = $iw;
            $src_h = $ih - $src_y * 2;
        }

        $im = imagecreatetruecolor($width, $height);

        if (!imagecopyresampled($im, $this->getImage(), 0, 0, $src_x, $src_y, $width, $height, $src_w, $src_h)) {
            throw new Exception("resize image fail", 1);
        }

        $this->image = $im;
    }

    /**
     * 9宫格拉伸
     */
    public function resize9($width, $height, $ix, $iy, $iw, $ih) {
        $im = imagecreatetruecolor($width, $height);
    }

    public function copy(MImage $src, $dst_x, $dst_y, $src_x, $src_y, $w, $h) {
        $dst = $this->getImage();
        return imagecopy($dst, $src->getImage(), $dst_x, $dst_y, $src_x, $src_y, $w, $h);
    }

    public function ttftext($text, $fontfile, $size, $x, $y, $angle = 0, $rgb = 0x000000) {
        $image = $this->getImage();
        $r = ($rgb >> 16) & 0xff;
        $g = ($rgb >> 8) & 0xff;
        $b = ($rgb >> 0) & 0xff;
        $color = imagecolorallocate($image, $r, $g, $b);
        return imagettftext($image, $size, $angle, $x, $y, $color, $fontfile, $text);
    }

    public function saveTo($path, $quality = 75) {
        imagejpeg($this->getImage(), $path, $quality);
    }

    public function display() {
        header("Content-type: image/png");
        imagepng($this->getImage());
    }

}

