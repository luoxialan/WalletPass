<?php

   function getPassJson($filepath){
   		$passjsonString = file_get_contents($filepath);
   		$passjson = json_decode($passjsonString);
   		return $passjson;
   }

   function setPassJson($filepath, $passjson, $id, $tier, $name, $since){
   		$json['generic']['primaryFields'][0]['value'] = "1865 " + $tier;
   		$json['generic']['secondaryFields'][0]['value'] = $name;  
   		$json['generic']['auxiliaryFields'][0]['Value'] = $id;
   		$json['generic']['auxiliaryFields'][1]['Value'] = $since;

   		$passjson = json_encode($json);
		file_put_contents($filepath, $passjson);
   }

?>