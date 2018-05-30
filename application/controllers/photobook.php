<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Photobook extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();

        if(!is_admin())
        {
            redirect('/login');
        }

        $this->client = $this->clients_model->client_by_url();
        $this->client_id = $this->client['client_id'];

        $this->load->model(array(
            'users_model',
            'photobook_model',
            'Images_model'

        ));
    }

    public function index()
    {
        $this->load->database();


        $data = $this->clients_model->get($this->client_id);
        $errors = true;
        $data['photobooks'] = $this->photobook_model->all($data['client_id']);
        if($this->input->post('name') !== FALSE){

            $new_data = [
                'name' => $this->input->post('name'),
            ];
            foreach ($data['photobooks'] as $photobook){
                if($photobook['name'] == $this->input->post('name')){
                    error_message('Fotoboek met deze naam bestaat/bestond al.');
                    $duplicated = true;
                }
            }
            if($duplicated != true){
                $this->photobook_model->insert($new_data);
            }

            redirect('/photobook');
        }



        $this->load->view('photobook/index', $data);
    }

    public function saveImage(){
        $new_data = [];
        $data['photobook_id'] = $_POST['photobook'];
        if(!empty($_POST['images'])){
            foreach ($_POST['images'] as $image){
                $new_data['images'][] = ['image_id' => $image];
            }

            $this->photobook_model->addImages($new_data, $data['photobook_id']);

        }
        redirect("/photobook/show/". $data['photobook_id']);

    }

    public function show($id)
    {
        $data['images'] = $this->images_model->getAllExeceptPhotobook($this->client_id,$id);
        $data['photobook'] = $this->photobook_model->getPhotobookImages($id);
        $data['photobook_id'] = $id;
        $data['client_id'] = $this->client_id;

        $data['pagination'] = ceil(count($data['images']) / 48);
        if(isset($_GET['page'])){
            $data['currentPage'] = $_GET['page'];
        }else{
            $data['currentPage'] = '1';
        }


        $this->load->view('photobook/show', $data);
    }

    public function delete($image_ids, $bulk = FALSE)
    {
        $image_ids_array = explode(",", $image_ids);
        if(!empty($image_ids_array))
        {
            foreach($image_ids_array as $image_id)
            {
                $image = $this->images_model->get($image_id);
                if(!is_admin() && $this->session->userdata('user_id') != $image['uploaded_by'])
                {
                    show_error('No right to delete this image.');
                }

                $this->photobook_model->delete($image_id,$this->client_id);
            }
        }

        succes_message('Succesvol verwijderd.');
        redirect('/photobook');
    }
}

/* End of file photobook.php */
/* Location: ./application/controllers/photobook.php */