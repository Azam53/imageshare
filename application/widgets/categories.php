<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Categories extends Widget
{
    function run($options = array()) {
		$this->load->model('categories_model');
		$this->load->model('main_categories_model');        
		$subcategories 		= $this->categories_model->get_all();
		$main_categories 	= $this->main_categories_model->get_all(); 
		$general_category	= array();		
		$existingIds		= array();
		
		if(empty($main_categories)) return FALSE;
		
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
		
		ob_start();
		$this->render('categories', array(
			'categories_array' => $main_categories,
			'general_category' => $general_category,
		));
		return ob_get_clean();
    }
}  