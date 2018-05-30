<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

if ( ! function_exists('resize'))
{
	function resize($client_id, $image_path, $width = 0, $height = 0, $watermark = TRUE, $check_cache = FALSE)
	{
		$imagemagick_convert = array('eps', 'ai', 'pdf', 'psd', 'tif', 'tiff');
		$no_thumbnail_create = array('doc','docx','ppt','pptx','xls','xlsx','txt');
		
		if(empty($image_path))
			return FALSE;

		//Get the Codeigniter object by reference
		$CI = & get_instance();

		$image_path = uploads_dir($client_id) . $image_path;
		#echo $image_path;
		//Alternative image if file was not found
		if ( !file_exists($image_path))
			return FALSE;
		
		// If its not a file, it's not a image
		if( !is_file($image_path) )
			return FALSE;		

		$fileinfo = pathinfo($image_path);

		if($width == 'full' && $height == 'full' && (!in_array($fileinfo['extension'],$no_thumbnail_create))) // Get original size of image.
		{			
			$imagePath 			= $fileinfo['dirname'].'/'.$fileinfo['basename'];
			$imageInformation 	= getimagesize($imagePath);
			$width 				= $imageInformation[0];
			$height 			= $imageInformation[1];		
		}		

		$new_extension = (in_array($fileinfo['extension'], $imagemagick_convert)) ? 'png' : $fileinfo['extension'];
		if(strtolower($fileinfo['extension']) == 'nef')
		{
			$new_extension = 'jpg'; //ufraw needs to be compliled with imagemagick for raw file support
		}

		//The new generated filename we want
		@mkdir(uploads_dir($client_id) . '/tmp');
				
		$ext_append =  (in_array($fileinfo['extension'], $imagemagick_convert)) ? '-' . $fileinfo['extension'] : '';
		#$ext_append .= (($watermark && $CI->settings_model->get('watermark')) || $CI->session->userdata('type') == 'readonly') ? '' : '-no-watermark';
		
		$ext_append .= (!may_download() || $CI->settings_model->get('always_show_watermark') ? '' : '-no-watermark');
		
		if(in_array($new_extension,$no_thumbnail_create)){			
			// Get this file when we dont want to show a thumbnail
			$no_thumbnail_imgpath 	= FCPATH.'img/no-thumbnail.gif';			
			// Create file with same name except -no-image.JPG 
			$new_image_filename = md5($client_id) . '_' . md5($fileinfo['filename']) . '_' . $width . 'x' . $height . $ext_append . '-no-image.JPG';
		}else{
			$new_image_filename = md5($client_id) . '_' . md5($fileinfo['filename']) . '_' . $width . 'x' . $height . $ext_append . '.' . $new_extension;
		}
		
		$new_image_server_path = uploads_dir($client_id) . '/tmp/' . $new_image_filename;
		$new_image_path = str_replace(FCPATH, "/", uploads_dir($client_id)) . '/tmp/' . $new_image_filename;

		//The first time the image is requested
		//Or the original image is newer than our cache image
		if ($CI->client['url'] == 'demo' || (! file_exists($new_image_server_path)) || filemtime($new_image_server_path) < filemtime($image_path)) {

			#echo $new_image_server_path;
			//return FALSE;

			if($check_cache)
			{
				return FALSE;
			}

			if(!is_writable(uploads_dir($client_id) . '/tmp/'))
			{
				show_error($new_image_server_path . ' cannot be writen. Directory can\'t be written.');
				exit;
			}

			$resize = TRUE;
			if(strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN')
			{
				$from = $image_path;
				$to = $new_image_server_path;

				if($new_extension == 'gif')
				{
					$exec = "convert ".escapeshellarg($from)." -coalesce " . escapeshellarg($to);
					exec($exec);
					$from = $to;
				}
				if($fileinfo['extension'] == 'pdf')
				{
					$from = $from . '[0]';
				}
				if(in_array($fileinfo['extension'],$no_thumbnail_create))
				{
					$from	= $no_thumbnail_imgpath;
				}

				$exec = "convert ".escapeshellarg($from)." -strip -resize ".$width."x".(empty($height) ? '' : $height)."\> -flatten -background white -gravity center -extent ".$width."x".$height." " . escapeshellarg($to);

				#@mail("robert@refresh-media.nl", "Convert", $exec);
				#echo $exec;

				exec($exec, $o);
				
				$image_path = $new_image_server_path;
				$resize = FALSE;
			}

			$CI->load->library('image_lib');

			$quality = 90;

			//The original sizes
			$original_size = @getimagesize($image_path);
			if($original_size === FALSE)
			{
				if(@filesize($image_path) > 0)
				{
					//show_error($image_path . ' is no valid image, cannot be read. (Image may be corrupt)');
					return FALSE;
				}
				else
				{
					log_message('error', $image_path . ' has a zero filesize (Image is empty)');
					return FALSE;
				}
			}

			$original_width = $original_size[0];
			$original_height = $original_size[1];
			$ratio = $original_width / $original_height;
			
			//The requested sizes
			$requested_width = $width;
			$requested_height = $height;
			
			//Initialising
			$new_width = 0;
			$new_height = 0;
			
			//Calculations
			if ($requested_width > $requested_height) {
				$new_width = $requested_width;
				$new_height = $new_width / $ratio;
				if ($requested_height == 0)
					$requested_height = $new_height;
				
				if ($new_height < $requested_height) {
					$new_height = $requested_height;
					$new_width = $new_height * $ratio;
				}
			}
			else {
				$new_height = $requested_height;
				$new_width = $new_height * $ratio;
				if ($requested_width == 0)
					$requested_width = $new_width;
				
				if ($new_width < $requested_width) {
					$new_width = $requested_width;
					$new_height = $new_width / $ratio;
				}
			}
			
			$new_width = ceil($new_width);
			$new_height = ceil($new_height);
				
			//Resizing
			if($resize) {
				$config = array();
				$config['image_library'] 	= 'gd2';
				$config['source_image'] 	= $image_path;
				$config['new_image'] 		= $new_image_server_path;
				$config['maintain_ratio'] 	= FALSE;
				$config['height'] 			= $new_height;
				$config['width'] 			= $new_width;
				$config['quality'] 			= $quality;
				$CI->image_lib->initialize($config);
				$CI->image_lib->resize();
				$CI->image_lib->clear();

				//Crop if both width and height are not zero
				if (($width != 0) && ($height != 0)) {
					$x_axis = floor(($new_width - $width) / 2);
					$y_axis = floor(($new_height - $height) / 2);
					
					//Cropping
					$config = array();
					$config['source_image'] = $new_image_server_path;
					$config['maintain_ratio'] = FALSE;
					$config['new_image'] = $new_image_server_path;
					$config['width'] = $width;
					$config['height'] = $height;
					$config['x_axis'] = $x_axis;
					$config['y_axis'] = $y_axis;
					$config['quality'] = $quality;
					$CI->image_lib->initialize($config);
					$CI->image_lib->crop();
					$CI->image_lib->clear();
				}
			}

			if(( $watermark && $CI->settings_model->get('watermark')) || $CI->session->userdata('type') == 'readonly') {
				if($original_width > 110) {
					$watermark = array(
						'full' 	=> FCPATH . 'img/watermark_png24.png',
						'thumb' => FCPATH . 'img/watermark_png24_140x.png',
						'medium' => FCPATH . 'img/watermark_png24_300x.png',
					);
					
					$CI->load->library('image_moo');
					
					if($original_width <= 299){
						$get_watermark_size = 'thumb';
					}elseif($original_width > 200 && $original_width < 600){
						$get_watermark_size = 'medium';
					}else{
						$get_watermark_size = 'full';
					}

					$custom_watermark = custom_watermark($client_id);
					
					if(!empty($custom_watermark)) {
						if($get_watermark_size == 'medium') $get_watermark_size = 'thumb'; // Bug voor tellier.
						$watermark = $custom_watermark;
					}					
					
					$watermark_path = $watermark[$get_watermark_size];
					
					#echo $watermark_path;
					/** transp position */
					$watermark_pos_x = $watermark_pos_y = 0;					
					$set_watermark_transparency = 10;
					if(empty($custom_watermark))
					{
						$set_watermark_transparency = 50;
						if($get_watermark_size == 'full'){
							$watermark_pos_x = $watermark_pos_y = 80;
						}elseif($get_watermark_size == 'medium' ){
							$watermark_pos_x = $watermark_pos_y = 20;
						}else{
							$watermark_pos_x = $watermark_pos_y = 20;
						}
					}

					$CI->image_moo->set_watermark_transparency($set_watermark_transparency);
					$CI->image_moo->load($new_image_server_path);
					$CI->image_moo->load_watermark($watermark_path, $watermark_pos_x, $watermark_pos_y);
					$CI->image_moo->watermark(5);
					$CI->image_moo->save($new_image_server_path, TRUE);
					
					if($CI->image_moo->errors)
					{
						var_dump($CI->image_moo->display_errors());						
						return false;
					}

					/*$config['source_image'] = $new_image_server_path;
					$config['wm_type'] = 'overlay';
					$config['wm_overlay_path'] = $watermark_path;
					$config['wm_opacity'] = 60;
					$config['wm_vrt_alignment'] = 'middle';
					$config['wm_hor_alignment'] = 'center';
					if(empty($custom_watermark))
					{
						$config['wm_x_transp'] = 80;
						$config['wm_y_transp'] = 80;
					}
					$config['padding'] = ($original_width * .2);
					$CI->image_lib->initialize($config);
					$CI->image_lib->watermark();
					$CI->image_lib->clear();*/
				}
			}
		}
		
		if($check_cache)
		{
			return TRUE;
		}


		return $new_image_path;
	}
}

if ( ! function_exists('client_logo'))
{
	function client_logo($client_id)
	{
		foreach(array('jpg', 'png', 'gif') as $ext)
		{
			$path = uploads_dir($client_id) . '_logo.' . $ext;
			if(file_exists($path)) return str_replace(FCPATH, "/", $path);
		}
		return FALSE;
	}
}

if ( ! function_exists('custom_watermark'))
{
	function custom_watermark($client_id)
	{
		$path = uploads_dir($client_id) . '_watermark.png';
		if(file_exists($path))
		{
			$CI = &get_instance();
			$small = str_replace("watermark", "watermark_small", $path);
			
			if(!file_exists($small))
			{
				$CI->load->library('image_lib');
				$config = array();
				$config['image_library'] = 'gd2';
				$config['source_image'] = str_replace(FCPATH, "./", $path);
				$config['new_image'] = str_replace(FCPATH, "./", $small);
				$config['maintain_ratio'] = TRUE;
				$config['width'] = 120;
				$config['quality'] = 100;
				$CI->image_lib->initialize($config);
				$CI->image_lib->resize();
			}
			
			return array(
				'full' => $path,
				'thumb' => $small
			);
		}
			
		return FALSE;
	}
}

if ( ! function_exists('uploads_dir'))
{
	function uploads_dir($client_id = FALSE)
	{
		return FCPATH . 'uploads/' . (($client_id === FALSE) ? '' : md5($client_id) . '/');
	}
}

if ( ! function_exists('format_bytes'))
{
	function format_bytes($bytes)
	{
		 $labels = array('B','KB','MB','GB','TB');
		for($x = 0; $bytes >= 1024 && $x < (count($labels) - 1); $bytes /= 1024, $x++);
		return(round($bytes, 2).' '.$labels[$x]);
	}
}

if ( ! function_exists('folder_size'))
{
	function folder_size($path)
	{
		$total_size = 0;
		
		if(is_dir($path))
		{		
			$files = scandir($path);
			$clean_path = rtrim($path, '/'). '/';

			foreach($files as $t)
			{
				if ($t != "." && $t !="..")
				{
					$current_file = $clean_path . $t;
					$size = (is_dir($current_file)) ? folder_size($current_file) : filesize($current_file);
					
					$total_size += $size;
				}   
			}
		}

		return $total_size;
	}
}

if ( ! function_exists('succes_message'))
{
	function succes_message($message)
	{
		$CI = &get_instance();
		$CI->session->set_flashdata('succes_message', $message);
	}
}
if ( ! function_exists('error_message'))
{
	function error_message($message)
	{
		$CI = &get_instance();
		$CI->session->set_flashdata('error_message', $message);
	}
}

if ( ! function_exists('logged_in'))
{
	function logged_in()
	{
		$CI = &get_instance();
		return $CI->session->userdata('logged_in') != FALSE;
	}
}

if ( ! function_exists('user_id'))
{
	function user_id()
	{
		$CI = &get_instance();
		if(!logged_in()) return FALSE;
		return $CI->session->userdata('user_id');
	}
}

if ( ! function_exists('is_admin'))
{
	function is_admin()
	{
		$CI = &get_instance();
		if(!logged_in()) return FALSE;
		return $CI->session->userdata('type') == 'admin';
	}
}
if ( ! function_exists('may_upload'))
{
	function may_upload()
	{
		$CI = &get_instance();
		if(!logged_in()) return FALSE;
		return $CI->session->userdata('type') == 'uploader' || $CI->session->userdata('type') == 'admin';
	}
}
if ( ! function_exists('may_download'))
{
	function may_download()
	{
		$CI = &get_instance();
		
		if(!logged_in() && $CI->client['download_without_login'] == 1)
			return TRUE;
			
		if(!logged_in())
			return FALSE;
	
		if($CI->session->userdata('type') == 'readonly')
			return FALSE;

		return TRUE; 
		
		//$CI->session->userdata('type') == 'downloader' || $CI->session->userdata('type') == 'downloader' || $CI->session->userdata('type') == 'admin';
	}
}

if ( ! function_exists('get_image_url'))
{
	function get_image_url($mode, $image)
	{
		$CI = &get_instance();

		$extra = '';
		if($mode == 'prev_next') {
			$extra = '?' . $_SERVER['QUERY_STRING'];
		} elseif($mode == 'search') {
			$extra = '?mode=search&keywords=' . $CI->keywords;
		} else {
			$extra = '?mode=' . $mode;
		}

		return '/images/info/' . $image['image_id'] . $extra;
	}
}

if ( ! function_exists('is_superadmin'))
{
	function is_superadmin()
	{
		$CI = &get_instance();
		return logged_in() && ($CI->session->userdata('user_id') == 1) && ($CI->session->userdata('client_id') == 0);
	}
}

/* End of file core_helper.php */
/* Location: ./system/application/helper/core_helper.php */