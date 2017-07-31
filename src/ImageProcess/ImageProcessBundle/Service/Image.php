<?php

namespace ImageProcess\ImageProcessBundle\Service;

use Symfony\Component\HttpFoundation\File\MimeType\FileinfoMimeTypeGuesser;
use ImageProcess\ImageProcessBundle\Exception\ImageResizeException;

class Image
{
    const CROP_TOP = 1;
    const CROP_CENTER = 2;
    const CROP_BOTTOM = 3;
    const CROP_LEFT = 4;
    const CROP_RIGHT = 5;
    const CROP_TOP_CENTER = 6;

    const ROTATE_90 = 90;
    const ROTATE_180 = 180;
    const ROTATE_270 = 270;

    const EXIF_TOPLEFT = 1;
    const EXIF_TOPRIGHT = 2;
    const EXIF_BOTTOMRIGHT = 3;
    const EXIF_BOTTOMLEFT = 4;
    const EXIF_LEFTTOP = 5;
    const EXIF_RIGHTTOP = 6;
    const EXIF_RIGHTBOTTOM = 7;
    const EXIF_LEFTBOTTOM = 8;

    const EXPECTED_MIME = 'image';

    public $quality_jpg = 90;
    public $quality_png = 9;
    public $quality_truecolor = TRUE;

    public $interlace = 1;
    public $source_type;

    protected $source_image;
    protected $orig_width;
    protected $orig_height;
    protected $dest_x = 0;
    protected $dest_y = 0;

    protected $source_x;
    protected $source_y;

    protected $dest_width;
    protected $dest_height;

    protected $source_width;
    protected $source_height;

    protected $source_info;

    public function __construct($filename)
    {
        if( $filename === null || empty($filename) || (substr($filename, 0, 7) !== 'data://' && !is_file($filename)) )
        {
            throw new ImageResizeException('File does not exist or could not open file.');
        }

        $mimeTypeGuesser = new FileinfoMimeTypeGuesser();

        if( !$mimeTypeGuesser->isSupported() )
        {
            throw new ImageResizeException('Fileinfo is not supported, please enable in your php.ini');
        }

        $mimeType = $mimeTypeGuesser->guess($filename);

        if( $mimeType !== self::EXPECTED_MIME )
        {
            throw new ImageResizeException('File is not an image');
        }

        $image_info = getimagesize($filename, $this->source_info);

        if( !$image_info )
        {
            throw new ImageResizeException('Could not read file');
        }

        list( $this->orig_width, $this->orig_height, $this->source_type ) = $image_info;

        switch($this->source_type)
        {
            case IMAGETYPE_GIF:
                $this->source_image = imagecreatefromgif($filename);
                break;
            case IMAGETYPE_JPEG:
                $this->source_image = $this->imageCreateJpegFromExif($filename);

                $this->orig_width = ImageSX($this->source_image);
                $this->orig_height = ImageSY($this->source_image);
                break;
            case IMAGETYPE_PNG:
                $this->source_image = imagecreatefrompng($filename);
                break;
            default:
                throw new ImageResizeException('Unsupported image type');
                break;
        }

        if( !$this->source_image )
        {
            throw new ImageResizeException('Could not load image file');
        }

        return $this;
    }

    public function getSourceImage()
    {
        return $this->source_image;
    }

    private function imageCreateJpegFromExif($filename)
    {
        $image = imagecreatefromjpeg($filename);

        if( !function_exists('exif_read_data') || !isset($tihs->source_info['APP1']) || strpos($this->source_info['APP1'], 'Exif') !== 0 )
        {
            return $image;
        }

        $exif_data = exif_read_data($filename);

        if( !$exif_data || !isset($exif_data['Orientation']) )
        {
            return $image;
        }

        $orientation = $exif_data['Orientation'];

        if( $orientation === self::EXIF_RIGHTTOP || $orientation === self::EXIF_LEFTTOP )
        {
            $image = imagerotate($image, self::ROTATE_270, null);
        }
        else if( $orientation === self::EXIF_BOTTOMRIGHT || $orientation === self::EXIF_BOTTOMLEFT )
        {
            $image = imagerotate($image, self::ROTATE_180, null);
        }
        else if( $orientation === self::EXIF_LEFTBOTTOM || $orientation === self::EXIF_RIGHTBOTTOM )
        {
            $image = imagerotate($image, self::ROTATE_90, null);
        }

        if( $orientation === self::EXIF_LEFTTOP || $orientation === self::EXIF_BOTTOMLEFT || $orientation === self::EXIF_RIGHTBOTTOM )
        {
            imageflip($image, IMG_FLIP_HORIZONTAL);
        }

        return $image;
    }

    public function __toString()
    {
        $tmp = tempnam(sys_get_temp_dir(), '');

        $this->save($tmp, null, null);
        $string = file_get_contents($tmp);

        unlink($tmp);
        return $string;
    }

