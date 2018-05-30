<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Image_loop extends Widget
{
    function run($images, $mode = FALSE, $col_width = 3,$keywords = FALSE,$embed = 0)
	{
        $this->render('image_loop', array(
			'images' 		=> $images,
			'keywords' 		=> $keywords,
			'mode' 			=> $mode,
			'col_width' 	=> $col_width,
			'embed'			=> $embed
		));
    }
}  