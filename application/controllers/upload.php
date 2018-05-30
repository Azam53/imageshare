<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Upload extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		
		$this->output->enable_profiler(isset($_GET['profile']));
		
		if(!logged_in())
		{
			redirect('/login');
		}
		
		$this->client = $this->clients_model->client_by_url();		
		$this->client_id = $this->client['client_id'];
		
		if((may_upload() === FALSE) && $this->uri->segment(2) != 'disabled')
		{
			redirect('/upload/disabled');
		}
		
		$this->load->library('file_uploader');
		
		// is here to check for the GD plugin, image_moo doesn't throw an exception
		$image = @imagecreatefrompng(FCPATH . 'img/logo-new.png');
		if(empty($image))
		{
			@mail("michel@refreshmedia.nl", "img01 GD plugin failed", "img01 GD plugin failed");
		}
	}

	public function index()
	{
		$time = $this->input->get('time');
		
		if($this->input->get('success') == 1)
		{
			succes_message('Bestanden zijn succesvol geupload.');
			redirect('/upload?time=' . $time);
			exit;
		}		
		
		/*$images_group = array();
		$images = $this->images_model->get_all($this->client_id, 'uploaded_at,desc');
		foreach($images as $image)
		{
			$keywords = $this->images_model->get_keywords($image['image_id']);
			if(empty($keywords))
			{
				$image['keywords_string'] = implode(",", $keywords);
				$images_group[$image['uploaded_at']][] = $image;
			}
		}*/
		
		$images = $this->images_model->get_all_with_empty_keywords($this->client_id, 'uploaded_at,desc');
		$images_group = array();
		foreach($images as $image)
		{
			$image['keywords_string'] = ''; //implode(",", $keywords);
			$images_group[$image['uploaded_at']][] = $image;
		}
	
		$this->load->view('upload/index', array(
			'page_title' 		=> 'Uploaden',
			'images_group' 		=> $images_group,
			'extensions' 		=> $this->file_uploader->getAllowedExtensions($this->client['extra_uploads']),
			'time' 				=> $time
		));
	}
	
	public function log()
	{
		$this->db->limit(100);
		$this->db->order_by('date', 'desc');
		$this->db->join('images', 'images.image_id = upload_log.image_id', 'left');
		$query = $this->db->get_where('upload_log', array(
			'client_id' => $this->client_id
		));
		$results = $query->result_array();

		
		$images = array();
		foreach($results as $item)
		{
			if(!empty($item['uploaded_by'])) // check a field from table "images"
			{
				$item['keywords'] = $this->images_model->get_keywords($item['image_id']);
				$images[] = $item;
			}
		}
		
		$this->load->view('upload/log', array(
			'page_title' => 'Upload log',
			'page_subtitle' => 'Laatste 100 uploads',
			'images' => $images
		));
	}
	
	/* public function test()
	{
		$path = FCPATH . "/uploads/6ea9ab1baa0efb9e19094440c317e21b/refresh-media.eps";
		$save_to = FCPATH . "/uploads/tmp/6ea9ab1baa0efb9e19094440c317e21b_ce2dcdaec72d211843871d6979c3a4fe_160x120.png";
	

		echo exec("convert ".$path." -resize 300x76 -depth 8 -strip " . $save_to);
		
		echo $save_to;
	
	} */
	
	public function save()
	{
		//$main_keywords = $this->input->post('main_keywords');

		$names_array = $this->input->post('names');
		$keywords_array = $this->input->post('keywords');
		$notes_array = $this->input->post('notes');

		$total = 0;
		if(!empty($names_array))
		{
			foreach($names_array as $image_id => $name)
			{			
				$keywords = trim($keywords_array[$image_id], ','); //$main_keywords . ',' . 
				if(!empty($keywords))
				{
					$this->images_model->update_keywords($image_id, $keywords);
					
					$total++;
				}

				$this->images_model->update($image_id, array(
					'name' => $name,
					'notes' => $notes_array[$image_id]
				));
				
				$this->db->update('upload_log', array(
					'keywords' => empty($keywords) ? '' : $keywords
				), array(
					'image_id' => $image_id
				));
			}
		}
		
		if($total > 0)
		{
			succes_message('Bij ' . $total . ' foto(\'s) succesvol trefwoorden toegevoegd.');
		}
		
		redirect('/upload');
	}

	public function handle_upload()
	{
		$keywords 	= @$_GET['keywords'];
		$date 		= @$_GET['currentTime'];
		
		// list of valid extensions, ex. array("jpeg", "xml", "bmp")
		// max file size in bytes
		$this->file_uploader->init();
		$result = $this->file_uploader->handleUpload(uploads_dir($this->client_id));

		if(!empty($result['success']) && $result['success'])
		{
			$this->load->helper('inflector');
			
			/** insert into database */
			$file = $result['filename'] . '.' . $result['ext'];
			$image_id = $this->images_model->add(array(
				'client_id' => $this->client_id,
				'uploaded_by' => $this->session->userdata('user_id'),
				'name' => $result['filename'],
				'file' => $file,
				'notes' => '',
				'orgi_image_id' => 0,
				'uploaded_at' => $date
			));
			if(!empty($keywords))
			{
				$this->images_model->update_keywords($image_id, $keywords);
			}
			
			$this->db->insert('upload_log', array(
				'image_id' => $image_id,
				'date' => $date,
				'keywords' => empty($keywords) ? '' : $keywords
			));
			
			/** Make some tmp images for thumbnail */
			resize($this->client_id, $file, 160, 120);			
			resize($this->client_id, $file, 700);
			
			usleep(50000);
		}
		
		header("Content-type: application/json");
		echo json_encode($result);
	}
	
	public function autocomplete_keywords()
	{
		$items = $array = array();
		
		$this->db->join('images', 'images.image_id = keywords.image_id');
		$results = $this->images_model->get_keywords_like($this->client_id, $this->input->get('term'));
		foreach($results as $item)
		{
			$array[$item['keyword']] = array(
				'id' => $item['keyword'],
				'label' => $item['keyword'],
				'value' => $item['keyword']
			);
		}
		echo json_encode($array);
	}
	
	public function delete($image_ids)
	{
		$ids = explode(",", $image_ids);
		foreach($ids as $id)
		{
			$this->images_model->delete($id);
		}
		redirect('/upload');
	}
	
	public function disabled()
	{
		$this->load->view('global/disabled');
	}
}

/* End of file login.php */
/* Location: ./application/controllers/login.php */