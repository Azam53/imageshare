<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Keywords_model extends MY_Model
{
	public function __construct()
	{
		parent::__construct('keywords', 'keyword_id');
	}

	public function get_all($client_id, $order_by = 'keyword,asc')
	{
		$order_ex = explode(",", $order_by);
		$this->db->order_by($order_ex[0], $order_ex[1]);
		$query = $this->db->get_where('keywords', array(
			'client_id' => $client_id
		));
		return $query->result_array();
	}

}

/* End of file images_model.php */
/* Location: ./application/models/images_model.php */