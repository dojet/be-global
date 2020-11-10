<?php
class ImageKit {

    protected $imagePathFile;
    protected $res;
    protected $img_info;
    protected $dst_im;

    const IMG_WIDTH = 0;
    const IMG_HEIGHT = 1;
    const IMG_TYPE = 2;

    function __construct($imagePathFile) {
        DAssert::assert(extension_loaded('gd') || dl('gd'), 'gd lib not exist');
        $this->imagePathFile = $imagePathFile;
    }

    public function load($forceReload = false)
    {

        if (is_resource($this->res) && !$forceReload) {
            return $this->res;
        }

        $this->res = null;

        $this->img_info = getimagesize($this->imagePathFile);

//      list($this->_width, $this->_height, $imgtype, $imginfo, $bits, $channels, $mime) = $info;
//      0 => 1221
//      1 => 849
//      2 => 2
//      3 => width="1221" height="849"
//      bits => 8
//      channels => 3
//      mime => image/jpeg

        switch ($this->img_info[IMG_TYPE])
        {
            case 3:
                $this->res = imagecreatefrompng($this->imagePathFile);
                break;

            case 2:
                $this->res = imagecreatefromjpeg($this->imagePathFile);
                break;

            case 1:
                $this->res = imagecreatefromgif($this->imagePathFile);
                break;

            default:
                $imgstr = file_get_contents($this->imagePathFile);
                $this->res = @imagecreatefromstring($imgstr);
        }

        return $this->res;
    }

    public function convert($width = null, $height = null)
    {
        if ( is_numeric($width) && is_numeric($height)  ) {
            return $this->_convert_both($width, $height);
        } elseif ( is_numeric($width) ) {
            return $this->_convert_by_width($width);
        } elseif ( is_numeric($height) ) {
            return $this->_convert_by_height($height);
        } else {
            return null;
        }
    }

    public static function getImageExt() {
        $this->load();
        $imginfo = $this->img_info;
        switch ($imginfo[self::IMG_TYPE]) {
            case 1:
                $ext = 'gif';
                break;
            case 2:
                $ext = 'jpg';
                break;
            case 3:
                $ext = 'png';
            default:
                $ext = null;
                break;
        }
        return $ext;
    }

    public static function createImage($type, $file){
        switch ($type) {
            case 1:
                $image = imagecreatefromgif($file);
                break;
            case 2:
                $image = imagecreatefromjpeg($file);
                break;
            case 3:
                $image = imagecreatefrompng($file);
                break;
            default:
                $image = false;
        }
        return $image;
    }

    public static function storeImage(&$image, $desFile, $usm=false){
        if($usm) {
            $image->unsharpmaskimage(0, 0, 0.8, 0.03);
        }
        $image->setImageCompression(imagick::COMPRESSION_JPEG);
        $image->setcompressionquality(100);
        $image->setimagecompressionquality(100);
        $storeRs = $image->writeImage($desFile);
        return $storeRs;
    }

    public static function scaleImageNoBlank($file, $desFile, $twidth, $theight, $setWater=1)
    {
        $result['code'] = S_OK;
        $result['info'] = 'resize success';
        $image = new Imagick($file);

        do{
            if($twidth == 0 || $theight == 0) {
                $result['code'] = E_PARAM;_INVALID;
                $result['info'] = 'the param is invalid';
                break;
            }
            $rs = $image->cropthumbnailimage($twidth, $theight);
            if($rs == false) {
                $result['code'] = E_CONVERT_FAILED;
                $result['info'] = 'the cropthumbnailimage function failed';
                break;
            }

            //$image->contrastimage(true);
            //$image->sharpenimage(0.5, 0.5);
            if($setWater === 1){
                self::setWaterMask($image);
            }
            $storeRs = self::storeImage($image, $desFile, true);
            if($storeRs == false) {
                $result['code'] = E_CONVERT_FAILED;
                $result['info'] = 'the image store function failed';
                break;
            }
            Trace::debug('scaleImageNoBlank('.$file.', '.$desFile.', '.$twidth.', '.$theight.')');
        } while(false);

        if(!empty($image)){
            $image->destroy();
        }

        return $result;
    }

