<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Users_model extends MY_Model
{	
	public function __construct()
	{
		parent::__construct('users', 'user_id');
	}
	
	public function get_all()
	{
		$this->db->order_by('username', 'asc');
		$this->db->where('client_id', $this->client_id);
		return parent::get_all();
	}
	
	public function username_exists($username)
	{
		$query = $this->db->get_where('users', array(
			'client_id' => $this->client_id,
			'username' => $username
		));
		return $query->num_rows() == 1;
	}
	
	public function is_active($user_id)
	{
		$query = $this->db->get_where('users', array(
			'client_id' => $this->client_id,
			'user_id' => $user_id
		));
		$row = $query->row_array();
		
		if(empty($row)) return FALSE;
		
		if(!$row['active'])
			return FALSE;
		
		if(!empty($row['expire_date']) && $row['expire_date'] < time())
			return FALSE;
		
		return TRUE;
	}
	
	public function downloads($user_id)
	{
		$this->db->order_by('downloaded_at', 'desc');
		$this->db->where('client_id', $this->client_id);
		$query = $this->db->get_where('downloads', array(
			'user_id' => $user_id
		));
		return $query->result_array();
	}

	public function count_downloads($user_id)
	{
		$this->db->where('client_id', $this->client_id);
		$query = $this->db->get_where('downloads', array(
			'user_id' => $user_id
		));
		return $query->num_rows();
	}
	
	public function count_uploads($user_id)
	{
		$this->db->where('client_id', $this->client_id);
		$query = $this->db->get_where('images', array(
			'uploaded_by' => $user_id
		));
		return $query->num_rows();
	}
	
	public function get_by_input($username, $password, $client_id = FALSE)
	{		
		if($client_id && $username != 'superadmin') $this->db->where('client_id', $client_id);
		$query = $this->db->get_where('users', array(
			'username' => $username,
			'password' => md5($password)
		));
		return $query->row_array();
	}	
	
	public function get_by_username($username)
	{		
		$query = $this->db->get_where('users', array(
			'username' => $username
		));
		return $query->row_array();
	}
	
	public function get_by_email($email)
	{		
		
		$query = $this->db->get_where('users', array(
			'email' => $email,
			'client_id' => $this->client_id
		));
		return $query->row_array();
	}
}

/* End of file users_model.php */
/* Location: ./application/models/users_model.php */