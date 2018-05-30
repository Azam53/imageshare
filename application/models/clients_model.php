<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Clients_model extends MY_Model
{
	public function __construct()
	{
		parent::__construct('clients', 'client_id');
	}
	
	public function name_exists($name)
	{
		$query = $this->db->get_where('clients', array(
			'name' => $name
		));
		return $query->num_rows() == 1;
	}
	
	public function url_exists($url)
	{
		$query = $this->db->get_where('clients', array(
			'url' => $url
		));
		return $query->num_rows() == 1;
	}
	
	public function get_all()
	{
		$this->db->order_by('name', 'asc');
		return parent::get_all();
	}
	
	public function get_client_url()
	{
		$stripped_url = str_replace('.' . $this->config->item('domain'), '', trim(str_replace(array('http://', 'https://'), '', base_url()), '/'));
		return ($stripped_url == $this->config->item('domain') || $stripped_url == 'www') ? FALSE : $stripped_url;
	}
	
	public function client_by_url()
	{
		$stripped_url = $this->get_client_url();

		if($stripped_url === FALSE)
		{
			redirect('/login');
		}

//        $stripped_url = 'viproses';

		$query = $this->db->get_where('clients', array(
			'url' => $stripped_url
		));
		$row = $query->row_array();

		if(empty($row))
		{
			//print_r($_SERVER);
			if(stripos($_SERVER['HTTP_HOST'], 'photo-gallery.nl') !== FALSE || stripos($_SERVER['HTTP_HOST'], 'photo-engine.nl') !== FALSE)
			{
				$sub = (substr_count($_SERVER['HTTP_HOST'], '.') == 2) ? reset(explode(".", $_SERVER['HTTP_HOST'])) . '.' : '';
				redirect('http://' . $sub . $this->config->item('domain') . '/' . ltrim($_SERVER['REQUEST_URI'], '/'));
			}
		
			show_error('Client not found');
		}
		
		if(logged_in() && $row['client_id'] != $this->session->userdata('client_id') && !is_superadmin())
		{
			redirect('http://' . $this->config->item('domain'));
		}

		$this->lang->load('main', $row['language']);
		$this->lang->switch_to($row['language']);
		
		$locale_key = 'en_US';
		if($row['language'] == 'dutch') $locale_key = 'nl_NL';

		setlocale(LC_TIME, $locale_key);
		
		return $row;
	}
	
	public function delete($client_id)
	{
		$this->db->delete('users', array(
			'client_id' => $client_id
		));
		$this->db->delete('keywords', array(
			'client_id' => $client_id
		));
		parent::delete($client_id);
	}
}

/* End of file clients_model.php */
/* Location: ./application/models/clients_model.php */