    public static function scaleImageNoDeform($file, $desFile, $twidth, $theight, $setWater=1)
    {
        $result['code'] = S_OK;
        $result['info'] = 'resize success';
        $image = new Imagick($file);
        do{
            if($twidth == 0 || $theight == 0) {
                $result['code'] = E_PARAM_INVALID;
                $result['info'] = 'the param is invalid';
                break;
            }

            $imgSize = $image->getimagegeometry();
            $width = (int)$imgSize['width'];
            $height = (int)$imgSize['height'];

            //如果缩略图比原图还大，则直接加水印拷贝
            if($width <= $twidth && $height <= $theight) {
                self::setWaterMaskForImage($file, $desFile, $setWater);
                break;
            }
            $wscale = $width / $twidth;
            $hscale = $height / $theight;
            if($wscale > $hscale) {
                $theight = $height / $wscale;
            } else {
                $twidth = $width / $hscale;
            }

            $rs = $image->scaleimage($twidth, $theight);
            if($rs == false) {
                $result['code'] = E_CONVERT_FAILED;
                $result['info'] = 'the scaleimage function failed';
                break;
            }

            //$image->contrastimage(true);
            //$image->sharpenimage(0.5, 0.5);
            self::setWaterMask($image, $setWater);

            $storeRs = self::storeImage($image, $desFile, true);
            if($storeRs == false) {
                $result['code'] = E_CONVERT_FAILED;
                $result['info'] = 'the image store function failed';
                break;
            }
            Trace::debug('scaleImageNoDeform('.$file.', '.$desFile.', '.$twidth.', '.$theight.')');
        } while(false);

        if(!empty($image)){
            $image->destroy();
        }

        return $result;
    }

    public static function scaleImageFixWidth($srcPath, $dstPath, $twidth, $setWater=1){
        $result['code'] = S_OK;
        $result['info'] = 'resize success';
        $image = new Imagick($srcPath);
        do{
            if($twidth == 0) {
                $result['code'] = E_PARAM;_INVALID;
                $result['info'] = 'the param x is invalid';
                break;
            }

            $imgSize = $image->getimagegeometry();
            $width = (int)$imgSize['width'];
            $height = (int)$imgSize['height'];

            //如果缩略图比原图还大，则直接加水印拷贝
            if($width <= $twidth) {
                self::setWaterMaskForImage($srcPath, $dstPath, $setWater);
                break;
            }
            $theight = $height*$twidth/$width;
            $rs = $image->scaleimage($twidth, $theight);
            if($rs == false) {
                $result['code'] = E_CONVERT_FAILED;
                $result['info'] = 'the scaleimage function failed';
                break;
            }

            //$image->contrastimage(true);
            //$image->sharpenimage(0.5, 0.5);
            if($setWater === 1){
                self::setWaterMask($image);
            }

            $storeRs = self::storeImage($image, $dstPath, true);
            if($storeRs == false) {
                $result['code'] = E_CONVERT_FAILED;
                $result['info'] = 'the image store function failed';
                break;
            }
            Trace::debug('scaleImageFixWidth('.$srcPath.', '.$dstPath.', '.$twidth.', '.$theight.')');
        } while(false);

        if(!empty($image)){
            $image->destroy();
        }

        return $result;
    }

    public static function cropImage($file, $saveFile, $x, $y, $w, $h, $dw=null, $dh=null,  $setWater=1)
    {
        $image = new Imagick($file);
        $result = $image->cropImage($w, $h, $x, $y);
        if (!$result) {
            return MResult::result(MResult::FAIL, 'cropImage failed');
        }

        if(is_numeric($dw) && $dw > 0  && is_numeric($dh) && $dh>0){
            $result = $image->cropThumbnailImage($dw, $dh);
            if (!$result) {
                return MResult::result(MResult::FAIL, 'cropThumbnailImage failed');
            }
        }

        //$image->contrastimage(true);
        //$image->sharpenimage(0.5, 0.5);
        if($setWater === 1){
            self::setWaterMask($image);
        }

        $result = self::storeImage($image, $saveFile, true);
        if (!$result) {
            return MResult::result(MResult::FAIL, 'writeImage failed');
        }

        return MResult::result(MResult::SUCCESS, $saveFile);
    }

    function _convert_by_width($width)
    {
//      trace("convert width: $width");

        if ( $width >= $this->img_info[IMG_WIDTH] ) {
            return $this->res;
        }

        $k = $width / $this->img_info[IMG_WIDTH];

        $cutWidth = $width;
        $cutHeight = $k * $this->img_info[IMG_HEIGHT];

//      trace("cut: $cutWidth, $cutHeight");

        $retImg = $this->_convert_img(0, 0, $this->img_info[IMG_WIDTH], $this->img_info[IMG_HEIGHT], $cutWidth, $cutHeight);

        return $retImg;
    }

    function _convert_by_height($height)
    {
        if ( $height >= $this->img_info[IMG_HEIGHT] ) {
            return $this->res;
        }

        $k = $height / $this->img_info[IMG_HEIGHT];

        $cutHeight = $height;
        $cutWidth = $k * $this->img_info[IMG_WIDTH];

        $retImg = $this->_convert_img(0, 0, $this->img_info[IMG_WIDTH], $this->img_info[IMG_HEIGHT], $cutWidth, $cutHeight);

//      trace($retImg ? "convert succeed." : "convert failed.");

        return $retImg;
    }

