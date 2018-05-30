<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Categories_model extends MY_Model
{	
	public function __construct()
	{
		parent::__construct('categories', 'category_id');
	}
	
	public function get_all()
	{
		$this->db->order_by('order asc, name asc');
		$this->db->where('client_id', $this->client_id);
		return parent::get_all();
	}
}
