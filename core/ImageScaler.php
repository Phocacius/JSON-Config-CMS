<?php

/**
 * Utility class to scale and crop jpeg, png or gif images into variable formats
 * Uses imagick (https://www.php.net/manual/de/book.imagick.php) if available or
 * GD (https://www.php.net/manual/de/book.image.php) as fallback
 *
 * The target size of the image can be defined using a scaleString. The following formats are supported:
 * - [size] (e.g. 500): scales the image to a square of [size] pixels. If the aspect ratio does not fit,
 *   the image will be center cropped to fit a square
 * - [width]x (e.g. 500x): scales the image to a width of [width] pixels. The height is adjusted to maintain
 *   the image's aspect ratio
 * - x[height] (e.g. x500): scales the image to a height of [width] pixels. The width is adjusted to maintain
 *   the image's aspect ratio
 * - sw[smallest-width]: scaled the image to a height or width of [smallest-width] pixels, whatever is smaller.
 *   The other dimension is adjusted to be at least as big as the [smallest-width], maintaining aspect ratio
 * - lw[largest-width]: scaled the image to a height or width of [largest-width] pixels, whatever is larger.
 *   The other dimension is adjusted to be at most as big as the [largest-width], maintaining aspect ratio
 * - [width]x[height] (e.g. 500x700): scales the image to a fixed pixel size. If the aspect ratio does not fit,
 *   the image will be center cropped to fit the given aspect ratio
 */
class ImageScaler {

    private $path;

    /**
     * @param string $path filepath to the source image
     */
    public function __construct(string $path) {
        $this->path = $path;
    }