    function _convert_both($width, $height)
    {
        $src_ratio = $this->img_info[IMG_WIDTH] / $this->img_info[IMG_HEIGHT];
        $dst_ratio = $width / $height;

        if ($src_ratio < $dst_ratio) {
            $src_w = $this->img_info[IMG_WIDTH];
            $src_h = $this->img_info[IMG_WIDTH] / $dst_ratio;

            $src_y = ( $this->img_info[IMG_HEIGHT] - $src_h ) / 2;
            $src_x = 0;
        } else {
            $src_w = $dst_ratio * $this->img_info[IMG_HEIGHT];
            $src_h = $this->img_info[IMG_HEIGHT];

            $src_y = 0;
            $src_x = ( $this->img_info[IMG_WIDTH] - $src_w ) / 2;
        }

        $convImg = $this->_convert_img($src_x, $src_y, $src_w, $src_h, $width, $height);

//      trace($convImg ? "convert succeed." : "convert failed.");

        return $convImg;
    }

    function _convert_img($src_x, $src_y, $src_w, $src_h, $dst_width, $dst_height)
    {
        $dst_im = imagecreatetruecolor($dst_width, $dst_height);

        $ret = imagecopyresampled($dst_im, $this->res,
                         0, 0, $src_x, $src_y,
                         $dst_width, $dst_height,
                         $src_w, $src_h
                        );
//      trace("convert image: $src_x, $src_y, $src_w, $src_h, $dst_width, $dst_height");

        if ( !$ret ) {
//          trace("copy image failed.");
        }

        $this->dst_im = $dst_im;

        return $this->dst_im;
    }

    public function unload() {
        if ( is_resource($this->res) ) {
            imagedestroy($this->res);
        }
    }

    function output($filename, $quality = 80, $destory = true)
    {
        if (!is_resource($this->res)) {
//          trace("image is null, failed.");
            return false;
        }

        $res = is_resource($this->dst_im) ? ($this->dst_im) : ($this->res);
        $ret = imagejpeg($res, $filename, $quality);

        if ( $destory && is_resource($this->res) ) {
            $this->unload();
        }

        return $ret;
    }

    function __destruct() {
        $this->unload();
    }

    function setWaterMask(&$image, $setWater = 1) {
        if($setWater !== C_SET_WATER && $setWater !== C_SET_WATER_2){
            return;
        }
        $mask = new Imagick('../webroot/static/image/mask.png');
        $maskSize = $mask->getimagegeometry();
        $width = $maskSize['width'];
        $height = $maskSize['height'];
        $imgSize = $image->getimagegeometry();
        $srcImg_w = $imgSize['width'];
        $srcImg_h = $imgSize['height'];

        $result = MResult::result(MResult::SUCCESS);
        do{
            //水印与图片面积比为：0.00694
            $maskArea = intval($srcImg_w * $srcImg_h * 0.00694);
            $maskh = sqrt($maskArea / 6);
            $maskw = $maskh * 6;
            if($maskw == 0 || $maskh == 0){
                $result = MResult::result(MResult::FAIL, 'setWaterMaskForImage result mask size is too small');
                break;
            }
            $wscale = $width / $maskw;
            $hscale = $height / $maskh;
            if($wscale > $hscale){
                $maskh = $height / $wscale;
            } else {
                $maskw = $width / $hscale;
            }
            $mask->scaleimage($maskw, $maskh);


            $ltx = $maskh;
            $lty = $maskh;
            $cx = ($srcImg_w - $maskw) / 2;
            $cy = ($srcImg_h - $maskh) / 2;
            $rbx = $srcImg_w - $maskw - $ltx;
            $rby = $srcImg_h - $maskh -$lty;

            $composite = $mask->getimagecompose();
            $image->compositeimage($mask, $composite, $ltx, $lty);
            if($setWater !== C_SET_WATER_2){
                $image->compositeimage($mask, $composite, $cx, $cy);
            }
            $image->compositeimage($mask, $composite, $rbx, $rby);
        }while(false);

        if(!empty($mask)){
            $mask->destroy();
        }

        return $result;
    }

    public static function setWaterMaskForImage($srcFile, $desFile, $setWater = 1)
    {
        $result =  MResult::result(MResult::SUCCESS, 'set water success');
        $image = new Imagick($srcFile);

        do{
            $result = self::setWaterMask($image, $setWater);
            if($result->error != MResult::SUCCESS){
                break;
            }

            $storeRs = self::storeImage($image, $desFile);
            if($storeRs == false) {
                $result = MResult::result(MResult::FAIL, 'setWaterMaskForImage store failed, desFile='.$desFile);
                break;
            }
        }while(false);

        if(!empty($image)){
            $image->destroy();
        }

        return $result;
    }
}
