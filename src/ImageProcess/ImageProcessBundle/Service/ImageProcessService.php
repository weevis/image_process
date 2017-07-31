<?php

namespace ImageProcess\ImageProcessBundle\Service;

use ImageProcess\ImageProcessBundle\Service\Image;
use ImageProcess\ImageProcessBundle\Exception\ImageResizeException;

class ImageProcessService
{
    protected $images;

    public function __construct($files = null)
    {
        $images = array();
        if( $files !== null && is_array($files) && !empty($files))
        {
            foreach($files as $file)
            {
                $this->createImageFromFile($file);
            }
        }
        return $this;
    }

    public function fetchImageByName($filename)
    {
        return $this->images[$filename];
    }

    public function createImageFromString($image_data)
    {
        if( empty($image_data) || $image_data === null )
        {
            throw new ImageResizeException('image_data must not be empty');
        }

        $image = new Image('data://application/octet-stream;base64,' . base64_encode($image_data) );
        $images[] = $image;
        return $this;
    }

    public function createImageFromFile($filename)
    {
        try
        {
            $image = new Image($filename);
            $this->images[$filename] = $image;
        }
        catch( ImageResizeException $e )
        {
            echo "Caught exception: {$e->getMessage()}";
        }

        return $this;
    }

    public function resizeToHeight(Image &$image, $height, $allow_enlarge = false )
    {
        $ratio = $height / $image->getSourceHeight();
        $width = $image->getSourceWidth() * $ratio;

        $image->resize($width, $height, $allow_enlarge);
       
        return $this; 
    }

    public function resizeToShortSide(Image &$image, $max_short, $allow_enlarge = false )
    {
        if( $image->getSourceHeight() < $image->getSourceWidth() )
        {
            $ratio = $max_short / $image->getSourceHeight();
            $long = $image->getSourceWidth() * $ratio;

            $image->resize($long, $max_short, $allow_enlarge);
        }
        else
        {
            $ratio = $max_short / $image->getSourceWidth();
            $long = $image->getSourceHeight() * $ratio;

            $image->resize($max_short, $long, $allow_enlarge );
        }
        return $this;
    }

    public function resizeToLongSide(Image &$image, $max_long, $allow_enlarge=false)
    {
        if( $image->getSourceHeight() > $image->getSourceWidth() )
        {
            $ratio = $max_long / $image->getSourceHeight();
            $short = $image->getSourceWidth() * $ratio;

            $image->resize($short, $max_long, $allow_enlarge);
        }
        else
        {
            $ratio = $max_long / $image->getSourceWidth();
            $short = $image->getSourceHeight() * $ratio;

            $image->resize($max_long, $short, $allow_enlarge);
        }
        return $this;
    }

    public function resizeToWidth(Image &$image, $width, $allow_enlarge = false)
    {
        $ratio = $width / $image->getSourceWidth();
        $height= $image->getSourceHeight() * $ratio;

        $image->resize($width, $height, $allow_enlarge);
        return $this;
    }

    public function resizeToBestFit(Image &$image, $max_height, $max_width, $allow_enlarge = false)
    {
        if($image->getSourceWidth() <= $max_width && $image->getSourceHeight <= $max_height && $allow_enlarge === false )
            return $this;

        $ratio = $image->getSourceHeight() / $image->getSourceWidth();
        $width = $max_width;
        $height = $width * $ratio;

        if( $height > $max_height )
        {
            $height = $max_height;
            $width = $height / $ratio;
        }

        $image->resize($width, $height, $allow_enlarge);
        return $this;
    }

    public function crop(Image &$image, $width, $height, $allow_enlarge = false, $position = Image::CROP_CENTER)
    {
        if( $width > $image->getSourceWidth() )
        {
            $width = $image->getSourceWidth();
        }

        if( $height > $image->getSourceHeight() )
        {
            $height = $image->getSourceHeight();
        }

        $source_ratio = $image->getSourceWidth() / $image->getSourceHeight();
        $dest_ratio = $width / $height;

        if( $dest_ratio < $source_ratio )
        {
            $this->resizeToHeight($image, $height, $allow_enlarge);
            $excess = ($image->getDestWidth() - $width) / $image->getDestWidth() * $image->getSourceWidth();

            $image->setSourceW($image->getSourceWidth() - $excess);
            $image->setSourceX($image->getCropPosition($excess, $position));
            $image->setDestWidth($width);
        }
        else
        {
            $this->resizeToWidth($image, $width, $allow_enlarge);

            $excess = ($image->getDestHeight() - $height) / $image->getDestheight() * $image->getSourceHeight();

            $image->setSourceH($image->getSourceHeight() - $excess);
            $image->setSourceY($image->getCropPosition($excess, $position));

            $image->setDestHeight($height);
        }

        return $this;
    }

    public function freeCrop(Image &$image, $width, $height, $x = false, $y = false)
    {
        if( $y === false || $x === false )
        {
            return $this->crop($image, $width, $height);
        }

        $image->setSourceX($x);
        $image->setSourceY($y);

        if( $width > ($image->getSourceHeight() - $y) )
        {
            $image->setSourceH($image->getSourceHeight() - $y );
        }
        else
        {
            $image->setSourceH($height);
        }

        $image->setDestWidth($width);
        $image->setDestHeight($height);

        return $this;
    }

    public function scale(Image &$image, $scale)
    {
        $width = $image->getSourceWidth() * $scale / 100;
        $heigth = $image->getSourceHeight() * $scale / 100;

        $image->resize($width, $height, true);

        return $this;
    }

    public function resize(Image &$image, $width, $height, $allow_enlarge = false)
    {
        $image->resize($width, $height, $allow_enlarge);

        return $this;
    }

    public function blerg()
    {
        return "blerg";
    }
}
