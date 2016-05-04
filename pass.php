<?php
      
    error_reporting(E_ALL | E_STRICT); 
  
    ini_set("display_errors", "On");

    require_once('class.phpmailer.php');
    
	/*
	step 0. get member info
	function: setMember
	To-do: connect to database, get member profile by member_id
	return array
	*/

	/*
	step 1. update pass.json file
    function: updateJson
	return: False / number(number of byte of input)
	*/
	function updateJson($dir, $tier, $name, $no, $since){
	
		$f = $dir.'/pass.json';
		$jsonString = file_get_contents($f);
	
		$data = json_decode($jsonString, true);
        
        //set Member Tier
        $data['generic']= setFieldValue($data['generic'], 'primaryFields', 'tier', $tier); 
       
        //set Member Name
		$data['generic'] = setFieldValue($data['generic'], 'secondaryFields', 'name', $name); 
        
        //set Member no & since
        $data['generic'] = setFieldValue($data['generic'], 'auxiliaryFields', 'no', $no); 
        $data['generic'] = setFieldValue($data['generic'], 'auxiliaryFields', 'since', $since); 
        
        file_put_contents($f,json_encode($data));
    }

	function setFieldValue($object, $field ,$key, $value){
    	foreach ($object[$field] as $k => $o){		
		if ($o['key'] ==  $key) {
        		$object[$field][$k]['value'] = $value;
    		}
		}
        return $object;
    }
	
	/*
	step 2. create manifest.json file
	function createManifest
	return: False / number(number of byte of input) 
	*/
    function createManifest($dir){
      $manifestJson =  './manifest.json';
  
      if(file_exists($manifestJson)){
        unlink($manifestJson);
      }

      $files = scandir($dir, 1); 
      $length = sizeof($files);
      unset($files[$length-1]);
      unset($files[$length-2]);  
      
      $manifest = array();

      foreach ($files as $name){
          $f = $dir.'/'.$name;
          $manifest[$name]=sha1(file_get_contents($f));
      };
	  
      $fo = fopen($manifestJson, 'w') or die("can't open file");
      fwrite($fo, json_encode($manifest,TRUE));
    };
    
    /*
	step 3. sign
	function sign
	*/

    function createSign($manifest, $s){
		
		$privKey = "./Certs/passkey.pem";
		$keyPassword = "bR1234";
		$wwdr = "./Certs/WWDR.pem";
		$cert = file_get_contents("./Certs/passcertificate.pem");
		$certData = openssl_x509_read($cert);
		$key = openssl_pkey_get_private("file://".realpath($privKey),$keyPassword);
		
	};
	
	
	/*
	step 4. zip file and create .pkpass file
	function createPkpass($dir, $filename)
	*/
	
	function createPkpass($dir, $filename)
    {
      
		$zip = new ZipArchive();
	    
		if (!$zip->open($filename, ZIPARCHIVE::CREATE)) {
			exit("cannot open <$filename>\n");
			return false;
		} 
		//add signature
		$zip->addFile("./signature", "signature");
		//add file manifest.json
		$zip->addFile("./manifest.json", "manifest.json");
		
		//add pass files
		$files = scandir($dir, 1); 
		$length = sizeof($files);
		unset($files[$length-1]);
		unset($files[$length-2]);  
	
		foreach ($files as $name){
			$f = $dir.'/'.$name;
			$zip->addFile($f, $name);
		}
		//echo "numfiles: " . $zip->numFiles . "\n";
		//echo "status:" . $zip->status . "\n";
		$zip->close();
		unset($zip);
		unlink('./manifest.json');
		unlink('./signature');
	    return true;	
    }
    
	/*
	step 5. create manifest.json file
	function createManifest
	return: False / number(number of byte of input) 
	*/

    function sendMail ($email, $attachment){
	
		date_default_timezone_set('Asia/Hong_Kong');

		$mail = new PHPMailer(true); 
		$mail->IsSMTP(); 
		$subject = 'test pass mail function';
		$sendMail = 'apple.luo@langhamhotels.com';
		$Message = 'test';

		try {
			$mail->Host = "10.10.80.117"; // SMTP server
			$mail->AddReplyTo($sendMail);
			$mail->AddAddress($email);
			$mail->SetFrom($sendMail);
			$mail->Subject = $subject;
			$mail->Body = $Message;
			$mail->AddAttachment($attachment);
			$mail->Send();
		   
		} catch (phpmailerException $e) {
		   echo $e->errorMessage(); //Pretty error messages from PHPMailer
		} catch (Exception $e) {
		   echo $e->getMessage(); //Boring error messages from anything else!
		}
		unlink($attachment);
    }

    
    $dir    = './SamplePasses';
    $email  = 'apple.luo@langhamhotels.com';
    $pkpassfile = './Temp/SamplePasses.pkpass';
   	
	
	updateJson($dir,'123456','happy','8888888','2018');
	//createManifest($dir);
	
	//createSign($manifest, $signature);
	//sign
	//createPkpass($dir, $pkpassfile);
	//sendMail($email, $pkpassfile);
    
?>