    /**
     * Scales/crops the source image according to the $scaleString and saves it to disk
     * @param string $scaleString see class phpdoc for details
     * @param string $target file path where the target image should be saved to
     * @throws ImagickException
     */
    public function scale(string $scaleString, string $target) {
        if (class_exists("Imagick")) {
            $im = new Imagick(realpath($this->path));

            if (strpos($scaleString, "x") !== false) {
                $split = explode("x", $scaleString);
                $w = is_numeric($split[0]) ? $split[0] : 0;
                $h = is_numeric($split[1]) ? $split[1] : 0;
                if ($w > 0 && $h > 0) {
                    $im->cropThumbnailImage($w, $h);
                } else {
                    $im->scaleImage($w, $h);
                }
            } elseif (strpos($scaleString, "sw") !== false) {
                $sw = (int) (substr($scaleString, 2));
                if ($im->getImageWidth() > $im->getImageHeight()) {
                    $im->scaleImage(0, $sw);
                } else {
                    $im->scaleImage($sw, 0);
                }
            } elseif (strpos($scaleString, "lw") !== false) {
                $lw = (int) (substr($scaleString, 2));
                if ($im->getImageWidth() > $im->getImageHeight()) {
                    $im->scaleImage($lw, 0);
                } else {
                    $im->scaleImage(0, $lw);
                }
            } else {
                $im->scaleImage($scaleString, $scaleString, true);
            }
            $dirname = dirname($target);
            if (!is_dir($dirname)) mkdir($dirname, 0777, true);
            $im->writeImage($target);

        } else {
            $im = $this->imageCreateFromAny(realpath($this->path));
            if ($im === false) {
                throw new Exception("Nicht unterstützter Dateityp. Verwenden Sie GIF, JPEG oder PNG Bilder.");
            }

            $srcWidth = imagesx($im);
            $srcHeight = imagesy($im);
            $srcCoords = [0, 0, $srcWidth, $srcHeight];

            if (strpos($scaleString, "x") !== false) {
                $split = explode("x", $scaleString);
                $targetWidth = is_numeric($split[0]) ? $split[0] : 0;
                $targetHeight = is_numeric($split[1]) ? $split[1] : 0;

                if ($targetWidth > 0 && $targetHeight > 0) {
                    $xRatio = ($srcWidth / (double)$targetWidth);
                    $yRatio = ($srcHeight / (double)$targetHeight);
                    if ($xRatio > $yRatio) {
                        $paddingX = ($srcWidth - ($yRatio * $targetWidth)) / 2;
                        $srcCoords[0] = (int) $paddingX;
                        $srcCoords[2] = (int) ($srcWidth - $paddingX);
                    } elseif ($yRatio > $xRatio) {
                        $paddingY = ($srcHeight - ($xRatio * $targetHeight)) / 2;
                        $srcCoords[1] = (int) $paddingY;
                        $srcCoords[3] = (int) ($srcHeight - $paddingY);
                    }
                } elseif ($targetWidth > 0) {
                    $targetHeight = (int)($targetWidth / ($srcWidth / (double)$srcHeight));
                } elseif ($targetHeight > 0) {
                    $targetWidth = (int)($targetHeight / ($srcHeight / (double)$srcWidth));
                } else {
                    throw new Exception("Ungültiger scaleString $scaleString");
                }
            } elseif (strpos($scaleString, "sw") !== false) {
                $sw = (int) (substr($scaleString, 2));
                if ($srcWidth > $srcHeight) {
                    $targetHeight = $sw;
                    $targetWidth = (int)($targetHeight / ($srcHeight / (double)$srcWidth));
                } else {
                    $targetWidth = $sw;
                    $targetHeight = (int)($targetWidth / ($srcWidth / (double)$srcHeight));
                }
            } elseif (strpos($scaleString, "lw") !== false) {
                $lw = (int) (substr($scaleString, 2));
                if ($srcWidth > $srcHeight) {
                    $targetWidth = $lw;
                    $targetHeight = (int)($targetWidth / ($srcWidth / (double)$srcHeight));
                } else {
                    $targetHeight = $lw;
                    $targetWidth = (int)($targetHeight / ($srcHeight / (double)$srcWidth));
                }
            } else {
                $targetSize = (int)$scaleString;
                if ($targetSize < 1) throw new Exception("Ungültiger scaleString $scaleString");

                if ($srcWidth > $srcHeight) {
                    $targetWidth = $targetSize;
                    $targetHeight = (int)($targetWidth / ($srcWidth / (double)$srcHeight));
                } else {
                    $targetHeight = $targetSize;
                    $targetWidth = (int)($targetHeight / ($srcHeight / (double)$srcWidth));
                }                
            }

            $srcType = exif_imagetype(realpath($this->path));
            $thumb = imagecreatetruecolor($targetWidth, $targetHeight);
            if ($srcType == 3) {
                // for pngs, make sure to preserve alpha channel
                imagesavealpha($thumb, true);
                $trans_colour = imagecolorallocatealpha($thumb, 0, 0, 0, 127);
                imagefill($thumb, 0, 0, $trans_colour);
            }


            imagecopyresampled($thumb, $im, 0, 0, $srcCoords[0], $srcCoords[1], $targetWidth, $targetHeight, $srcCoords[2] - $srcCoords[0], $srcCoords[3] - $srcCoords[1]);

            $dirname = dirname($target);
            if (!is_dir($dirname)) mkdir($dirname, 0777, true);

            switch ($srcType) {
                case 1 :
                    imagegif($thumb, $target);
                    break;
                case 2 :
                    imagejpeg($thumb, $target);
                    break;
                case 3 :
                    imagepng($thumb, $target);
                    break;
            }
        }
    }

    private function correctImageOrientation($resource, $filename) {
        if (function_exists('exif_read_data')) {
            $exif = @exif_read_data($filename);
            if ($exif && isset($exif['Orientation'])) {
                $orientation = $exif['Orientation'];
                if ($orientation != 1) {
                    $deg = 0;
                    switch ($orientation) {
                        case 3:
                            $deg = 180;
                            break;
                        case 6:
                            $deg = 270;
                            break;
                        case 8:
                            $deg = 90;
                            break;
                    }
                    if ($deg) {
                        $resource = imagerotate($resource, $deg, 0);
                    }
                }
            }
        }
        return $resource;
    }

    private function imageCreateFromAny($path) {
        $type = exif_imagetype($path);
        $allowedTypes = array(
            1,  // [] gif
            2,  // [] jpg
            3  // [] png
        );
        if (!in_array($type, $allowedTypes)) {
            return false;
        }
        switch ($type) {
            case 1 :
                $im = imageCreateFromGif($path);
                break;
            case 2 :
                $im = imageCreateFromJpeg($path);
                break;
            case 3 :
                $im = imageCreateFromPng($path);
                break;
            default:
                throw new Exception("Unknown image format detected");
        }
        return $this->correctImageOrientation($im, $path);
    }

}