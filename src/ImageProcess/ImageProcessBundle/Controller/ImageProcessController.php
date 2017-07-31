<?php

namespace ImageProcess\ImageProcessBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;

use ImageProcess\ImageProcessBundle\Service\ImageProcessService;

class ImageProcessController extends Controller
{
    /**
     * @Route("/")
     */
    public function welcome()
    {
        $files = array('path/to/file', 'path/to/another/file');
        $images = new ImageProcessService($files);
        echo $images->blerg();
        return $this->render('ImageProcessImageProcessBundle:ImageProcess:index.html.twig');
    }

    /**
     * @Route("/image/process")
     */
    public function processImage()
    {
        return $this->render('ImageProcessImageProcessBundle:ImageProcess:process.html.twig');
    }
}
