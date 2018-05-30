<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/**
 * Handle file uploads via XMLHttpRequest
 */
class qqUploadedFileXhr {
    /**
     * Save the file to the specified path
     * @return boolean TRUE on success
     */
    function save($path) {    
        $input = fopen("php://input", "r");
        $temp = tmpfile();
        $realSize = stream_copy_to_stream($input, $temp);
        fclose($input);
        
        if ($realSize != $this->getSize()){            
            return false;
        }




        $target = fopen($path, "w");        
        fseek($temp, 0, SEEK_SET);
        stream_copy_to_stream($temp, $target);
        fclose($target);

        $exif = @exif_read_data($path);

        if (isset($exif['Orientation']))
        {

            // Create the canvas
            $source = imagecreatefromjpeg($path) ;

            switch ($exif['Orientation'])
            {
                case 3:
                    // Need to rotate 180 deg
                    // Rotates the image
                    $rotate = imagerotate($source, 180, 0) ;

                    // Outputs a jpg image, you could change this to gif or png if needed
                    imagejpeg($rotate, $path);

                    // Need to rotate 90 deg clockwise
                    break;

                case 6:
                    // Rotates the image
                    $rotate = imagerotate($source, 270, 0) ;

                    // Outputs a jpg image, you could change this to gif or png if needed
                    imagejpeg($rotate, $path);

                    // Need to rotate 90 deg clockwise
                    break;

                case 8:
                    // Need to rotate 90 deg counter clockwise
                    // Rotates the image
                    $rotate = imagerotate($source, 90, 0) ;

                    // Outputs a jpg image, you could change this to gif or png if needed
                    imagejpeg($rotate, $path);

                    // Need to rotate 90 deg clockwise
                    break;

            }
        }





//        exit($path);

        return true;
    }
    function getName() {
        return $_GET['qqfile'];
    }

//    function getSize() {
//        if (isset($_SERVER["CONTENT_LENGTH"])){
//            return (int)$_SERVER["CONTENT_LENGTH"];
//        } else {
//            throw new Exception('Getting content length is not supported.');
//        }
//    }

    function getSize()
    {
        if (isset($_SERVER["CONTENT_LENGTH"]) || isset($_SERVER['HTTP_CONTENT_LENGTH'])){
            if(isset($_SERVER['HTTP_CONTENT_LENGTH']))
                return (int)$_SERVER["HTTP_CONTENT_LENGTH"];
            else
                return (int)$_SERVER["CONTENT_LENGTH"];
        } else {

            throw new Exception('Getting content length is not supported.');
        }
    }
}

/**
 * Handle file uploads via regular form post (uses the $_FILES array)
 */
class qqUploadedFileForm {  
    /**
     * Save the file to the specified path
     * @return boolean TRUE on success
     */
    function save($path) {
        if(!move_uploaded_file($_FILES['qqfile']['tmp_name'], $path)){
            return false;
        }
        return true;
    }
    function getName() {
        return $_FILES['qqfile']['name'];
    }
    function getSize() {
        return $_FILES['qqfile']['size'];
    }
}

class File_uploader {
    private $allowedExtensions 				= array('jpg', 'jpeg', 'png', 'gif', 'eps', 'ai', 'pdf', 'nef', 'psd', 'tif', 'tiff'); //
	private $allowedExtensionsOptional  	= array('doc','docx','ppt','pptx','xls','xlsx','txt');
    private $sizeLimit = 0;
    private $file;

    function init(array $allowedExtensions = array(), $sizeLimit = 10485760){ 
        $allowedExtensions 	= array_map("strtolower", $allowedExtensions);
        $sizeLimit 			= 36 * 1024 * 1024;    
			
        $this->allowedExtensions = $allowedExtensions;        
        $this->sizeLimit = $sizeLimit;
        
        $this->checkServerSettings();       

        if (isset($_GET['qqfile'])) {
            $this->file = new qqUploadedFileXhr();
        } elseif (isset($_FILES['qqfile'])) {
            $this->file = new qqUploadedFileForm();
        } else {
            $this->file = false; 
        }
    }
    
