<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Migrate extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		
		if(!is_superadmin())
		{
			redirect('/login');
		}
		
		$this->load->dbforge();
	}
	
	public function index()
	{
		echo 'migrate!';
		
		$query = $this->db->get_where('users', array(
		
		));
		foreach($query->result_array() as $user)
		{
			$cquery = $this->db->get_where('clients', array(
				'client_id' => $user['client_id']
			));
			$client = $cquery->row_array();

			$this->db->update('users', array(
				'type' => ($user['admin']) ? 'admin' : (($client['user_may_upload'] == 0) ? 'downloader' : 'uploader')
			), array(
				'user_id' => $user['user_id']
			));	
		}
		
		$this->dbforge->modify_column('clients', array(
			'user_may_upload' => array(
				'name' => 'deprecated_user_may_upload',
				'type' => 'INT'
			)
		));
	
	}
}

/* End of file login.php */
/* Location: ./application/controllers/login.php */