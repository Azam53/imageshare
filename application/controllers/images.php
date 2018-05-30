<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Images extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		
		$this->client = $this->clients_model->client_by_url();		
		$this->client_id = $this->client['client_id'];
		
		$this->limit = ($this->input->cookie('limit')) ? $this->input->cookie('limit') : 48; //$this->settings_model->get('pagination') ? (6 * 4) : (6 * 6);
		if(!is_numeric($this->limit) && $this->limit != 'all') exit;
		
		$this->load->model('users_model');
		$this->load->library('encrypt');
		
		if($this->uri->segment(2) != 'download' && $this->client['public'] == 0 && !logged_in())
		{
			redirect('/login');
		}
	}
	
	public function add_tags()
	{
	exit;
		$uploaded_by = 1;
		$keywords = 'dans,kinderen,dansen,kids,2013,kaleidoskoop,streetdance,kidsdance';
		
		$images = $this->images_model->get_by_user($uploaded_by);
		foreach($images as $image)
		{
			if(date("d-m-Y", $image['uploaded_at']) == "24-12-2013")
			{
				echo $image['name'] . ': ' . $keywords . "<br />";
				$this->images_model->update_keywords($image['image_id'], $keywords);
			}
		}
	}
	
	public function index($mode = 'home', $offset = 0)
	{		
		$pagination = '';		
		if($this->limit == 'all'){
			$limit = FALSE;
		}else{
			$limit = TRUE;
		}
		
		if($limit == TRUE || $this->settings_model->get('pagination'))
		{
			$this->load->library('pagination');
			$config['base_url'] = base_url() . 'images/index/' . $mode;
			$config['total_rows'] = $this->images_model->client_image_count($this->client_id);
			$config['per_page'] = $this->limit;
			$config['uri_segment'] = 4;
			$this->pagination->initialize($config);
			$pagination = $this->pagination->create_links();
		}
		
		$calc_offset = 0;
		if($this->limit != 'all'){
			$calc_offset  = $offset / $this->limit;
			$arrayImages  = $this->images_model->get_all_offset($this->client_id, $calc_offset, $this->limit);
		}else{
			$arrayImages = $this->images_model->get_all($this->client_id);
		}
	
		$keywords 		= $this->images_model->get_all_keywords_by_user($this->client_id);				
		
		$this->_index_view(array(
			'searched' 		=> FALSE,
			'mode' 			=> $mode,
			'pagination' 	=> $pagination,
			'images'		=> $arrayImages,
			'keywords'		=> $keywords,
			'embed'			=> $this->input->get('embed'),
		));
	}
	
	public function thumb()
	{
		$size = $this->input->get('size');
		if(empty($size)) $size = "160x120";
		
		$output = ($this->input->get('output') == 1);
		
		list($width, $height) = explode("x", $size);
		if(empty($height)) $height = FALSE;
	
		$image_id = $this->input->get('image_id');
		if(empty($image_id)) exit;
		
		$image = $this->images_model->get($image_id);
		if(empty($image)) exit;
		
		$src = resize($this->client_id, $image['file'], $width, $height);
		
		if($output)
		{
			$type = 'image/jpeg';
			header('Content-Type:'.$type);
			header('Content-Length: ' . filesize(FCPATH . $src));
			readfile(FCPATH . $src);
			exit;
		}
		else
		{
			echo $src . '?v=' . substr(md5_file(FCPATH . $src), 0, 10);
		}
	}
	
	public function fullsize()
	{
		if(!may_download())	exit;
		// Deze functie word alleen uitgevoerd als iemand de foto mag downloaden.
		$image_id = $this->input->get('image_id');
		if(empty($image_id)) exit;
				
		$image = $this->images_model->get($image_id);
		if(empty($image)) exit;		
		
		$src = resize($this->client_id, $image['file'], 'full', 'full');
		
		echo $src . '?v=' . substr(md5_file(FCPATH . $src), 0, 10);
	}
	
	
	
	public function set_limit()
	{
		$limit = $this->input->post('limit');
		$limit = $this->input->post('limit');
		$this->input->set_cookie(array(
			'name'   => 'limit',
			'value'  => $limit,
			'expire' => '86500'
		));
		$this->load->library('user_agent');
		redirect( ($this->agent->is_referral()) ? $this->agent->referrer() : '/' );
	}
	
	public function load_more($offset)
	{
		$this->load->view('images/_loop', array(
			'images' => $this->images_model->get_all_offset($this->client_id, $offset, $this->limit)
		), FALSE, FALSE);
	}
	
	public function search($keyword = FALSE, $offset = 0)
	{
		if(empty($keyword))
		{
			$keyword = $this->input->post('keyword');
			if(empty($keyword))
			{
				redirect('/images');
			}
			else
			{
				redirect('/images/search/' . trim($keyword) . (($offset == 0) ? '' : '/' . $offset));
			}
		}
		
		$keyword = trim(urldecode($keyword));
		
		$keywords_array = explode(",", $keyword);
		foreach($keywords_array as $item)
		{
			$this->db->insert('searched', array(
				'client_id' => $this->client_id,
				'user_id' => $this->session->userdata('user_id'),
				'keywords' => $item,
				'total_query' => $keyword
			));
		}
		if($this->limit=='all'){
			$limit = FALSE;
		}else{
			$limit = TRUE;
		}
		
		$pagination = '';		
		if($limit==TRUE || $this->settings_model->get('pagination'))
		{
			$this->load->library('pagination');
			$config['base_url'] = base_url() . 'images/search/' . $keyword;
			$config['total_rows'] = $this->images_model->count_search($this->client_id, $keyword);
			$config['per_page'] = $this->limit;
			$config['uri_segment'] = 4;
			$limit = $this->limit;
			$this->pagination->initialize($config);
			$pagination = $this->pagination->create_links();
		}
		
		
		$this->keywords = $keyword;
		$keywords 		= $this->images_model->get_all_keywords_by_user($this->client_id);				
		//print_r($this->images_model->search($this->client_id, $keyword, $offset, $limit));

		$this->_index_view(array(
			'images' => $this->images_model->search($this->client_id, $keyword, $offset, $limit),
			'searched' => TRUE,
			'pagination' => $pagination,
			'keyword' => $keyword,
			'mode' => 'search',
			'keywords' => $keywords,
		));
	}

	private function _index_view($data)
	{
		$categories = @Widget::run('categories');
		$left_column = TRUE;
		$image_width = 3;
		if(empty($categories) || $data['mode'] == 'all')
		{
			$left_column = FALSE;
			$image_width = 2;
		}
		
		$data['categories'] = $categories;
		$data['left_column'] = $left_column;
		$data['image_width'] = $image_width;
		$data['limit'] = $this->limit;
	
		$this->load->view('images/index', $data);
	}
	
	public function info($image_id)
	{
		$popup = ($this->input->get('popup') == 1);
		$embed = ($this->input->get('embed') == 1);
		
		$image_data = $this->images_model->get($image_id);
		
		if(empty($image_data) || $image_data['client_id'] != $this->client_id)
		{
			die('Bestand is niet gevonden.');
		}

		$special_extensions = array('eps', 'ai', 'pdf');

		setlocale(LC_TIME, 'nl_NL');		
		
		$image_data['absolute_path'] = uploads_dir($image_data['client_id']) . $image_data['file'];
		$image_data['filesize'] = @filesize($image_data['absolute_path']);
		$image_data['keywords'] = $this->images_model->get_keywords($image_id);
		$image_data['show_uploader'] = $this->settings_model->get('show_uploader');
		$image_data['extension'] = end(explode(".", $image_data['file']));
		$image_data['show_extension'] = (in_array($image_data['extension'], $special_extensions));
		$image_data['show_format'] = (in_array($image_data['extension'], $special_extensions) === FALSE);		
		
		if($image_data['show_uploader'])
		{
			$this->load->model('users_model');
			$image_data['uploader'] = $this->users_model->get($image_data['uploaded_by']);
		}

		list($image_data['width'], $image_data['height']) = @getimagesize($image_data['absolute_path']);
		
		$images = array();
		$raw_images = array();
		if($this->input->get('mode') == 'last_uploaded')
		{
			$raw_images = $this->images_model->get_latests_uploaded(6 * 3);
		} 
		elseif($this->input->get('mode') == 'search')
		{
			$raw_images = $this->images_model->get_all($this->client_id);
		}
		
		foreach($raw_images as $key => $value)
		{
			if($value['image_id'] == $image_id)
			{			
				$first_image = $this->images_model->get(@$raw_images[$key - 2]['image_id']);
				$images[0] = $first_image;
				
				$prev_image = $this->images_model->get(@$raw_images[$key - 1]['image_id']);
				$images[1] = $prev_image;
				
				$current_image = $this->images_model->get($image_id);
				$images[2] = $current_image;
				
				$next_image = $this->images_model->get(@$raw_images[$key + 1]['image_id']);
				$images[3] = $next_image;
				
				$last_image = $this->images_model->get(@$raw_images[$key + 2]['image_id']);
				$images[4] = $last_image;
				
				break;
			}
		}
		$image_data['images'] = $images;
		$image_data['embed']  = ($embed ? TRUE : FALSE);		
			
		$this->active_image_id = $image_id;

		$this->load->view('images/info', $image_data, FALSE, ($popup ? FALSE : TRUE));
	}

	public function download($image_ids, $expire = FALSE, $expire_hash = FALSE, $security_hash = FALSE)
	{
		if(!may_download() && $expire === FALSE)
			exit;

		if($expire)
		{
			if($expire_hash != substr(sha1($expire . '&#$%^!@#$!@#^*!#$%'), 0, 6))
			{
				@mail("michel@refreshmedia.nl", "Invalid expire hash", "Invalid expire hash" . print_r($_SERVER, TRUE));
				die('Invalid hash.');
			}
			if(time() > $expire)
			{
				die('De downloadlink is verlopen.');
			}
			
			if(empty($security_hash)) die('Deze link is niet meer geldig');
			if($security_hash != substr(sha1($image_ids . '&#$%^!@#$!@#8926148912lFGHSDFGksad^*!#$%'), 0, 8)) die('Invalid security hash.');
		}
	
		$gwidth = $this->input->get('width');
		$gheight = $this->input->get('height');
	
		$this->load->helper('download');
		
		$image_ids_array = explode(",", $image_ids);
		if(empty($image_ids_array))
			exit;

		$to_download = array();
		$images = array();
		foreach($image_ids_array as $image_id)
		{
			$image = $this->images_model->get($image_id);
			if(empty($image)) exit;

			$absolute_path = uploads_dir($image['client_id']) . $image['file'];
			list($width, $height) = getimagesize($absolute_path);
			
			$original_size = (empty($gwidth) && empty($gheight) || ($gwidth == $width && $gheight == $height));
			
			if($this->session->userdata('user_id') != '')
			{
				$this->images_model->add_download($image['client_id'], $image_id);
			}
			
			$name = url_title($image['name'], 'dash') . ($original_size ? '' : ('-' . $gwidth . 'x' . $gheight)) . '.' . end(explode(".", $image['file']));

			if($original_size)
			{
				$to_download[] = array('path' => $absolute_path, 'name' => $name);
			}
			else
			{
				$resized_path = array('path' => FCPATH . ltrim(resize($this->client_id, $image['file'], $gwidth, $gheight, FALSE), '/'), 'name' => $name);
				$to_download[] = $resized_path;
			}
		}
		
		if(count($to_download) == 1)
		{
			$file = reset($to_download);
			force_download($file['name'], file_get_contents($file['path']));
		}
		else
		{
			$tmp_file = tempnam("tmp", "zip");
			$zip = new ZipArchive();
			$zip->open($tmp_file, ZipArchive::OVERWRITE);

			// Stuff with content
			foreach($to_download as $file)
			{
				$zip->addFile($file['path'], $file['name']);
			}
			
			// Close and send to users
			$zip->close();
			
			header('Content-Type: application/zip');
			header('Content-Length: ' . filesize($tmp_file));
			header('Content-Disposition: attachment; filename="'.$this->client['url'].'_files_'.str_replace(",", "-", $image_ids).'.zip"');
			readfile($tmp_file);
			unlink($tmp_file); 
		}
	}
	
	public function typeahead()
	{
		$keyword = $this->input->get('q');
	
		$items = array();
		$this->db->group_by('keyword');
		$keywords = $this->images_model->get_keywords_like($this->client_id, $keyword);
		foreach($keywords as $item)
		{
			$items[] = $item['keyword'];
		}
		
		$this->output->set_content_type('application/json')->set_output(json_encode($items));		
	}
	
	public function edit($image_id)
	{
		$image = $this->images_model->get($image_id);
		
		if(!is_admin() && $this->session->userdata('user_id') != $image['uploaded_by'])
		{
			show_error('No right to edit this image.');
		}
		
		$data = array(
			'page_title' => 'Bestand wijzigen',
			'page_subtitle' => $image['name'],
			'image_id' => $image_id,
			'image' => $image,
			'keywords' => $this->images_model->get_keywords($image_id)
		);
		
		if($this->input->post('name') !== FALSE)
		{
			$this->form_validation->set_rules('name', 'Naam', 'trim|required|max_length[255]|xss_clean');
			$this->form_validation->set_rules('keywords', 'Trefwoorden', 'trim|xss_clean');
			//$this->form_validation->set_rules('notes', 'Notities', 'trim|xss_clean');
			
			if ($this->form_validation->run() !== FALSE)
			{
				$image = $this->images_model->get($image_id);

				$this->images_model->update_keywords($image_id, $this->input->post('keywords'));
				
				$this->images_model->update($image_id, array(
					'name' => $this->input->post('name') ,
					'show_on_homepage' => $this->input->post('show_on_homepage')
					//'notes' => $this->input->post('notes')
				));
				
				if(!empty($_FILES['qqfile']['name']))
				{
					$this->load->library('file_uploader');
					$this->file_uploader->init();
					
					$overwrite = FALSE;
					if($image['name'] == $_FILES['qqfile']['name']) $overwrite = TRUE;					
					$result = $this->file_uploader->handleUpload(uploads_dir($this->client_id), $overwrite);
					if(@$result['success'])
					{
						$this->images_model->update($image_id, array(
							'file' => $result['filename'] . '.' . $result['ext']
						));
							
						if(!$overwrite) @unlink(uploads_dir($this->client_id) . $image['file']);
					}
					else
					{
						error_message($result['error']);
						redirect('/images/info/' . $image_id);
					}
				}
				
				succes_message('Bestand is succesvol gewijzigd.');
				redirect('/images/info/' . $image_id);
			}
		}
		
		$this->load->view('images/edit', $data);
	}
	
	public function share($image_ids = '')
	{
		$image_ids_array = explode(",", $image_ids);
		if(empty($image_ids_array))
			exit;

		$images = array();
		foreach($image_ids_array as $image_id)
		{
			$image = $this->images_model->get($image_id);
			if(empty($image)) exit;
			
			$images[] = $image;
		}

		if(!may_download()) exit;
		
		$data = array(
			'page_title' => 'Bestand' . (count($image_ids_array) == 1 ? '' : 'en') . ' mailen',
			'page_subtitle' => $image['name'],
			'image_ids' => $image_ids_array,
			'images' => $images
		);

		if($this->input->post('to') !== FALSE)
		{
			$this->form_validation->set_rules('from', 'Afzender e-mail', 'trim|required|max_length[255]|valid_email|xss_clean');
			$this->form_validation->set_rules('to', 'Ontvanger e-mail', 'trim|required|xss_clean');
			$this->form_validation->set_rules('message', 'Bericht', 'trim|xss_clean');
			
			if ($this->form_validation->run() !== FALSE)
			{
				$from_name = $this->client['name'];
				$from = $this->input->post('from');
				$to_raw = $this->input->post('to');
				$message = $this->input->post('message');
				
				$to = array_map('trim', explode(";", str_replace(",", ";", $to_raw)));
				
				$expire_date = strtotime('+10 days');
				$link = base_url() . 'images/download/' . $image_ids . '/' . $expire_date . '/' . substr(sha1($expire_date . '&#$%^!@#$!@#^*!#$%'), 0, 6) . '/' . substr(sha1($image_ids . '&#$%^!@#$!@#8926148912lFGHSDFGksad^*!#$%'), 0, 8);

				$config['protocol'] = 'mail';
				$config['wordwrap'] = FALSE;
				$config['mailtype'] = 'html'; 
				$this->load->library('email', $config);

				$success = TRUE;
				foreach($to as $to_email)
				{
					$this->email->from('info@image-share.nl', ($this->client['whitelabel_footer'] ? $from_name : ($from_name . ' | ImageShare')));
					$this->email->to($to_email);
					$this->email->reply_to($from);

					$via = ($this->client['whitelabel_logo'] || $this->client['whitelabel_footer']) ? '' : ' via ImageShare';
					
					$this->email->subject('Bestand(en) gedeeld ' . ($this->client['whitelabel_footer'] ? '' : ('via ImageShare ')) . 'van ' . $from_name);
					$this->email->message('Er ' . (count($image_ids_array) == 1 ? 'is een bestand' : 'zijn bestanden') . ' met u gedeeld'.$via.'.<br /><br /><strong>Afzender:</strong><br />'.$from.'<br /><br /><strong>Naam:</strong><br />'.$image['name'].'<br /><br /><strong>Bericht:</strong><br /> ' . (empty($message) ? '(geen)' : nl2br($message)) . '<br /><br /><strong>Download link:</strong><br /> <a href="'.$link.'" target="_blank">' . $link . '</a><br /><small>U kunt het bestand downloaden tot ' . date("d-m-Y", $expire_date));

					if(!$this->email->send())
					{			
						$success = FALSE;
					}
				}
				
				if($success)
				{
					succes_message('E-mail is succesvol verstuurd!');
				}
				else
				{
					error_message('E-mail kon niet worden verstuurd.');
				}
				redirect('/images/info/' . $image_id);
			}
		}
		
		$this->load->view('images/share', $data);
	}
	
	public function bulk_add_keywords()
	{
		//$this->images_model->update_keywords($image_id, $this->input->post('keywords'));
		
		$image_ids = explode(",", $this->input->post('image_ids'));
		foreach($image_ids as $image_id)
		{		
			$keywords = $this->images_model->get_keywords($image_id);
			$save_keywords = array_unique(array_merge($keywords, explode(",", $this->input->post('keywords'))));

			$this->images_model->update_keywords($image_id, implode(",", $save_keywords));
		}

		$this->load->library('user_agent');
		if ($this->agent->is_referral())
		{
			redirect($this->agent->referrer());
		}
		else
		{
			redirect('/');
		}
	}
	
	public function delete($image_ids, $bulk = FALSE)
	{
		$image_ids_array = explode(",", $image_ids);
		if(!empty($image_ids_array))
		{
			foreach($image_ids_array as $image_id)
			{
				$image = $this->images_model->get($image_id);
				if(!is_admin() && $this->session->userdata('user_id') != $image['uploaded_by'])
				{
					show_error('No right to delete this image.');
				}
				
				$this->images_model->delete($image_id);
			}
		}

		succes_message('Succesvol verwijderd.');
		redirect('/');
	}
}

/* End of file images.php */
/* Location: ./application/controllers/images.php */