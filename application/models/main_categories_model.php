<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Main_categories_model extends MY_Model
{	
	public function __construct()
	{
		parent::__construct('main_categories', 'main_category_id');
	}
	
	public function get_all()
	{
		$this->db->order_by('order asc, name asc');
		$this->db->where('client_id', $this->client_id);
		return parent::get_all();
	}
	public function check_if_exists($value, $array, $key)
	{
		foreach($array as $item)
			if($item[$key] == $value)
				return true;
		 return false;
	
	}
}

/* End of file users_model.php */
/* Location: ./application/models/users_model.php */