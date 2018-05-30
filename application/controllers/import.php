<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class Import extends CI_Controller
{
	public function __construct()
	{
		exit;
		
		parent::__construct();
		
		$this->client_id = 20;
		$this->uploaded_by = 123;
		
		$this->load->model('users_model');
		
		mysql_connect("hotspotnl.nl", "photoluc_photo", "aa84yDAz") or die(mysql_error());
		mysql_select_db("photoluc_photo") or die(mysql_error());
	}

	public function reset_images($client_id = FALSE)
	{
		if(!is_superadmin()) exit;
	
		if($client_id === FALSE)
			$client_id = $this->client_id;
	
		$this->db->delete('images', array(
			'client_id' => $client_id
		));
		$this->db->delete('keywords', array(
			'client_id' => $client_id
		));
		
		foreach(glob(uploads_dir($client_id) . '/*') as $file) {		
			//unlink($file);
		}
	}

	public function import_images($offset = 0)
	{
		$this->db->delete('images', array(
			'client_id' => $this->client_id
		));
		$this->db->delete('keywords', array(
			'client_id' => $this->client_id
		));
	
		ini_set('memory_limit','500M');
		
		$limit = 999999;
		
		$i = $offset * $limit;
		$query = mysql_query("SELECT * FROM photo LIMIT ".$limit." OFFSET " . ($offset * $limit)) or die(mysql_error());
		while($row = mysql_fetch_assoc($query))
		{
			set_time_limit(0);
			echo ($i++ + 1) . ': ';
			
		/* 	$user_query = mysql_query("SELECT * FROM gebruikers WHERE orgi_user_id = ''") or die(mysql_error());
			$user_row = mysql_fetch_assoc($user_query); */

			//file_put_contents(uploads_dir($this->client_id) . $row['url_image'], file_get_contents('http://photogallery.lucatti.nl/downloadphoto.php?id=' . $row['id']));

			$path = uploads_dir($this->client_id) . htmlspecialchars_decode($row['url_image']);
			
			echo $path . ' ('.file_exists($path).') - ';
			
			if(file_exists($path))
			{
				resize($this->client_id, $row['url_image'], 160, 120);
				//resize($this->client_id, $row['url_image'], 80, 60);
				
				$image_id = $this->images_model->add(array(
					'client_id' => $this->client_id,
					'name' => $row['naam'],
					'uploaded_by' => $this->uploaded_by,
					'file' => $row['url_image'],
					'uploaded_at' => $row['geupload'],
					'notes' => $row['opmerkingen'],
					'orgi_image_id' => $row['id'],
				));
				
				$this->images_model->update_keywords($image_id, trim($row['trefwoorden'], ','));

				echo 'bestand toegevoegd - ';
			}
			
			echo $row['naam'] . '<br />';
			flush();
		}
	}
	
	public function import_users()
	{
		$query = mysql_query("SELECT * FROM gebruikers") or die(mysql_error());
		while($row = mysql_fetch_assoc($query))
		{
			$this->users_model->add(array(
				'username' => $row['naam'],
				'password' => $row['wachtwoord'],
				'encryption_type' => 'sha256',
				'first_name' => $row['voornaam'], 
				'middle_name' => $row['tussenvoegsel'], 
				'last_name' => $row['achternaam'], 
				'company_name' => $row['bedrijfsnaam'], 
				'email' => $row['email'],
				'address' => $row['adres'],
				'postal_code' => $row['postcode'], 
				'place' => $row['plaats'], 
				'phone_number' => $row['telefoonnummer'], 
				'admin' => $row['admin'],
				'client_id' => $this->client_id,
				'active' => $row['active'],
			//	'expire_date' => $row['expired'],
				'orgi_user_id' => $row['id']
			));
		}
	}
}

/* End of file import.php */
/* Location: ./application/controllers/import.php */