<?php
      
    error_reporting(E_ALL); 
    ini_set('display_errors','1'); 
	
    function createManifest ($dir){
      $manifestJson = './manifest.json';
  
      if (file_exists($manifestJson)){
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
      }
      $fo = fopen($manifestJson, 'w') or die("can't open file");
      fwrite($fo, json_encode($manifest,TRUE));
    };
    
	
	/*
	Configuration: Windows users need to enable php_zip.dll inside of php.ini in order to use these functions.
	Function: compress pass file to .pkpass
	*/
	
	function createZip($dir, $filename)
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
			//echo $f;
			$zip->addFile($f, $name);
		}
		//echo "numfiles: " . $zip->numFiles . "\n";
		//echo "status:" . $zip->status . "\n";
		$zip->close();
		unset($zip);
	    return true;	
    }
	
	function convertPEMtoDER($signature)
    {
        $begin     = 'filename="smime.p7s"';
        $end       = '------';
        $signature = substr($signature, strpos($signature, $begin) + strlen($begin));
        $signature = substr($signature, 0, strpos($signature, $end));
        $signature = trim($signature);
        $signature = base64_decode($signature);
        return $signature;
    }
	
	function createSign(){
		$privKey = "./Certs/passkey.pem";
		$keyPassword = "bR1234";
		$wwdr = "./Certs/WWDR.pem";
		$cert = file_get_contents("./Certs/passcertificate.pem");
		$certData = openssl_x509_read($cert);
		$key = openssl_pkey_get_private("file://".realpath($privKey),$keyPassword);
		
		openssl_pkcs7_sign(
			"./manifest.json", 
			"./signature", 
			$certData, 
			$key,
			[], 
			PKCS7_BINARY | PKCS7_DETACHED,
			"./certs/wwdr.pem"
		);
		$signature = file_get_contents('./signature');
		$signature = convertPEMtoDER($signature);
        file_put_contents('./signature', $signature);
		
		

	    //$signature = file_get_contents('./signature');
        //$signature = $this->convertPEMtoDER($signature);
        //file_put_contents('./signature', $signature);
        return true;
	}
	
	
	function createSignature($manifest)
    {
        $paths = $this->paths();
        file_put_contents($paths['manifest'], $manifest);
        $pkcs12 = file_get_contents($this->certPath);
        $certs  = [];
		
        if (openssl_pkcs12_read($pkcs12, $certs, $this->certPass) == true) {
            $certdata = openssl_x509_read($certs['cert']);
            $privkey  = openssl_pkey_get_private($certs['pkey'], $this->certPass);
            if ( !empty($this->WWDRcertPath)) {
                if ( !file_exists($this->WWDRcertPath)) {
                    $this->sError = 'WWDR Intermediate Certificate does not exist';
                    return false;
                }
                openssl_pkcs7_sign(
                    $paths['manifest'],
                    $paths['signature'],
                    $certdata,
                    $privkey,
                    [],
                    PKCS7_BINARY | PKCS7_DETACHED,
                    $this->WWDRcertPath
                );
            } else {
                openssl_pkcs7_sign('./manifest.json', './signature', $certdata, $privkey, [], PKCS7_BINARY | PKCS7_DETACHED);
            }
            $signature = file_get_contents($paths['signature']);
            $signature = $this->convertPEMtoDER($signature);
            file_put_contents($paths['signature'], $signature);
            return true;
        } else {
            echo 'Could not read the certificate';
            return false;
        }
    }
	
	function sendEmail ($mailaddress){
		$to = $mailaddress; //收件者
        $subject = "Your Wallet Pass"; //信件標題
        $msg = "Attached please find your 1865 Membership pass"; 
		$headers = "From: admin@your.com"; //寄件者
	}
	
    $dir    = './lhgmember1865.pass';
	$filename = "./lhgmember1865.pkpass";
	
    createManifest($dir);
	createSign();
	createZip($dir, $filename);
	
    //createZip ($dir);
?>