	public function getAllowedExtensions($extensionOptional=false) {
		$extensions = $this->allowedExtensions;
		if($extensionOptional)
			$extensions = array_merge($extensions,$this->allowedExtensionsOptional);
			
		return $extensions;
	}
	
    private function checkServerSettings(){        
        $postSize = $this->toBytes(ini_get('post_max_size'));
        $uploadSize = $this->toBytes(ini_get('upload_max_filesize'));        
        
        if ($postSize < $this->sizeLimit || $uploadSize < $this->sizeLimit){
            $size = max(1, $this->sizeLimit / 1024 / 1024) . 'M';             
            die("{'error':'increase post_max_size and upload_max_filesize to $size'}");    
        }        
    }
    
    private function toBytes($str){
        $val = trim($str);
        $last = strtolower($str[strlen($str)-1]);
        switch($last) {
            case 'g': $val *= 1024;
            case 'm': $val *= 1024;
            case 'k': $val *= 1024;        
        }
        return $val;
    }
	
	private $mimeTypes = array(
		"ez" => "application/andrew-inset", 
		"hqx" => "application/mac-binhex40", 
		"cpt" => "application/mac-compactpro", 
		"doc" => "application/msword", 
		"docx" => "application/vnd.openxmlformats-officedocument.wordprocessingml.document",
		"xls" => "application/vnd.ms-excel",
		"xlsx" => "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
		"ppt" => "application/vnd.ms-powerpoint",
		"pptx" => "application/vnd.openxmlformats-officedocument.presentationml.presentation",
		"bin" => "application/octet-stream", 
		"dms" => "application/octet-stream", 
		"lha" => "application/octet-stream", 
		"lzh" => "application/octet-stream", 
		"exe" => "application/octet-stream", 
		"class" => "application/octet-stream", 
		"so" => "application/octet-stream", 
		"dll" => "application/octet-stream", 
		"oda" => "application/oda", 
		"pdf" => array("application/octet-stream", "application/pdf"), 
		"ai" => array("application/octet-stream", "application/postscript", "application/pdf"), 
		"eps" => array("application/octet-stream", "application/postscript"), 
		"ps" => "application/postscript", 
		"smi" => "application/smil", 
		"smil" => "application/smil", 
		"wbxml" => "application/vnd.wap.wbxml", 
		"wmlc" => "application/vnd.wap.wmlc", 
		"wmlsc" => "application/vnd.wap.wmlscriptc", 
		"bcpio" => "application/x-bcpio", 
		"vcd" => "application/x-cdlink", 
		"pgn" => "application/x-chess-pgn", 
		"cpio" => "application/x-cpio", 
		"csh" => "application/x-csh", 
		"dcr" => "application/x-director", 
		"dir" => "application/x-director", 
		"dxr" => "application/x-director", 
		"dvi" => "application/x-dvi", 
		"spl" => "application/x-futuresplash", 
		"gtar" => "application/x-gtar", 
		"hdf" => "application/x-hdf", 
		"js" => "application/x-javascript", 
		"skp" => "application/x-koan", 
		"skd" => "application/x-koan", 
		"skt" => "application/x-koan", 
		"skm" => "application/x-koan", 
		"latex" => "application/x-latex", 
		"nc" => "application/x-netcdf", 
		"cdf" => "application/x-netcdf", 
		"sh" => "application/x-sh", 
		"shar" => "application/x-shar", 
		"swf" => "application/x-shockwave-flash", 
		"sit" => "application/x-stuffit", 
		"sv4cpio" => "application/x-sv4cpio", 
		"sv4crc" => "application/x-sv4crc", 
		"tar" => "application/x-tar", 
		"tcl" => "application/x-tcl", 
		"tex" => "application/x-tex", 
		"texinfo" => "application/x-texinfo", 
		"texi" => "application/x-texinfo", 
		"t" => "application/x-troff", 
		"tr" => "application/x-troff", 
		"roff" => "application/x-troff", 
		"man" => "application/x-troff-man", 
		"me" => "application/x-troff-me", 
		"ms" => "application/x-troff-ms", 
		"ustar" => "application/x-ustar", 
		"src" => "application/x-wais-source", 
		"xhtml" => "application/xhtml+xml", 
		"xht" => "application/xhtml+xml", 
		"zip" => "application/zip", 
		"au" => "audio/basic", 
		"snd" => "audio/basic", 
		"mid" => "audio/midi", 
		"midi" => "audio/midi", 
		"kar" => "audio/midi", 
		"mpga" => "audio/mpeg", 
		"mp2" => "audio/mpeg", 
		"mp3" => "audio/mpeg", 
		"aif" => "audio/x-aiff", 
		"aiff" => "audio/x-aiff", 
		"aifc" => "audio/x-aiff", 
		"m3u" => "audio/x-mpegurl", 
		"ram" => "audio/x-pn-realaudio", 
		"rm" => "audio/x-pn-realaudio", 
		"rpm" => "audio/x-pn-realaudio-plugin", 
		"ra" => "audio/x-realaudio", 
		"wav" => "audio/x-wav", 
		"pdb" => "chemical/x-pdb", 
		"xyz" => "chemical/x-xyz", 
		"bmp" => "image/bmp", 
		"gif" => "image/gif", 
		"ief" => "image/ief", 
		"jpeg" => "image/jpeg", 
		"jpg" => "image/jpeg", 
		"jpe" => "image/jpeg", 
		"png" => "image/png", 
		"tiff" => array("image/tif", "image/tiff"), 
		"tif" => array("image/tif", "image/tiff"), 
		"djvu" => "image/vnd.djvu", 
		"djv" => "image/vnd.djvu", 
		"wbmp" => "image/vnd.wap.wbmp", 
		"ras" => "image/x-cmu-raster", 
		"pnm" => "image/x-portable-anymap", 
		"pbm" => "image/x-portable-bitmap", 
		"pgm" => "image/x-portable-graymap", 
		"ppm" => "image/x-portable-pixmap", 
		"rgb" => "image/x-rgb", 
		"xbm" => "image/x-xbitmap", 
		"xpm" => "image/x-xpixmap", 
		"xwd" => "image/x-windowdump", 
		"igs" => "model/iges", 
		"iges" => "model/iges", 
		"msh" => "model/mesh", 
		"mesh" => "model/mesh", 
		"silo" => "model/mesh", 
		"wrl" => "model/vrml", 
		"vrml" => "model/vrml", 
		"css" => "text/css", 
		"html" => "text/html", 
		"htm" => "text/html", 
		"asc" => "text/plain", 
		"txt" => "text/plain", 
		"rtx" => "text/richtext", 
		"rtf" => "text/rtf", 
		"sgml" => "text/sgml", 
		"sgm" => "text/sgml", 
		"tsv" => "text/tab-seperated-values", 
		"wml" => "text/vnd.wap.wml", 
		"wmls" => "text/vnd.wap.wmlscript", 
		"etx" => "text/x-setext", 
		"xml" => "text/xml", 
		"xsl" => "text/xml", 
		"mpeg" => "video/mpeg", 
		"mpg" => "video/mpeg", 
		"mpe" => "video/mpeg", 
		"qt" => "video/quicktime", 
		"mov" => "video/quicktime", 
		"mxu" => "video/vnd.mpegurl", 
		"avi" => "video/x-msvideo", 
		"movie" => "video/x-sgi-movie", 
		"ice" => "x-conference-xcooltalk",
		"nef" => "image/x-nikon-nef",
		"psd" => array("image/photoshop", "image/x-photoshop", "image/psd", "application/photoshop", "application/psd", "zz-application/zz-winassoc-psd", "image/vnd.adobe.photoshop")
	);
	
