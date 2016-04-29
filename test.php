<?php
      
    error_reporting(E_ALL); 
    ini_set('display_errors','1'); 
    
    $dir    = './SamplePasses';
    
    function createManifest ($dir){
      $manifestJson =  $dir.'/'.'manifest.json';
  
      if (file_exists($manifestJson)){
        unlink($manifestJson)；
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
    
    createManifest($dir)；
  
?>