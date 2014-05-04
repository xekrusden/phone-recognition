<?php

namespace aotd\Captcha;

use Imagick;

interface CaptchaRecognitionInterface {

    /**
     * Prepare image for segmentation
     * @param Imagick $image
     * @return Imagick
     */
    public static function preprocess(Imagick $image);

    /**
     * Get boundary regions from image
     * @param Imagick $image
     * @return array
     */
    public static function segmentation(Imagick $image);

    /**
     * Recognize each symbol from boundary box of original image
     * @param Imagick $image
     * @param array $segments
     * @return string
     */
    public static function recognition(Imagick $image, $segments);

}