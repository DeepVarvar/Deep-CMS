<?php



/**
 * captcha module,
 * rewrite of RECAPTCHA
 */

class captcha_jpg extends baseController {


    private


        /**
         * captcha string length,
         * set integer value or "random" for random length
         */

        $length = "random",


        /**
         * dimension of captcha image
         */

        $width  = 140,
        $height = 52,


        /**
         * foreground color RGB array (0-255, 0-255, 0-255) or "random"
         */

        $foregroundColor = "random",


        /**
         * jpeg image quality percentes
         */

        $jpegQuality = 100,


        /**
         * wave fluctuation amplitude of image
         */

        $fluctuationAmplitude = 5;


    /**
     * generate image and stored keystring into storage
     */

    public function index() {


        /**
         * set configuration environment of image generation
         */

        if ($this->length === "random") {
            $this->length = mt_rand(5, 8);
        }

        $keyString = "";
        if (!$fonts = utils::glob(APPLICATION . "resources/captcha/*.png")) {
            throw new systemErrorException(
                "Captcha error", "Fonts on resources not found"
            );
        }

        $alphabet = "0123456789abcdefghijklmnopqrstuvwxyz";
        $alphabetLength = strlen($alphabet);
        $allowedSymbols = "23456789abcdeghkmnpqsuvxyz";

        if ($this->foregroundColor === "random") {
            $this->foregroundColor = array(
                mt_rand(0,150), mt_rand(0,150), mt_rand(0,15)
            );
        }

        do {

            for ($i = 0; $i < $this->length; $i++) {
                $keyString .= $allowedSymbols{mt_rand(0, strlen($allowedSymbols) - 1)};
            }

            $font = imagecreatefrompng($fonts[mt_rand(0, sizeof($fonts) - 1)]);
            imagealphablending($font, true);

            $fontFileWidth  = imagesx($font);
            $fontFileHeight = imagesy($font) - 1;
            $fontMetrics = array();
            $symbol = 0;
            $readingSymbol = false;

            for ($i = 0; $i < $fontFileWidth && $symbol < $alphabetLength; $i++) {

                $transparent = (imagecolorat($font, $i, 0) >> 24) == 127;
                if(!$readingSymbol && !$transparent) {

                    $fontMetrics[$alphabet{$symbol}] = array("start" => $i);
                    $readingSymbol = true;
                    continue;

                }

                if($readingSymbol && $transparent) {

                    $fontMetrics[$alphabet{$symbol}]['end'] = $i;
                    $readingSymbol = false;
                    $symbol++;
                    continue;

                }

            }

            $img = imagecreatetruecolor($this->width, $this->height);
            imagealphablending($img, true);

            $white = imagecolorallocate($img, 255, 255, 255);
            $black = imagecolorallocate($img, 0, 0, 0);
            imagefilledrectangle(
                $img, 0, 0, $this->width - 1, $this->height - 1, $white
            );

            $x = 1;
            for ($i = 0; $i < $this->length; $i++) {

                $m = $fontMetrics[$keyString{$i}];
                $y = mt_rand( - $this->fluctuationAmplitude, $this->fluctuationAmplitude) + ($this->height - $fontFileHeight) / 2 + 2;
                $shift = 0;

                if($i > 0) {

                    $shift = 10000;
                    for ($sy = 7; $sy < $fontFileHeight - 20; $sy += 1) {
                        for ($sx = $m['start'] - 1; $sx < $m['end']; $sx += 1) {

                            $rgb = imagecolorat($font, $sx, $sy);
                            $opacity = $rgb >> 24;

                            if ($opacity < 127) {

                                $left = $sx - $m['start'] + $x;
                                $py = $sy + $y;

                                if ($py > $this->height) {
                                    break;
                                }

                                for ($px = min($left, $this->width - 1); $px > $left - 12 && $px >= 0; $px -= 1) {

                                    $color = imagecolorat($img, $px, $py) & 0xff;
                                    if($color + $opacity < 190) {

                                        if($shift > $left - $px) {
                                            $shift = $left - $px;
                                        }

                                        break;

                                    }

                                }

                                break;

                            }

                        }
                    }

                    if($shift == 10000) {
                        $shift = mt_rand(4, 6);
                    }

                }

                imagecopy($img, $font, $x - $shift, $y, $m['start'], 1, $m['end'] -$m['start'], $fontFileHeight);
                $x += $m['end'] - $m['start'] - $shift;

            }

        } while ( $x >= ($this->width - 10) );

        $center = $x/2;
        $img2 = imagecreatetruecolor($this->width, $this->height);
        $foreground = imagecolorallocate($img2, $this->foregroundColor[0], $this->foregroundColor[1], $this->foregroundColor[2]);
        $background = imagecolorallocate($img2, 255, 255, 255);

        imagefilledrectangle($img2, 0, 0, $this->width - 1, $this->height - 1, $background);
        imagestring($img2, 2, $this->width - 2, $this->height - 2, 0, $background);


        /**
         * periods
         */

        $rand1 = mt_rand(750000, 1200000)/10000000;
        $rand2 = mt_rand(750000, 1200000)/10000000;
        $rand3 = mt_rand(750000, 1200000)/10000000;
        $rand4 = mt_rand(750000, 1200000)/10000000;


        /**
         * phases
         */

        $rand5 = mt_rand(0, 31415926)/10000000;
        $rand6 = mt_rand(0, 31415926)/10000000;
        $rand7 = mt_rand(0, 31415926)/10000000;
        $rand8 = mt_rand(0, 31415926)/10000000;


        /**
         * amplitudes
         */

        $rand9  = mt_rand(330, 420)/110;
        $rand10 = mt_rand(330, 450)/110;


        /**
         * wave distortion
         */

        for ($x = 0; $x < $this->width; $x++) {
            for ($y = 0; $y < $this->height; $y++) {

                $sx = $x + (sin($x * $rand1 + $rand5) + sin($y * $rand3 + $rand6)) * $rand9 - $this->width / 2 + $center + 1;
                $sy = $y + (sin($x * $rand2 + $rand7) + sin($y * $rand4 + $rand8)) * $rand10;

                if ($sx < 0 || $sy < 0 || $sx >= $this->width - 1 || $sy >= $this->height - 1) {
                    continue;
                } else {

                    $color    = imagecolorat($img, $sx, $sy) & 0xFF;
                    $color_x  = imagecolorat($img, $sx + 1, $sy) & 0xFF;
                    $color_y  = imagecolorat($img, $sx, $sy + 1) & 0xFF;
                    $color_xy = imagecolorat($img, $sx + 1, $sy + 1) & 0xFF;

                }


                if ($color == 255 && $color_x == 255 && $color_y == 255 && $color_xy == 255) {
                    continue;
                } else if ($color == 0 && $color_x == 0 && $color_y == 0 && $color_xy == 0) {

                    $newred   = $this->foregroundColor[0];
                    $newgreen = $this->foregroundColor[1];
                    $newblue  = $this->foregroundColor[2];

                } else {

                    $frsx  = $sx - floor($sx);
                    $frsy  = $sy - floor($sy);
                    $frsx1 = 1 - $frsx;
                    $frsy1 = 1 - $frsy;

                    $newcolor = $color * $frsx1 * $frsy1 + $color_x * $frsx * $frsy1 + $color_y * $frsx1 * $frsy + $color_xy * $frsx * $frsy;
                    if ($newcolor > 255) {
                        $newcolor = 255;
                    }

                    $newcolor  = $newcolor/255;
                    $newcolor0 = 1 - $newcolor;
                    $newred    = $newcolor0 * $this->foregroundColor[0] + $newcolor * 255;
                    $newgreen  = $newcolor0 * $this->foregroundColor[1] + $newcolor * 255;
                    $newblue   = $newcolor0 * $this->foregroundColor[2] + $newcolor * 255;

                }

                imagesetpixel($img2, $x, $y, imagecolorallocate($img2, $newred, $newgreen, $newblue));

            }

        }

        imagedestroy($img);
        storage::write("captcha", $keyString);
        request::addHeader("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
        request::addHeader("Cache-Control: no-store, no-cache, must-revalidate");
        request::addHeader("Cache-Control: post-check=0, pre-check=0", false);
        request::addHeader("Pragma: no-cache");
        request::addHeader("Content-Type: image/jpeg");

        request::sendHeaders();
        imagejpeg($img2, null, $this->jpegQuality);
        exit();


    }


}



