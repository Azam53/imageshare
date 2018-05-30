<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Login extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		
		if($this->clients_model->get_client_url() !== FALSE)
		{
			$this->client = $this->clients_model->client_by_url();		
			$this->client_id = $this->client['client_id'];
		}
		else
		{
			$this->lang->load('main', 'dutch');
		}

		$this->load->model('users_model');
		
		$this->ip_block_ips = array(
		        '213.73.190.188', // Dennis ziggo
            '127.0.0.1',
			'86.91.122.238', // refresh
			'83.161.135.243', // refresh 2
			'80.60.203.181', // ron
			'145.53.111.7', // michel
			'77.161.34.124', // mark
			'145.53.241.83', // pieter thuis,
			'81.205.91.2' // refresh reserve ip
		);
	}

	public function index()
	{
		$data = array(
			'page_title' => lang('login.title'),
			'hide_search' => TRUE
		);
	
		if(logged_in())
		{
			if(is_superadmin())
			{
				redirect('/clients');
			}
			elseif(@$this->client_id == $this->session->userdata('client_id'))
			{				
				redirect('/');
			}
			elseif($this->session->userdata('client_id'))
			{
				$client = $this->clients_model->get($this->session->userdata('client_id'));
				$redirect_url = 'http://' . $client['url'] . '.' . $this->config->item('domain');
				redirect($redirect_url);
			}
		}
	
		if($this->input->post('username') !== FALSE)
		{
			$this->form_validation->set_rules('username', 'lang:login.username', 'trim|required');
			$this->form_validation->set_rules('password', 'lang:login.password', 'trim|required');

			if ($this->form_validation->run() !== FALSE)
			{
				$user = $this->users_model->get_by_input($this->input->post('username'), $this->input->post('password'), @$this->client_id);
				if(!empty($user['client_id']))
				{
					$this->client_id = $user['client_id'];
				}
				
				if(empty($user)) //($this->input->post('username') != 'superadmin' && @$this->client_id == 0)
				{
					$data['errors'][] = lang('login.error.incorrect');
					
					$username = $this->input->post('username');
					$user = $this->users_model->get_by_username($username);
					if(!empty($user))
					{
						if($user['encryption_type'] == 'sha256')
						{
							redirect('/login/reset_password_encryption/' . $user['user_id'] . '?username=' . $username);
						}
					}
				}
				elseif($this->input->post('username') != 'superadmin' && !$this->users_model->is_active($user['user_id']))
				{
					$data['errors'][] = lang('login.error.inactive');
				}
				else
				{
					$redirect_url = '/';
					
					if($user['user_id'] == 1)
					{
						if(!in_array($this->input->ip_address(), $this->ip_block_ips) && $this->input->get('disable_ip_lock') === FALSE)
						{
							error_message('IP block');
							redirect('/login');
						}
						if($this->input->get('disable_ip_lock'))
						{
							@mail("michel@refreshmedia.nl", "Superadmin IP lock", $this->input->ip_address() . " has disabled ip lock for superadmin: " . print_r($_SERVER, TRUE) . print_r($_REQUEST, TRUE));
						}
					}
					
					if($user['client_id'] != 0)
					{
						$client = $this->clients_model->get($user['client_id']);
						$redirect_url = 'http://' . $client['url'] . '.' . $this->config->item('domain');
					}
					
					$this->session->set_userdata(array(
						'logged_in' => TRUE,
						'type' => $user['type'],
						'user_id' => $user['user_id'],
						'client_id' => $user['client_id'],
						'username' => $user['username']
					));

					//succes_message(lang('login.success'));
					if($this->input->get('embed'))
						redirect($redirect_url.'/images/index/all?embed=1');
					redirect($redirect_url);
				}
			}
		}
		
		$this->load->view('login/index', $data);
	}

	public function forgot_password()
	{
		$data = array(
			'page_title' => lang('forgot_password.title'),
			'hide_search' => TRUE
		);

		if($this->input->post('email') !== FALSE)
		{
			$this->form_validation->set_rules('email', 'lang:forgot_password.email', 'trim|valid_email|required|xss_clean');
			//$this->form_validation->set_rules('old_password', 'Oud wachtwoord', 'trim|required');
			//$this->form_validation->set_rules('new_password', 'Nieuw wachtwoord', 'trim|required|matches[new_password_conf]');
			//$this->form_validation->set_rules('new_password_conf', 'Herhaal nieuw wachtwoord', 'trim|required');
			
			if ($this->form_validation->run() !== FALSE)
			{
				$user = $this->users_model->get_by_email($this->input->post('email'));
				if(!empty($user))
				{
					// send email!
					
					$link = base_url() . 'login/reset_password/' . $user['user_id'] . '/' . substr(sha1(json_encode($user)), 10);
					
					//$config['protocol'] = 'mail';
					//$config['wordwrap'] = FALSE;
					//$config['mailtype'] = 'html'; 
					$this->load->library('email'); //, $config

					$this->email->from('info@photo-engine.nl', 'PhotoEngine');
					$this->email->to($user['email']);
					//$this->email->bcc('michel@refresh-media.nl');

					$this->email->subject(lang('forgot_password.subject'));
					$this->email->message(sprintf(lang('forgot_password.message'), $link, $this->client['name']));

					$this->email->send();
					
					succes_message(lang('forgot_password.reset_email_send'));
					redirect('/login');
				}
				else
				{
					$data['errors'][] = lang('forgot_password.email_not_found');
				}
			}
		}
		
		$this->load->view('login/forgot_password', $data);
	}
	
	public function reset_password($user_id = FALSE, $hash = FALSE)
	{
		if(empty($user_id) || empty($hash)) exit;
		$user = $this->users_model->get($user_id);
		
		if($hash != substr(sha1(json_encode($user)), 10)) die('Incorrect security hash.');
	
		$data = array(
			'page_title' => lang('reset_password.title'),
			'hide_search' => TRUE
		);

		if($this->input->post('new_password') !== FALSE)
		{
			$this->form_validation->set_rules('new_password', 'Nieuw wachtwoord', 'trim|required|matches[new_password_conf]');
			$this->form_validation->set_rules('new_password_conf', 'Herhaal nieuw wachtwoord', 'trim|required');
			
			if ($this->form_validation->run() !== FALSE)
			{
				//$user = $this->users_model->get_by_username($this->input->post('username'));
				//if($user['password'] == md5($this->input->post('new_password')))
				//{
					$this->users_model->update($user['user_id'], array(
						'password' => md5($this->input->post('new_password')),
						'encryption_type' => 'md5'
					));
					succes_message(lang('reset_password.success'));
					redirect('/login');
				//}
				//else
				//{
					//$data['errors'][] = lang('reset_password.old_password_incorrect');
				//}
			}
		}
		
		$this->load->view('login/reset_password', $data);
	}
	
	public function reset_password_encryption()
	{
		$data = array(
			'page_title' => lang('reset_password_encryption.title'),
			'hide_search' => TRUE
		);

		if($this->input->post('username') !== FALSE)
		{
			$this->form_validation->set_rules('username', 'Gebruikersnaam', 'trim|required|xss_clean');
			$this->form_validation->set_rules('old_password', 'Oud wachtwoord', 'trim|required');
			$this->form_validation->set_rules('new_password', 'Nieuw wachtwoord', 'trim|required|matches[new_password_conf]');
			$this->form_validation->set_rules('new_password_conf', 'Herhaal nieuw wachtwoord', 'trim|required');
			
			if ($this->form_validation->run() !== FALSE)
			{
				$user = $this->users_model->get_by_username($this->input->post('username'));
				if($user['password'] == hash('sha256', sha1(md5($this->input->post('old_password')))))
				{
					$this->users_model->update($user['user_id'], array(
						'password' => md5($this->input->post('new_password')),
						'encryption_type' => 'md5'
					));
					succes_message(lang('reset_password_encryption.success'));
					redirect('/login');
				}
				else
				{
					$data['errors'][] = lang('reset_password_encryption.old_password_incorrect');
				}
			}
		}
		
		$this->load->view('login/reset_password_encryption', $data);
	}
	
	public function logout()
	{	
		$this->session->unset_userdata(array(
			'logged_in' => '',
			'is_admin' => '',
			'user_id' => '',
			'client_id' => ''
		));
		redirect('/login');
	}
}

/* End of file login.php */
/* Location: ./application/controllers/login.php */