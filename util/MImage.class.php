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

    function __construct($image) {
        DAssert::assert(extension_loaded('gd') || dl('gd'), 'gd lib not exist');
        DAssert::assert(is_resource($image), 'illegal image resource');
        $this->image = $image;
    }

    public static function imageFromFile($imagePathFile) {
        DAssert::assertFileExists($imagePathFile);
        $imageData = file_get_contents($imagePathFile);
        $image = imagecreatefromstring($imageData);
        return new MImage($image);
    }

    public static function imageWithWidthHeight($width, $height) {
        $image = imagecreatetruecolor($width, $height);
        return new MImage($image);
    }

    public static function image($image) {
        return new MImage($image);
    }

    public function getImage() {
        return $this->image;
    }

    public function width() {
        return imagesx($this->image);
    }

    public function height() {
        return imagesy($this->image);
    }

    public function scale($width, $height) {
        $im = imagecreatetruecolor($width, $height);

        if (!imagecopyresampled($im, $this->image, 0, 0, 0, 0, $width, $height, $this->width(), $this->height())) {
            throw new Exception("scale image fail", 1);
        }

        $this->image = $im;
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

        if (!imagecopyresampled($im, $this->image, 0, 0, $src_x, $src_y, $width, $height, $src_w, $src_h)) {
            throw new Exception("resize image fail", 1);
        }

        $this->image = $im;
    }

    /**
     * 9宫格拉伸
     */
    public function resize9($width, $height, $ix, $iy, $iw, $ih) {
        $dst = MImage::imageWithWidthHeight($width, $height);

        //  left up
        $dst->copy($this, 0, 0, 0, 0, $ix, $iy);

        //  right up
        $dst_x = $width - ($this->width() - $ix - $iw);
        $dst_y = 0;
        $src_x = $ix + $iw;
        $src_y = 0;
        $w = $this->width() - $src_x;
        $h = $iy;
        $dst->copy($this, $dst_x, $dst_y, $src_x, $src_y, $w, $h);

        //  left down
        $dst_x = 0;
        $dst_y = $height - ($this->height() - $iy - $ih);
        $src_x = 0;
        $src_y = $iy + $ih;
        $w = $ix;
        $h = $this->height() - $src_y;
        $dst->copy($this, $dst_x, $dst_y, $src_x, $src_y, $w, $h);

        //  right down
        $dst_x = $width - ($this->width() - $ix - $iw);
        $dst_y = $height - ($this->height() - $iy - $ih);
        $src_x = $ix + $iw;
        $src_y = $iy + $ih;
        $w = $this->width() - $src_x;
        $h = $this->height() - $src_y;
        $dst->copy($this, $dst_x, $dst_y, $src_x, $src_y, $w, $h);

        $dh = $this->height() - $iy - $ih; # down height
        $rw = $this->width() - $ix - $iw; # right width
        $sw = $width - $ix - $rw;
        $sh = $height - $iy - $dh;

        //  left
        $cw = $ix;
        $ch = $ih;
        $clip = MImage::imageWithWidthHeight($cw, $ch);
        $clip->copy($this, 0, 0, 0, $iy, $cw, $ch);
        $clip->scale($cw, $sh);
        $dst->copy($clip, 0, $iy, 0, 0, $clip->width(), $clip->height());

        //  top
        $cw = $iw;
        $ch = $iy;
        $clip = MImage::imageWithWidthHeight($cw, $ch);
        $clip->copy($this, 0, 0, $ix, 0, $cw, $ch);
        $clip->scale($sw, $iy);
        $dst->copy($clip, $ix, 0, 0, 0, $clip->width(), $clip->height());

        //  right
        $cw = $rw;
        $ch = $ih;
        $clip = MImage::imageWithWidthHeight($cw, $ch);
        $clip->copy($this, 0, 0, $ix + $iw, $iy, $cw, $ch);
        $clip->scale($rw, $sh);
        $dst->copy($clip, $ix + $sw, $iy, 0, 0, $clip->width(), $clip->height());

        //  bottom
        $cw = $iw;
        $ch = $dh;
        $clip = MImage::imageWithWidthHeight($cw, $ch);
        $clip->copy($this, 0, 0, $ix, $iy + $ih, $cw, $ch);
        $clip->scale($sw, $dh);
        $dst->copy($clip, $ix, $iy + $sh, 0, 0, $clip->width(), $clip->height());

        //  center
        $cw = $iw;
        $ch = $ih;
        $clip = MImage::imageWithWidthHeight($cw, $ch);
        $clip->copy($this, 0, 0, $ix, $iy, $cw, $ch);
        $clip->scale($sw, $sh);
        $dst->copy($clip, $ix, $iy, 0, 0, $clip->width(), $clip->height());

        $this->image = $dst->getImage();
    }

    public function copy(MImage $src, $dst_x, $dst_y, $src_x, $src_y, $w, $h) {
        $dst = $this->image;
        return imagecopy($dst, $src->image, $dst_x, $dst_y, $src_x, $src_y, $w, $h);
    }

    public function ttftext($text, $fontfile, $size, $x, $y, $angle = 0, $rgb = 0x000000) {
        $image = $this->image;
        $r = ($rgb >> 16) & 0xff;
        $g = ($rgb >> 8) & 0xff;
        $b = ($rgb >> 0) & 0xff;
        $color = imagecolorallocate($image, $r, $g, $b);
        return imagettftext($image, $size, $angle, $x, $y, $color, $fontfile, $text);
    }

    public function saveTo($path, $quality = 75) {
        imagejpeg($this->image, $path, $quality);
    }

    public function display() {
        header("Content-type: image/png");
        imagepng($this->image);
    }

}

