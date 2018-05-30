<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Image extends Widget
{
    function run($image = array(), $mode = FALSE,$embed = 0) {

        $this->render('image', array(
			'image' => $image,
			'filename_rendering' => $this->settings_model->get('filename_rendering'),
			'show_extension' => $this->settings_model->get('show_extension'),
			'mode' => $mode,
			'embed'	=> $embed
		));
    }
}  