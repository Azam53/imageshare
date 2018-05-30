<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Settings extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		
		if(!is_admin())
		{
			redirect('/login');
		}
		
		$this->client = $this->clients_model->client_by_url();		
		$this->client_id = $this->client['client_id'];

		$this->load->model(array(
			'users_model'
		));
	}
	
	public function clear_cache()
	{
		$files = uploads_dir() . '/tmp/';
		
	}
	
	public function index()
	{
		$data = $this->clients_model->get($this->client_id);
		$data['page_title'] = 'Instellingen';
		
		$data['quota_used'] = folder_size(uploads_dir($this->client_id)) / 1048576;
		$data['quota_percent'] = (empty($data['quota_limit']) ? 0 : $data['quota_used'] / $data['quota_limit'] * 100);
	
		if($this->input->post('allow_registration') !== FALSE)
		{
			$this->form_validation->set_rules('allow_registration', 'Registreren', 'required|is_numeric|xss_clean');
			$this->form_validation->set_rules('email', 'Admin mailadres', 'required|trim|xss_clean');
			$this->form_validation->set_rules('show_uploader', 'Toon uploader', 'required|is_numeric|xss_clean');
			//$this->form_validation->set_rules('user_may_upload', 'Gebruiker mag uploaden', 'required|is_numeric|xss_clean');
			//$this->form_validation->set_rules('watermark', 'Watermerk', 'required|is_numeric|xss_clean');
			
			if(is_superadmin())
			{
				$this->form_validation->set_rules('public', 'Beschikbaarheid', 'required|is_numeric|xss_clean');
				$this->form_validation->set_rules('download_without_login', 'Downloaden zonder inloggen', 'required|is_numeric|xss_clean');
				$this->form_validation->set_rules('quota_limit', 'Schrijfruimte limiet', 'required|is_numeric|xss_clean');
				$this->form_validation->set_rules('filename_rendering', 'Tonen in overzicht onder thumbnails', 'required|trim|xss_clean');
				$this->form_validation->set_rules('language', 'Taal', 'required|xss_clean');
				$this->form_validation->set_rules('show_last_uploaded', 'Toon laatst toegevoegde bestanden op homepage', 'required|is_numeric|xss_clean');
				$this->form_validation->set_rules('whitelabel_logo', 'PublishEngine logo linsboven tonen', 'required|is_numeric|xss_clean');
				$this->form_validation->set_rules('whitelabel_footer', 'Vermelding "Refresh Media" onderin in footer tonen', 'required|is_numeric|xss_clean');
				$this->form_validation->set_rules('sponser_footer', 'Sponsor footer', 'required|is_numeric|xss_clean');
				$this->form_validation->set_rules('extra_uploads', 'Uploadmogelijkheid', 'required|is_numeric|xss_clean');
				$this->form_validation->set_rules('always_show_watermark','Toon altijd watermerk','required');
				#$this->form_validation->set_rules('watermark', 'Watermerk', 'required|is_numeric|xss_clean');
			}
			
			if ($this->form_validation->run() !== FALSE)
			{
				$update_data = array(
					'allow_registration' => $this->input->post('allow_registration'), 
					'email' => $this->input->post('email'), 
					'show_uploader' => $this->input->post('show_uploader'), 
					//'user_may_upload' => $this->input->post('user_may_upload'), 
					'terms' => $this->input->post('terms'), 					
					'intro' => $this->input->post('intro')
				);
				
				if(is_superadmin())
				{
					$update_data['public'] = $this->input->post('public');
					$update_data['download_without_login'] = $this->input->post('download_without_login');
					$update_data['quota_limit'] = $this->input->post('quota_limit');
					$update_data['filename_rendering'] = $this->input->post('filename_rendering');
					$update_data['language'] = $this->input->post('language');
					$update_data['show_last_uploaded'] = $this->input->post('show_last_uploaded');
					$update_data['whitelabel_logo'] = $this->input->post('whitelabel_logo');
					$update_data['whitelabel_footer'] = $this->input->post('whitelabel_footer');
					$update_data['sponser_footer'] = $this->input->post('sponser_footer');
					$update_data['extra_uploads'] = $this->input->post('extra_uploads');
					$update_data['watermark'] =  $this->input->post('watermark');
					$update_data['always_show_watermark'] = $this->input->post('always_show_watermark');
				}
				
				$this->settings_model->update($update_data);

				$errors = FALSE;
				if(!empty($_FILES['logo']['tmp_name']))
				{
					$this->load->library('upload');
					$this->load->library('image_lib');
		
					foreach(array('jpg', 'gif', 'png') as $ext)
					{
						$path = uploads_dir($this->client_id) . '_logo.' . $ext;	
						if(file_exists($path)) unlink($path);
					}
					
					@mkdir(uploads_dir($this->client_id));

					$config['upload_path'] = uploads_dir($this->client_id);
					$config['allowed_types'] = 'jpg|gif|png';
					$config['file_name'] = '_logo';
					$config['overwrite'] = TRUE;
					$this->upload->initialize($config);
					
					if (!$this->upload->do_upload('logo'))
					{
						error_message($this->upload->display_errors());
						$errors = TRUE;
					}
				}
				
				if(!empty($_FILES['watermark']['tmp_name']))
				{
                    $path = uploads_dir($this->client_id) . '_watermark.png';
                    $path_small = uploads_dir($this->client_id) . '_watermark_small.png';

                    if(file_exists($path)) unlink($path);
                    if(file_exists($path_small)) unlink($path_small);

				    $this->load->library('upload');
					$this->load->library('image_lib');

					@mkdir(uploads_dir($this->client_id));

					$config['upload_path'] = uploads_dir($this->client_id);
					$config['allowed_types'] = 'png';
					$config['file_name'] = '_watermark';
					$config['overwrite'] = TRUE;
					$this->upload->initialize($config);
					
					if (!$this->upload->do_upload('watermark'))
					{
						error_message($this->upload->display_errors());
						$errors = TRUE;
					}
				}
				
				if($errors === FALSE)
					succes_message('Succesvol gewijzigd.');
				
				redirect('/settings');
			}
		}
		
#		print_r($data);
		
		#exit;
		
		$this->load->view('settings/index', $data);
	}
}

/* End of file settings.php */
/* Location: ./application/controllers/settings.php */