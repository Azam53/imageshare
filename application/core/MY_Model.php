<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MY_Model extends CI_Model
{
	public function __construct($table = FALSE, $primary_key = FALSE)
	{
		$this->table = $table;
		$this->primary_key = $primary_key;
	}

	public function get($id)
	{
		$query = $this->db->get_where($this->table, array(
			$this->primary_key => $id
		));
		return $query->row_array();
	}
	
	public function get_all()
	{
		$query = $this->db->get($this->table);
		return $query->result_array();
	}
	
	public function add($data)
	{
		$this->db->insert($this->table, $data);
		return $this->db->insert_id();
	}
	
	public function update($id, $data)
	{
		$this->db->update($this->table, $data, array(
			$this->primary_key => $id
		));
		return TRUE;
	}
	
	public function delete($id)
	{
		$this->db->delete($this->table, array(
			$this->primary_key => $id
		));
		return TRUE;
	}
}

/* End of file MY_Model.php */
/* Location: ./application/core/MY_Model.php */