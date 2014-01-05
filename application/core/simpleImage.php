<?php


/**
 * simple image library class
 */

class simpleImage {


    /**
     * image resource descriptor and type of input image
     */

    private $image, $type;


    /**
     * return image extension string
     */

    public static function typeToExtension($type) {

        $allowedTypes = array(
            IMAGETYPE_GIF  => "gif",
            IMAGETYPE_JPEG => "jpg",
            IMAGETYPE_PNG  => "png"
        );

        if (array_key_exists($type, $allowedTypes)) {
            return $allowedTypes[$type];
        }

    }


    /**
     * load image file
     */

    public function __construct($file) {


        if(is_file($file)) {

            if (!$info = getimagesize($file)) {
                throw new systemErrorException(
                    "Load image error", "File is not image"
                );
            }

            $this->type = $info[2];
            switch ($this->type) {

                case IMAGETYPE_JPEG:
                    $this->image = imagecreatefromjpeg($file);
                break;

                case IMAGETYPE_GIF:

                    $this->image = imagecreatefromgif($file);

                    // gif alpha channel fix
                    $w = $this->getWidth();
                    $h = $this->getHeight();

                    $back = imagecreatetruecolor($w, $h);
                    imagesavealpha($back, true);

                    $alpha = imagecolorallocatealpha($back, 0, 0, 0, 0);
                    $transparent = imagecolortransparent($back, $alpha);

                    imagefill($back, 0, 0, $transparent);
                    imagecopy($back, $this->image, 0, 0, 0, 0, $w, $h);
                    $this->image = $back;

                break;

                case IMAGETYPE_PNG:
                    $this->image = imagecreatefrompng($file);
                break;

                default:
                    throw new systemErrorException(
                        "Load image error", "Unsupported image format"
                    );
                break;

            }

            imagealphablending($this->image, true);
            return $this;

        }


    }


    /**
     * save image file
     */

    public function save($file, $permissions = 0664,
                            $quality = 100, $type = null) {

        if(!$type) {
            $type = $this->type;
        }

        switch ($type) {

            case IMAGETYPE_JPEG:
                imagejpeg($this->image, $file, $quality);
            break;

            case IMAGETYPE_GIF:
                imagegif($this->image, $file);
            break;

            case IMAGETYPE_PNG:
                imagealphablending($this->image, false);
                imagesavealpha($this->image, true);
                imagepng($this->image, $file);
            break;

            default:
                throw new systemErrorException(
                    "Save image error", "Unsupported image format"
                );
            break;

        }

        chmod($file, $permissions);
        return $this;

    }


    /**
     * flush image data
     */

    public function output($type = null) {

        if(!$type) {
            $type = $this->type;
        }

        switch ($type) {

            case IMAGETYPE_JPEG:
                request::addHeader("Content-Type: image/jpeg");
                request::sendHeaders();
                imagejpeg($this->image);
            break;

            case IMAGETYPE_GIF:
                request::addHeader("Content-Type: image/gif");
                request::sendHeaders();
                imagegif($this->image);
            break;

            case IMAGETYPE_PNG:
                request::addHeader("Content-Type: image/x-png");
                request::sendHeaders();
                imagealphablending($this->image, false);
                imagesavealpha($this->image, true);
                imagepng($this->image);
            break;

            default:
                throw new systemErrorException(
                    "Flush image error", "Unsupported image format"
                );
            break;

        }

        imagedestroy($this->image);

    }


    /**
     * return width of image
     */

    public function getWidth() {
        return imagesx($this->image);
    }


    /**
     * return height of image
     */

    public function getHeight() {
        return imagesy($this->image);
    }


    /**
     * intelligent resize of image
     */

    public function intelligentResize($width, $height, $stretch = false) {

        $w = $this->getWidth();
        $h = $this->getHeight();

        $aspectRatio    = $width/$height;
        $aspectRatioImg = $w/$h;

        if($aspectRatioImg > $aspectRatio) {
            if ($stretch or $w > $width) {
                $this->resizeToWidth($width);
            }
        } else {
            if ($stretch or $h > $height) {
                $this->resizeToHeight($height);
            }
        }

        return $this;

    }


    /**
     * crop image edges to square
     */

    public function squareCrop() {

        $w = $this->getWidth();
        $h = $this->getHeight();

        $cropX = $w>$h?round(($w-$h)/2):0;
        $cropY = $h>$w?round(($h-$w)/2):0;

        // one fucking pixel
        if ($cropY > 0) {
            $w -= 1;
        }

        $this->resize($w, $h, $cropX, $cropY);
        return $this;

    }


    /**
     * soft image resize to height
     */

    public function resizeToHeight($height) {

        $ratio = $height/$this->getHeight();
        $width = $this->getWidth() * $ratio;

        $this->resize($width, $height, 0, 0);
        return $this;

    }


    /**
     * soft image resize to width
     */

    public function resizeToWidth($width) {

        $ratio  = $width/$this->getWidth();
        $height = $this->getheight() * $ratio;

        $this->resize($width, $height, 0, 0);
        return $this;

    }


    /**
     * hard scale stretch image
     */

    public function scale($scale) {

        $width  = $this->getWidth() * $scale / 100;
        $height = $this->getheight() * $scale / 100;

        $this->resize($width, $height, 0, 0);
        return $this;

    }


    /**
     * resize image
     */

    public function resize($width, $height, $cropX, $cropY) {

        $dCropX = $cropX * 2;
        $dCropY = $cropY * 2;

        $width  = round( $width  - ($dCropX / $this->getWidth()  * $width ) );
        $height = round( $height - ($dCropY / $this->getHeight() * $height) );

        $new = imagecreatetruecolor($width, $height);
        if ($this->type == IMAGETYPE_PNG) {
            imagealphablending($new, false);
            imagesavealpha($new, true);
        }

        imagecopyresampled(
            $new, $this->image, 0, 0, $cropX, $cropY, $width, $height,
            $this->getWidth() - $dCropX, $this->getHeight() - $dCropY
        );

        $this->image = $new;
        return $this;

    }


    /**
     * return image resource
     */

    public function getImageResource() {
        return $this->image;
    }


    /**
     * add watermark
     */

    public function addWaterMark($image) {

        if (file_exists($image)) {

            $watermark = new self($image);

            $tw = $this->getWidth();
            $th = $this->getHeight();

            $watermark->intelligentResize($tw, $th, true);
            $wResource = $watermark->getImageResource();

            $ww = $watermark->getWidth();
            $wh = $watermark->getHeight();

            $w = round( ($tw - $ww) / 2 );
            $h = round( ($th - $wh) / 2 );

            imagecopy($this->image, $wResource, $w, $h, 0, 0, $ww, $wh);
            imagedestroy($wResource);
            unset($watermark);

        }

    }


}