	private function getMimeByPath($file) {
		if (function_exists("finfo_file")) 
		{
			$finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
			$mime = finfo_file($finfo, $file);
			finfo_close($finfo);
			return trim($mime);
		} else if (function_exists("mime_content_type")) 
		{
			return mime_content_type($file);
		} else if (!stristr(ini_get("disable_functions"), "shell_exec")) 
		{
			// http://stackoverflow.com/a/134930/1593459
			$file = escapeshellarg($file);
			$mime = shell_exec("file -bi " . $file);
			return trim($mime);
		} else 
		{
			return false;
		}
	}
    
    /**
     * Returns array('success'=>true) or array('error'=>'error message')
     */
    function handleUpload($uploadDirectory, $replaceOldFile = FALSE){
		@mkdir($uploadDirectory);
	
        if (!is_writable($uploadDirectory)){
            return array('error' => "Server error. Upload directory isn't writable.");
        }
        
        if (!$this->file){
            return array('error' => 'No files were uploaded.');
        }
        
        $size = $this->file->getSize();
        
        if ($size == 0) {
            return array('error' => 'File is empty');
        }
        
        if ($size > $this->sizeLimit) {
            return array('error' => 'Bestand is te groot');
        }
		
		$CI = &get_instance();
		$quota_limit = $CI->settings_model->get('quota_limit');
		$quota_used = folder_size(uploads_dir($CI->client_id)) / 1048576;
		
		if ($quota_limit != 0 && $quota_used + ($size / 1048576) > $quota_limit) {
            return array('error' => 'Er is niet genoeg ruimte om nog een bestand te uploaden.');
        }
        
        $pathinfo = pathinfo($this->file->getName());
        $filename = strtolower(url_title($pathinfo['filename'], 'dash'));
		
		
        //$filename = md5(uniqid());
        $ext = strtolower($pathinfo['extension']);
		
		if($this->allowedExtensions && !in_array(strtolower($ext), $this->allowedExtensions)){
            $these = implode(', ', $this->allowedExtensions);
            return array('error' => 'Bestand heeft een incorrecte extenstie. De volgende extenties zijn ondersteund: '. $these . '.');
        }
		
		
		/*if($this->allowedExtensions && !in_array(strtolower($ext), $this->allowedExtensions)){
            $these = implode(', ', $this->allowedExtensions);
            return array('error' => 'Bestand heeft een incorrecte extenstie. De volgende extenties zijn ondersteund: '. $these . '.');
        }*/

        if(!$replaceOldFile){
            /// don't overwrite previous files that were uploaded
			$i = 1;
			$ori_filename = $filename;
            while (file_exists($uploadDirectory . $filename . '.' . $ext)) {
                $filename = $ori_filename . $i++;
            }
        }
        
        if ($this->file->save($uploadDirectory . $filename . '.' . $ext)){
			$full_path = $uploadDirectory . $filename . '.' . $ext;
			$mime = $this->getMimeByPath($full_path);

			$mime_type_lookup = $this->mimeTypes[strtolower($ext)];
			if(!is_array($mime_type_lookup))
			{
				$mime_type_lookup = array($mime_type_lookup);
			}
			
			if(!in_array($mime, $mime_type_lookup)) {			
				@unlink($full_path);
				return array('error'=> 'Bestand heeft een incorrecte type wat niet is ondersteund ('.$mime.'/'.$this->mimeTypes[$ext].')');
			}

            return array(
				'success' => TRUE,
				'full_path' => $full_path,
				'filename' => $filename,
				'ext' => $ext
			);
        } else {
            return array('error'=> 'Could not save uploaded file.' .
                'The upload was cancelled, or server error encountered');
        }
        
    }    
}

/* End of file qqFileUploader.php */
/* Location: ./application/libraries/qqFileUploader.php */