    public function getSourceWidth()
    {
        return $this->orig_width;
    }

    public function getSourceHeight()
    {
        return $this->orig_height;
    }

    public function getDestWidth()
    {
        return $this->dest_width;
    }

    public function setDestWidth($width)
    {
        $this->dest_width = $width;
    }

    public function getDestHeight()
    {
        return $this->dest_height;
    }

    public function setDestHeight($height)
    {
        $this->dest_height = $height;
    }

    public function getSourceH()
    {
        return $this->source_height;
    }

    public function setSourceH($height)
    {
        $this->source_height = $height;
    }

    public function getSourceW()
    {
        return $this->source_width;
    }

    public function setSourceW($width)
    {
        $this->source_width = $width;
    }

    public function getSourceX()
    {
        return $this->source_x;
    }

    public function setSourceX($x)
    {
        $this->source_x = $x;
    }

    public function getSourceY()
    {
        return $this->source_y;
    }

    public function setSourceY($y)
    {
        $this->source_y = $y;
    }

    public function getCropPosition($expectedSize, $position = self::CROP_CENTER)
    {
        $size = 0;
        switch($position)
        {
            case self::CROP_BOTTOM:
            case self::CROP_RIGHT:
                $size = $expectedSize;
                break;
            case self::CROP_CENTER:
                $size = $expectedSize / 2;
                break;
            case self::CROP_TOP_CENTER:
                $size = $expectedSize / 4;
                break;
        }

        return $size;
    }

    public function save($filename, $image_type = null, $quality = null, $permissions = null )
    {
        $image_type = (!is_null($image_type) ? $image_type : $this->source_type);
        $quality = (is_numeric($quality) ? (int)abs($quality) : null);

        switch($image_type)
        {
            case IMAGETYPE_JPEG:
                $dest_image = imagecreatetruecolor($this->dest_width, $this->dest_height);
                $background = imagecolorallocate($dest_image, 255, 255, 255);
                imagefilledrectangle($dest_image, 0, 0, $this->dest_width, $this->dest_height, $background);
                break;
            case IMAGETYPE_GIF:
                $dest_image = imagecreatetruecolor($this->dest_width, $this->dest_height);
                $background = imagecolorallocatealpha($dest_image, 255, 255, 255, 1 );
                imagecolortransparent($dest_image, $background);
                imagefill($dest_image, 0, 0, $background);
                imagesavealpha($dest_image, true);
                break;
            case IMAGETYPE_PNG:
                if( !$this->quality_truecolor && !imageistruecolor($this->source_image) )
                {
                    $dest_image = imagecreate($this->dest_width, $this->dest_height);
                    $background = imagecolorallocatealpha($dest_image, 255, 255, 255, 1);
                    imagecolortransparent($dest_image, $background);
                    imagefill($dest_image, 0, 0, $background);
                }
                else
                {
                    $dest_image = imagecreatetruecolor($this->dest_width, $this->dest_height);
                }
                imagealphablending($dest_image, false);
                imagesavealpha($dest_image, true);
                break;
        }

        imageinterlace($dest_image, $this->interlace);

        imagecopyresampled($dest_image, $this->source_image, $this->dest_x, $this->dest_y, $this->source_x, $this->source_y, $this->dest_width, $this->dest_height, $this->source_width, $this->source_height);

        switch($image_type)
        {
            case IMAGETYPE_GIF:
                imagegif($dest_image, $filename);
                break;
            case IMAGETYPE_JPEG:
                if( $quality === null || $quality > 100 )
                {
                    $quality = $this->quality_jpg;
                }
                imagejpeg($dest_image, $filename, $quality);
                break;
            case IMAGETYPE_PNG:
                if( $quality === null || $quality > 9 )
                {
                    $quality = $this->quality_png;
                }
                imagepng($dest_image, $filename, $quality);
                break;
        }

        if($permissions)
        {
            chmod($filename, $permissions);
        }

        imagedestroy($dest_image);

        return $this;
    }

    public function resize($width, $height, $allow_enlarge = false)
    {
        if( !$allow_enlarge)
        {
            if( $width > $this->orig_width || $height > $this->orig_height )
            {
                $width = $this->orig_width;
                $height = $this->orig_height;
            }
        }

        $this->source_x = 0;
        $this->source_y = 0;

        $this->dest_width = $width;
        $this->dest_heigth = $height;

        $this->source_width = $this->orig_width;
        $this->source_height = $this->orig_height;

        return $this;
    }

    public function output($image_type = null, $quality = null )
    {
        $image_type = (!is_null($image_type) ? $image_type : $this->source_type);
        header("Content-Type: " . image_type_to_mime_type($image_type));

        $this->save(null, $image_type, $quality);
    }
}
