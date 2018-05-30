<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Users extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		
		if(!is_admin() && $this->uri->segment(2) != 'profile')
		{
			redirect('/login');
		}
		
		$this->client = $this->clients_model->client_by_url();		
		$this->client_id = $this->client['client_id'];
		
		$this->load->model('users_model');
	}
	
	public function index()
	{
		$this->load->view('users/index', array(
			'page_title' => 'Gebruikers',
			'users' => $this->users_model->get_all()
		));
	}
	
	public function username_check($username)
	{
		if ($this->users_model->username_exists($username))
		{
			$this->form_validation->set_message('username_check', 'De gebruikersnaam "'.$username.'" is al in gebruik.');
			return FALSE;
		}
		else
		{
			return TRUE;
		}
	}
	
	public function add()
	{
		$data = array(
			'page_title' => 'Gebruiker toevoegen'
		);
	
		if($this->input->post('username') !== FALSE)
		{
			$this->form_validation->set_rules('username', 'Gebruikersnaam', 'trim|required|max_length[255]|min_length[2]|xss_clean|callback_username_check');
			$this->form_validation->set_rules('password', 'Wachtwoord', 'trim|required|max_length[255]|min_length[2]|xss_clean');
			$this->form_validation->set_rules('company_name', 'Bedrijfsnaam', 'trim|max_length[255]|xss_clean');
			$this->form_validation->set_rules('first_name', 'Voornaam', 'trim|max_length[255]|xss_clean');
			$this->form_validation->set_rules('middle_name', 'Tussenvoegsel', 'trim|max_length[255]|xss_clean');
			$this->form_validation->set_rules('last_name', 'Achternaam', 'trim|max_length[255]|xss_clean');
			$this->form_validation->set_rules('email', 'E-mail', 'trim|max_length[255]|valid_email|xss_clean');
			$this->form_validation->set_rules('address', 'Adres', 'trim|max_length[255]|xss_clean');
			$this->form_validation->set_rules('postal_code', 'Postcode', 'trim|max_length[255]|xss_clean');
			$this->form_validation->set_rules('place', 'Plaats', 'trim|max_length[255]|xss_clean');
			$this->form_validation->set_rules('phone_number', 'Telefoonnummer', 'trim|max_length[255]|xss_clean');
			$this->form_validation->set_rules('type', 'Type', 'required|xss_clean');
			$this->form_validation->set_rules('active', 'Status', 'required|is_numeric|xss_clean');
			$this->form_validation->set_rules('expire_date', 'Verloopdatum', 'trim|xss_clean');
			
			if ($this->form_validation->run() !== FALSE)
			{
				$this->users_model->add(array(
					'username' => $this->input->post('username'), 
					'password' => md5($this->input->post('password')),
					'company_name' => $this->input->post('company_name'), 
					'first_name' => $this->input->post('first_name'), 
					'middle_name' => $this->input->post('middle_name'), 
					'last_name' => $this->input->post('last_name'), 
					'email' => $this->input->post('email'), 
					'address' => $this->input->post('address'),
					'postal_code' => $this->input->post('postal_code'), 
					'place' => $this->input->post('place'), 
					'phone_number' => $this->input->post('phone_number'), 
					'type' => $this->input->post('type'),
					'client_id' => $this->client_id,
					'active' => $this->input->post('active'),
					'expire_date' => strtotime($this->input->post('expire_date'))
				));
				redirect('/users');
			}
		}
		
		$this->load->view('users/add', $data);
	}
	
	public function edit($user_id)
	{
		$user = $this->users_model->get($user_id);
		$data = array(
			'page_title' => 'Gebruiker wijzigen',
			'user_id' => $user_id,
			'user' => $user
		);
		
		if($this->input->post('company_name') !== FALSE)
		{
			$save_type = ($user['user_id'] != user_id());
		
			$this->form_validation->set_rules('company_name', 'Bedrijfsnaam', 'trim|max_length[255]|xss_clean');
			$this->form_validation->set_rules('first_name', 'Voornaam', 'trim|max_length[255]|xss_clean');
			$this->form_validation->set_rules('middle_name', 'Tussenvoegsel', 'trim|max_length[255]|xss_clean');
			$this->form_validation->set_rules('last_name', 'Achternaam', 'trim|max_length[255]|xss_clean');
			$this->form_validation->set_rules('email', 'E-mail', 'trim|max_length[255]|valid_email|xss_clean');
			$this->form_validation->set_rules('address', 'Adres', 'trim|max_length[255]|xss_clean');
			$this->form_validation->set_rules('postal_code', 'Postcode', 'trim|max_length[255]|xss_clean');
			$this->form_validation->set_rules('place', 'Plaats', 'trim|max_length[255]|xss_clean');
			$this->form_validation->set_rules('phone_number', 'Telefoonnummer', 'trim|max_length[255]|xss_clean');
			if($save_type) $this->form_validation->set_rules('type', 'Type', 'required|xss_clean');
			$this->form_validation->set_rules('active', 'Status', 'required|is_numeric|xss_clean');
			$this->form_validation->set_rules('expire_date', 'Verloopdatum', 'trim|xss_clean');
			
			if ($this->form_validation->run() !== FALSE)
			{
				$update_data = array(
					'company_name' => $this->input->post('company_name'), 
					'first_name' => $this->input->post('first_name'), 
					'middle_name' => $this->input->post('middle_name'), 
					'last_name' => $this->input->post('last_name'), 
					'email' => $this->input->post('email'), 
					'address' => $this->input->post('address'),
					'postal_code' => $this->input->post('postal_code'), 
					'place' => $this->input->post('place'), 
					'phone_number' => $this->input->post('phone_number'), 
					'active' => $this->input->post('active'),
					'expire_date' => strtotime($this->input->post('expire_date'))
				);
				
				if($save_type)
				{
					$update_data['type'] = $this->input->post('type');
				}
				
				$new_password = $this->input->post('new_password');
				if(!empty($new_password))
				{
					$update_data['password'] = md5($new_password);
				}
				
				$this->users_model->update($user_id, $update_data);
				redirect('/users');
			}
		}
		
		$this->load->view('users/edit', $data);
	}
	
	public function profile($user_id)
	{
		$user = $this->users_model->get($user_id);
		if(empty($user))
		{
			show_error('User cannot be found.');
		}
		
		$downloads = $this->users_model->downloads($user_id);
		$downloads_grouped = array();
		foreach($downloads as $download)
		{
			#print_r($download);
			if(!$download['downloaded_at'] < '1') // downloadtijd onbekend, geeft php errors.
				$downloads_grouped[strtotime(date("d-m-Y", $download['downloaded_at']))][] = $download;
		}
		
		$this->load->view('users/profile', array(
			'page_title' => lang('profile.title'),
			'page_subtitle' => $user['username'],
			'user' => $user,
			'show_uploads' => TRUE,
			'downloads_grouped' => $downloads_grouped,
			'counted_downloads' => $this->users_model->count_downloads($user_id),
			'counted_uploads' => $this->users_model->count_uploads($user_id)
		));
	}

	public function delete($client_id)
	{
		$this->users_model->delete($client_id);
		redirect('/users');
	}
}

/* End of file users.php */
/* Location: ./application/controllers/users.php */