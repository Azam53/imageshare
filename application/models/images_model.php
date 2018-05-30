<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Images_model extends MY_Model
{
	public function __construct()
	{
		parent::__construct('images', 'image_id');
	}

	public function get_all($client_id, $order_by = 'name,asc')
	{
		$order_ex = explode(",", $order_by);
		$this->db->order_by($order_ex[0], $order_ex[1]);
		$query = $this->db->get_where('images', array(
			'client_id' => $client_id
		));
		return $query->result_array();
	}

    public function getAllExeceptPhotobook($client_id, $photobookId, $order_by = 'name,asc')
    {
        $order_ex = explode(",", $order_by);
        if(isset($_POST['keyword']) && !empty($_POST['keyword'])){
            $query = "select * from images as img join keywords on img.image_id = keywords.image_id where img.client_id =  ". $client_id ." and img.image_id not IN (select image_id FROM photobook_image where photobook_id = " . $photobookId . ") and  keywords.keyword = '" .$_POST['keyword']  ."'  order by name asc";
        }else{
            $query = "select * from images where client_id = ". $client_id ." and image_id not IN (select image_id FROM photobook_image where photobook_id = " . $photobookId . ") order by name asc";
        }

        $query = $this->db->query($query);
        //        $query = $this->db->get_where('images', array(
//            'client_id' => $client_id
//        ));
//        dump($query);
        return $query->result_array();
    }

	public function get_all_with_keywords($client_id, $order_by = 'name,asc')
	{
		$order_ex = explode(",", $order_by);
		$this->db->order_by($order_ex[0], $order_ex[1]);
        $this->db->join('keywords', 'keywords.image_id = images.image_id', 'inner');
		$query = $this->db->get_where('images', array(
			'images.client_id' => $client_id
		));
		return $query->result_array();
	}

    public function get_all_by_keyword($client_id, $keyword, $order_by = 'name,asc')
    {
        //hotfix, replace space in url with space to search the right keyword
        $keyword = str_replace('%20', ' ', $keyword);
        $order_ex = explode(",", $order_by);
        $this->db->order_by($order_ex[0], $order_ex[1]);
        $this->db->join('keywords', 'keywords.image_id = images.image_id', 'inner');
        $query = $this->db->get_where('images', array(
            'images.client_id' => $client_id,
            'keywords.keyword' => $keyword
        ));
        return $query->result_array();
    }
	
	public function get_all_offset($client_id, $offset, $limit)
	{
		$this->db->offset($offset * $limit);
		$this->db->limit($limit);
		return $this->get_all($client_id, 'name,asc');
	}
	
	public function get_all_by_date($client_id, $uploaded_at)
	{
		$this->db->where('uploaded_at', $uploaded_at);
		return $this->get_all($client_id, 'image_id,desc');
	}
	
	public function get_by_user($uploaded_by)
	{
		$this->db->where('uploaded_by', $uploaded_by);
		return $this->get_all($this->client_id);
	}
	
	public function get_latests_uploaded($limit = 1)
	{
		$this->db->limit($limit);
		$this->db->order_by('uploaded_at', 'desc');
		$this->db->group_by('images.image_id');
		$this->db->join('keywords', 'keywords.image_id = images.image_id', 'inner');
		$query = $this->db->get_where('images', array(
			'images.client_id' => $this->client_id
		));
		return $query->result_array();
	}
	
	public function get_selected_images($limit = 1){
		$this->db->limit($limit);
		$this->db->order_by('uploaded_at', 'desc');
		$this->db->group_by('images.image_id');
		$this->db->join('keywords', 'keywords.image_id = images.image_id', 'inner');
		$query = $this->db->get_where('images', array(
			'images.client_id' => $this->client_id,
			'images.show_on_homepage' => 1,
		));
		return $query->result_array();
	}
	
	public function get_all_with_empty_keywords($client_id)
	{
		$this->db->order_by('uploaded_by', 'desc');
		$this->db->where('`image_id` NOT IN (SELECT `image_id` FROM `keywords`)', NULL, FALSE);
		$query = $this->db->get_where('images', array(
			'client_id' => $client_id
		));
		return $query->result_array();
	}
	
	
	
	public function search($client_id, $keywords, $offset = FALSE, $limit = FALSE)
	{
		$where_in = array();
		$keywords_array = explode(",", str_replace("OR", ",", str_replace("AND", ",", $keywords)));
		foreach($keywords_array as $keyword)
		{
			$keyword = trim($keyword);

			$this->db->like('name', $keyword);
			$query = $this->db->get('images');
			foreach($query->result_array() as $item)
			{
				$where_in[] = $item['image_id'];
			}
			
			$number_keyword = str_ireplace(array("#", "nr: ", "nr:", "nr"), "", $keyword);
			if(is_numeric($number_keyword))
			{
				$this->db->where('image_id', $number_keyword);
				$query = $this->db->get_where('images');
				foreach($query->result_array() as $item)
				{
					$where_in[] = $item['image_id'];
				}
			}

			$get_keywords_like = $this->get_keywords_like($client_id, $keyword);		
			
			if($offset) $this->db->offset($offset);
			if($limit) $this->db->limit($limit);
			
			foreach($get_keywords_like as $item)
			{
				$where_in[] = $item['image_id'];
			}
		}

		$ids = $where_in;
		
		if(strpos($keywords, ' AND ') !== FALSE)
		{
			$ids = array();
			$counts = array_count_values($where_in);
			foreach($counts as $value => $count)
			{
				if($count >= count($keywords_array))
				{
					$ids[] = $value;
				}
			}
		}
		
		if(empty($ids))
		{
			return array(); //$this->db->where('image_id', '0'); // hax to find nothing
		}
		else
		{
			$this->db->where_in('image_id', $ids);
		}
		
		return $this->get_all($client_id);
	}
	
	public function count_search($client_id, $keyword)
	{
		return count($this->search($client_id, $keyword));
	}
	
	public function get_keywords_like($client_id, $keyword)
	{
		if(empty($keyword))
			return array();
	
		//$this->db->group_by('keyword');
		$this->db->like('keyword', $keyword);
		$query = $this->db->get_where('keywords', array(
			'keywords.client_id' => $client_id
		));
		return $query->result_array();
	}
	
	public function client_image_count($client_id)
	{
		$query = $this->db->get_where('images', array(
			'client_id' => $client_id
		));
		return $query->num_rows();
	}
	
	public function add_download($client_id, $image_id)
	{
		$this->db->insert('downloads', array(
			'client_id' => $client_id,
			'image_id' => $image_id,
			'user_id' => $this->session->userdata('user_id'),
			'downloaded_at' => time()
		));
		return $this->db->insert_id();
	}
	
	public function get_keywords($image_id)
	{
		$this->db->order_by('keyword_id', 'asc');
		$query = $this->db->get_where('keywords', array(
			'image_id' => $image_id
		));
		
		$return = array();
		foreach($query->result_array() as $item)
		{
			$return[] = $item['keyword'];
		}
		
		return $return;
	}

	public function getImageById($imageId){

        $query = $this->db->get_where('images', array(
            'image_id' => $imageId
        ));

        $return = array();
        foreach($query->result() as $item)
        {
            $return[] = str_replace($_SERVER['DOCUMENT_ROOT'], '', 'http://' . $_SERVER['SERVER_NAME'] . uploads_dir($this->client_id) . $item->file);
        }

	    return $return;

    }

	public function get_all_keywords_by_user($client_id)
	{
		// Haalt alle keywords op waar een foto onder valt.
		// moet het volgende worden
		// SELECT `keywords`.* FROM `keywords`,`images` WHERE `keywords`.`client_id` = '10' AND `images`.image_id = `keywords`.`image_id` 
		$this->db->select('keywords.*');		
		$this->db->join('images','images.image_id = keywords.image_id');
		$this->db->order_by('keyword_id','asc');
		#$this->db->join('images','images.image_id','keywords.image_id');
		$query = $this->db->get_where('keywords',array(
			'keywords.client_id'	=> $client_id
		));

		$return = array();
		foreach($query->result_array() as $item)
		{
			if(!in_array($item['keyword'],$return))
				$return[] = $item['keyword'];
		}
		
		return $return;
	
	}

    public function get_all_keywords_by_image($image_id)
    {
        // Haalt alle keywords op waar een foto onder valt.
        // moet het volgende worden
        // SELECT `keywords`.* FROM `keywords`,`images` WHERE `keywords`.`client_id` = '10' AND `images`.image_id = `keywords`.`image_id`
        $this->db->select('keywords.*');
        $this->db->join('images','images.image_id = keywords.image_id');
        $this->db->order_by('keyword_id','asc');
        #$this->db->join('images','images.image_id','keywords.image_id');
        $query = $this->db->get_where('keywords',array(
            'keywords.image_id'	=> $image_id
        ));

        $return = array();
        foreach($query->result_array() as $item)
        {
            if(!in_array($item['keyword'],$return))
                $return[] = $item['keyword'];
        }

        return $return;

    }
	
	public function update_keywords($image_id, $keywords)
	{
		/** update keyword */
		$this->db->delete('keywords', array(
			'image_id' => $image_id
		));
		
		$keywords_exploded = array_unique(explode(",", trim(trim($keywords), ',')));
		if(!empty($keywords_exploded[0]))
		{
			foreach($keywords_exploded as $keyword)
			{
				$this->db->insert('keywords', array(
					'client_id' => $this->client_id, 
					'image_id' => $image_id,
					'keyword' => trim($keyword)
				));
			}
		}
	}
	
	public function delete($image_id)
	{
		/** remove file */
		$image_data = $this->get($image_id);
		
		$path = uploads_dir($image_data['client_id']) . $image_data['file'];
		if(file_exists($path)) @unlink($path) or die('Could not remove file.');
		
		parent::delete($image_id);
	}
}

/* End of file images_model.php */
/* Location: ./application/models/images_model.php */