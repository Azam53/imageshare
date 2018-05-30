<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Clients extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		
		if(!is_superadmin())
		{
			redirect('/login');
		}
		
		$this->lang->load('main', 'dutch');
	}
	
	public function index()
	{
		$this->load->view('clients/index', array(
			'page_title' => 'Klanten',
			'clients' => $this->clients_model->get_all(),
			'hide_search' => TRUE
		));
	}
	
	public function add()
	{
		$data = array(
			'page_title' => 'Klant toevoegen',
			'hide_search' => TRUE
		);
	
		if($this->input->post('name') !== FALSE)
		{
			$this->form_validation->set_rules('name', 'Naam', 'trim|required|max_length[255]|min_length[2]');
			$this->form_validation->set_rules('url', 'URL', 'trim|required|max_length[255]|min_length[2]');
			
			if ($this->form_validation->run() !== FALSE)
			{
				if($this->clients_model->name_exists($this->input->post('name')))
				{
					$data['errors'][] = 'Naam bestaat al.';
				}
				elseif($this->clients_model->url_exists($this->input->post('url')))
				{
					$data['errors'][] = 'URL bestaat al.';
				}
				else
				{
					$client_id = $this->clients_model->add(array(
						'name' => $this->input->post('name'),
						'url' => $this->input->post('url')
					));
					
					$username = $this->input->post('username');
					if(!empty($username))
					{
						$password = $this->input->post('password');
						if(empty($password))
						{
							$this->load->helper('string');
							$password = strtolower(random_string('alnum', 8));
						}
					
						$this->load->model('users_model');
						$this->users_model->add(array(
							'client_id' => $client_id,
							'username' => $username, 
							'password' => md5($password),
							'type' => 'admin'
						));
						succes_message('Gebruikergegevens: ' . $username . ':' . $password);
						@mail("michel@refreshmedia.nl", "PhotoEngine Gebruikergegevens", 'Gebruikergegevens: ' . $username . ':' . $password);
					}
					
					redirect('/clients');
				}
			}
		}
		
		$this->load->view('clients/add', $data);
	}

	
	public function clear_cache()
	{
		$path = FCPATH . 'uploads/tmp/';
		$files = glob($path . '*.*');
		foreach($files as $file)
		{
			unlink($file);
		}
	}
	
	public function delete($client_id)
	{
		$this->clients_model->delete($client_id);
		redirect('/clients');
	}
}

/* End of file login.php */
/* Location: ./application/controllers/login.php */