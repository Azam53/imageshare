<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Categories extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
		
		if(!is_admin())
		{
			redirect('/login');
		}
		
		$this->client 		= $this->clients_model->client_by_url();		
		$this->client_id 	= $this->client['client_id'];
		
		$this->load->model('categories_model','categories_model');
		$this->load->model('main_categories_model','main_categories_model');
	}
	
	public function updatecategories()
	{		
		$allClients 		= $this->clients_model->get_all();
		foreach($allClients as $client)
		{							
			$this->client 		= $client;
			$this->client_id 	= $client['client_id'];
			$categories 		= $this->categories_model->get_all();
			$main_categories  	= $this->main_categories_model->get_all(); 
			$array_categories 	= array();
			$temporary_array	= array();					
			
			foreach($categories as $category)
			{				
				$array_categories[] = $category['subcategory'];					
			}
				
			foreach($array_categories as $tmp)
			{										
				if(trim($tmp)=='')
					continue;
			
				$main_categories  	= $this->main_categories_model->get_all(); 
				$doesExists 		= $this->main_categories_model->check_if_exists($tmp,$main_categories,'name');
				if($doesExists === false){					
					$db_data = array(
						'client_id' 	=> $this->client_id,
						'name' 			=> $tmp						
					);							

					$main_category_id = $this->main_categories_model->add($db_data); // regel 87 kan je deze id toevoegen
					$temporary_array[] = array(
						'main_category_id' 	=> $main_category_id,
						'name'				=> $tmp
					);
				}
			}
			
			if(is_array($temporary_array) && !empty($temporary_array))
			{
				foreach($temporary_array as $array)
				{
					$updateArray['main_category_id'] = $array['main_category_id'];
					
					$this->db->update('categories', $updateArray, array(
						'client_id' 		=> $this->client_id,
						'subcategory' 		=> $array['name']
					));
				}
			}
		}
	}
	
	public function index()
	{				
		$main_categories	= $this->main_categories_model->get_all(); 
		$subcategories 		= $this->categories_model->get_all();		
		$general_category	= array();		
		$existingIds		= array();
		
		foreach($main_categories as &$main_category){
			foreach($subcategories as $subcategory){
				if($main_category['main_category_id'] == $subcategory['main_category_id']){
					$main_category['subcategory'][] = $subcategory;
				}elseif($subcategory['main_category_id'] == 0){					
					if(!in_array($subcategory['category_id'],$existingIds)){
						$general_category['subcategory'][] = $subcategory;
					}
					array_push($existingIds,$subcategory['category_id']);
				}
			}
		}
		
		
		#print_r($general_category);
		#exit;
	
		$this->load->view('categories/index', array(
			'page_title' 		=> 'Categorieën',
			'categories_array' 	=> $main_categories,
			'general_category'	=> $general_category
		));
	}
	
	public function form($category_id = FALSE)
	{
		$action = ($category_id === FALSE) ? 'add' : 'edit';
		
		$data = array(
			'page_title' => lang('categories.form.title') . ' ' . (($action == 'edit') ? 'wijzigen' : 'toevoegen'),
			'action' => $action
		);
		
		if($action == 'edit')
		{
			$data['category'] = $this->categories_model->get($category_id);
		}			
		
		$categories 				= $this->categories_model->get_all();
		$data['main_categories']  	= $this->main_categories_model->get_all(); 

			
		if($this->input->post('name') !== FALSE)
		{
			$this->form_validation->set_rules('name', 'Categorie', 'trim|required|max_length[255]|min_length[2]|xss_clean');
			$this->form_validation->set_rules('subcategory', 'Subcategorie', 'trim|xss_clean');
			$this->form_validation->set_rules('keywords', 'Trefwoord', 'trim|required|xss_clean');
			
			if ($this->form_validation->run() !== FALSE)
			{
				$keywords =  $this->input->post('keywords');			
				
				$main_category_id = $this->input->post('main_category_id');
				
				$db_data = array(
					'client_id' 			=> $this->client_id,
					'name' 					=> $this->input->post('name'), 					
					'main_category_id'		=> $main_category_id,
					'order' 				=> $this->input->post('order'),
					'keywords' 				=> $keywords,
					'query_type' 			=> $this->input->post('query_type')
				);
				if($action == 'edit')
				{
					$this->categories_model->update($category_id, $db_data);
				}
				else
				{
					$this->categories_model->add($db_data);
				}
				redirect('/categories');
			}
		}
		$this->load->view('categories/form', $data);
	}
	
	public function form_cat($main_category_id = FALSE)
	{
		$action = ($main_category_id === FALSE) ? 'add' : 'edit';
		
		$data = array(
			'page_title' => lang('categories.form.title') . ' ' . (($action == 'edit') ? 'wijzigen' : 'toevoegen'),
			'action' => $action
		);
		
		if($action == 'edit')
		{
			$data['main_category'] = $this->main_categories_model->get($main_category_id);
		}
		
		if($this->input->post('main_category') !== FALSE)
		{
			$this->form_validation->set_rules('main_category', 'Hoofdcategorie', 'trim|required|max_length[255]|min_length[2]|xss_clean');
			if ($this->form_validation->run() !== FALSE)
			{				
				$db_data = array(
					'client_id'	=> $this->client_id,
					'name'		=> $this->input->post('main_category'),
					'order'		=> $this->input->post('order'),
				);
								
				if($action == 'edit')
				{	
					$this->main_categories_model->update($main_category_id, $db_data);
				}
				else
				{					
					$this->main_categories_model->add($db_data);
				}
				redirect('/categories');
			}
		}
		$this->load->view('categories/form_cat', $data);
	}
	

	public function delete($category_id)
	{
		$this->categories_model->delete($category_id);
		redirect('/categories');
	}
	
	public function delete_main($main_category_id)
	{
		$this->main_categories_model->delete($main_category_id);
		redirect('categories');
	}
	
}

/* End of file users.php */
/* Location: ./application/controllers/users.php */