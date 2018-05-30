<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class photobook_model extends MY_Model
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


    public function insert($data)
    {

        $sql = "INSERT INTO photobook (name,client_id) " .
            "VALUES(". $this->db->escape($data['name'])."," . $this->db->escape($this->client_id) . ")";

        $this->db->query($sql);

        return TRUE;
    }
    public function all($client_id)
    {
        $query = $this->db->get_where('photobook', array(
            'client_id' => $client_id,
            'flag_delete' => '0'
        ));
        $result = $query->result_array();
        return $result;
    }
    public function addImages($data, $photobook_id)
    {

        $query = "SELECT * FROM photobook_image WHERE photobook_id = '" . $photobook_id . "'";
        $query = $this->db->query($query);

        $result = $query->result();
        foreach ($result as $item){
            $databaseImages[] = $item->image_id;
        }


        foreach ($data['images'] as $key => $image){
            if(in_array($image['image_id'],$databaseImages)){
                continue;
            }else{
                $sql = "INSERT INTO photobook_image (photobook_id,image_id) " .
                    "VALUES(". $this->db->escape($photobook_id)."," . $this->db->escape($image['image_id']) . ")";
                $this->db->query($sql);
            }

        }

        function in_array_r($needle, $haystack, $strict = false) {
            foreach ($haystack as $item) {
                if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && in_array_r($needle, $item, $strict))) {
                    return true;
                }
            }

            return false;
        }

        foreach ($databaseImages as $databaseImage) {
            if (in_array_r($databaseImage, $data['images'])) {
                continue;
            } else {
                $query = "DELETE FROM photobook_image WHERE image_id = " . $databaseImage . " AND photobook_id = " . $photobook_id;
                $this->db->query($query);
            }
        }
        return TRUE;
    }

    public function getImagesByPhotobook($photobook_id){
        $query = $this->db->get_where('photobook_image', array(
            'photobook_id' => $photobook_id,
        ));
        $resultImages = $query->result_array();

        foreach ($resultImages as $resultImage){

            $photobook_images[$resultImage['image_id']] = $resultImage['image_id'];
            
        }
        foreach ($photobook_images as $photobook_image){
            $imageQuery = $this->db->get_where('images', array(
                'image_id' => $photobook_image
            ));
            $images[] = $imageQuery->result_array();
        }
        foreach ($images as $image){
            $photobook[$image[0]['image_id']]['image_id'] = $image[0]['image_id'];
            $photobook[$image[0]['image_id']]['name'] = $image[0]['name'];
            $photobook[$image[0]['image_id']]['file'] = 'http://' . $_SERVER['HTTP_HOST'] . '/uploads/'. md5($image[0]['client_id']) . '/' . $image[0]['file'];
	        $photobook[$image[0]['image_id']]['large'] = str_replace($_SERVER['DOCUMENT_ROOT'], '', 'http://' . $_SERVER['SERVER_NAME'] . '/api/get_image/' . $image[0]['image_id'] . '/1200/900');
            $photobook[$image[0]['image_id']]['thumb'] = str_replace($_SERVER['DOCUMENT_ROOT'], '', 'http://' . $_SERVER['SERVER_NAME'] . '/api/get_image/' . $image[0]['image_id'] . '/120/90');
            $photobook[$image[0]['image_id']]['client_id'] = $image[0]['client_id'];
        }

        return $photobook;
    }

    public function getPhotobookImages($photobook_id){
        $query = $this->db->get_where('photobook_image', array(
            'photobook_id' => $photobook_id,
        ));
        $resultImages = $query->result_array();

        foreach ($resultImages as $resultImage){
            $query2 = $this->db->get_where('images', array(
                'image_id' => $resultImage['image_id'],
            ));

            $photobook_images[] = $query2->result_array();
        }

        
        return $photobook_images;
    }

    public function delete($data, $client_id)
    {

       $sql = "UPDATE photobook SET flag_delete = 1 WHERE id = '" . $data ."' AND client_id = '".$client_id ."'";

        $this->db->query($sql);

    }

}

/* End of file settings_model.php */
/* Location: ./application/models/settings_model.php */