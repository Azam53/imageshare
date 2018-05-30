<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MY_Loader extends CI_Loader
{
	public function view($view, $data = array(), $return = FALSE, $layout = TRUE)
	{
		$CI = &get_instance();
		
		/** setup some global vars */
		$data['logged_in'] = $CI->session->userdata('logged_in');
		$data['is_superadmin'] = is_superadmin();
	
		$data['clients'] = $CI->clients_model->get_all();
		$data['client_url'] = $CI->clients_model->get_client_url();
		
		$data['is_client_page'] = (empty($data['client_url'])) ? FALSE : TRUE;
		
		$client_id = @$CI->client_id;
		if(!empty($client_id))
		{
			$data['client'] = $CI->clients_model->get($client_id);
		}
		
		if($data['is_client_page'])
		{
			if($data['logged_in'])
			{
			}
			else
			{
				$data['allow_registration'] = $CI->settings_model->get('allow_registration');
			}
		}
		
		if(!is_writable(FCPATH . 'uploads'))
		{
			$data['errors'][] = '"uploads" folder is not writable.';
		}
	
		/** handle view */
		$view = str_replace('.phtml', '', $view) . '.phtml';
		
		$data['_main_content'] = parent::view($view, $data, TRUE);
		if($layout)
		{
			return parent::view('layout.phtml', $data, $return);
		}
		else
		{
			if($return)
			{
				return $data['_main_content'];
			}

			echo $data['_main_content'];
		}
	}
}

/* End of file MY_Loader.php */
/* Location: ./application/core/MY_Loader.php */