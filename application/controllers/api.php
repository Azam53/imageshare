<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Api extends CI_Controller
{
	public function __construct()
	{
		parent::__construct();
        $this->client = $this->clients_model->client_by_url();
        $this->client_id = $this->client['client_id'];
		$this->load->model(array(
			'clients_model',
			'settings_model',
			'images_model',
            'categories_model',
            'photobook_model',
            'keywords_model'
		));
		$this->load->library('session');
        $this->load->library('../controllers/images.php');
	}

//	@todo: expand with api key to extend security
//	@todo: expand with request ip from requesting server, allow certain frontend servers to connect
	private function validateRequest()
    {

        $accepted_origins = array("http://localhost", "http://192.168.10.10");

        $clientSites = explode(',', $this->client['allow_request_from']);

        foreach ($clientSites as $clientSite)
        {
            array_push($accepted_origins, $clientSite);
        }

        if (isset($_SERVER['HTTP_ORIGIN'])) {
            // same-origin requests won't set an origin. If the origin is set, it must be valid.
            if (in_array($_SERVER['HTTP_ORIGIN'], $accepted_origins)) {
                header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
            } else {
                header("HTTP/1.0 403 Origin Denied");
                exit;
            }
        }
        else
        {
            header("HTTP/1.0 403 Origin Denied");
            exit;
        }

        return true;

    }

	public function uploadImage()
    {

        $this->validateRequest();
        /*******************************************************
         * Only these origins will be allowed to upload images *
         ******************************************************/


        /*********************************************
         * Change this line to set the upload folder *
         *********************************************/

        $imageFolder = uploads_dir($this->client_id);
        if (false === file_exists($imageFolder))
        {

            mkdir($imageFolder, 777, true);
        }

        reset ($_FILES);
        $temp = current($_FILES);
        if (is_uploaded_file($temp['tmp_name'])){
            if (isset($_SERVER['HTTP_ORIGIN'])) {
                // same-origin requests won't set an origin. If the origin is set, it must be valid.
                if (in_array($_SERVER['HTTP_ORIGIN'], $accepted_origins)) {
                    header('Access-Control-Allow-Origin: ' . $_SERVER['HTTP_ORIGIN']);
                } else {
                    header("HTTP/1.0 403 Origin Denied");
                    return;
                }
            }

            /*
              If your script needs to receive cookies, set images_upload_credentials : true in
              the configuration and enable the following two headers.
            */
            // header('Access-Control-Allow-Credentials: true');
            // header('P3P: CP="There is no P3P policy."');

            // Sanitize input
            if (preg_match("/([^\w\s\d\-_~,;:\[\]\(\).])|([\.]{2,})/", $temp['name'])) {
                header("HTTP/1.0 500 Invalid file name.");
                return;
            }

            // Verify extension
            if (!in_array(strtolower(pathinfo($temp['name'], PATHINFO_EXTENSION)), array("gif", "jpg", "png"))) {
                header("HTTP/1.0 500 Invalid extension.");
                return;
            }

            // Accept upload if there was no origin, or if it is an accepted origin
            $filetowrite = $imageFolder . $temp['name'];
            move_uploaded_file($temp['tmp_name'], $filetowrite);

            // Respond to the successful upload with JSON.
            // Use a location key to specify the path to the saved image resource.
            // { location : '/your/uploaded/image/file'}

            $filetowrite = str_replace($imageFolder, '', $filetowrite);

            $image_id = $this->images_model->add(array(
                'client_id' => $this->client_id,
                'uploaded_by' => 1,
                'name' => $filetowrite,
                'file' => $filetowrite,
                'uploaded_at' => time(),
                'notes' => 'from website',
                'orgi_image_id' => 0
            ));

            $this->images_model->update_keywords($image_id, 'van_website');

            echo json_encode(array('location' => $filetowrite));
        } else {
            // Notify editor that the upload failed
            header("HTTP/1.0 500 Server Error");
        }

        exit;
    }

	public function get_client()
	{
        exit;
		
		if(empty($this->session->userdata))
		{
			$this->_output_api(array(
				'error_code'	=> 'UNKNOWN_USER',
				'message' => 'User is not logged in'
			),TRUE);
		}else{
			$this->_output_api(array(				
				'message' => 'User is logged in'
			),TRUE);
			$this->client = $this->session->userdata;
		}
	}
	
	private function _output_api($array, $error = FALSE)
	{
		header("Access-Control-Allow-Origin: *");
		if ($_SERVER['REQUEST_METHOD'] === 'GET') {
			header('Content-type: application/json; charset=utf-8');
		}elseif ($_SERVER['REQUEST_METHOD'] === 'POST' ) {
			header("Content-Type: text/javascript; charset=utf-8");
		}
		if($error)
		{
			header('Status: 400 Bad Request', TRUE, 400);
			$array['error'] = 1;
		}
		echo json_encode($array);
		exit;
	}

	public function load_image($imageId)
    {
        // @todo: check request origin to decide if image displaying is allowed
        $image = $this->images_model->get($imageId);

        echo '<img title="' . $image['name'] . '" alt="' . $image['name'] . '" src="http://viproses.imageshare.app/api/get_image/' . $imageId .'">';
        exit;
    }
	
	public function get_image($imageId, $width = 'full', $height = 'full' )
    {
        // @todo: check request origin to decide if image displaying is allowed


        $image = $this->images_model->get($imageId);

        $src = resize($this->client_id, $image['file'], $width, $height);

        $name = $_SERVER['DOCUMENT_ROOT'] . $src;

        $fp = fopen($name, 'rb');

        // send the right headers
        header("Content-Type: image/jpg");
        header("Content-Length: " . filesize($name));

        // dump the picture and stop the script
        fpassthru($fp);
        exit;
       
    }

    public function get_tag_list()
    {
        $this->validateRequest();
        $keywords = $this->keywords_model->get_all($this->client_id);
        $tags = [];
        foreach ($keywords as $keyword) {
            $tags[$keyword['keyword']] = $keyword['keyword'];
        }

        echo json_encode($tags);
        exit;
    }

    public function get_photobook_list()
    {
        $photobooks = $this->photobook_model->all($this->client_id);
        $books = [];
        foreach ($photobooks as $photobook) {
            $books[$photobook['id']] = $photobook['name'];
        }
        echo json_encode($books);
        exit;
    }

    public function get_photobook_images($photobook_id)
    {
        $images = $this->photobook_model->getImagesByPhotobook($photobook_id);
        echo json_encode($images);
        exit;
    }

    public function get_tag_images()
    {

        $this->validateRequest();
        $keyword = $_POST['keyword'];
//        $keyword = 'sugar';

        $images = $this->images_model->get_all_by_keyword($this->client_id, $keyword);
        $tags = [];
        foreach ($images as $image) {
            $tags[$image['name']] = str_replace($_SERVER['DOCUMENT_ROOT'], '', uploads_dir($this->client_id) . $image['file']);
        }

        echo json_encode($tags);
        exit;
    }

    public function getImagesByKeyword($keyword)
    {
        $images = $this->images_model->get_all_by_keyword($this->client_id, $keyword);

        $tags = [];
        foreach ($images as $image) {
                $tags[$image['image_id']]['id'] = $image['image_id'];
                $tags[$image['image_id']]['name'] = $image['name'];
                $tags[$image['image_id']]['imageUrl'] = str_replace($_SERVER['DOCUMENT_ROOT'], '', 'https://' . $_SERVER['SERVER_NAME'] . uploads_dir($this->client_id) . $image['file']);
                $tags[$image['image_id']]['thumb'] = str_replace($_SERVER['DOCUMENT_ROOT'], '', 'https://' . $_SERVER['SERVER_NAME'] . '/api/get_image/' . $image['image_id'] . '/120/90');
        }
        echo json_encode($tags);
        exit;
    }

	public function getImage($imageId){

           $path['url'] = $this->images_model->getImageById($imageId);

           $uploadDir = strstr($path['url'][0], '/uploads');
           $image['url'][0] = 'http://viproses.image-share.nl' . $uploadDir;
       echo json_encode($image);
   }
    public function get_image_list()
    {
        $this->validateRequest();

        // @todo add relevant webserver ip's
        if ($_SERVER['REMOTE_ADDR'] != '127.0.0.1')
        {
//            exit('Access denied');
        }

        // @todo: check request origin to decide if image displaying is allowed
        $images = $this->images_model->get_all_with_keywords($this->client_id);

        $keywords = $this->categories_model->get_all($this->client_id);

        $imageKeywords = $this->keywords_model->get_all($this->client_id);

        $keywordsArray = [];
        foreach ($imageKeywords as $imageKeyword) {
            if (!isset($keywordsArray[$imageKeyword['image_id']]))
            {
                $keywordsArray[$imageKeyword['image_id']] = [];
            }
            $keywordsArray[$imageKeyword['image_id']][] = $imageKeyword['keyword'];
        }

        $returnMessage = '
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
        <style>
            li {
                
            }
            </style>
            
        ';

        $returnMessage .= '<ul style="float: left; clear: both">';

        foreach ($keywords as $keyword) {
            $returnMessage .= '<li style="float: left;
                list-style: none;
                text-align: center;
                padding: 10px;
                margin: 5px;
                border-radius: 5px;
                border: 1px solid black;
                overflow: hidden;"><a class="filter" data-key_id="' . $keyword['name'] . '">' . $keyword['name'] . '</a></li>';
        }

        $returnMessage .= '</ul>';

        $returnMessage .= '<ul style="float: left; clear: both">';
        foreach ($images as $image) {

            $imageKeys = '';
            foreach ($keywordsArray[$image['image_id']] as $item) {
                $imageKeys .= $item . ',';
            }

            $returnMessage .= '
                <li style="
                float: left;
                list-style: none;
                text-align: center;
                padding: 10px;
                margin: 1%;
                border-radius: 5px;
                border: 1px solid black;
                overflow: hidden;
                " data-keyword_id="' . $imageKeys . '" class="image">
                    <img onclick="selectImage(' . $image['image_id'] . ');" title="' . $image['name'] . '" alt="' . $image['name'] . '" src="http://' . $_SERVER['HTTP_HOST'] . '/api/get_image/' . $image['image_id'] .'/160/120">
                    <br />
                    '.$image['name'].
                    '<br />
                    '.$imageKeys.'
                </li>
            ';
        }

        $returnMessage .= '</ul>';

        $returnMessage .= "
        <script type=\"text/javascript\">
	$( document ).ready(function() {

		$(\".filter\").on('click', function () {
		    var filter_id = ($(this).data('key_id'));
			
			$('.image').each(function(i, obj) {
               var keywords = $(obj).data('keyword_id').split(',');
                                     
                   var filterName = filter_id.toLowerCase();
                        
                   if (jQuery.inArray( filterName, keywords) != -1)
                   {
                        $(this).show();
                   }
                   else 
                   {
                        $(this).hide();
                   }
               
            });
			
		})

	});
</script>";

        echo $returnMessage;
        exit();

    }
}