<?php

namespace aotd\Captcha;

use Imagick;
use ImagickPixel;

class AvitoPhone implements CaptchaRecognitionInterface {

    public static $templatesPath;

    private static $templates;

    /**
     * @param Imagick $image
     * @return Imagick
     */
    public static function preprocess(Imagick $image)
    {
        // Nothing to do
        return $image;
    }

    /**
     * Get boundary regions from original image
     * @param Imagick $image
     * @param int $maxSegment
     * @return Region[]
     */
    public static function segmentation(Imagick $image, $maxSegment = 7)
    {
        $regions = array();

        list($rows, $cols) = self::vectors($image->getpixeliterator());
        // Top image border
        reset($rows);
        $top = 0;
        while ( next($rows) === 0 ) $top++;

        // Bottom image border
        $bottom = $top;
        while ( next($rows) != 0 ) $bottom++;

        $state = 0;
        $width = 0;
        $left = 0;
        foreach ($cols as $i=>$column) {
            $width++;
            if ($column>0 && $state !== 1) {
                $left = $i;
                $state = 1;
                $width = 0;
            } elseif ($state === 1 && ($column == 0 || $width >= $maxSegment)) {
                $state = 0;
                $regions[] = new Region($left, $top+1, $width, $bottom-$top+1);
            }
        }

        return $regions;
    }

    /**
     * Get region rows summ and columns summ vectors
     * @param \ImagickPixelIterator $iterator
     * @return array(int[], int[])
     */
    public static function vectors(\ImagickPixelIterator $iterator)
    {
        $rows = array();
        $cols = array();

        foreach( $iterator as $row => $pixels ) {
            /* @var ImagickPixel $pixel */
            foreach ( $pixels as $column => $pixel )
            {
                $pixelFill = 255*3 + 1 - array_sum($pixel->getcolor());
                isset ($rows[$row]) ? $rows[$row] += $pixelFill : $rows[$row] = $pixelFill;
                isset ($cols[$column]) ? $cols[$column] += $pixelFill : $cols[$column] = $pixelFill;
            }
        }
        return array($rows, $cols);
    }

    /**
     * Recognize each symbol from boundary box of original image
     * @param Imagick $image
     * @param array $segments
     * @return string
     */
    public static function recognition(Imagick $image, $segments)
    {
        $digits = '';
        foreach ($segments as $segment) {
            $digitVectors = self::vectors(
                $image->getpixelregioniterator($segment->x, $segment->y, $segment->columns, $segment->rows)
            );
            $digits .= self::compareWithTemplates($digitVectors);
        }
        return $digits;
    }

    /**
     * Returns symbol with minimal root mean square error. No penalty or error threshold
     * RMSE between rows vectors of template and predicted region
     * @param array $digitVectors
     * @return string
     */
    public static function compareWithTemplates($digitVectors)
    {
        self::loadTemplates();
        $minExtimate = PHP_INT_MAX;
        $estimatedDigit = '';
        foreach (self::$templates as $digit => $template) {
            $rms = self::rmse($template[0], $digitVectors[0]);
            if ($rms < $minExtimate) {
                $estimatedDigit = $digit;
                $minExtimate = $rms;
            }
        }
        return $estimatedDigit;
    }

    /**
     * Root mean square error
     * @param array $a
     * @param array $b
     * @return float
     */
    private static function rmse($a, $b)
    {
        $summ = 0;
        foreach ($a as $i => $value) {
            $value2 = isset($b[$i]) ? $b[$i] : 0;
            $summ += pow(2, $value -  $value2);
        }
        return sqrt($summ/count($a));
    }

    /**
     * Fill self::$templates vectors from original template symbols
     */
    protected static function loadTemplates()
    {
        if (empty(self::$templates)) {
            $iterator = new \DirectoryIterator(self::getTemplatesPath());
            /* @var \DirectoryIterator $file */
            foreach ($iterator as $file) {
                if ($file->getExtension() === 'png') {
                    $image = new Imagick($file->getPathname());
                    self::$templates[$file->getBasename('.png')] = self::vectors($image->getpixeliterator());
                }
            }
        }
    }

    /**
     * @return string
     */
    public static function getTemplatesPath()
    {
        if (self::$templatesPath === null) {
            self::$templatesPath = __DIR__ . '/../../../templates/';
        }
        return self::$templatesPath;
    }

}