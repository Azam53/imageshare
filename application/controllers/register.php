<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Register extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		
		if(logged_in())
		{
			redirect('/');
		}
		
		$this->client = $this->clients_model->client_by_url();		
		$this->client_id = $this->client['client_id'];

		$allow_registration = $this->settings_model->get('allow_registration');
		if(!$allow_registration && $this->uri->segment(2) != 'disabled')
		{
			redirect('/register/disabled');
		}
		
		$this->load->model('users_model');
	}
	
	public function index()
	{
		$data = array(
			'page_title' => 'Registeren',
			'terms' => $this->settings_model->get('terms'),
			'hide_search' => TRUE
		);
		
		if($this->input->post('username') !== FALSE)
		{
			$this->form_validation->set_rules('username', 'Gebruikersnaam', 'trim|required|max_length[255]|min_length[2]|is_unique[users.username]|xss_clean');
			$this->form_validation->set_rules('password', 'Wachtwoord', 'trim|required|max_length[255]|min_length[2]|matches[passconf]|xss_clean');
			$this->form_validation->set_rules('passconf', 'Herhaal wachtwoord', 'trim|max_length[255]|min_length[2]|xss_clean');
			$this->form_validation->set_rules('company_name', 'Bedrijfsnaam', 'trim|max_length[255]|xss_clean');
			$this->form_validation->set_rules('first_name', 'Voornaam', 'trim|max_length[255]|xss_clean');
			$this->form_validation->set_rules('middle_name', 'Tussenvoegsel', 'trim|max_length[255]|xss_clean');
			$this->form_validation->set_rules('last_name', 'Achternaam', 'trim|max_length[255]|xss_clean');
			$this->form_validation->set_rules('email', 'E-mail', 'trim|max_length[255]|valid_email|xss_clean');
			$this->form_validation->set_rules('address', 'Adres', 'trim|max_length[255]|xss_clean');
			$this->form_validation->set_rules('postal_code', 'Postcode', 'trim|max_length[255]|xss_clean');
			$this->form_validation->set_rules('place', 'Plaats', 'trim|max_length[255]|xss_clean');
			$this->form_validation->set_rules('phone_number', 'Telefoonnummer', 'trim|max_length[255]|xss_clean');
			$this->form_validation->set_rules('terms', 'Algemene voorwaarden', 'required|xss_clean', 'U moet akkoord gaan met onze algemene voorwaarden.');
			
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
					'client_id' => $this->client_id,
					'type' => 'downloader',
					'active' => '0'
				));
				
				succes_message('U bent succesvol geregistreerd, uw account moet nog wel goedgekeurd worden door de administrator.');
				
				if(!empty($this->client['email']))
				{
					$config['protocol'] = 'mail';
					$config['wordwrap'] = FALSE;
					$config['mailtype'] = 'html'; 
					$this->load->library('email', $config);

					$this->email->from('info@image-share.nl', 'ImageShare');
					$this->email->to($this->client['email']);
					$this->email->bcc('michel@refresh-media.nl');

					$this->email->subject('Nieuwe gebruiker');
					$this->email->message('Beste Admin<br /><br />Een nieuwe gebruiker heeft zich aangemeld voor ImageShare. U kunt deze gebruiker activeren door in te loggen op ImageShare.<br /><br /><b>Gegevens van de gebruiker</b><br />Gebruikersnaam:&nbsp;'.$this->input->post('username').'<br />E-mailadres:&nbsp;'.$this->input->post('email').'<br />Voornaam:&nbsp;'.$this->input->post('first_name').'<br />Achternaam:&nbsp;'.$this->input->post('last_name').'<br />');

					$this->email->send();
				}
				
				redirect('/login');
			}
		}
		
		$this->load->view('register/index', $data);
	}
	
	public function terms()
	{
		$this->load->view('register/terms', array(
			'terms' => $this->settings_model->get('terms'),
			'hide_search' => TRUE
		));
	}
	
	public function disabled()
	{
		$this->load->view('global/disabled', array(
			'hide_search' => TRUE
		));
	}
}

/* End of file users.php */
/* Location: ./application/controllers/users.php */