<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Settings_model extends MY_Model
{
	public function __construct()
	{
		parent::__construct('clients', 'client_id');
	}

	public function get($setting, $client_id = FALSE)
	{
		if($client_id === FALSE)
			$client_id = $this->client_id;

		if(!empty($client_id))
		{
			$client = $this->clients_model->get($client_id);

			if(!isset($client[$setting]))
			{
				var_dump(debug_backtrace());
				show_error('Setting is not defined.');
			}

			return $client[$setting];
		}

		return FALSE;
	}
	
	
	public function update($data)
	{
		$this->db->update('clients', $data, array(
			'client_id' => $this->client_id
		));
		return TRUE;
	}
}

/* End of file settings_model.php */
/* Location: ./application/models/settings_model.php */